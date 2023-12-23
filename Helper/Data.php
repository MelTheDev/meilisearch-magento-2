<?php

namespace MelTheDev\MeiliSearch\Helper;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Search\Model\Query;
use MelTheDev\MeiliSearch\Exception\MissingObjectId;
use MelTheDev\MeiliSearch\Helper\Entity\PageHelper;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeCodeResolver;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use MelTheDev\MeiliSearch\Helper\Entity\SuggestionHelper;
use MelTheDev\MeiliSearch\Logger\Logger;

class Data
{
    private ConfigHelper $configHelper;
    private Logger $logger;
    protected $emulationRuns = false;
    private ScopeCodeResolver $scopeCodeResolver;
    private StoreManagerInterface $storeManager;
    private Emulation $emulation;
    private ProductHelper $productHelper;
    private ResourceConnection $resource;
    private MeiliSearchHelper $meiliSearchHelper;
    private CategoryHelper $categoryHelper;
    private State $appState;

    /**
     * Data constructor.
     * @param ConfigHelper $configHelper
     * @param ScopeCodeResolver $scopeCodeResolver
     * @param StoreManagerInterface $storeManager
     * @param Emulation $emulation
     * @param ProductHelper $productHelper
     * @param Logger $logger
     * @param ResourceConnection $resource
     * @param MeiliSearchHelper $meiliSearchHelper
     * @param CategoryHelper $categoryHelper
     * @param PageHelper $pageHelper
     * @param State $appState
     */
    public function __construct(
        ConfigHelper $configHelper,
        ScopeCodeResolver $scopeCodeResolver,
        StoreManagerInterface $storeManager,
        Emulation $emulation,
        ProductHelper $productHelper,
        Logger $logger,
        ResourceConnection $resource,
        MeiliSearchHelper $meiliSearchHelper,
        CategoryHelper $categoryHelper,
        private PageHelper $pageHelper,
        private AdditionalSectionHelper $additionalSectionHelper,
        private SuggestionHelper $suggestionHelper,
        State $appState
    ) {
        $this->configHelper = $configHelper;
        $this->scopeCodeResolver = $scopeCodeResolver;
        $this->storeManager = $storeManager;
        $this->emulation = $emulation;
        $this->productHelper = $productHelper;
        $this->logger = $logger;
        $this->resource = $resource;
        $this->meiliSearchHelper = $meiliSearchHelper;
        $this->categoryHelper = $categoryHelper;
        $this->appState = $appState;
    }

    /**
     * @param $storeId
     * @param $productIds
     * @return void
     * @throws \Exception
     */
    public function rebuildStoreProductIndex($storeId, $productIds)
    {
        if ($this->isIndexingEnabled($storeId) === false) {
            return;
        }

        $this->startEmulation($storeId);
        $this->logger->start('Indexing');
        try {
            $this->logger->start('ok');
            $collection = $this->productHelper->getProductCollectionQuery($storeId, $productIds);
            $size = $collection->getSize();
            if (!empty($productIds)) {
                $size = max(count($productIds), $size);
            }
            $this->logger->log('Store ' . $this->logger->getStoreName($storeId) . ' collection size : ' . $size);
            if ($size > 0) {
                $pages = ceil($size / $this->configHelper->getNumberOfElementByPage());
                $collection->clear();
                $page = 1;
                while ($page <= $pages) {
                    $this->rebuildStoreProductIndexPage(
                        $storeId,
                        $collection,
                        $page,
                        $this->configHelper->getNumberOfElementByPage(),
                        null,
                        $productIds
                    );
                    $page++;
                }
            }
        } catch (\Exception $e) {
            $this->stopEmulation();
            throw $e;
        }
        $this->logger->stop('Indexing');
        $this->stopEmulation();
    }

