<?php

namespace MelTheDev\MeiliSearch\Helper;

use Magento\Bundle\Model\Product\Type as BundleProductType;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as AttributeResource;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Helper\Stock;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Model\ResourceModel\Group\Collection as GroupCollection;
use Magento\Directory\Model\Currency as CurrencyHelper;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MelTheDev\MeiliSearch\Exception\ProductDeletedException;
use MelTheDev\MeiliSearch\Exception\ProductDisabledException;
use MelTheDev\MeiliSearch\Exception\ProductNotVisibleException;
use MelTheDev\MeiliSearch\Exception\ProductOutOfStockException;
use MelTheDev\MeiliSearch\Helper\Entity\Product\PriceManager;
use MelTheDev\MeiliSearch\Logger\Logger;

class ProductHelper
{
    /**
     * @var AbstractType[]
     */
    private $compositeTypes;
    /**
     * @var Type
     */
    private Type $productType;
    /** @var ProductCollectionFactory */
    private ProductCollectionFactory $productCollectionFactory;
    private Visibility $visibility;
    private Stock $stockHelper;
    private CurrencyHelper $currencyManager;
    private CategoryHelper $categoryHelper;
    private PriceManager $priceManager;
    private ImageHelper $imageHelper;

    /**
     * @var string[]
     */
    protected $predefinedProductAttributes = [
        'name',
        'url_key',
        'image',
        'small_image',
        'thumbnail',
        'msrp_enabled', // Needed to handle MSRP behavior
    ];
    /**
     * @var string[]
     */
    protected $createdAttributes = [
        'path',
        'categories',
        'categories_without_path',
        'ordered_qty',
        'total_ordered',
        'stock_qty',
        'rating_summary',
        'media_gallery',
        'in_stock',
    ];

    /**
     * @var string[]
     */
    protected $attributesToIndexAsArray = [
        'sku',
        'color',
    ];
    private ConfigHelper $configHelper;
    private StockRegistryInterface $stockRegistry;
    private MeiliSearchHelper $meiliSearchHelper;
    private Logger $logger;
    private StoreManagerInterface $storeManager;
    private ResourceConnection $resourceConnection;
    private StockIndexTableNameResolverInterface $stockIndexTableNameResolver;
    private StockResolverInterface $stockResolver;
    private EavConfig $eavConfig;
    private GroupCollection $groupCollection;

    /**
     * ProductHelper constructor.
     * @param Type $productType
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Visibility $visibility
     * @param Stock $stockHelper
     * @param CurrencyHelper $currencyManager
     * @param CategoryHelper $categoryHelper
     * @param PriceManager $priceManager
     * @param ImageHelper $imageHelper
     * @param ConfigHelper $configHelper
     * @param StockRegistryInterface $stockRegistry
     * @param MeiliSearchHelper $meiliSearchHelper
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resourceConnection
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param StockResolverInterface $stockResolver
     * @param EavConfig $eavConfig
     * @param GroupCollection $groupCollection
     */
    public function __construct(
        Type $productType,
        ProductCollectionFactory $productCollectionFactory,
        Visibility $visibility,
        Stock $stockHelper,
        CurrencyHelper $currencyManager,
        CategoryHelper $categoryHelper,
        PriceManager $priceManager,
        ImageHelper $imageHelper,
        ConfigHelper $configHelper,
        StockRegistryInterface $stockRegistry,
        MeiliSearchHelper $meiliSearchHelper,
        Logger $logger,
        StoreManagerInterface $storeManager,
        ResourceConnection $resourceConnection,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        StockResolverInterface $stockResolver,
        EavConfig $eavConfig,
        GroupCollection $groupCollection
    ) {
        $this->productType = $productType;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->visibility = $visibility;
        $this->stockHelper = $stockHelper;
        $this->currencyManager = $currencyManager;
        $this->categoryHelper = $categoryHelper;
        $this->priceManager = $priceManager;
        $this->imageHelper = $imageHelper;
        $this->configHelper = $configHelper;
        $this->stockRegistry = $stockRegistry;
        $this->meiliSearchHelper = $meiliSearchHelper;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->stockResolver = $stockResolver;
        $this->eavConfig = $eavConfig;
        $this->groupCollection = $groupCollection;
    }

