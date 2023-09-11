<?php

namespace MelTheDev\MeiliSearch\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Job extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('melthedev_meilisearch_queue', 'job_id');
    }

    /**
     * @throws \Zend_Db_Statement_Exception
     * @throws LocalizedException
     *
     * @return array
     */
    public function getQueueInfo()
    {
        $select = $this->getConnection()->select()
            ->from(
                [$this->getMainTable()],
                [
                    'count' => 'COUNT(*)',
                    'oldest' => 'MIN(created)',
                ]
            );

        $queueInfo = $this->getConnection()->query($select)->fetch();

        if (!$queueInfo['oldest']) {
            $queueInfo['oldest'] = '[no jobs in indexing queue]';
        }

        return $queueInfo;
    }
}