    /**
     * @param $storeId
     * @param $productIds
     * @param $page
     * @param $pageSize
     * @param $useTmpIndex
     * @return void
     * @throws \Exception
     */
    public function rebuildProductIndex($storeId, $productIds, $page, $pageSize, $useTmpIndex)
    {
        if ($this->isIndexingEnabled($storeId) === false) {
            return;
        }
        $collection = $this->productHelper->getProductCollectionQuery($storeId, null, $useTmpIndex);
        $this->rebuildStoreProductIndexPage($storeId, $collection, $page, $pageSize, null, $productIds, $useTmpIndex);

        //TODO
        // MeiliSearch require reindex per sorting asc/desc with duplicate
        // in-order to work with Algolia instantsearch js, else we need to rewrite the js to work with sort-order
        // if rewritten, please remove this function
        //for more details => https://www.meilisearch.com/docs/learn/fine_tuning_results/sorting
        $this->rebuildStoreProductSortAttrIndexPage(
            $collection,
            $storeId,
            $productIds,
            $page,
            $pageSize,
            $useTmpIndex
        );
    }

    /**
     * @param $collection
     * @param int $storeId
     * @param array $productIds
     * @param int $page
     * @param int $pageSize
     * @param bool $useTmpIndex
     * @return void
     * @throws NoSuchEntityException
     */
    public function rebuildStoreProductSortAttrIndexPage($collection, $storeId, $productIds, $page, $pageSize, $useTmpIndex)
    {
        $sortingAttributes = $this->configHelper->getSorting($storeId);
        foreach ($sortingAttributes as $sortingAttribute) {

            if ($sortingAttribute['attribute'] == 'price') {
                $indexName =
                    $this->getIndexName($this->productHelper->getIndexNameSuffix(), $storeId, $useTmpIndex) .
                    '_' . $sortingAttribute['attribute'] . '_' .
                    $this->storeManager->getStore($storeId)->getCode() . '_' .
                    $sortingAttribute['sort'];
            } else {
                $indexName =
                    $this->getIndexName($this->productHelper->getIndexNameSuffix(), $storeId, $useTmpIndex) .
                    '_' . $sortingAttribute['attribute'] . '_' .
                    $sortingAttribute['sort'];
            }

            $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
            $collection->setOrder($sortingAttribute['attribute'], $sortingAttribute['sort']);
            $this->rebuildStoreProductIndexPage(
                $storeId,
                $collection,
                $page,
                $pageSize,
                null,
                $productIds,
                $useTmpIndex,
                $indexName
            );
        }
    }

    /**
     * @param int $storeId
     * @return void
     * @throws MissingObjectId
     * @throws NoSuchEntityException|LocalizedException
     */
    public function rebuildStoreAdditionalSectionsIndex($storeId)
    {
        if ($this->isIndexingEnabled($storeId) === false) {
            return;
        }

        $additionalSections = $this->configHelper->getAutocompleteSections();

        $protectedSections = ['products', 'categories', 'pages', 'suggestions'];
        foreach ($additionalSections as $section) {
            if (in_array($section['name'], $protectedSections, true)) {
                continue;
            }

            $indexName = $this->getIndexName($this->additionalSectionHelper->getIndexNameSuffix(), $storeId);
            $indexName = $indexName . '_' . $section['name'];

            $attributeValues = $this->additionalSectionHelper->getAttributeValues($storeId, $section);

            foreach (array_chunk($attributeValues, 100) as $chunk) {
                //$this->meiliSearchHelper->addObjects($chunk, $indexName . '_tmp');
                $this->meiliSearchHelper->addObjects($chunk, $indexName);
            }

            //$this->meiliSearchHelper->copyQueryRules($indexName, $indexName . '_tmp');
            //$this->meiliSearchHelper->moveIndex($indexName . '_tmp', $indexName);

            $this->meiliSearchHelper->setSettings($indexName, $this->additionalSectionHelper->getIndexSettings());
        }
    }