    /**
     * @param $storeId
     * @param $productIds
     * @param $onlyVisible
     * @param $includeNotVisibleIndividually
     * @return ProductCollection
     */
    public function getProductCollectionQuery(
        $storeId,
        $productIds = null,
        $onlyVisible = true,
        $includeNotVisibleIndividually = false
    ) {
        $productCollection = $this->productCollectionFactory->create();
        $products = $productCollection
            ->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->distinct(true);

        if ($onlyVisible) {
            $products = $products->addAttributeToFilter('status', ['=' => Status::STATUS_ENABLED]);

            if ($includeNotVisibleIndividually === false) {
                $products = $products
                    ->addAttributeToFilter('visibility', ['in' => $this->visibility->getVisibleInSiteIds()]);
            }

            $this->addStockFilter($products, $storeId);
        }

        $this->addMandatoryAttributes($products);

        $additionalAttr = $this->getAdditionalAttributes($storeId);

        foreach ($additionalAttr as &$attr) {
            $attr = $attr['attribute'];
        }

        $attrs = array_merge($this->predefinedProductAttributes, $additionalAttr);
        $attrs = array_diff($attrs, $this->createdAttributes);

        $products = $products->addAttributeToSelect(array_values($attrs));

        if ($productIds && count($productIds) > 0) {
            $products = $products->addAttributeToFilter('entity_id', ['in' => $productIds]);
        }

        /*// Only for backward compatibility
        $this->eventManager->dispatch(
            'mel_meilisearch_rebuild_store_product_index_collection_load_before',
            ['store' => $storeId, 'collection' => $products]
        );
        $this->eventManager->dispatch(
            'mel_meilisearch_after_products_collection_build',
            [
                'store' => $storeId,
                'collection' => $products,
                'only_visible' => $onlyVisible,
                'include_not_visible_individually' => $includeNotVisibleIndividually,
            ]
        );*/

        return $products;
    }

    /**
     * @param $products
     * @param $storeId
     * @return void
     */
    protected function addStockFilter($products, $storeId)
    {
        if ($this->configHelper->getShowOutOfStock($storeId) === false) {
            $this->stockHelper->addInStockFilterToCollection($products);
        }
    }

    /**
     * @param ProductCollection $products
     * @return void
     */
    protected function addMandatoryAttributes($products)
    {
        $products->addFinalPrice()
            ->addAttributeToSelect('special_price')
            ->addAttributeToSelect('special_from_date')
            ->addAttributeToSelect('special_to_date')
            ->addAttributeToSelect('visibility')
            ->addAttributeToSelect('status');
    }

    /**
     * @param $storeId
     * @return array
     */
    public function getAdditionalAttributes($storeId = null)
    {
        return $this->configHelper->getProductAdditionalAttributes($storeId);
    }

    /**
     * Returns all parent product IDs, e.g. when simple product is part of configurable or bundle
     *
     * @param array $productIds
     *
     * @return array
     */
    public function getParentProductIds(array $productIds)
    {
        $parentIds = [];
        foreach ($this->getCompositeTypes() as $typeInstance) {
            $parentIds = array_merge($parentIds, $typeInstance->getParentIdsByChild($productIds));
        }

        return $parentIds;
    }

    /**
     * Returns composite product type instances
     *
     * @return AbstractType[]
     *
     * @see \Magento\Catalog\Model\Indexer\Product\Flat\AbstractAction::_getProductTypeInstances
     * @noinspection PhpParamsInspection
     */
    protected function getCompositeTypes()
    {
        if ($this->compositeTypes === null) {
            $productEmulator = new DataObject();
            foreach ($this->productType->getCompositeTypes() as $typeId) {
                $productEmulator->setTypeId($typeId);
                $this->compositeTypes[$typeId] = $this->productType->factory($productEmulator);
            }
        }

        return $this->compositeTypes;
    }

    /**
     * @return string
     */
    public function getIndexNameSuffix()
    {
        return '_products';
    }

