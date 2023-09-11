<?php

namespace MelTheDev\MeiliSearch\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\ObjectManagerInterface;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;
use MelTheDev\MeiliSearch\Logger\Logger;
use Symfony\Component\Console\Output\ConsoleOutput;
use MelTheDev\MeiliSearch\Model\ResourceModel\Job\CollectionFactory as JobCollectionFactory;
use MelTheDev\MeiliSearch\Model\ResourceModel\Job\Collection as JobCollection;
use PDO;
use Zend_Db_Expr;
use Zend_Db_Statement_Exception;
use Exception;

class Queue
{
    public const FULL_REINDEX_TO_REALTIME_JOBS_RATIO = 0.33;
    public const UNLOCK_STACKED_JOBS_AFTER_MINUTES = 15;
    public const CLEAR_ARCHIVE_LOGS_AFTER_DAYS = 30;

    public const SUCCESS_LOG = 'melthedev_meilisearch_queue_log.txt';
    public const ERROR_LOG = 'melthedev_meilisearch_queue_errors.log';

    /** @var array */
    protected $logRecord;

    /** @var ConfigHelper */
    private ConfigHelper $configHelper;
    /** @var ResourceConnection */
    private ResourceConnection $resourceConnection;
    /** @var ObjectManagerInterface */
    private ObjectManagerInterface $objectManager;
    /** @var AdapterInterface */
    private AdapterInterface $db;
    private ConsoleOutput $output;
    private JobCollectionFactory $jobCollectionFactory;

    /** @var int */
    protected $maxSingleJobDataSize;
    /** @var array */
    protected $staticJobMethods = [
        'saveConfigurationToMeiliSearch',
        'moveIndexWithSetSettings',
        'deleteObjects',
    ];
    /** @var int */
    protected $noOfFailedJobs = 0;
    private Logger $logger;

    /**
     * Queue constructor.
     * @param ConfigHelper $configHelper
     * @param ResourceConnection $resourceConnection
     * @param ObjectManagerInterface $objectManager
     * @param ConsoleOutput $output
     * @param JobCollectionFactory $jobCollectionFactory
     * @param Logger $logger
     */
    public function __construct(
        ConfigHelper           $configHelper,
        ResourceConnection     $resourceConnection,
        ObjectManagerInterface $objectManager,
        ConsoleOutput          $output,
        JobCollectionFactory   $jobCollectionFactory,
        Logger                 $logger
    ) {
        $this->configHelper       = $configHelper;
        $this->resourceConnection = $resourceConnection;
        $this->objectManager      = $objectManager;
        $this->db = $objectManager->create(ResourceConnection::class)->getConnection('core_write');
        $this->output               = $output;
        $this->jobCollectionFactory = $jobCollectionFactory;
        $this->maxSingleJobDataSize = $this->configHelper->getNumberOfElementByPage();
        $this->logger               = $logger;
    }

    public function getQueueTableName()
    {
        return $this->resourceConnection->getTableName('melthedev_meilisearch_queue');
    }

    public function getQueueLogTableName()
    {
        return $this->resourceConnection->getTableName('melthedev_meilisearch_queue_log');
    }

    public function getArchiveTableName()
    {
        return $this->resourceConnection->getTableName('melthedev_meilisearch_queue_archive');
    }

    /**
     * @param string|object $className
     * @param string $method
     * @param array $data
     * @param int $dataSize
     * @param bool $isFullReindex
     */
    public function addToQueue($className, $method, array $data, $dataSize = 1, $isFullReindex = false)
    {
        if (is_object($className)) {
            $className = get_class($className);
        }

        if ($this->configHelper->isQueueActive()) {
            $this->db->insert($this->getQueueTableName(), [
                'created'   => date('Y-m-d H:i:s'),
                'class'     => $className,
                'method'    => $method,
                'data'      => json_encode($data),
                'data_size' => $dataSize,
                'pid'       => null,
                'max_retries' => $this->configHelper->getRetryLimit(),
                'is_full_reindex' => $isFullReindex ? 1 : 0,
            ]);
        } else {
            $object = $this->objectManager->get($className);
            call_user_func_array([$object, $method], $data);
        }
    }