    /**
     * @param $storeId
     * @return void
     * @throws MissingObjectId
     * @throws NoSuchEntityException
     */
    public function rebuildStoreSuggestionIndex($storeId)
    {
        if ($this->isIndexingEnabled($storeId) === false ||
            !$this->configHelper->isQuerySuggestionsIndexEnabled($storeId)
        ) {
            return;
        }

        if (!$this->configHelper->isQuerySuggestionsIndexEnabled($storeId)) {
            $this->logger->log('Query Suggestions Indexing is not enabled for the store.');
            return;
        }

        $collection = $this->suggestionHelper->getSuggestionCollectionQuery($storeId);
        $size = $collection->getSize();

        if ($size > 0) {
            $pages = ceil($size / $this->configHelper->getNumberOfElementByPage());
            $collection->clear();
            $page = 1;

            while ($page <= $pages) {
                $this->rebuildStoreSuggestionIndexPage(
                    $storeId,
                    $collection,
                    $page,
                    $this->configHelper->getNumberOfElementByPage()
                );
                $page++;
            }
            unset($indexData);
        }
        //$this->moveStoreSuggestionIndex($storeId);
    }

//    /**
//     * @param $storeId
//     * @return void
//     */
//    public function moveStoreSuggestionIndex($storeId)
//    {
//        if ($this->isIndexingEnabled($storeId) === false) {
//            return;
//        }
//
//        $indexNameSuffix = $this->suggestionHelper->getIndexNameSuffix();
//        $tmpIndexName = $this->getIndexName($indexNameSuffix, $storeId, true);
//        $indexName = $this->getIndexName($indexNameSuffix, $storeId);
//        $this->meiliSearchHelper->copyQueryRules($indexName, $tmpIndexName);
//        $this->meiliSearchHelper->moveIndex($tmpIndexName, $indexName);
//    }

    /**
     * @param $storeId
     * @param $collectionDefault
     * @param $page
     * @param $pageSize
     * @return void
     * @throws MissingObjectId
     * @throws NoSuchEntityException
     */
    public function rebuildStoreSuggestionIndexPage($storeId, $collectionDefault, $page, $pageSize)
    {
        if ($this->isIndexingEnabled($storeId) === false) {
            return;
        }

        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $collection */
        $collection = clone $collectionDefault;
        $collection->setCurPage($page)->setPageSize($pageSize);
        $collection->load();
        //$indexName = $this->getIndexName($this->suggestionHelper->getIndexNameSuffix(), $storeId, true);
        $indexName = $this->getIndexName($this->suggestionHelper->getIndexNameSuffix(), $storeId);
        $indexData = [];

        /** @var Query $suggestion */
        foreach ($collection as $suggestion) {
            $suggestion->setStoreId($storeId);
            $suggestionObject = $this->suggestionHelper->getObject($suggestion);
            if (mb_strlen($suggestionObject['query']) >= 3) {
                array_push($indexData, $suggestionObject);
            }
        }

        if (count($indexData) > 0) {
            $this->meiliSearchHelper->addObjects($indexData, $indexName);
        }
        unset($indexData);
        $collection->walk('clearInstance');
        $collection->clear();
        unset($collection);
    }

    /**
     * @param $storeId
     * @param $collectionDefault
     * @param $page
     * @param $pageSize
     * @param $emulationInfo
     * @param $productIds
     * @param $useTmpIndex
     * @param $indexName
     * @return void
     * @throws NoSuchEntityException|MissingObjectId
     */
    public function rebuildStoreProductIndexPage(
        $storeId,
        $collectionDefault,
        $page,
        $pageSize,
        $emulationInfo = null,
        $productIds = null,
        $useTmpIndex = false,
        $indexName = null
    ) {
        if ($this->isIndexingEnabled($storeId) === false) {
            return;
        }
        $wrapperLogMessage = 'rebuildStoreProductIndexPage: ' . $this->logger->getStoreName($storeId) . ',
            page ' . $page . ',
            pageSize ' . $pageSize;
        $this->logger->start($wrapperLogMessage);

        if ($emulationInfo === null) {
            $this->startEmulation($storeId);
        }
        $additionalAttributes = $this->configHelper->getProductAdditionalAttributes($storeId);

        /** @var Collection $collection */
        $collection = clone $collectionDefault;
        $collection->setCurPage($page)->setPageSize($pageSize);
        $collection->addCategoryIds();
        $collection->addUrlRewrite();

        if ($this->productHelper->isAttributeEnabled($additionalAttributes, 'rating_summary')) {
            $reviewTableName = $this->resource->getTableName('review_entity_summary');
            $collection
                ->getSelect()
                ->columns('(SELECT MAX(rating_summary) FROM ' . $reviewTableName . ' AS o WHERE o.entity_pk_value = e.entity_id AND o.store_id = ' . $storeId . ') as rating_summary');
        }

        /*$this->eventManager->dispatch(
            'mel_meilisearch_before_products_collection_load',
            ['collection' => $collection, 'store' => $storeId]
        );*/

        $logMessage = 'LOADING: ' . $this->logger->getStoreName($storeId) . ',
            collection page: ' . $page . ',
            pageSize: ' . $pageSize;

        $this->logger->start($logMessage);

        $collection->load();

        $this->logger->log('Loaded ' . count($collection) . ' products');
        $this->logger->stop($logMessage);

        $indexName = empty($indexName) ? $this->getIndexName($this->productHelper->getIndexNameSuffix(), $storeId, $useTmpIndex) : $indexName;
        $indexData = $this->getProductsRecords($storeId, $collection, $productIds);

        if (!empty($indexData['toIndex'])) {
            $this->logger->start('ADD/UPDATE TO MEL MEILISEARCH');

            $this->meiliSearchHelper->addObjects($indexData['toIndex'], $indexName);

            $this->logger->log('Product IDs: ' . implode(', ', array_keys($indexData['toIndex'])));
            $this->logger->stop('ADD/UPDATE TO MEL MEILISEARCH');
        }

        if (!empty($indexData['toRemove'])) {
            $toRealRemove = $this->getIdsToRealRemove($indexName, $indexData['toRemove']);
            if (!empty($toRealRemove)) {
                $this->logger->start('REMOVE FROM MEL MEILISEARCH');

                $this->meiliSearchHelper->deleteObjects($toRealRemove, $indexName);

                $this->logger->log('Product IDs: ' . implode(', ', $toRealRemove));
                $this->logger->stop('REMOVE FROM MEL MEILISEARCH');
            }
        }
        unset($indexData);
        $collection->walk('clearInstance');
        $collection->clear();
        unset($collection);
        if ($emulationInfo === null) {
            $this->stopEmulation();
        }
        $this->logger->stop($wrapperLogMessage);
    }