    /**
     * @param $additionalAttributes
     * @param $attributeName
     * @return bool
     */
    public function isAttributeEnabled($additionalAttributes, $attributeName)
    {
        foreach ($additionalAttributes as $attr) {
            if ($attr['attribute'] === $attributeName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if product can be index on MeiliSearch
     *
     * @param Product $product
     * @param int $storeId
     * @param bool $isChildProduct
     *
     * @return bool
     */
    public function canProductBeReindexed($product, $storeId, $isChildProduct = false)
    {
        if ($product->isDeleted() === true) {
            throw (new ProductDeletedException())
                ->withProduct($product)
                ->withStoreId($storeId);
        }

        if ($product->getStatus() == Status::STATUS_DISABLED) {
            throw (new ProductDisabledException())
                ->withProduct($product)
                ->withStoreId($storeId);
        }

        if ($isChildProduct === false && !in_array($product->getVisibility(), [
                Visibility::VISIBILITY_BOTH,
                Visibility::VISIBILITY_IN_SEARCH,
                Visibility::VISIBILITY_IN_CATALOG,
            ])) {
            throw (new ProductNotVisibleException())
                ->withProduct($product)
                ->withStoreId($storeId);
        }

        $isInStock = true;
        if (!$this->configHelper->getShowOutOfStock($storeId)
            || (!$this->configHelper->indexOutOfStockOptions($storeId) && $isChildProduct === true)) {
            $isInStock = $this->productIsInStock($product, $storeId);
        }

        if (!$isInStock) {
            throw (new ProductOutOfStockException())
                ->withProduct($product)
                ->withStoreId($storeId);
        }

        return true;
    }

    /**
     * Returns is product in stock
     *
     * @param Product $product
     * @param int $storeId
     *
     * @return bool
     */
    public function productIsInStock($product, $storeId)
    {
        /*$stockItem = $this->stockRegistry->getStockItem($product->getId());
        return $product->isSaleable() && $stockItem->getIsInStock();*/
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['stock_index' => $this->getStockTableName($storeId)],
                ['is_salable']
            )
            ->where('stock_index.sku = ?', $product->getSku());

        return (int) $connection->fetchOne($select);
    }

    /**
     * int $storeId
     *
     * @return string
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getStockTableName($storeId)
    {
        $tableName = $this->stockIndexTableNameResolver->execute($this->getStockId($storeId));
        return $this->resourceConnection->getTableName($tableName);
    }

    /**
     * @param int $storeId
     *
     * @return int
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getStockId($storeId)
    {
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        return (int) $this->stockResolver->execute(
            SalesChannelInterface::TYPE_WEBSITE,
            $this->storeManager->getWebsite($websiteId)->getCode()
        )->getStockId();
    }

    /**
     * @param Product $product
     * @return array|mixed|null
     * @throws \Exception
     */
    public function getObject(Product $product)
    {
        $storeId = $product->getStoreId();

        $this->logger->start('CREATE RECORD ' . $product->getId() . ' ' . $this->logger->getStoreName($storeId));
        $defaultData = [];

        $transport = new DataObject($defaultData);
        /*$this->eventManager->dispatch(
            'melthedev_meilisearch_product_index_before',
            ['product' => $product, 'custom_data' => $transport]
        );*/

        $defaultData = $transport->getData();

        $visibility = $product->getVisibility();

        $visibleInCatalog = $this->visibility->getVisibleInCatalogIds();
        $visibleInSearch = $this->visibility->getVisibleInSearchIds();

        $urlParams = [
            '_secure' => $this->configHelper->useSecureUrlsInFrontend($product->getStoreId()),
            '_nosid' => true,
        ];

        $customData = [
            'objectID'           => $product->getId(),
            'name'               => $product->getName(),
            'url'                => $product->getUrlModel()->getUrl($product, $urlParams),
            'visibility_search'  => (int) (in_array($visibility, $visibleInSearch)),
            'visibility_catalog' => (int) (in_array($visibility, $visibleInCatalog)),
            'type_id'            => $product->getTypeId(),
        ];

        $additionalAttributes = $this->getAdditionalAttributes($product->getStoreId());
        $groups = null;

        $customData = $this->addAttribute('description', $defaultData, $customData, $additionalAttributes, $product);
        $customData = $this->addAttribute('ordered_qty', $defaultData, $customData, $additionalAttributes, $product);
        $customData = $this->addAttribute('total_ordered', $defaultData, $customData, $additionalAttributes, $product);
        $customData = $this->addAttribute('rating_summary', $defaultData, $customData, $additionalAttributes, $product);
        $customData = $this->addCategoryData($customData, $product);
        $customData = $this->addImageData($customData, $product, $additionalAttributes);
        $customData = $this->addInStock($defaultData, $customData, $product);
        $customData = $this->addStockQty($defaultData, $customData, $additionalAttributes, $product);
        $subProducts = $this->getSubProducts($product);
        $customData = $this->addAdditionalAttributes($customData, $additionalAttributes, $product, $subProducts);
        $customData = $this->priceManager->addPriceDataByProductType($customData, $product, $subProducts);
        $transport = new DataObject($customData);

        /*$this->eventManager->dispatch(
            'melthedev_meilisearch_subproducts_index',
            ['custom_data' => $transport, 'sub_products' => $subProducts, 'productObject' => $product]
        );*/

        $customData = $transport->getData();
        $customData = array_merge($customData, $defaultData);
        $this->meiliSearchHelper->castProductObject($customData);
        $transport = new DataObject($customData);
        /*$this->eventManager->dispatch(
            'melthedev_meilisearch_after_create_product_object',
            ['custom_data' => $transport, 'sub_products' => $subProducts, 'productObject' => $product]
        );*/
        $customData = $transport->getData();

        $this->logger->stop('CREATE RECORD ' . $product->getId() . ' ' . $this->logger->getStoreName($storeId));

        return $customData;
    }

    /**
     * @param $customData
     * @param $additionalAttributes
     * @param Product $product
     * @param $subProducts
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addAdditionalAttributes($customData, $additionalAttributes, Product $product, $subProducts)
    {
        foreach ($additionalAttributes as $attribute) {
            $attributeName = $attribute['attribute'];

            if (isset($customData[$attributeName]) && $attributeName !== 'sku') {
                continue;
            }

            /** @var \Magento\Catalog\Model\ResourceModel\Product $resource */
            $resource = $product->getResource();

            /** @var AttributeResource $attributeResource */
            $attributeResource = $resource->getAttribute($attributeName);
            if (!$attributeResource) {
                continue;
            }

            $attributeResource = $attributeResource->setData('store_id', $product->getStoreId());

            $value = $product->getData($attributeName);

            if ($value !== null) {
                $customData = $this->addNonNullValue($customData, $value, $product, $attribute, $attributeResource);

                if (!in_array($attributeName, $this->attributesToIndexAsArray, true)) {
                    continue;
                }
            }

            $type = $product->getTypeId();
            if ($type !== 'configurable' && $type !== 'grouped' && $type !== 'bundle') {
                continue;
            }

            $customData = $this->addNullValue($customData, $subProducts, $attribute, $attributeResource);
        }

        return $customData;
    }

