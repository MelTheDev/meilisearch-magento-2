<?php

namespace MelTheDev\MeiliSearch\Helper;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Category as MagentoCategory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Framework\Url;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Eav\Model\Config as EavConfig;
use MelTheDev\MeiliSearch\Exception\CategoryEmptyException;
use MelTheDev\MeiliSearch\Exception\CategoryNotActiveException;
use MelTheDev\MeiliSearch\Logger\Logger;

class CategoryHelper
{
    private CategoryCollectionFactory $categoryCollectionFactory;
    private CategoryResource $categoryResource;
    private CategoryRepository $categoryRepository;
    private ResourceConnection $resourceConnection;
    private StoreManagerInterface $storeManager;
    private ConfigHelper $configHelper;
    private Manager $moduleManager;
    private EavConfig $eavConfig;
    /**
     * @var MeiliSearchHelper
     */
    private MeiliSearchHelper $meiliSearchHelper;
    /**
     * @var Logger
     */
    private Logger $logger;
    /**
     * @var int
     */
    protected $rootCategoryId = -1;

    /**
     * CategoryHelper constructor.
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryResource $categoryResource
     * @param CategoryRepository $categoryRepository
     * @param ResourceConnection $resourceConnection
     * @param StoreManagerInterface $storeManager
     * @param ConfigHelper $configHelper
     * @param Manager $moduleManager
     * @param EavConfig $eavConfig
     * @param MeiliSearchHelper $meiliSearchHelper
     * @param Logger $logger
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryResource          $categoryResource,
        CategoryRepository        $categoryRepository,
        ResourceConnection        $resourceConnection,
        StoreManagerInterface     $storeManager,
        ConfigHelper              $configHelper,
        Manager                   $moduleManager,
        EavConfig                 $eavConfig,
        MeiliSearchHelper         $meiliSearchHelper,
        Logger                    $logger
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryResource          = $categoryResource;
        $this->categoryRepository        = $categoryRepository;
        $this->resourceConnection        = $resourceConnection;
        $this->storeManager              = $storeManager;
        $this->configHelper              = $configHelper;
        $this->moduleManager             = $moduleManager;
        $this->eavConfig                 = $eavConfig;
        $this->meiliSearchHelper         = $meiliSearchHelper;
        $this->logger                    = $logger;
    }

    /**
     * @param int $categoryId
     * @param null $storeId
     *
     * @return string|null
     * @throws LocalizedException
     */
    public function getCategoryName($categoryId, $storeId = null)
    {
        if ($categoryId instanceof MagentoCategory) {
            $categoryId = $categoryId->getId();
        }

        if ($storeId instanceof Store) {
            $storeId = $storeId->getId();
        }

        $categoryId = (int) $categoryId;
        $storeId = (int) $storeId;
        if (!isset($this->categoryNames)) {
            $this->categoryNames = [];

            /** @var CategoryResource $categoryModel */
            $categoryModel = $this->categoryResource;

            if ($attribute = $categoryModel->getAttribute('name')) {
                $columnId = $this->getCorrectIdColumn();
                $expression = new \Zend_Db_Expr("CONCAT(backend.store_id, '-', backend." . $columnId . ')');

                $connection = $this->resourceConnection->getConnection();
                $select = $connection->select()
                    ->from(
                        ['backend' => $attribute->getBackendTable()],
                        [$expression, 'backend.value']
                    )
                    ->join(
                        ['category' => $categoryModel->getTable('catalog_category_entity')],
                        'backend.' . $columnId . ' = category.' . $columnId,
                        []
                    )
                    ->where('backend.attribute_id = ?', $attribute->getAttributeId())
                    ->where('category.level > ?', 1);

                $this->categoryNames = $connection->fetchPairs($select);
            }
        }

        $categoryName = null;

        $categoryKeyId = $this->getCategoryKeyId($categoryId, $storeId);

        if ($categoryKeyId === null) {
            return $categoryName;
        }

        $key = $storeId . '-' . $categoryKeyId;

        if (isset($this->categoryNames[$key])) {
            // Check whether the category name is present for the specified store
            $categoryName = (string) $this->categoryNames[$key];
        } elseif ($storeId !== 0) {
            // Check whether the category name is present for the default store
            $key = '0-' . $categoryKeyId;
            if (isset($this->categoryNames[$key])) {
                $categoryName = (string) $this->categoryNames[$key];
            }
        }

        return $categoryName;
    }