    /**
     * Return the average processing time for the 2 last two days
     * (null if there was less than 100 runs with processed jobs)
     *
     * @throws \Zend_Db_Statement_Exception
     *
     * @return float|null
     */
    public function getAverageProcessingTime()
    {
        $select = $this->db->select()
            ->from($this->getQueueLogTableName(), ['number_of_runs' => 'COUNT(duration)', 'average_time' => 'AVG(duration)'])
            ->where('processed_jobs > 0 AND with_empty_queue = 0 AND started >= (CURDATE() - INTERVAL 2 DAY)');

        $data = $this->db->query($select)->fetch();

        return (int) $data['number_of_runs'] >= 100 && isset($data['average_time']) ?
            (float) $data['average_time'] :
            null;
    }

    /**
     * @throws Zend_Db_Statement_Exception
     */
    protected function clearOldLogRecords()
    {
        $select = $this->db->select()
            ->from($this->getQueueLogTableName(), ['id'])
            ->order(['started DESC', 'id DESC'])
            ->limit(PHP_INT_MAX, 25000);

        $idsToDelete = $this->db->query($select)->fetchAll(PDO::FETCH_COLUMN, 0);

        if ($idsToDelete) {
            $this->db->delete($this->getQueueLogTableName(), ['id IN (?)' => $idsToDelete]);
        }
    }

    /**
     * @param int|null $nbJobs
     * @param bool $force
     *
     * @throws \Exception
     */
    public function runCron($nbJobs = null, $force = false)
    {
        if (!$this->configHelper->isQueueActive() && $force === false) {
            return;
        }

        $this->clearOldLogRecords();
        $this->clearOldArchiveRecords();
        $this->unlockStackedJobs();

        $this->logRecord = [
            'started' => date('Y-m-d H:i:s'),
            'processed_jobs' => 0,
            'with_empty_queue' => 0,
        ];

        $started = time();

        if ($nbJobs === null) {
            $nbJobs = $this->configHelper->getNumberOfJobToRun();
            if ($this->shouldEmptyQueue() === true) {
                $nbJobs = -1;

                $this->logRecord['with_empty_queue'] = 1;
            }
        }

        $this->run($nbJobs);

        $this->logRecord['duration'] = time() - $started;

        if (php_sapi_name() === 'cli') {
            $this->output->writeln(
                $this->logRecord['processed_jobs'] . ' jobs processed in ' . $this->logRecord['duration'] . ' seconds.'
            );
        }

        $this->db->insert($this->getQueueLogTableName(), $this->logRecord);
    }

    /**
     * @return void
     */
    protected function clearOldArchiveRecords()
    {
        $archiveLogClearLimit = $this->configHelper->getArchiveLogClearLimit();
        // Adding a fallback in case this configuration was not set in a consistent way
        if ($archiveLogClearLimit < 1) {
            $archiveLogClearLimit = self::CLEAR_ARCHIVE_LOGS_AFTER_DAYS;
        }

        $this->db->delete(
            $this->getArchiveTableName(),
            'created_at < (NOW() - INTERVAL ' . $archiveLogClearLimit . ' DAY)'
        );
    }

    /**
     * @return void
     */
    protected function clearOldFailingJobs()
    {
        $this->archiveFailedJobs('retries > max_retries');
        $this->db->delete($this->getQueueTableName(), 'retries > max_retries');
    }

    /**
     * @param string $whereClause
     */
    protected function archiveFailedJobs($whereClause)
    {
        $select = $this->db->select()
            ->from($this->getQueueTableName(), ['pid', 'class', 'method', 'data', 'error_log', 'data_size', 'NOW()'])
            ->where($whereClause);

        $query = $this->db->insertFromSelect(
            $select,
            $this->getArchiveTableName(),
            ['pid', 'class', 'method', 'data', 'error_log', 'data_size', 'created_at']
        );

        $this->db->query($query);
    }

    /**
     * @param int $maxJobs
     *
     * @throws Exception
     *
     * @return Job[]
     *
     */
    protected function getJobs($maxJobs)
    {
        $maxJobs = ($maxJobs === -1) ? $this->configHelper->getNumberOfJobToRun() : $maxJobs;

        $fullReindexJobsLimit = (int) ceil(self::FULL_REINDEX_TO_REALTIME_JOBS_RATIO * $maxJobs);

        try {
            $this->db->beginTransaction();

            $fullReindexJobs = $this->fetchJobs($fullReindexJobsLimit, true);
            $fullReindexJobsCount = count($fullReindexJobs);

            $realtimeJobsLimit = (int) $maxJobs - $fullReindexJobsCount;

            $realtimeJobs = $this->fetchJobs($realtimeJobsLimit, false);

            $jobs = array_merge($fullReindexJobs, $realtimeJobs);
            $jobsCount = count($jobs);

            if ($jobsCount > 0 && $jobsCount < $maxJobs) {
                $restLimit = $maxJobs - $jobsCount;
                $lastFullReindexJobId = max($this->getJobsIdsFromMergedJobs($jobs));

                $restFullReindexJobs = $this->fetchJobs($restLimit, true, $lastFullReindexJobId);

                $jobs = array_merge($jobs, $restFullReindexJobs);
            }

            $this->lockJobs($jobs);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();

            throw $e;
        }

        return $jobs;
    }