    /**
     * @param $indexName
     * @param $idsToRemove
     * @return array|mixed
     */
    protected function getIdsToRealRemove($indexName, $idsToRemove)
    {
        return $idsToRemove;
        //as this is not required for meilisearch, skipping this method
        if (count($idsToRemove) === 1) {
            return $idsToRemove;
        }

        $toRealRemove = [];
        $idsToRemove = array_map('strval', $idsToRemove);
        foreach (array_chunk($idsToRemove, 1000) as $chunk) {
            $objects = $this->meiliSearchHelper->getObjects($indexName, $chunk);
            if (!empty($objects)) {
                foreach ($objects['results'] as $object) {
                    if (isset($object['objectID'])) {
                        $toRealRemove[] = $object['objectID'];
                    }
                }
            }
        }
        return $toRealRemove;
    }

    /**
     * @param $storeId
     * @param $collection
     * @param $potentiallyDeletedProductsIds
     * @return array
     * @throws \Exception
     */
    protected function getProductsRecords($storeId, $collection, $potentiallyDeletedProductsIds = null)
    {
        $productsToIndex = [];
        $productsToRemove = [];

        // In $potentiallyDeletedProductsIds there might be IDs of deleted products which will not be in a collection
        if (is_array($potentiallyDeletedProductsIds)) {
            $potentiallyDeletedProductsIds = array_combine(
                $potentiallyDeletedProductsIds,
                $potentiallyDeletedProductsIds
            );
        }

        $this->logger->start('CREATE RECORDS ' . $this->logger->getStoreName($storeId));
        $this->logger->log(count($collection) . ' product records to create');

        $salesData = $this->getSalesData($storeId, $collection);
        $transport = new ProductDataArray();

        /*$this->eventManager->dispatch(
            'melthedev_meilisearch_product_collection_add_additional_data',
            ['collection' => $collection, 'store_id' => $storeId, 'additional_data' => $transport]
        );*/

        /** @var Product $product */
        foreach ($collection as $product) {
            $product->setStoreId($storeId);
            $product->setPriceCalculation(false);
            $productId = $product->getId();
            // If $productId is in the collection, remove it from $potentiallyDeletedProductsIds
            // so it's not removed without check
            if (isset($potentiallyDeletedProductsIds[$productId])) {
                unset($potentiallyDeletedProductsIds[$productId]);
            }

            if (isset($productsToIndex[$productId]) || isset($productsToRemove[$productId])) {
                continue;
            }

            try {
                $this->productHelper->canProductBeReindexed($product, $storeId);
            } catch (\Exception $e) {
                $productsToRemove[$productId] = $productId;
                continue;
            }

            if (isset($salesData[$productId])) {
                $product->setData('ordered_qty', $salesData[$productId]['ordered_qty']);
                $product->setData('total_ordered', $salesData[$productId]['total_ordered']);
            }

            if ($additionalData = $transport->getItem($productId)) {
                foreach ($additionalData as $key => $value) {
                    $product->setData($key, $value);
                }
            }

            $productsToIndex[$productId] = $this->productHelper->getObject($product);
        }

        if (is_array($potentiallyDeletedProductsIds)) {
            $productsToRemove = array_merge($productsToRemove, $potentiallyDeletedProductsIds);
        }

        $this->logger->stop('CREATE RECORDS ' . $this->logger->getStoreName($storeId));
        return [
            'toIndex' => $productsToIndex,
            'toRemove' => array_unique($productsToRemove),
        ];
    }