    /**
     * @param $categoryId
     * @param $storeId
     * @return mixed|null
     * @throws LocalizedException
     */
    private function getCategoryKeyId($categoryId, $storeId = null)
    {
        $categoryKeyId = $categoryId;

        if ($this->getCorrectIdColumn() === 'row_id') {
            $category = $this->getCategoryById($categoryId, $storeId);
            return $category ? $category->getRowId() : null;
        }

        return $categoryKeyId;
    }

    /**
     * @param $categoryId
     * @param $storeId
     * @return mixed|null
     * @throws LocalizedException
     */
    private function getCategoryById($categoryId, $storeId = null)
    {
        $categories = $this->getCoreCategories(false, $storeId);
        return $categories[$categoryId] ?? null;
    }

    /**
     * @return string
     */
    private function getCorrectIdColumn()
    {
        if (isset($this->idColumn)) {
            return $this->idColumn;
        }

        $this->idColumn = 'entity_id';

        $edition = $this->configHelper->getMagentoEdition();
        $version = $this->configHelper->getMagentoVersion();

        if ($edition !== 'Community' && version_compare($version, '2.1.0', '>=') &&
            $this->moduleManager->isEnabled('Magento_Staging')
        ) {
            $this->idColumn = 'row_id';
        }

        return $this->idColumn;
    }

    /**
     * @param $filterNotIncludedCategories
     * @param $storeId
     * @return array
     * @throws LocalizedException
     */
    public function getCoreCategories($filterNotIncludedCategories = true, $storeId = null)
    {
        $key = $filterNotIncludedCategories ? 'filtered' : 'non_filtered';

        $collection = $this->categoryCollectionFactory->create()
            ->distinct(true)
            ->setStoreId($storeId)
            ->addNameToResult()
            ->addIsActiveFilter()
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('level', ['gt' => 1]);

        if ($filterNotIncludedCategories) {
            $collection->addAttributeToFilter('include_in_menu', '1');
        }

        $coreCategories[$key] = [];

        /** @var \Magento\Catalog\Model\Category $category */
        foreach ($collection as $category) {
            $coreCategories[$key][$category->getId()] = $category;
        }

        return $coreCategories[$key];
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getAllAttributes()
    {
        if (isset($this->categoryAttributes)) {
            return $this->categoryAttributes;
        }

        $this->categoryAttributes = [];

        //$allAttributes = $this->eavConfig->getEntityAttributeCodes('catalog_category');
        $allAttributes = array_keys($this->eavConfig->getEntityAttributes('catalog_category'));

        $categoryAttributes = array_merge($allAttributes, ['product_count']);

        $excludedAttributes = [
            'all_children', 'available_sort_by', 'children', 'children_count', 'custom_apply_to_products',
            'custom_design', 'custom_design_from', 'custom_design_to', 'custom_layout_update',
            'custom_use_parent_settings', 'default_sort_by', 'display_mode', 'filter_price_range',
            'global_position', 'image', 'include_in_menu', 'is_active', 'is_always_include_in_menu', 'is_anchor',
            'landing_page', 'level', 'lower_cms_block', 'page_layout', 'path_in_store', 'position', 'small_image',
            'thumbnail', 'url_key', 'url_path','visible_in_menu',
        ];

        $categoryAttributes = array_diff($categoryAttributes, $excludedAttributes);

        foreach ($categoryAttributes as $attributeCode) {
            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
            $attribute = $this->eavConfig->getAttribute('catalog_category', $attributeCode);
            $this->categoryAttributes[$attributeCode] = $attribute->getData('frontend_label');
        }

        return $this->categoryAttributes;
    }

    /**
     * @param $storeId
     * @return array
     */
    public function getAdditionalAttributes($storeId = null)
    {
        return $this->configHelper->getCategoryAdditionalAttributes($storeId);
    }

    /**
     * @param $storeId
     * @param null $categoryIds
     *
     * @return CategoryCollection
     * @throws NoSuchEntityException
     *
     * @throws LocalizedException
     */
    public function getCategoryCollectionQuery($storeId, $categoryIds = null)
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore($storeId);
        $storeRootCategoryPath = sprintf('%d/%d', $this->getRootCategoryId(), $store->getRootCategoryId());

        $unSerializedCategoriesAttrs = $this->getAdditionalAttributes($storeId);
        $additionalAttr = array_column($unSerializedCategoriesAttrs, 'attribute');

        $categories = $this->categoryCollectionFactory->create()
            ->distinct(true)
            ->addNameToResult()
            ->setStoreId($storeId)
            ->addUrlRewriteToResult()
            ->addAttributeToFilter('level', ['gt' => 1])
            ->addPathFilter($storeRootCategoryPath)
            ->addAttributeToSelect(array_merge(['name', 'is_active', 'include_in_menu', 'image'], $additionalAttr))
            ->addOrderField('entity_id');

        if ($categoryIds) {
            $categories->addAttributeToFilter('entity_id', ['in' => $categoryIds]);
        }

        /*$this->eventManager->dispatch(
            'mel_meilisearch_after_categories_collection_build',
            ['store' => $storeId, 'collection' => $categories]
        );*/

        return $categories;
    }

