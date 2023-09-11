<?php

namespace MelTheDev\MeiliSearch\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use MelTheDev\MeiliSearch\Api\Data\JobInterface;
use MelTheDev\MeiliSearch\Model\ResourceModel\Job as ResourceModel;

class Job extends AbstractModel implements JobInterface
{
    protected $_eventPrefix = 'melthedev_meilisearch_queue_job';

    /** @var ObjectManagerInterface */
    protected $objectManager;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ObjectManagerInterface $objectManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ObjectManagerInterface $objectManager,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->objectManager = $objectManager;
    }

    /**
     * Magento Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @throws AlreadyExistsException
     *
     * @return $this
     */
    public function execute()
    {
        $model  = $this->objectManager->get($this->getClass());
        $method = $this->getMethod();
        $data   = $this->getDecodedData();
        $this->setRetries((int) $this->getRetries() + 1);

        call_user_func_array([$model, $method], $data);

        $this->getResource()->save($this);
        $this->save();
        return $this;
    }

    /**
     * @return $this
     */
    public function prepare()
    {
        if ($this->getMergedIds() === null) {
            $this->setMergedIds([$this->getId()]);
        }

        if ($this->getDecodedData() === null) {
            $decodedData = json_decode($this->getData('data'), true);

            $this->setDecodedData($decodedData);

            if (isset($decodedData['store_id'])) {
                $this->setStoreId($decodedData['store_id']);
            }
        }

        return $this;
    }

    /**
     * @param Job $job
     * @param $maxJobDataSize
     *
     * @return bool
     */
    public function canMerge(Job $job, $maxJobDataSize)
    {
        if ($this->getClass() !== $job->getClass()) {
            return false;
        }

        if ($this->getMethod() !== $job->getMethod()) {
            return false;
        }

        if ($this->getStoreId() !== $job->getStoreId()) {
            return false;
        }

        $decodedData = $this->getDecodedData();

        if ((!isset($decodedData['product_ids']) || count($decodedData['product_ids']) <= 0)
            && (!isset($decodedData['category_ids']) || count($decodedData['category_ids']) < 0)
            && (!isset($decodedData['page_ids']) || count($decodedData['page_ids']) < 0)) {
            return false;
        }

        $candidateDecodedData = $job->getDecodedData();

        if ((!isset($candidateDecodedData['product_ids']) || count($candidateDecodedData['product_ids']) <= 0)
            && (!isset($candidateDecodedData['category_ids']) || count($candidateDecodedData['category_ids']) < 0)
            && (!isset($candidateDecodedData['page_ids']) || count($candidateDecodedData['page_ids']) < 0)) {
            return false;
        }

        if (isset($decodedData['product_ids'])
            && count($decodedData['product_ids']) + count($candidateDecodedData['product_ids']) > $maxJobDataSize) {
            return false;
        }

        if (isset($decodedData['category_ids'])
            && count($decodedData['category_ids']) + count($candidateDecodedData['category_ids']) > $maxJobDataSize) {
            return false;
        }

        if (isset($decodedData['page_ids'])
            && count($decodedData['page_ids']) + count($candidateDecodedData['page_ids']) > $maxJobDataSize) {
            return false;
        }

        return true;
    }

    /**
     * @param Job $mergedJob
     *
     * @return Job
     */
    public function merge(Job $mergedJob)
    {
        $mergedIds = $this->getMergedIds();
        array_push($mergedIds, $mergedJob->getId());

        $this->setMergedIds($mergedIds);

        $decodedData = $this->getDecodedData();
        $mergedJobDecodedData = $mergedJob->getDecodedData();

        $dataSize = $this->getDataSize();

        if (isset($decodedData['product_ids'])) {
            $decodedData['product_ids'] = array_unique(array_merge(
                $decodedData['product_ids'],
                $mergedJobDecodedData['product_ids']
            ));

            $dataSize = count($decodedData['product_ids']);
        } elseif (isset($decodedData['category_ids'])) {
            $decodedData['category_ids'] = array_unique(array_merge(
                $decodedData['category_ids'],
                $mergedJobDecodedData['category_ids']
            ));

            $dataSize = count($decodedData['category_ids']);
        } elseif (isset($decodedData['page_ids'])) {
            $decodedData['page_ids'] = array_unique(array_merge(
                $decodedData['page_ids'],
                $mergedJobDecodedData['page_ids']
            ));

            $dataSize = count($decodedData['page_ids']);
        }

        $this->setDecodedData($decodedData);
        $this->setDataSize($dataSize);

        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        $status = JobInterface::STATUS_PROCESSING;

        if (is_null($this->getPid())) {
            $status = JobInterface::STATUS_NEW;
        }

        if ((int) $this->getRetries() >= $this->getMaxRetries()) {
            $status = JobInterface::STATUS_ERROR;
        }

        return $status;
    }

    /**
     * @param \Exception $e
     *
     * @throws AlreadyExistsException
     *
     * @return Job
     */
    public function saveError(\Exception $e)
    {
        $this->setErrorLog($e->getMessage());
        $this->getResource()->save($this);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getClass(): string
    {
        return $this->getData(self::FIELD_CLASS);
    }

    /**
     * @inheritdoc
     */
    public function setClass(string $class): JobInterface
    {
        return $this->setData(self::FIELD_CLASS, $class);
    }

    /**
     * @inheritdoc
     */
    public function getMethod(): string
    {
        return $this->getData(self::FIELD_METHOD);
    }

    /**
     * @inheritdoc
     */
    public function setMethod(string $method): JobInterface
    {
        return $this->setData(self::FIELD_METHOD, $method);
    }

    /**
     * @inheritdoc
     */
    public function getBody(): string
    {
        return $this->getData(self::FIELD_DATA);
    }

    /**
     * @inheritdoc
     */
    public function setBody(string $data): JobInterface
    {
        return $this->setData(self::FIELD_DATA, $data);
    }

    /**
     * @inheritdoc
     */
    public function getBodySize(): int
    {
        return $this->getData(self::FIELD_DATA_SIZE);
    }

    /**
     * @inheritdoc
     */
    public function setBodySize(int $size): JobInterface
    {
        return $this->setData(self::FIELD_DATA_SIZE, $size);
    }
}