    /**
     * @param Job[] $jobs
     */
    protected function lockJobs(array $jobs)
    {
        $jobsIds = $this->getJobsIdsFromMergedJobs($jobs);

        if ($jobsIds !== []) {
            $pid = getmypid();
            $this->db->update($this->getQueueTableName(), [
                'locked_at' => date('Y-m-d H:i:s'),
                'pid' => $pid,
            ], ['job_id IN (?)' => $jobsIds]);
        }
    }

    /**
     * @param Job[] $mergedJobs
     *
     * @return string[]
     */
    protected function getJobsIdsFromMergedJobs(array $mergedJobs)
    {
        $jobsIds = [];
        foreach ($mergedJobs as $job) {
            $jobsIds = array_merge($jobsIds, $job->getMergedIds());
        }

        return $jobsIds;
    }

    /**
     * @param int $jobsLimit
     * @param bool $fetchFullReindexJobs
     * @param int|null $lastJobId
     *
     * @return Job[]
     */
    protected function fetchJobs($jobsLimit, $fetchFullReindexJobs = false, $lastJobId = null)
    {
        $jobs = [];

        $actualBatchSize = 0;
        $maxBatchSize = $this->configHelper->getNumberOfElementByPage() * $jobsLimit;

        $limit = $maxJobs = $jobsLimit;
        $offset = 0;

        $fetchFullReindexJobs = $fetchFullReindexJobs ? 1 : 0;

        while ($actualBatchSize < $maxBatchSize) {
            $jobsCollection = $this->jobCollectionFactory->create();
            $jobsCollection
                ->addFieldToFilter('pid', ['null' => true])
                ->addFieldToFilter('is_full_reindex', $fetchFullReindexJobs)
                ->setOrder('job_id', JobCollection::SORT_ORDER_ASC)
                ->getSelect()
                ->limit($limit, $offset)
                ->forUpdate();

            if ($lastJobId !== null) {
                $jobsCollection->addFieldToFilter('job_id', ['gt' => $lastJobId]);
            }

            $rawJobs = $jobsCollection->getItems();

            if ($rawJobs === []) {
                break;
            }

            $rawJobs = array_merge($jobs, $rawJobs);
            $rawJobs = $this->mergeJobs($rawJobs);

            $rawJobsCount = count($rawJobs);

            $offset += $limit;
            $limit = max(0, $maxJobs - $rawJobsCount);

            // $jobs will always be completely set from $rawJobs
            // Without resetting not-merged jobs would be stacked
            $jobs = [];

            if (count($rawJobs) === $maxJobs) {
                $jobs = $rawJobs;
                break;
            }

            foreach ($rawJobs as $job) {
                $jobSize = (int) $job->getDataSize();

                if ($actualBatchSize + $jobSize <= $maxBatchSize || !$jobs) {
                    $jobs[] = $job;
                    $actualBatchSize += $jobSize;
                } else {
                    break 2;
                }
            }
        }

        return $jobs;
    }

    /**
     * @param Job[] $unmergedJobs
     *
     * @return Job[]
     */
    protected function mergeJobs(array $unmergedJobs)
    {
        $unmergedJobs = $this->sortJobs($unmergedJobs);

        $jobs = [];

        /** @var Job $currentJob */
        $currentJob = array_shift($unmergedJobs);
        $nextJob = null;

        while ($currentJob !== null) {
            if (count($unmergedJobs) > 0) {
                $nextJob = array_shift($unmergedJobs);

                if ($currentJob->canMerge($nextJob, $this->maxSingleJobDataSize)) {
                    $currentJob->merge($nextJob);

                    continue;
                }
            } else {
                $nextJob = null;
            }

            $jobs[] = $currentJob;
            $currentJob = $nextJob;
        }

        return $jobs;
    }