    /**
     * @param $storeId
     * @param Collection $collection
     * @return array
     */
    protected function getSalesData($storeId, Collection $collection)
    {
        $additionalAttributes = $this->configHelper->getProductAdditionalAttributes($storeId);
        if ($this->productHelper->isAttributeEnabled($additionalAttributes, 'ordered_qty') === false
            && $this->productHelper->isAttributeEnabled($additionalAttributes, 'total_ordered') === false) {
            return [];
        }

        $salesData = [];
        $ids = $collection->getColumnValues('entity_id');
        if (count($ids)) {
            $ordersTableName = $this->resource->getTableName('sales_order_item');
            try {
                $salesConnection = $this->resource->getConnectionByName('sales');
            } catch (\DomainException $e) {
                $salesConnection = $this->resource->getConnection();
            }
            $select = $salesConnection->select()
                ->from($ordersTableName, [])
                ->columns('product_id')
                ->columns(['ordered_qty' => new \Zend_Db_Expr('SUM(qty_ordered)')])
                ->columns(['total_ordered' => new \Zend_Db_Expr('SUM(row_total)')])
                ->where('product_id IN (?)', $ids)
                ->group('product_id');
            $salesData = $salesConnection->fetchAll($select, [], \PDO::FETCH_GROUP|\PDO::FETCH_ASSOC|\PDO::FETCH_UNIQUE);
        }
        return $salesData;
    }

    /**
     * @param int $storeId
     * @param array|null $categoryIds
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function rebuildStoreCategoryIndex($storeId, $categoryIds = null)
    {
        if ($this->isIndexingEnabled($storeId) === false) {
            return;
        }

        $this->startEmulation($storeId);

        try {
            $collection = $this->categoryHelper->getCategoryCollectionQuery($storeId, $categoryIds);

            $size = $collection->getSize();
            if (!empty($categoryIds)) {
                $size = max(count($categoryIds), $size);
            }

            if ($size > 0) {
                $pages = ceil($size / $this->configHelper->getNumberOfElementByPage());
                $page = 1;
                while ($page <= $pages) {
                    $this->rebuildStoreCategoryIndexPage(
                        $storeId,
                        $collection,
                        $page,
                        $this->configHelper->getNumberOfElementByPage(),
                        $categoryIds
                    );
                    $page++;
                }
                unset($indexData);
            }
        } catch (\Exception $e) {
            $this->stopEmulation();
            throw $e;
        }
        $this->stopEmulation();
    }

    /**
     * @param $storeId
     * @param $page
     * @param $pageSize
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function rebuildCategoryIndex($storeId, $page, $pageSize)
    {
        if ($this->isIndexingEnabled($storeId) === false) {
            return;
        }

        $this->startEmulation($storeId);
        $collection = $this->categoryHelper->getCategoryCollectionQuery($storeId, null);
        $this->rebuildStoreCategoryIndexPage($storeId, $collection, $page, $pageSize);
        $this->stopEmulation();
    }

    /**
     * @param $storeId
     * @param $collection
     * @param $page
     * @param $pageSize
     * @param null $categoryIds
     * @throws NoSuchEntityException
     */
    public function rebuildStoreCategoryIndexPage($storeId, $collection, $page, $pageSize, $categoryIds = null)
    {
        if ($this->isIndexingEnabled($storeId) === false) {
            return;
        }
        $collection->setCurPage($page)->setPageSize($pageSize);
        $collection->load();

        $indexName = $this->getIndexName($this->categoryHelper->getIndexNameSuffix(), $storeId);
        $indexData = $this->getCategoryRecords($storeId, $collection, $categoryIds);

        if (!empty($indexData['toIndex'])) {
            $this->logger->start('ADD/UPDATE TO MeiliSearch');
            $this->meiliSearchHelper->addObjects($indexData['toIndex'], $indexName);
            $this->logger->log('Product IDs: ' . implode(', ', array_keys($indexData['toIndex'])));
            $this->logger->stop('ADD/UPDATE TO MeiliSearch');
        }

        if (!empty($indexData['toRemove'])) {
            $toRealRemove = $this->getIdsToRealRemove($indexName, $indexData['toRemove']);
            if (!empty($toRealRemove)) {
                $this->logger->start('REMOVE FROM MeiliSearch');
                $this->meiliSearchHelper->deleteObjects($toRealRemove, $indexName);
                $this->logger->log('Category IDs: ' . implode(', ', $toRealRemove));
                $this->logger->stop('REMOVE FROM MeiliSearch');
            }
        }
        unset($indexData);
        $collection->walk('clearInstance');
        $collection->clear();
        unset($collection);
    }

