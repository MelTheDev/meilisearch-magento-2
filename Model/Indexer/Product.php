<?php

namespace MelTheDev\MeiliSearch\Model\Indexer;

use Magento\Store\Model\StoreManagerInterface;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;
use MelTheDev\MeiliSearch\Helper\Data;
use MelTheDev\MeiliSearch\Helper\ProductHelper;
use MelTheDev\MeiliSearch\Model\IndicesConfigurator;
use MelTheDev\MeiliSearch\Model\Queue;

class Product implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * Product constructor.
     * @param ProductHelper $productHelper
     * @param ConfigHelper $configHelper
     * @param Data $dataHelper
     * @param Queue $queue
     * @param StoreManagerInterface $storeManager
     * @noinspection PhpPropertyCanBeReadonlyInspection
     */
    public function __construct(
        private ProductHelper $productHelper,
        private ConfigHelper $configHelper,
        private Data $dataHelper,
        private Queue $queue,
        private StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute($ids)
    {
        $productIds = $ids;
        if ($productIds) {
            $productIds = array_unique(array_merge($productIds, $this->productHelper->getParentProductIds($productIds)));
        }
        $storeIds = array_keys($this->storeManager->getStores());

        foreach ($storeIds as $storeId) {
            if ($this->dataHelper->isIndexingEnabled($storeId) === false) {
                continue;
            }

            $productsPerPage = $this->configHelper->getNumberOfElementByPage();

            if (is_array($productIds) && count($productIds) > 0) {
                foreach (array_chunk($productIds, $productsPerPage) as $chunk) {
                    /** @uses Data::rebuildStoreProductIndex() */
                    $this->queue->addToQueue(
                        Data::class,
                        'rebuildStoreProductIndex',
                        ['storeId' => $storeId, 'productIds' => $chunk],
                        count($chunk)
                    );
                }

                continue;
            }

            $useTmpIndex = $this->configHelper->isQueueActive($storeId);
            $collection = $this->productHelper->getProductCollectionQuery($storeId, $productIds, $useTmpIndex);
            $size = $collection->getSize();

            $pages = ceil($size / $productsPerPage);

            /** @uses IndicesConfigurator::saveConfigurationToMeiliSearch() */
            $this->queue->addToQueue(IndicesConfigurator::class, 'saveConfigurationToMeiliSearch', [
                'storeId' => $storeId,
                'useTmpIndex' => $useTmpIndex,
            ], 1, true);
            for ($i = 1; $i <= $pages; $i++) {
                $data = [
                    'storeId' => $storeId,
                    'productIds' => $productIds,
                    'page' => $i,
                    'pageSize' => $productsPerPage,
                    'useTmpIndex' => $useTmpIndex,
                ];

                /** @uses Data::rebuildProductIndex() */
                $this->queue->addToQueue(Data::class, 'rebuildProductIndex', $data, $productsPerPage, true);
            }

//            if ($useTmpIndex) {
//                $suffix = $this->productHelper->getIndexNameSuffix();
//                /** @uses IndexMover::moveIndexWithSetSettings() */
//                $this->queue->addToQueue(IndexMover::class, 'moveIndexWithSetSettings', [
//                    'tmpIndexName' => $this->fullAction->getIndexName($suffix, $storeId, true),
//                    'indexName' => $this->fullAction->getIndexName($suffix, $storeId),
//                    'storeId' => $storeId,
//                ], 1, true);
//            }
        }
    }

    /**
     * @inheritDoc
     */
    public function executeFull()
    {
        $this->execute(null);
    }
    /**
     * @inheritDoc
     */
    public function executeList(array $ids)
    {
        $this->execute($ids);
    }
    /**
     * @inheritDoc
     */
    public function executeRow($id)
    {
        $this->execute([$id]);
    }
}