    /**
     * Sorts the jobs and preserves the order of jobs with static methods defined in $this->staticJobMethods
     *
     * @param Job[] $jobs
     *
     * @return Job[]
     */
    protected function sortJobs(array $jobs)
    {
        $sortedJobs = [];

        $tempSortableJobs = [];

        /** @var Job $job */
        foreach ($jobs as $job) {
            $job->prepare();

            if (in_array($job->getMethod(), $this->staticJobMethods, true)) {
                $sortedJobs = $this->stackSortedJobs($sortedJobs, $tempSortableJobs, $job);
                $tempSortableJobs = [];

                continue;
            }

            $tempSortableJobs[] = $job;
        }

        $sortedJobs = $this->stackSortedJobs($sortedJobs, $tempSortableJobs);

        return $sortedJobs;
    }

    /**
     * @param Job[] $sortedJobs
     * @param Job[] $tempSortableJobs
     * @param Job|null $job
     *
     * @return array
     */
    protected function stackSortedJobs(array $sortedJobs, array $tempSortableJobs, Job $job = null)
    {
        if ($tempSortableJobs && $tempSortableJobs !== []) {
            $tempSortableJobs = $this->jobSort(
                $tempSortableJobs,
                'class',
                SORT_ASC,
                'method',
                SORT_ASC,
                'store_id',
                SORT_ASC,
                'job_id',
                SORT_ASC
            );
        }

        $sortedJobs = array_merge($sortedJobs, $tempSortableJobs);

        if ($job !== null) {
            $sortedJobs = array_merge($sortedJobs, [$job]);
        }

        return $sortedJobs;
    }

    /**
     * @return array
     */
    protected function jobSort()
    {
        $args = func_get_args();

        $data = array_shift($args);

        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = [];

                /**
                 * @var int $key
                 * @var Job $row
                 */
                foreach ($data as $key => $row) {
                    $tmp[$key] = $row->getData($field);
                }

                $args[$n] = $tmp;
            }
        }

        $args[] = &$data;

        call_user_func_array('array_multisort', $args);

        return array_pop($args);
    }

    /**
     * @param int $maxJobs
     *
     * @throws Exception
     */
    public function run($maxJobs)
    {
        $this->clearOldFailingJobs();

        $jobs = $this->getJobs($maxJobs);

        if ($jobs === []) {
            return;
        }

        // Run all reserved jobs
        foreach ($jobs as $job) {
            // If there are some failed jobs before move, we want to skip the move
            // as most probably not all products have prices reindexed
            // and therefore are not indexed yet in TMP index
            if ($job->getMethod() === 'moveIndex' && $this->noOfFailedJobs > 0) {
                // Set pid to NULL so it's not deleted after
                $this->db->update($this->getQueueTableName(), ['pid' => null], ['job_id = ?' => $job->getId()]);

                continue;
            }

            try {
                $job->execute();

                // Delete one by one
                $this->db->delete($this->getQueueTableName(), ['job_id IN (?)' => $job->getMergedIds()]);

                $this->logRecord['processed_jobs'] += count($job->getMergedIds());
            } catch (Exception $e) {
                $this->noOfFailedJobs++;

                // Log error information
                $logMessage = 'Queue processing ' . $job->getPid() . ' [KO]:
                    Class: ' . $job->getClass() . ',
                    Method: ' . $job->getMethod() . ',
                    Parameters: ' . json_encode($job->getDecodedData());
                $this->logger->log($logMessage);

                $logMessage = date('c') . ' ERROR: ' . get_class($e) . ':
                    ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() .
                    "\nStack trace:\n" . $e->getTraceAsString();
                $this->logger->log($logMessage);

                $this->db->update($this->getQueueTableName(), [
                    'pid' => null,
                    'retries' => new Zend_Db_Expr('retries + 1'),
                    'error_log' => $logMessage,
                ], ['job_id IN (?)' => $job->getMergedIds()]);

                if (php_sapi_name() === 'cli') {
                    $this->output->writeln($logMessage);
                }
            }
        }

        $isFullReindex = ($maxJobs === -1);
        if ($isFullReindex) {
            $this->run(-1);
            return;
        }
    }

    /**
     * @return void
     */
    protected function unlockStackedJobs()
    {
        $this->db->update($this->getQueueTableName(), [
            'locked_at' => null,
            'pid' => null,
        ], ['locked_at < (NOW() - INTERVAL ' . self::UNLOCK_STACKED_JOBS_AFTER_MINUTES . ' MINUTE)']);
    }

    /**
     * @return bool
     */
    protected function shouldEmptyQueue()
    {
        if (getenv('PROCESS_FULL_QUEUE') && getenv('PROCESS_FULL_QUEUE') === '1') {
            return true;
        }
        if (getenv('EMPTY_QUEUE') && getenv('EMPTY_QUEUE') === '1') {
            return true;
        }
        return false;
    }
}
