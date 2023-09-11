<?php

namespace MelTheDev\MeiliSearch\ViewModel\Adminthtml\Queue;

use Magento\Framework\Indexer\StateInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\UrlInterface;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;
use MelTheDev\MeiliSearch\Model\Indexer\QueueRunner;
use MelTheDev\MeiliSearch\Model\Queue;
use Magento\Indexer\Model\Indexer;
use Magento\Indexer\Model\IndexerFactory;

class Status
{
    public const CRON_QUEUE_FREQUENCY      = 330;
    public const QUEUE_NOT_PROCESSED_LIMIT = 3600;
    public const QUEUE_FAST_LIMIT          = 220;

    /**
     * @var ConfigHelper
     */
    private ConfigHelper $configHelper;
    /**
     * @var Queue
     */
    private Queue $queue;
    /**
     * @var IndexerFactory
     */
    private IndexerFactory $indexerFactory;
    /**
     * @var DateTime
     */
    private DateTime $dateTime;
    /**
     * @var Indexer
     */
    private $queueRunnerIndexer;
    /**
     * @var UrlInterface
     */
    private UrlInterface $url;

    /**
     * Status constructor.
     * @param ConfigHelper $configHelper
     * @param Queue $queue
     * @param IndexerFactory $indexerFactory
     * @param DateTime $dateTime
     * @param UrlInterface $url
     */
    public function __construct(
        ConfigHelper   $configHelper,
        Queue          $queue,
        IndexerFactory $indexerFactory,
        DateTime       $dateTime,
        UrlInterface   $url
    ) {
        $this->configHelper   = $configHelper;
        $this->queue          = $queue;
        $this->indexerFactory = $indexerFactory;
        $this->dateTime       = $dateTime;
        $this->url            = $url;
        if ($this->isQueueActive()) {
            $this->queueRunnerIndexer = $this->indexerFactory->create();
            $this->queueRunnerIndexer->load(QueueRunner::INDEXER_ID);
        }
    }

    public function isQueueActive()
    {
        return $this->configHelper->isQueueActive();
    }

    /**
     * @return string
     */
    public function getQueueRunnerStatus()
    {
        $status = 'unknown';
        switch ($this->queueRunnerIndexer->getStatus()) {
            case StateInterface::STATUS_VALID:
                $status = 'Ready';
                break;
            case StateInterface::STATUS_INVALID:
                $status = 'Reindex required';
                break;
            case StateInterface::STATUS_WORKING:
                $status = 'Processing';
                break;
        }
        return $status;
    }

    public function getLastQueueUpdate()
    {
        return $this->queueRunnerIndexer->getLatestUpdated();
    }

    public function getResetQueueUrl()
    {
        return $this->url->getUrl('');
    }

    public function getNotices()
    {
        $notices = [];

        if ($this->isQueueStuck()) {
            $notices[] = '<a href="' . $this->getResetQueueUrl() . '"> ' . __('Reset queue') . '</a>';
        }

        if ($this->isQueueNotProcessed()) {
            $notices[] =  __(
                'Queue has not been processed for one hour and indexing might be stuck or you cron is not set up properly.'
            );
            $notices[] =  __(
                'To help you, please read our <a href="%1" target="_blank">documentation</a>.',
                'http://test.test/doc/integration/magento-2/how-it-works/indexing-queue/'
            );
        }

        if ($this->isQueueFast()) {
            $notices[] = __(
                'The average processing time of the queue has been performed under 3 minutes.'
            );
            $notices[] = __(
                'Adding more jobs in <a href="%1">the extension configuration</a> would increase the indexing speed.',
                $this->url->getUrl('adminhtml/system_config/edit/section/melthedev_meilisearch_queue')
            );
        }

        return $notices;
    }

    /**
     * If the queue status is not "ready" and it is running for more than 5 minutes, we consider that the queue is stuck
     *
     * @return bool
     */
    private function isQueueStuck()
    {
        if ($this->queueRunnerIndexer->getStatus() == StateInterface::STATUS_VALID) {
            return false;
        }
        if ($this->getTimeSinceLastIndexerUpdate() > self::CRON_QUEUE_FREQUENCY) {
            return true;
        }
        return false;
    }

    /**
     * Check if the queue indexer has not been processed for more than 1 hour
     *
     * @return bool
     */
    private function isQueueNotProcessed()
    {
        return $this->getTimeSinceLastIndexerUpdate() > self::QUEUE_NOT_PROCESSED_LIMIT;
    }

    /**
     * Check if the average processing time  of the queue is fast
     *
     * @return bool
     * @throws \Zend_Db_Statement_Exception
     */
    private function isQueueFast()
    {
        $averageProcessingTime = $this->queue->getAverageProcessingTime();

        return !is_null($averageProcessingTime) && $averageProcessingTime < self::QUEUE_FAST_LIMIT;
    }

    /**
     * @return int
     */
    private function getIndexerLastUpdateTimestamp()
    {
        return $this->dateTime->gmtTimestamp($this->queueRunnerIndexer->getLatestUpdated());
    }

    /**
     * @return int
     */
    private function getTimeSinceLastIndexerUpdate()
    {
        return $this->dateTime->gmtTimestamp('now') - $this->getIndexerLastUpdateTimestamp();
    }
}