    /**
     * @param $customData
     * @param $value
     * @param Product $product
     * @param $attribute
     * @param AttributeResource $attributeResource
     * @return mixed
     */
    protected function addNonNullValue(
        $customData,
        $value,
        Product $product,
        $attribute,
        AttributeResource $attributeResource
    ) {
        $valueText = null;

        if (!is_array($value) && $attributeResource->usesSource()) {
            $valueText = $product->getAttributeText($attribute['attribute']);
        }

        if ($valueText) {
            $value = $valueText;
        } else {
            $attributeResource = $attributeResource->setData('store_id', $product->getStoreId());
            $value = $attributeResource->getFrontend()->getValue($product);
        }

        if ($value) {
            $customData[$attribute['attribute']] = $value;
        }

        return $customData;
    }

    /**
     * @param $subProductImages
     * @param $attribute
     * @param $subProduct
     * @param $valueText
     * @return mixed
     */
    protected function addSubProductImage($subProductImages, $attribute, $subProduct, $valueText)
    {
        if (mb_strtolower($attribute['attribute'], 'utf-8') !== 'color') {
            return $subProductImages;
        }

        $image = $this->imageHelper
            ->init($subProduct, $this->configHelper->getImageType())
            ->resize(
                $this->configHelper->getImageWidth(),
                $this->configHelper->getImageHeight()
            );

        $subImage = $subProduct->getData($image->getType());
        if (!$subImage || $subImage === 'no_selection') {
            return $subProductImages;
        }

        try {
            $textValueInLower = mb_strtolower($valueText, 'utf-8');
            $subProductImages[$textValueInLower] = $image->getUrl();
        } catch (\Exception $e) {
            $this->logger->log($e->getMessage());
            $this->logger->log($e->getTraceAsString());
        }

        return $subProductImages;
    }