    /**
     * @param MagentoCategory $category
     * @return array|mixed|null
     * @throws LocalizedException
     */
    public function getObject(Category $category)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $category->getProductCollection();
        $category->setProductCount($productCollection->getSize());

        $transport = new DataObject();
        /*$this->eventManager->dispatch(
            'mel_meilisearch_category_index_before',
            ['category' => $category, 'custom_data' => $transport]
        );*/
        $customData = $transport->getData();
        $storeId    = $category->getStoreId();

        /** @var Url $urlInstance */
        $urlInstance = $category->getUrlInstance();
        $urlInstance->setData('store', $storeId);

        $path = '';
        foreach ($category->getPathIds() as $categoryId) {
            if ($path !== '') {
                $path .= ' / ';
            }
            $path .= $this->getCategoryName($categoryId, $storeId);
        }

        $imageUrl = null;
        try {
            $imageUrl = $category->getImageUrl();
        } catch (\Exception $e) {
            /* no image, no default: not fatal */
        }

        $data = [
            'objectID'        => $category->getId(),
            'name'            => $category->getName(),
            'path'            => $path,
            'level'           => $category->getLevel(),
            'url'             => $this->getUrl($category),
            'include_in_menu' => $category->getIncludeInMenu(),
            '_tags'           => ['category'],
            'popularity'      => 1,
            'product_count'   => $category->getProductCount(),
        ];

        if (!empty($imageUrl)) {
            $data['image_url'] = $imageUrl;
        }

        foreach ($this->configHelper->getCategoryAdditionalAttributes($storeId) as $attribute) {
            $value = $category->getData($attribute['attribute']);

            /** @var CategoryResource $resource */
            $resource = $category->getResource();

            $attributeResource = $resource->getAttribute($attribute['attribute']);
            if ($attributeResource) {
                $value = $attributeResource->getFrontend()->getValue($category);
            }

            if (isset($data[$attribute['attribute']])) {
                $value = $data[$attribute['attribute']];
            }

            if ($value) {
                $data[$attribute['attribute']] = $value;
            }
        }

        $data = array_merge($data, $customData);

        $transport = new DataObject($data);
        /*$this->eventManager->dispatch(
            'mel_meilisearch_after_create_category_object',
            ['category' => $category, 'categoryObject' => $transport]
        );*/
        $data = $transport->getData();

