<?php

namespace MelTheDev\MeiliSearch\Plugin\Model\ResourceModel;

use Magento\Framework\Indexer\IndexerInterface;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Model\AbstractModel;

class Page
{
    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param ConfigHelper $configHelper
     * @noinspection PhpPropertyCanBeReadonlyInspection
     */
    public function __construct(
        IndexerRegistry      $indexerRegistry,
        private ConfigHelper $configHelper
    ) {
        $this->indexer = $indexerRegistry->get('melthedev_meilisearch_pages');
    }

    /**
     * @param \Magento\Cms\Model\ResourceModel\Page $pageResource
     * @param AbstractModel $page
     * @return AbstractModel[]
     */
    public function beforeSave(
        \Magento\Cms\Model\ResourceModel\Page $pageResource,
        AbstractModel $page
    ) {
        if (!$this->configHelper->getApiUrl()
            || !$this->configHelper->getApiKey()
        ) {
            return [$page];
        }

        $pageResource->addCommitCallback(function () use ($page) {
            if (!$this->indexer->isScheduled()) {
                $this->indexer->reindexRow($page->getId());
            }
        });

        return [$page];
    }

    /**
     * @param \Magento\Cms\Model\ResourceModel\Page $pageResource
     * @param AbstractModel $page
     * @return AbstractModel[]
     */
    public function beforeDelete(
        \Magento\Cms\Model\ResourceModel\Page $pageResource,
        AbstractModel $page
    ) {
        if (!$this->configHelper->getApiUrl()
            || !$this->configHelper->getApiKey()
        ) {
            return [$page];
        }

        $pageResource->addCommitCallback(function () use ($page) {
            if (!$this->indexer->isScheduled()) {
                $this->indexer->reindexRow($page->getId());
            }
        });

        return [$page];
    }
}