    /**
     * @param $customData
     * @param $subProducts
     * @param $attribute
     * @param AttributeResource $attributeResource
     * @return mixed
     */
    protected function addNullValue($customData, $subProducts, $attribute, AttributeResource $attributeResource)
    {
        $attributeName = $attribute['attribute'];

        $values = [];
        $subProductImages = [];

        if (isset($customData[$attributeName])) {
            $values[] = $customData[$attributeName];
        }

        /** @var Product $subProduct */
        foreach ($subProducts as $subProduct) {
            $value = $subProduct->getData($attributeName);
            if ($value) {
                /** @var string|array $valueText */
                $valueText = $subProduct->getAttributeText($attributeName);

                $values = array_merge($values, $this->getValues($valueText, $subProduct, $attributeResource));
                if ($this->configHelper->useAdaptiveImage($attributeResource->getStoreId())) {
                    $subProductImages = $this->addSubProductImage(
                        $subProductImages,
                        $attribute,
                        $subProduct,
                        $valueText
                    );
                }
            }
        }

        if (is_array($values) && count($values) > 0) {
            $customData[$attributeName] = array_values(array_unique($values));
        }

        if (count($subProductImages) > 0) {
            $customData['images_data'] = $subProductImages;
        }

        return $customData;
    }

    /**
     * @param $valueText
     * @param Product $subProduct
     * @param AttributeResource $attributeResource
     * @return array
     */
    protected function getValues($valueText, Product $subProduct, AttributeResource $attributeResource)
    {
        $values = [];

        if ($valueText) {
            if (is_array($valueText)) {
                foreach ($valueText as $valueText_elt) {
                    $values[] = $valueText_elt;
                }
            } else {
                $values[] = $valueText;
            }
        } else {
            $values[] = $attributeResource->getFrontend()->getValue($subProduct);
        }

        return $values;
    }

    /**
     * @param Product $product
     * @return array|ProductInterface[]|DataObject[]
     */
    protected function getSubProducts(Product $product)
    {
        $type = $product->getTypeId();

        if (!in_array($type, ['bundle', 'grouped', 'configurable'], true)) {
            return [];
        }

        $storeId = $product->getStoreId();
        $typeInstance = $product->getTypeInstance();

        if ($typeInstance instanceof Configurable) {
            $subProducts = $typeInstance->getUsedProducts($product);
        } elseif ($typeInstance instanceof BundleProductType) {
            $subProducts = $typeInstance->getSelectionsCollection($typeInstance->getOptionsIds($product), $product)->getItems();
        } else { // Grouped product
            $subProducts = $typeInstance->getAssociatedProducts($product);
        }

        /**
         * @var int $index
         * @var Product $subProduct
         */
        foreach ($subProducts as $index => $subProduct) {
            try {
                $this->canProductBeReindexed($subProduct, $storeId, true);
            } catch (\Exception $e) {
                unset($subProducts[$index]);
            }
        }

        return $subProducts;
    }

    /**
     * @param $defaultData
     * @param $customData
     * @param $additionalAttributes
     * @param Product $product
     * @return mixed
     */
    protected function addStockQty($defaultData, $customData, $additionalAttributes, Product $product)
    {
        if (isset($defaultData['stock_qty']) === false
            && $this->isAttributeEnabled($additionalAttributes, 'stock_qty')) {
            $customData['stock_qty'] = 0;

            $stockItem = $this->stockRegistry->getStockItem($product->getId());
            if ($stockItem) {
                $customData['stock_qty'] = (int) $stockItem->getQty();
            }
        }

        return $customData;
    }

    /**
     * @param $defaultData
     * @param $customData
     * @param Product $product
     * @return mixed
     */
    protected function addInStock($defaultData, $customData, Product $product)
    {
        if (isset($defaultData['in_stock']) === false) {
            $stockItem = $this->stockRegistry->getStockItem($product->getId());
            $customData['in_stock'] = $product->isSaleable() && $stockItem->getIsInStock();
        }

        return $customData;
    }

    /**
     * @param array $customData
     * @param Product $product
     * @param $additionalAttributes
     * @return array
     */
    protected function addImageData(array $customData, Product $product, $additionalAttributes)
    {
        if (false === isset($customData['thumbnail_url'])) {
            $customData['thumbnail_url'] = $this->imageHelper
                ->init($product, 'product_thumbnail_image')
                ->getUrl();
        }

        if (false === isset($customData['image_url'])) {
            $this->imageHelper
                ->init($product, $this->configHelper->getImageType())
                ->resize($this->configHelper->getImageWidth(), $this->configHelper->getImageHeight());

            $customData['image_url'] = $this->imageHelper->getUrl();

            if ($this->isAttributeEnabled($additionalAttributes, 'media_gallery')) {
                $product->load($product->getId(), 'media_gallery');

                $customData['media_gallery'] = [];

                $images = $product->getMediaGalleryImages();
                if ($images) {
                    foreach ($images as $image) {
                        $customData['media_gallery'][] = $image->getUrl();
                    }
                }
            }
        }

        return $customData;
    }