        return $data;
    }

    /**
     * @param MagentoCategory $category
     * @return array|string|string[]
     */
    private function getUrl(Category $category)
    {
        $categoryUrl = $category->getUrl();

        if ($this->configHelper->useSecureUrlsInFrontend($category->getStoreId()) === false) {
            return $categoryUrl;
        }

        $unsecureBaseUrl = $category->getUrlInstance()->getBaseUrl(['_secure' => false]);
        $secureBaseUrl = $category->getUrlInstance()->getBaseUrl(['_secure' => true]);

        if (mb_strpos($categoryUrl, $unsecureBaseUrl) === 0) {
            return substr_replace($categoryUrl, $secureBaseUrl, 0, mb_strlen($unsecureBaseUrl));
        }

        return $categoryUrl;
    }

    /**
     * @return int|mixed
     * @throws LocalizedException
     */
    public function getRootCategoryId()
    {
        if ($this->rootCategoryId !== -1) {
            return $this->rootCategoryId;
        }

        $collection = $this->categoryCollectionFactory->create()->addAttributeToFilter('parent_id', '0');

        /** @var \Magento\Catalog\Model\Category $rootCategory */
        $rootCategory = $collection->getFirstItem();

        $this->rootCategoryId = $rootCategory->getId();

        return $this->rootCategoryId;
    }

    /**
     * @param Category $category
     * @param int $storeId
     *
     * @return bool
     *
     * @throws NoSuchEntityException
     *
     */
    public function canCategoryBeReindexed($category, $storeId)
    {
        if ($this->isCategoryActive($category, $storeId) === false) {
            throw new CategoryNotActiveException();
        }

        if ($this->configHelper->shouldIndexEmptyCategories($storeId) === false && $category->getProductCount() <= 0) {
            throw new CategoryEmptyException();
        }

        return true;
    }

    /**
     * @param Category $category
     * @param int|null $storeId
     *
     * @return bool
     *
     * @throws NoSuchEntityException
     *
     */
    public function isCategoryActive($category, $storeId = null)
    {
        $pathIds = $category->getPathIds();
        array_shift($pathIds);

        foreach ($pathIds as $pathId) {
            $parent = $this->categoryRepository->get($pathId, $storeId);
            if ($parent && (bool) $parent->getIsActive() === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getIndexNameSuffix()
    {
        return '_categories';
    }

    public function setSettings(string $indexName, int $storeId)
    {
        $settings = $this->getIndexSettings($storeId);
        $this->meiliSearchHelper->setSettings($indexName, $settings, false, true);
    }

    /**
     * @param $storeId
     * @return array|mixed|null
     */
    public function getIndexSettings($storeId)
    {
        $searchableAttributes    = [];
        $unretrievableAttributes = [];
        $retrievableAttributes   = [];

        foreach ($this->configHelper->getCategoryAdditionalAttributes($storeId) as $attribute) {
            if ($attribute['searchable'] === '1') {
                $searchableAttributes[] = $attribute['attribute'];
                /*if ($attribute['order'] === 'ordered') {
                    $searchableAttributes[] = $attribute['attribute'];
                } else {
                    $searchableAttributes[] = 'unordered(' . $attribute['attribute'] . ')';
                }*/
            }
            if ($attribute['retrievable'] !== '1') {
                $unretrievableAttributes[] = $attribute['attribute'];
            }
            if ($attribute['retrievable'] === '1') {
                $retrievableAttributes[] = $attribute['attribute'];
            }
        }

        /*$requiredToShowAttributes = [
            'objectID',
            'url',
            'thumbnail_url',
            'image_url'
        ];

        $retrievableAttributes = array_merge($requiredToShowAttributes, $retrievableAttributes);*/

        $customRankings = $this->configHelper->getCategoryCustomRanking($storeId);
        $defaultRankingRules = [
            ['attribute' => 'words', 'order' => ''],
            ['attribute' => 'typo', 'order'  => ''],
            ['attribute' => 'proximity', 'order'  => ''],
            ['attribute' => 'attribute', 'order'  => ''],
            ['attribute' => 'sort', 'order'  => ''],
            ['attribute' => 'exactness', 'order'  => ''],
        ];
        $customRankings = array_merge($defaultRankingRules, $customRankings);

        $customRankingsArr = [];
        foreach ($customRankings as $ranking) {
            //$customRankingsArr[] = $ranking['order'] . '(' . $ranking['attribute'] . ')';
            //https://docs.meilisearch.com/learn/getting_started/customizing_relevancy.html#ranking-rules
            if (empty($ranking['order'])) {
                $customRankingsArr[] = $ranking['attribute'];
            } else {
                $customRankingsArr[] = $ranking['attribute'] . ':' . $ranking['order'];
            }
        }

        // Default index settings
        $indexSettings = [
            'searchableAttributes'    => array_values(array_unique($searchableAttributes)),
            //'searchableAttributes'    => ['*'],
            'rankingRules'            => $customRankingsArr,
            'displayedAttributes'     => $retrievableAttributes,
            //'displayedAttributes'     => ['*'],
            'filterableAttributes' => [ //TODO : CHANGE THESE HARDCODED VALUES LATER
                "categories",
                "categoryIds",
                "color",
                "price.INR.default",
                "size",
                "visibility_catalog",
                "visibility_search",
                "include_in_menu"
            ]
        ];

        // Additional index settings from event observer
        $transport = new DataObject($indexSettings);

        $indexSettings = $transport->getData();

        return $indexSettings;
    }
}