    /**
     * @param int $storeId
     * @param \Magento\Catalog\Model\ResourceModel\Category\Collection $collection
     * @param array|null $potentiallyDeletedCategoriesIds
     *
     * @return array
     * @throws NoSuchEntityException|LocalizedException
     *
     */
    protected function getCategoryRecords($storeId, $collection, $potentiallyDeletedCategoriesIds = null)
    {
        $categoriesToIndex = [];
        $categoriesToRemove = [];

        // In $potentiallyDeletedCategoriesIds there might be IDs of deleted products which will not be in a collection
        if (is_array($potentiallyDeletedCategoriesIds)) {
            $potentiallyDeletedCategoriesIds = array_combine(
                $potentiallyDeletedCategoriesIds,
                $potentiallyDeletedCategoriesIds
            );
        }

        /** @var Category $category */
        foreach ($collection as $category) {
            $category->setStoreId($storeId);
            $categoryId = $category->getId();
            // If $categoryId is in the collection, remove it from $potentiallyDeletedProductsIds
            // so it's not removed without check
            if (isset($potentiallyDeletedCategoriesIds[$categoryId])) {
                unset($potentiallyDeletedCategoriesIds[$categoryId]);
            }

            if (isset($categoriesToIndex[$categoryId]) || isset($categoriesToRemove[$categoryId])) {
                continue;
            }

            try {
                $this->categoryHelper->canCategoryBeReindexed($category, $storeId);
            } catch (\Exception $e) {
                $categoriesToRemove[$categoryId] = $categoryId;
                continue;
            }

            $categoriesToIndex[$categoryId] = $this->categoryHelper->getObject($category);
        }

        if (is_array($potentiallyDeletedCategoriesIds)) {
            $categoriesToRemove = array_merge($categoriesToRemove, $potentiallyDeletedCategoriesIds);
        }

        return [
            'toIndex' => $categoriesToIndex,
            'toRemove' => array_unique($categoriesToRemove),
        ];
    }