    /**
     * @param $categoryIds
     * @param $storeId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllCategories($categoryIds, $storeId)
    {
        $filterNotIncludedCategories = $this->configHelper->showCatsNotIncludedInNavigation($storeId);
        $categories = $this->categoryHelper->getCoreCategories($filterNotIncludedCategories, $storeId);

        $selectedCategories = [];
        foreach ($categoryIds as $id) {
            if (isset($categories[$id])) {
                $selectedCategories[] = $categories[$id];
            }
        }

        return $selectedCategories;
    }

    /**
     * @param $customData
     * @param Product $product
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function addCategoryData($customData, Product $product)
    {
        $storeId = $product->getStoreId();
        $categories = [];
        $categoriesWithPath = [];
        $categoryIds = [];

        $_categoryIds = $product->getCategoryIds();

        if (is_array($_categoryIds) && count($_categoryIds) > 0) {
            $categoryCollection = $this->getAllCategories($_categoryIds, $storeId);

            /** @var Store $store */
            $store = $this->storeManager->getStore($product->getStoreId());
            $rootCat = $store->getRootCategoryId();

            foreach ($categoryCollection as $category) {
                // Check and skip all categories that is not
                // in the path of the current store.
                $path = $category->getPath();
                $pathParts = explode('/', $path);
                if (isset($pathParts[1]) && $pathParts[1] !== $rootCat) {
                    continue;
                }

                $categoryName = $this->categoryHelper->getCategoryName($category->getId(), $storeId);
                if ($categoryName) {
                    $categories[] = $categoryName;
                }

                $category->getUrlInstance()->setStore($product->getStoreId());
                $path = [];

                foreach ($category->getPathIds() as $treeCategoryId) {
                    $name = $this->categoryHelper->getCategoryName($treeCategoryId, $storeId);
                    if ($name) {
                        $categoryIds[] = $treeCategoryId;
                        $path[] = $name;
                    }
                }

                $categoriesWithPath[] = $path;
            }
        }

        foreach ($categoriesWithPath as $result) {
            for ($i = count($result) - 1; $i > 0; $i--) {
                $categoriesWithPath[] = array_slice($result, 0, $i);
            }
        }

        $categoriesWithPath = array_intersect_key(
            $categoriesWithPath,
            array_unique(array_map('serialize', $categoriesWithPath))
        );

        $hierarchicalCategories = $this->getHierarchicalCategories($categoriesWithPath);

        $customData['categories'] = $hierarchicalCategories;
        $customData['categories_without_path'] = $categories;
        $customData['categoryIds'] = array_values(array_unique($categoryIds));

        return $customData;
    }

    /**
     * @param $categoriesWithPath
     * @return array
     */
    protected function getHierarchicalCategories($categoriesWithPath)
    {
        $hierachivalCategories = [];

        $levelName = 'level';

        foreach ($categoriesWithPath as $category) {
            $categoryCount = count($category);
            for ($i = 0; $i < $categoryCount; $i++) {
                if (isset($hierachivalCategories[$levelName . $i]) === false) {
                    $hierachivalCategories[$levelName . $i] = [];
                }

                if ($category[$i] === null) {
                    continue;
                }

                $hierachivalCategories[$levelName . $i][] = implode(' /// ', array_slice($category, 0, $i + 1));
            }
        }

        foreach ($hierachivalCategories as &$level) {
            $level = array_values(array_unique($level));
        }

        return $hierachivalCategories;
    }

    /**
     * @param $attribute
     * @param $defaultData
     * @param $customData
     * @param $additionalAttributes
     * @param Product $product
     * @return mixed
     */
    protected function addAttribute($attribute, $defaultData, $customData, $additionalAttributes, Product $product)
    {
        if (isset($defaultData[$attribute]) === false
            && $this->isAttributeEnabled($additionalAttributes, $attribute)) {
            $customData[$attribute] = $product->getData($attribute);
        }

        return $customData;
    }

    /**
     * @param $addEmptyRow
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllAttributes($addEmptyRow = false)
    {
        if (!isset($this->productAttributes)) {
            $this->productAttributes = [];
            //$allAttributes = $this->eavConfig->getEntityAttributeCodes('catalog_product');
            $allAttributes = array_keys($this->eavConfig->getEntityAttributes('catalog_product'));

            $productAttributes = array_merge([
                'name',
                'path',
                'categories',
                'categories_without_path',
                'description',
                'ordered_qty',
                'total_ordered',
                'stock_qty',
                'rating_summary',
                'media_gallery',
                'in_stock',
            ], $allAttributes);

            $excludedAttributes = [
                'all_children', 'available_sort_by', 'children', 'children_count', 'custom_apply_to_products',
                'custom_design', 'custom_design_from', 'custom_design_to', 'custom_layout_update',
                'custom_use_parent_settings', 'default_sort_by', 'display_mode', 'filter_price_range',
                'global_position', 'image', 'include_in_menu', 'is_active', 'is_always_include_in_menu', 'is_anchor',
                'landing_page', 'lower_cms_block', 'page_layout', 'path_in_store', 'position', 'small_image',
                'thumbnail', 'url_key', 'url_path', 'visible_in_menu', 'quantity_and_stock_status',
            ];

            $productAttributes = array_diff($productAttributes, $excludedAttributes);

            foreach ($productAttributes as $attributeCode) {
                $this->productAttributes[$attributeCode] = $this->eavConfig
                    ->getAttribute('catalog_product', $attributeCode)
                    ->getFrontendLabel();
            }
        }

        $attributes = $this->productAttributes;

        if ($addEmptyRow === true) {
            $attributes[''] = '';
        }

        uksort($attributes, function ($a, $b) {
            return strcmp($a, $b);
        });

        return $attributes;
    }

    public function setSettings($indexName, $indexNameTmp, $storeId, $saveToTmpIndicesToo = false)
    {
        $searchableAttributes = $this->getSearchableAttributes($storeId);
        $customRanking = $this->getCustomRanking($storeId);
        $retrievableAttributes = $this->getRetrieveableAttributes($storeId);
        $filterableAttributes = $this->getAttributesForFaceting($storeId);

        $requiredToShowAttributes = [
            'objectID',
            'url',
            'thumbnail_url',
            'image_url'
        ];
        $retrievableAttributes = array_unique(array_merge($requiredToShowAttributes, $retrievableAttributes));

        $requiredFilterableAttributes = [
            'visibility_catalog',
            'visibility_search'
        ];
        $filterableAttributes = array_unique(array_merge($filterableAttributes, $requiredFilterableAttributes));

        $indexSettings = [
            'searchableAttributes' => $searchableAttributes,
            'rankingRules'         => $customRanking,
            'sortableAttributes'   => $this->configHelper->getSortableAttributes($storeId),
            'displayedAttributes'  => $retrievableAttributes,
            'filterableAttributes' => $filterableAttributes,
        ];

        // Additional index settings from event observer
        $transport = new DataObject($indexSettings);
        // Only for backward compatibility
        /*$this->eventManager->dispatch(
            'melthedev_meilisearch_index_settings_prepare',
            ['store_id' => $storeId, 'index_settings' => $transport]
        );
        $this->eventManager->dispatch(
            'melthedev_meilisearch_products_index_before_set_settings',
            [
                'store_id'       => $storeId,
                'index_settings' => $transport,
            ]
        );*/

        $indexSettings = $transport->getData();
        $this->meiliSearchHelper->setSettings($indexName, $indexSettings, false, true);
        $this->logger->log('Settings: ' . json_encode($indexSettings));
        if ($saveToTmpIndicesToo === true) {
            $this->meiliSearchHelper->setSettings($indexNameTmp, $indexSettings, false, true, $indexName);
            $this->logger->log('Pushing the same settings to TMP index as well');
        }

        //sorting replica is not supported in MeiliSearch like Algolia does
        //$sortingIndices = $this->configHelper->getSortingIndices($indexName, $storeId);

        $replicas = [];
        /*if ($this->configHelper->isInstantEnabled($storeId)) {
            $replicas = array_values(array_map(function ($sortingIndex) {
                return $sortingIndex['name'];
            }, $sortingIndices));
        }*/
        // Managing Virtual Replica
        /*if ($this->configHelper->useVirtualReplica($storeId)) {
            $replicas = $this->handleVirtualReplica($replicas, $indexName);
        }*/
    }

    /**
     * @param $storeId
     * @return array
     */
    protected function getRetrieveableAttributes($storeId = null)
    {
        $retrievableAttributes = [];
        foreach ($this->getAdditionalAttributes($storeId) as $attribute) {
            if ($attribute['retrievable'] == '1') {
                $retrievableAttributes[] = $attribute['attribute'];
            }
        }
        return $retrievableAttributes;
    }

    /**
     * @param $storeId
     * @return array
     */
    private function getSearchableAttributes($storeId = null)
    {
        $searchableAttributes = [];

        foreach ($this->getAdditionalAttributes($storeId) as $attribute) {
            if ($attribute['searchable'] === '1') {
                $searchableAttributes[] = $attribute['attribute'];
                /*if (!isset($attribute['order']) || $attribute['order'] === 'ordered') {
                    $searchableAttributes[] = $attribute['attribute'];
                } else {
                    $searchableAttributes[] = 'unordered(' . $attribute['attribute'] . ')';
                }

                if ($attribute['attribute'] === 'categories') {
                    $searchableAttributes[] = (isset($attribute['order']) && $attribute['order'] === 'ordered') ?
                        'categories_without_path' : 'unordered(categories_without_path)';
                }*/
            }
        }

        $searchableAttributes = array_values(array_unique($searchableAttributes));

        return $searchableAttributes;
    }

    /**
     * @param $storeId
     * @return array
     */
    private function getCustomRanking($storeId)
    {
        $customRanking = [];

        $customRankings = $this->configHelper->getProductCustomRanking($storeId);
        $defaultRankingRules = [
            ['attribute' => 'words', 'order' => ''],
            ['attribute' => 'typo', 'order'  => ''],
            ['attribute' => 'proximity', 'order'  => ''],
            ['attribute' => 'attribute', 'order'  => ''],
            ['attribute' => 'sort', 'order'  => ''],
            ['attribute' => 'exactness', 'order'  => ''],
        ];

        $customRankings = array_merge($defaultRankingRules, $customRankings);
        foreach ($customRankings as $ranking) {
            //$customRanking[] = $ranking['order'] . '(' . $ranking['attribute'] . ')';
            //https://docs.meilisearch.com/learn/getting_started/customizing_relevancy.html#ranking-rules
            if (empty($ranking['order'])) {
                $customRanking[] = $ranking['attribute'];
            } else {
                $customRanking[] = $ranking['attribute'] . ':' . $ranking['order'];
            }
        }
        return $customRanking;
    }

    /**
     * @param $storeId
     * @return array
     */
    protected function getUnretrieveableAttributes($storeId = null)
    {
        $unretrievableAttributes = [];

        foreach ($this->getAdditionalAttributes($storeId) as $attribute) {
            if ($attribute['retrievable'] !== '1') {
                $unretrievableAttributes[] = $attribute['attribute'];
            }
        }

        return $unretrievableAttributes;
    }

    /**
     * @param $storeId
     * @return array
     */
    protected function getAttributesForFaceting($storeId)
    {
        $attributesForFaceting = [];

        $currencies = $this->currencyManager->getConfigAllowCurrencies();

        $facets = $this->configHelper->getFacets($storeId);
        foreach ($facets as $facet) {
            if ($facet['attribute'] === 'price') {
                foreach ($currencies as $currency_code) {
                    $facet['attribute'] = 'price.' . $currency_code . '.default';

                    if ($this->configHelper->isCustomerGroupsEnabled($storeId)) {
                        foreach ($this->groupCollection as $group) {
                            $group_id = (int) $group->getData('customer_group_id');

                            $attributesForFaceting[] = 'price.' . $currency_code . '.group_' . $group_id;
                        }
                    }

                    $attributesForFaceting[] = $facet['attribute'];
                }
            } else {
                $attribute = $facet['attribute'];
                /*if (array_key_exists('searchable', $facet)) {
                    if ($facet['searchable'] === '1') {
                        $attribute = 'searchable(' . $attribute . ')';
                    } elseif ($facet['searchable'] === '3') {
                        $attribute = 'filterOnly(' . $attribute . ')';
                    }
                }*/
                $attributesForFaceting[] = $attribute;
            }
        }

        if ($this->configHelper->replaceCategories($storeId) && !in_array('categories', $attributesForFaceting, true)) {
            $attributesForFaceting[] = 'categories';
        }

        // Used for merchandising
        $attributesForFaceting[] = 'categoryIds';

        return $attributesForFaceting;
    }
}
