<?php

namespace MelTheDev\MeiliSearch\Plugin\Model\ResourceModel;

use Magento\Framework\Indexer\IndexerInterface;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Indexer\IndexerRegistry;

class Product
{
    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        IndexerRegistry $indexerRegistry
    ) {
        $this->indexer = $indexerRegistry->get('melthedev_meilisearch_products');
    }

    /**
     * @param ProductResource $productResource
     * @param ProductResource $result
     * @param ProductModel $product
     *
     * @return ProductResource
     * @noinspection PluginInspection
     */
    public function afterSave(ProductResource $productResource, ProductResource $result, ProductModel $product)
    {
        $productResource->addCommitCallback(function () use ($product) {
            if (!$this->indexer->isScheduled()) {
                $this->indexer->reindexRow($product->getId());
            }
        });
        return $result;
    }

    /**
     * @param ProductResource $productResource
     * @param ProductResource $result
     * @param ProductModel $product
     *
     * @return ProductResource
     * @noinspection PluginInspection
     */
    public function afterDelete(ProductResource $productResource, ProductResource $result, ProductModel $product)
    {
        $productResource->addCommitCallback(function () use ($product) {
            if (!$this->indexer->isScheduled()) {
                $this->indexer->reindexRow($product->getId());
            }
        });
        return $result;
    }

    /**
     * @param Action $subject
     * @param Action|null $result
     * @param array $productIds
     *
     * @return Action|null
     */
    public function afterUpdateAttributes(Action $subject, Action $result = null, $productIds)
    {
        if (!$this->indexer->isScheduled()) {
            $this->indexer->reindexList(array_unique($productIds));
        }
        return $result;
    }

    /**
     * @param Action $subject
     * @param Action|null $result
     * @param array $productIds
     *
     * @return Action|null
     */
    public function afterUpdateWebsites(Action $subject, Action $result = null, array $productIds)
    {
        if (!$this->indexer->isScheduled()) {
            $this->indexer->reindexList(array_unique($productIds));
        }
        return $result;
    }
}