<?php

namespace MelTheDev\MeiliSearch\Model\Indexer;

class QueueRunner implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    public const INDEXER_ID = 'melthedev_meilisearch_queue_runner';

    /**
     * @inheritDoc
     */
    public function execute($ids)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function executeFull()
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function executeList(array $ids)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function executeRow($id)
    {
        return $this;
    }
}
