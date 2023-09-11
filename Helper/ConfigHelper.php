<?php

namespace MelTheDev\MeiliSearch\Helper;

use Magento\Customer\Model\ResourceModel\Group\Collection as GroupCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConfigHelper
{
    public const API_IP  = 'melthedev_meilisearch_credentials/credentials/search_api_ip';
    public const API_KEY = 'melthedev_meilisearch_credentials/credentials/api_key';

    public const ENABLE_FRONTEND = 'melthedev_meilisearch_credentials/credentials/enable_frontend';
    public const ENABLE_BACKEND  = 'melthedev_meilisearch_credentials/credentials/enable_backend';
    public const LOGGING_ENABLED = 'melthedev_meilisearch_credentials/credentials/debug';
    public const INDEX_PREFIX    = 'melthedev_meilisearch_credentials/credentials/index_prefix';

    public const IS_INSTANT_ENABLED = 'melthedev_meilisearch_instant/instant/is_instant_enabled';
    public const REPLACE_CATEGORIES = 'melthedev_meilisearch_instant/instant/replace_categories';
    public const INSTANT_SELECTOR = 'melthedev_meilisearch_instant/instant/instant_selector';
    public const NUMBER_OF_PRODUCT_RESULTS = 'melthedev_meilisearch_instant/instant/number_product_results';
    public const FACETS = 'melthedev_meilisearch_instant/instant/facets';
    public const MAX_VALUES_PER_FACET = 'melthedev_meilisearch_instant/instant/max_values_per_facet';
    public const SORTING_INDICES = 'melthedev_meilisearch_instant/instant/sorts';
    //public const SHOW_SUGGESTIONS_NO_RESULTS = 'melthedev_meilisearch_instant/instant/show_suggestions_on_no_result_page';
    public const XML_ADD_TO_CART_ENABLE = 'melthedev_meilisearch_instant/instant/add_to_cart_enable';
    public const INFINITE_SCROLL_ENABLE = 'melthedev_meilisearch_instant/instant/infinite_scroll_enable';
    public const SEARCHBOX_ENABLE = 'melthedev_meilisearch_instant/instant/instantsearch_searchbox';

    public const PRODUCT_ATTRIBUTES = 'melthedev_meilisearch_products/products/product_additional_attributes';
    public const PRODUCT_CUSTOM_RANKING = 'melthedev_meilisearch_products/products/custom_ranking_product_attributes';
    public const USE_ADAPTIVE_IMAGE = 'melthedev_meilisearch_products/products/use_adaptive_image';
    public const INDEX_OUT_OF_STOCK_OPTIONS = 'melthedev_meilisearch_products/products/index_out_of_stock_options';

    public const CATEGORY_ATTRIBUTES = 'melthedev_meilisearch_categories/categories/category_additional_attributes';
    public const CATEGORY_CUSTOM_RANKING = 'melthedev_meilisearch_categories/categories/custom_ranking_category_attributes';
    public const SHOW_CATS_NOT_INCLUDED_IN_NAV = 'melthedev_meilisearch_categories/categories/show_cats_not_included_in_navigation';
    public const INDEX_EMPTY_CATEGORIES = 'melthedev_meilisearch_categories/categories/index_empty_categories';

    public const IS_QUEUE_ACTIVE = 'melthedev_meilisearch_queue/queue/active';
    public const NUMBER_OF_JOB_TO_RUN = 'melthedev_meilisearch_queue/queue/number_of_job_to_run';
    public const RETRY_LIMIT = 'melthedev_meilisearch_queue/queue/number_of_retries';

    //images
    public const XML_PATH_IMAGE_WIDTH = 'melthedev_meilisearch_images/image/width';
    public const XML_PATH_IMAGE_HEIGHT = 'melthedev_meilisearch_images/image/height';
    public const XML_PATH_IMAGE_TYPE = 'melthedev_meilisearch_images/image/type';

    public const NUMBER_OF_ELEMENT_BY_PAGE = 'melthedev_meilisearch_advanced/advanced/number_of_element_by_page';
    public const REMOVE_IF_NO_RESULT = 'melthedev_meilisearch_advanced/advanced/remove_words_if_no_result';
    public const PARTIAL_UPDATES = 'melthedev_meilisearch_advanced/advanced/partial_update';
    public const CUSTOMER_GROUPS_ENABLE = 'melthedev_meilisearch_advanced/advanced/customer_groups_enable';
    public const REMOVE_PUB_DIR_IN_URL = 'melthedev_meilisearch_advanced/advanced/remove_pub_dir_in_url';
    public const MAKE_SEO_REQUEST = 'melthedev_meilisearch_advanced/advanced/make_seo_request';
    public const REMOVE_BRANDING = 'melthedev_meilisearch_advanced/advanced/remove_branding';
    public const AUTOCOMPLETE_SELECTOR = 'melthedev_meilisearch_autocomplete/autocomplete/autocomplete_selector';
    public const IDX_PRODUCT_ON_CAT_PRODUCTS_UPD = 'melthedev_meilisearch_advanced/advanced/index_product_on_category_products_update';
    public const PREVENT_BACKEND_RENDERING = 'melthedev_meilisearch_advanced/advanced/prevent_backend_rendering';
    public const PREVENT_BACKEND_RENDERING_DISPLAY_MODE =
        'melthedev_meilisearch_advanced/advanced/prevent_backend_rendering_display_mode';
    public const BACKEND_RENDERING_ALLOWED_USER_AGENTS =
        'melthedev_meilisearch_advanced/advanced/backend_rendering_allowed_user_agents';
    public const NON_CASTABLE_ATTRIBUTES = 'melthedev_meilisearch_advanced/advanced/non_castable_attributes';
    public const MAX_RECORD_SIZE_LIMIT = 'melthedev_meilisearch_advanced/advanced/max_record_size_limit';
    public const ARCHIVE_LOG_CLEAR_LIMIT = 'melthedev_meilisearch_advanced/advanced/archive_clear_limit';

    public const SHOW_OUT_OF_STOCK = 'cataloginventory/options/show_out_of_stock';
    public const USE_SECURE_IN_FRONTEND = 'web/secure/use_in_frontend';

    public const IS_POPUP_ENABLED = 'melthedev_meilisearch_autocomplete/autocomplete/is_popup_enabled';
    public const AUTOCOMPLETE_SECTIONS = 'melthedev_meilisearch_autocomplete/autocomplete/sections';
    public const NB_OF_PRODUCTS_SUGGESTIONS = 'melthedev_meilisearch_autocomplete/autocomplete/nb_of_products_suggestions';
    public const NB_OF_CATEGORIES_SUGGESTIONS = 'melthedev_meilisearch_autocomplete/autocomplete/nb_of_categories_suggestions';
    public const NB_OF_QUERIES_SUGGESTIONS = 'melthedev_meilisearch_autocomplete/autocomplete/nb_of_queries_suggestions';
    public const EXCLUDED_PAGES = 'melthedev_meilisearch_autocomplete/autocomplete/excluded_pages';
    public const MIN_POPULARITY = 'melthedev_meilisearch_autocomplete/autocomplete/min_popularity';
    public const MIN_NUMBER_OF_RESULTS = 'melthedev_meilisearch_autocomplete/autocomplete/min_number_of_results';
    public const RENDER_TEMPLATE_DIRECTIVES = 'melthedev_meilisearch_autocomplete/autocomplete/render_template_directives';
    public const AUTOCOMPLETE_MENU_DEBUG = 'melthedev_meilisearch_autocomplete/autocomplete/debug';

    private const AUTOCOMPLETE_KEYBORAD_NAVIAGATION = 'melthedev_meilisearch_autocomplete/autocomplete/navigator';

    private const IS_RECOMMEND_FREQUENTLY_BOUGHT_TOGETHER_ENABLED
        = 'melthedev_meilisearch_recommend/recommend/frequently_bought_together/is_frequently_bought_together_enabled';
    private const IS_RECOMMEND_RELATED_PRODUCTS_ENABLED
        = 'melthedev_meilisearch_recommend/recommend/related_product/is_related_products_enabled';

    private const IS_REMOVE_RELATED_PRODUCTS_BLOCK
        = 'melthedev_meilisearch_recommend/recommend/related_product/is_remove_core_related_products_block';
    private const IS_REMOVE_UPSELL_PRODUCTS_BLOCK
        = 'melthedev_meilisearch_recommend/recommend/frequently_bought_together/is_remove_core_upsell_products_block';
    private const IS_TREND_ITEMS_ENABLED_IN_PDP
        = 'melthedev_meilisearch_recommend/recommend/trends_item/is_trending_items_enabled_on_pdp';

    /**
     * ConfigHelper constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     * @param ProductMetadataInterface $productMetadata
     * @param StoreManagerInterface $storeManager
     * @param GroupCollection $groupCollection
     * @noinspection PhpPropertyCanBeReadonlyInspection
     */
    public function __construct(
        private ScopeConfigInterface     $scopeConfig,
        private SerializerInterface      $serializer,
        private ProductMetadataInterface $productMetadata,
        private StoreManagerInterface    $storeManager,
        private GroupCollection          $groupCollection
    ) {
    }

    /**
     * Get Api URL
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiUrl($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::API_IP,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Api Key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiKey($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function getShowOutOfStock($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::SHOW_OUT_OF_STOCK, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $attributes
     * @param $addedAttributes
     * @param $searchable
     * @param $retrievable
     * @param $indexNoValue
     * @return mixed
     */
    protected function addIndexableAttributes(
        $attributes,
        $addedAttributes,
        $searchable = '1',
        $retrievable = '1',
        $indexNoValue = '1'
    ) {
        foreach ((array)$addedAttributes as $addedAttribute) {
            foreach ((array)$attributes as $attribute) {
                if ($addedAttribute['attribute'] === $attribute['attribute']) {
                    continue 2;
                }
            }
            $attributes[] = [
                'attribute' => $addedAttribute['attribute'],
                'searchable' => $searchable,
                'retrievable' => $retrievable,
                'index_no_value' => $indexNoValue,
            ];
        }
        return $attributes;
    }

    /**
     * @param $storeId
     * @return array
     */
    public function getProductAdditionalAttributes($storeId = null)
    {
        $attributes = $this->unserialize($this->scopeConfig->getValue(
            self::PRODUCT_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        $facets = $this->unserialize($this->scopeConfig->getValue(
            self::FACETS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
        $attributes = $this->addIndexableAttributes($attributes, $facets, '0');

        $sorts = $this->unserialize($this->scopeConfig->getValue(
            self::SORTING_INDICES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
        $attributes = $this->addIndexableAttributes($attributes, $sorts, '0');

        $customRankings = $this->unserialize($this->scopeConfig->getValue(
            self::PRODUCT_CUSTOM_RANKING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
        $customRankings = $customRankings ?: [];
        $customRankings = array_filter($customRankings, function ($customRanking) {
            return $customRanking['attribute'] !== 'custom_attribute';
        });
        $attributes = $this->addIndexableAttributes($attributes, $customRankings, '0', '0');
        if (is_array($attributes)) {
            return $attributes;
        }
        return [];
    }

    /**
     * @param $value
     * @return array|bool|float|int|mixed|string|null
     */
    protected function unserialize($value)
    {
        if (false === $value || null === $value || '' === $value) {
            return false;
        }
        $unSerialized = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $unSerialized;
        }
        return $this->serializer->unserialize($value);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isEnabledBackend($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::ENABLE_BACKEND, ScopeInterface::SCOPE_STORE, $storeId);
    }
    /**
     * @param $storeId
     * @return bool
     */
    public function isLoggingEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::LOGGING_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }
    /**
     * Number of times to retry processing of queued jobs
     *
     * @param $storeId
     * @return int
     */
    public function getRetryLimit($storeId = null)
    {
        return (int)$this->scopeConfig->getValue(self::RETRY_LIMIT, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Maximal number of elements per indexing job (default value is 300)
     *
     * @param $storeId
     * @return int
     */
    public function getNumberOfElementByPage($storeId = null)
    {
        return (int)$this->scopeConfig->getValue(self::NUMBER_OF_ELEMENT_BY_PAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Enable Indexing Queue
     *
     * @param $storeId
     * @return bool
     */
    public function isQueueActive($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::IS_QUEUE_ACTIVE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getNumberOfJobToRun($storeId = null)
    {
        $nbJobs = (int)$this->scopeConfig->getValue(self::NUMBER_OF_JOB_TO_RUN, ScopeInterface::SCOPE_STORE, $storeId);

        return max($nbJobs, 1);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getIndexPrefix($storeId = null)
    {
        return $this->scopeConfig->getValue(self::INDEX_PREFIX, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isPartialUpdateEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::PARTIAL_UPDATES, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return int
     */
    public function getMaxRecordSizeLimit($storeId = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::MAX_RECORD_SIZE_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function indexOutOfStockOptions($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::INDEX_OUT_OF_STOCK_OPTIONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function showCatsNotIncludedInNavigation($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::SHOW_CATS_NOT_INCLUDED_IN_NAV,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function useSecureUrlsInFrontend($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::USE_SECURE_IN_FRONTEND,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * @return string
     */
    public function getMagentoEdition()
    {
        return $this->productMetadata->getEdition();
    }

    /**
     * @param $storeId
     * @return int
     */
    public function getImageWidth($storeId = null)
    {
        $imageWidth = $this->scopeConfig->getValue(
            self::XML_PATH_IMAGE_WIDTH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!$imageWidth) {
            return 265;
        }

        return (int)$imageWidth;
    }

    /**
     * @param $storeId
     * @return int
     */
    public function getImageHeight($storeId = null)
    {
        $imageHeight = $this->scopeConfig->getValue(
            self::XML_PATH_IMAGE_HEIGHT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!$imageHeight) {
            return 265;
        }

        return (int)$imageHeight;
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isCustomerGroupsEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::CUSTOMER_GROUPS_ENABLE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getStoreLocale($storeId)
    {
        return $this->scopeConfig->getValue(
            \Magento\Directory\Helper\Data::XML_PATH_DEFAULT_LOCALE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function useAdaptiveImage($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::USE_ADAPTIVE_IMAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getImageType($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_IMAGE_TYPE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function shouldRemovePubDirectory($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::REMOVE_PUB_DIR_IN_URL, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getBatchSize()
    {
        return 1000;
    }

    /**
     * @param $storeId
     * @return int
     */
    public function getArchiveLogClearLimit($storeId = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::ARCHIVE_LOG_CLEAR_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function indexProductOnCategoryProductsUpdate($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::IDX_PRODUCT_ON_CAT_PRODUCTS_UPD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return array
     */
    public function getCategoryAdditionalAttributes($storeId = null)
    {
        $attributes = $this->unserialize($this->scopeConfig->getValue(
            self::CATEGORY_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
        $customRankings = $this->unserialize($this->scopeConfig->getValue(
            self::CATEGORY_CUSTOM_RANKING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
        $customRankings = $customRankings ?: [];
        $customRankings = array_filter($customRankings, function ($customRanking) {
            return $customRanking['attribute'] !== 'custom_attribute';
        });
        $attributes = $this->addIndexableAttributes($attributes, $customRankings, '0', '0');
        if (is_array($attributes)) {
            return $attributes;
        }
        return [];
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function shouldIndexEmptyCategories($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::INDEX_EMPTY_CATEGORIES, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return array
     */
    public function getProductCustomRanking($storeId = null)
    {
        $attrs = $this->unserialize($this->getRawProductCustomRanking($storeId));
        if (is_array($attrs)) {
            return $attrs;
        }
        return [];
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getRawProductCustomRanking($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::PRODUCT_CUSTOM_RANKING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getFacets($storeId = null)
    {
        $attrs = $this->unserialize($this->scopeConfig->getValue(
            self::FACETS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
        if ($attrs) {
            foreach ($attrs as &$attr) {
                if ($attr['type'] === 'other') {
                    $attr['type'] = $attr['other_type'];
                }
            }
            if (is_array($attrs)) {
                return array_values($attrs);
            }
        }
        return [];
    }

    /**
     * @param $storeId
     * @return int
     */
    public function getMaxValuesPerFacet($storeId = null)
    {
        return (int)$this->scopeConfig->getValue(self::MAX_VALUES_PER_FACET, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getRemoveWordsIfNoResult($storeId = null)
    {
        return $this->scopeConfig->getValue(self::REMOVE_IF_NO_RESULT, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function replaceCategories($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::REPLACE_CATEGORIES, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return array
     */
    public function getCategoryCustomRanking($storeId = null)
    {
        $attrs = $this->unserialize($this->scopeConfig->getValue(
            self::CATEGORY_CUSTOM_RANKING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
        if (is_array($attrs)) {
            return $attrs;
        }
        return [];
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isEnabledFrontEnd($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::ENABLE_FRONTEND, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get MeiliSearch search api key (not master key)
     *
     * @return string
     */
    public function getApiSearchKey()
    {
        return 'test';
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isInstantEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::IS_INSTANT_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isClickConversionAnalyticsEnabled($storeId = null)
    {
        return false;
    }

    /**
     * @param $groupId
     * @return array
     */
    public function getAttributesToFilter($groupId)
    {
        $transport = new DataObject();
        /*$this->eventManager->dispatch(
            'melthedev_meilisearch_get_attributes_to_filter',
            ['filter_object' => $transport, 'customer_group_id' => $groupId]
        );*/
        $attributes = $transport->getData();
        $attributes = array_unique($attributes);
        $attributes = array_values($attributes);
        return count($attributes) ? ['filters' => implode(' AND ', $attributes)] : [];
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getInstantSelector($storeId = null)
    {
        return $this->scopeConfig->getValue(self::INSTANT_SELECTOR, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isAddToCartEnable($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_ADD_TO_CART_ENABLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isInfiniteScrollEnabled($storeId = null)
    {
        return $this->isInstantEnabled($storeId)
            && $this->scopeConfig->isSetFlag(self::INFINITE_SCROLL_ENABLE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isInstantSearchBoxEnabled($storeId = null)
    {
        return $this->isInstantEnabled($storeId)
            && $this->scopeConfig->isSetFlag(self::SEARCHBOX_ENABLE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isAutoCompleteEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::IS_POPUP_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getAutocompleteSelector($storeId = null)
    {
        return $this->scopeConfig->getValue(self::AUTOCOMPLETE_SELECTOR, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return array
     */
    public function getAutocompleteSections($storeId = null)
    {
        $attrs = $this->unserialize($this->scopeConfig->getValue(
            self::AUTOCOMPLETE_SECTIONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
        if (is_array($attrs)) {
            return array_map(function ($item) {
                if (isset($item['hitsPerPage'])) {
                    $item['hitsPerPage'] = (int) $item['hitsPerPage'];
                }
                return $item;
            }, array_values($attrs));
        }
        return [];
    }

    /**
     * @param $storeId
     * @return int
     */
    public function getNumberOfProductsSuggestions($storeId = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::NB_OF_PRODUCTS_SUGGESTIONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return int
     */
    public function getNumberOfCategoriesSuggestions($storeId = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::NB_OF_CATEGORIES_SUGGESTIONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return int
     */
    public function getNumberOfQueriesSuggestions($storeId = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::NB_OF_QUERIES_SUGGESTIONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isAutocompleteDebugEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::AUTOCOMPLETE_MENU_DEBUG, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function isAutocompleteNavigatorEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::AUTOCOMPLETE_KEYBORAD_NAVIAGATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return int
     */
    public function getNumberOfProductResults($storeId = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::NUMBER_OF_PRODUCT_RESULTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getRawSortingValue($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::SORTING_INDICES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /***
     * @param int|null $storeId
     * @return array
     */
    public function getSorting($storeId = null)
    {
        return $this->unserialize($this->getRawSortingValue($storeId));
    }

    /**
     * @param int|null $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreCode($storeId = null)
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore($storeId);
        return $store->getCode();
    }

    /**
     * @param $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrencyCode($storeId = null)
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore($storeId);
        return $store->getCurrentCurrencyCode();
    }

    /**
     * @param null $storeId
     * @param null $currentCustomerGroupId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSortableAttributes($storeId = null, $currentCustomerGroupId = null)
    {
        $sortAttributes      = [];
        $addedAttributeLists = [];
        $currency = $this->getCurrencyCode($storeId);
        foreach ($this->getSorting($storeId) as $attr) {
            if (!in_array($attr['attribute'], $addedAttributeLists)) {
                $addedAttributeLists[] = $attr['attribute'];

                if ($this->isCustomerGroupsEnabled($storeId) && $attr['attribute'] === 'price') {
                    $groupCollection = $this->groupCollection;
                    if (!is_null($currentCustomerGroupId)) {
                        $groupCollection->addFilter('customer_group_id', $currentCustomerGroupId);
                    }
                    foreach ($groupCollection as $group) {
                        $customerGroupId      = (int)$group->getData('customer_group_id');
                        $groupIndexNameSuffix = 'group_' . $customerGroupId;
                        $sortAttributes[] = $attr['attribute'] . '.' . $currency . '.' . $groupIndexNameSuffix;
                    }
                } elseif ($attr['attribute'] === 'price') {
                    $sortAttributes[] = $attr['attribute'] . '.' . $currency . '.' . 'default';;
                } else {
                    $sortAttributes[] = $attr['attribute'];;
                }
            }
        }
        return $sortAttributes;
    }

    /**
     * @param $originalIndexName
     * @param $storeId
     * @param $currentCustomerGroupId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSortingIndices($originalIndexName, $storeId = null, $currentCustomerGroupId = null)
    {
        //$this->getSortableAttributes($storeId);

        $this->getSortableAttributes($storeId);

        $attrs = $this->getSorting($storeId);
        $currency = $this->getCurrencyCode($storeId);
        $attributesToAdd = [];
        foreach ($attrs as $key => $attr) {
            $indexName = false;
            $sortAttribute = false;
            if ($this->isCustomerGroupsEnabled($storeId) && $attr['attribute'] === 'price') {
                $groupCollection = $this->groupCollection;
                if (!is_null($currentCustomerGroupId)) {
                    $groupCollection->addFilter('customer_group_id', $currentCustomerGroupId);
                }
                foreach ($groupCollection as $group) {
                    $customerGroupId = (int)$group->getData('customer_group_id');
                    $groupIndexNameSuffix = 'group_' . $customerGroupId;
                    $groupIndexName =
                        $originalIndexName . '_' . $attr['attribute'] . '_' . $groupIndexNameSuffix . '_' . $attr['sort'];
                    $groupSortAttribute = $attr['attribute'] . '.' . $currency . '.' . $groupIndexNameSuffix;
                    $newAttr = [];
                    $newAttr['name'] = $groupIndexName;
                    $newAttr['attribute'] = $attr['attribute'];
                    $newAttr['sort'] = $attr['sort'];
                    $newAttr['sortLabel'] = $attr['sortLabel'];
                    if (!array_key_exists('label', $newAttr) && array_key_exists('sortLabel', $newAttr)) {
                        $newAttr['label'] = $newAttr['sortLabel'];
                    }
                    $newAttr['ranking'] = [
                        $newAttr['sort'] . '(' . $groupSortAttribute . ')',
                        'typo',
                        'geo',
                        'words',
                        'filters',
                        'proximity',
                        'attribute',
                        'exact',
                        'custom',
                    ];
                    $attributesToAdd[$newAttr['sort']][] = $newAttr;
                }
            } elseif ($attr['attribute'] === 'price') {
                $indexName = $originalIndexName . '_' . $attr['attribute'] . '_' . 'default' . '_' . $attr['sort'];
                $sortAttribute = $attr['attribute'] . '.' . $currency . '.' . 'default';
            } else {
                $indexName = $originalIndexName . '_' . $attr['attribute'] . '_' . $attr['sort'];
                $sortAttribute = $attr['attribute'];
            }
            if ($indexName && $sortAttribute) {
                $attrs[$key]['name'] = $indexName;
                if (!array_key_exists('label', $attrs[$key]) && array_key_exists('sortLabel', $attrs[$key])) {
                    $attrs[$key]['label'] = $attrs[$key]['sortLabel'];
                }
                $attrs[$key]['ranking'] = [
                    $attr['sort'] . '(' . $sortAttribute . ')',
                    'typo',
                    'geo',
                    'words',
                    'filters',
                    'proximity',
                    'attribute',
                    'exact',
                    'custom',
                ];
            }
        }
        $attrsToReturn = [];
        if (count($attributesToAdd) > 0) {
            foreach ($attrs as $key => $attr) {
                if ($attr['attribute'] == 'price' && isset($attributesToAdd[$attr['sort']])) {
                    $attrsToReturn = array_merge($attrsToReturn, $attributesToAdd[$attr['sort']]);
                } else {
                    $attrsToReturn[] = $attr;
                }
            }
        }
        if (count($attrsToReturn) > 0) {
            return $attrsToReturn;
        }
        if (is_array($attrs)) {
            return $attrs;
        }
        return [];
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isRemoveBranding($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::REMOVE_BRANDING, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function preventBackendRendering($storeId = null)
    {
        $preventBackendRendering = $this->scopeConfig->isSetFlag(
            self::PREVENT_BACKEND_RENDERING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($preventBackendRendering === false) {
            return false;
        }
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }
        $userAgent = mb_strtolower($_SERVER['HTTP_USER_AGENT'], 'utf-8');
        $allowedUserAgents = $this->scopeConfig->getValue(
            self::BACKEND_RENDERING_ALLOWED_USER_AGENTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $allowedUserAgents = trim($allowedUserAgents);
        if ($allowedUserAgents === '') {
            return true;
        }
        $allowedUserAgents = preg_split('/\n|\r\n?/', $allowedUserAgents);
        $allowedUserAgents = array_filter($allowedUserAgents);
        foreach ($allowedUserAgents as $allowedUserAgent) {
            $allowedUserAgent = mb_strtolower($allowedUserAgent, 'utf-8');
            if (mb_strpos($userAgent, $allowedUserAgent) !== false) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isRecommendFrequentlyBroughtTogetherEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::IS_RECOMMEND_FREQUENTLY_BOUGHT_TOGETHER_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isRecommendRelatedProductsEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::IS_RECOMMEND_RELATED_PRODUCTS_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return int
     */
    public function isTrendItemsEnabledInPDP($storeId = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::IS_TREND_ITEMS_ENABLED_IN_PDP,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isRemoveCoreRelatedProductsBlock($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::IS_REMOVE_RELATED_PRODUCTS_BLOCK, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isRemoveUpsellProductsBlock($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(self::IS_REMOVE_UPSELL_PRODUCTS_BLOCK, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return array
     */
    public function getExcludedPages($storeId = null)
    {
        $attrs = $this->unserialize($this->scopeConfig->getValue(
            self::EXCLUDED_PAGES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
        if (is_array($attrs)) {
            return $attrs;
        }
        return [];
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getRenderTemplateDirectives($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::RENDER_TEMPLATE_DIRECTIVES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
