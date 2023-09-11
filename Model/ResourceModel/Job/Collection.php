<?php

namespace MelTheDev\MeiliSearch\Model\ResourceModel\Job;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'job_id';

    protected $_eventPrefix = 'melthedev_meilisearch_queue_job_collection';

    protected $_eventObject = 'jpb_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \MelTheDev\MeiliSearch\Model\Job::class,
            \MelTheDev\MeiliSearch\Model\ResourceModel\Job::class
        );
    }
}