    /**
     * @param $storeId
     * @param array|null $pageIds
     * @return void
     * @throws \MelTheDev\MeiliSearch\Exception\MeiliSearchException
     */
    public function rebuildStorePageIndex($storeId, array $pageIds = null)
    {
        if ($this->isIndexingEnabled($storeId) === false) {
            return;
        }

        $indexName = $this->getIndexName($this->pageHelper->getIndexNameSuffix(), $storeId);

        $this->startEmulation($storeId);

        $pages = $this->pageHelper->getPages($storeId, $pageIds);

        $this->stopEmulation();

        // if there are pageIds defined, do not index to _tmp
        //$isFullReindex = (!$pageIds);

        $isFullReindex = false;

        if (isset($pages['toIndex']) && count($pages['toIndex'])) {
            $pagesToIndex = $pages['toIndex'];
            $toIndexName = $indexName . ($isFullReindex ? '_tmp' : '');

            foreach (array_chunk($pagesToIndex, 100) as $chunk) {
                try {
                    $this->meiliSearchHelper->addObjects($chunk, $toIndexName);
                } catch (\Exception $e) {
                    $this->logger->log($e->getMessage());
                    continue;
                }
            }
        }

        if (!$isFullReindex && isset($pages['toRemove']) && count($pages['toRemove'])) {
            $pagesToRemove = $pages['toRemove'];
            foreach (array_chunk($pagesToRemove, 100) as $chunk) {
                try {
                    $this->meiliSearchHelper->deleteObjects($chunk, $indexName);
                } catch (\Exception $e) {
                    $this->logger->log($e->getMessage());
                    continue;
                }
            }
        }

        /*if ($isFullReindex) {
            $this->meiliSearchHelper->copyQueryRules($indexName, $indexName . '_tmp');
            $this->meiliSearchHelper->moveIndex($indexName . '_tmp', $indexName);
        }*/
        $this->meiliSearchHelper->setSettings($indexName, $this->pageHelper->getIndexSettings($storeId));
    }

    /**
     * @param $indexSuffix
     * @param $storeId
     * @param $tmp
     * @return string
     * @throws NoSuchEntityException
     */
    public function getIndexName($indexSuffix, $storeId = null, $tmp = false)
    {
        return $this->getBaseIndexName($storeId) . $indexSuffix . ($tmp ? '_tmp' : '');
    }

    /**
     * @param $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getBaseIndexName($storeId = null)
    {
        return $this->configHelper->getIndexPrefix($storeId) . $this->storeManager->getStore($storeId)->getCode();
    }

    /**
     * @param $storeId
     * @return void
     * @throws \Exception
     */
    public function startEmulation($storeId)
    {
        if ($this->emulationRuns === true) {
            return;
        }
        $this->logger->start('START EMULATION');

        try {
            $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
            $this->scopeCodeResolver->clean();
        } catch (\Exception $e) {
            $this->appState->emulateAreaCode(Area::AREA_FRONTEND, function () use ($storeId) {
                $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
                $this->scopeCodeResolver->clean();
            });
        }

        $this->emulationRuns = true;
        $this->logger->stop('START EMULATION');
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function stopEmulation()
    {
        $this->logger->start('STOP EMULATION');
        $this->emulation->stopEnvironmentEmulation();
        $this->emulationRuns = false;
        $this->logger->stop('STOP EMULATION');
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isIndexingEnabled($storeId = null)
    {
        if ($this->configHelper->isEnabledBackend($storeId) === false) {
            $this->logger->log('INDEXING IS DISABLED FOR ' . $this->logger->getStoreName($storeId));
            return false;
        }
        return true;
    }

    /**
     * Delete inactive products from MeiliSearch
     *
     * @param int|string $storeId
     * @return void
     * @throws NoSuchEntityException
     */
    public function deleteInactiveProducts(int|string $storeId)
    {
        $indexName = $this->getIndexName($this->productHelper->getIndexNameSuffix(), $storeId);
        $index = $this->meiliSearchHelper->getIndex($indexName);
        $objectIds = [];
        $counter = 0;
        $fieldsToSelect = ['objectID'];
        foreach ($index->browseObjects($fieldsToSelect) as $hit) {
            $objectIds[] = $hit['objectID'];
            $counter++;
            if ($counter === 1000) {
                $this->deleteInactiveIds($storeId, $objectIds, $indexName);
                $objectIds = [];
                $counter = 0;
            }
        }
        if (!empty($objectIds)) {
            $this->deleteInactiveIds($storeId, $objectIds, $indexName);
        }
    }

    /**
     * Delete inactive products from MeiliSearch
     *
     * @param int $storeId
     * @param array $objectIds
     * @param string $indexName
     * @return void
     */
    protected function deleteInactiveIds($storeId, $objectIds, $indexName)
    {
        $collection = $this->productHelper->getProductCollectionQuery($storeId, $objectIds);
        $dbIds = $collection->getAllIds();
        $collection = null;
        $idsToDelete = array_diff($objectIds, $dbIds);
        $this->meiliSearchHelper->deleteObjects($idsToDelete, $indexName);
    }
}
