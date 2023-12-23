<?php

namespace MelTheDev\MeiliSearch\ViewModel;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Locale\Currency;
use Magento\Framework\Locale\Format;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use MelTheDev\MeiliSearch\Helper\CategoryHelper;
use MelTheDev\MeiliSearch\Helper\MeiliSearchHelper;
use MelTheDev\MeiliSearch\Helper\ProductHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Search\Helper\Data as CatalogSearchHelper;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;
use MelTheDev\MeiliSearch\Helper\Data as CoreHelper;

class Configuration implements ArgumentInterface
{
    private ConfigHelper $configHelper;
    private CatalogSearchHelper $catalogSearchHelper;
    private CoreHelper $coreHelper;
    private CategoryHelper $categoryHelper;
    private ProductHelper $productHelper;
    private MeiliSearchHelper $meiliSearchHelper;
    private UrlInterface $url;
    private StoreManagerInterface $storeManager;
    private Currency $currency;
    private Format $format;
    private HttpContext $httpContext;
    protected $priceKey;
    private Registry $registry;
    private CheckoutSession $checkoutSession;
    private FormKey $formKey;
    private Data $urlHelper;
    private DateTime $dateTime;

    /**
     * Configuration constructor.
     * @param ConfigHelper $configHelper
     * @param CatalogSearchHelper $catalogSearchHelper
     * @param CoreHelper $coreHelper
     * @param CategoryHelper $categoryHelper
     * @param ProductHelper $productHelper
     * @param MeiliSearchHelper $meiliSearchHelper
     * @param UrlInterface $url
     * @param StoreManagerInterface $storeManager
     * @param Currency $currency
     * @param Format $format
     * @param HttpContext $httpContext
     * @param Registry $registry
     * @param CheckoutSession $checkoutSession
     * @param FormKey $formKey
     * @param Data $urlHelper
     * @param DateTime $dateTime
     */
    public function __construct(
        ConfigHelper $configHelper,
        CatalogSearchHelper $catalogSearchHelper,
        CoreHelper $coreHelper,
        CategoryHelper $categoryHelper,
        ProductHelper $productHelper,
        MeiliSearchHelper $meiliSearchHelper,
        UrlInterface $url,
        StoreManagerInterface $storeManager,
        Currency $currency,
        Format $format,
        HttpContext $httpContext,
        Registry $registry,
        CheckoutSession $checkoutSession,
        FormKey $formKey,
        Data $urlHelper,
        DateTime $dateTime
    ) {
        $this->configHelper = $configHelper;
        $this->catalogSearchHelper = $catalogSearchHelper;
        $this->coreHelper = $coreHelper;
        $this->categoryHelper = $categoryHelper;
        $this->productHelper = $productHelper;
        $this->meiliSearchHelper = $meiliSearchHelper;
        $this->url = $url;
        $this->storeManager = $storeManager;
        $this->currency = $currency;
        $this->format = $format;
        $this->httpContext = $httpContext;
        $this->registry = $registry;
        $this->checkoutSession = $checkoutSession;
        $this->formKey = $formKey;
        $this->urlHelper = $urlHelper;
        $this->dateTime = $dateTime;
    }

    private function getCurrentLandingPage()
    {
        $landingPageId = $this->meiliSearchHelper->getRequest()->getParam('landing_page_id');
        if (!$landingPageId) {
            return null;
        }
        //return $this->landingPageHelper->getLandingPage($landingPageId);
    }

    /**
     * Get add-to-cart url
     *
     * @param array $additional
     * @return string
     */
    private function getAddToCartUrl($additional = [])
    {
        $continueUrl = $this->urlHelper->getEncodedUrl($this->url->getCurrentUrl());
        $urlParamName = ActionInterface::PARAM_NAME_URL_ENCODED;
        $routeParams = [
            $urlParamName => $continueUrl,
            '_secure' => $this->meiliSearchHelper->getRequest()->isSecure(),
        ];
        if ($additional !== []) {
            $routeParams = array_merge($routeParams, $additional);
        }
        return $this->url->getUrl('checkout/cart/add', $routeParams);
    }

    /**
     * Get add-to-cart params
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAddToCartParams()
    {
        $url = $this->getAddToCartUrl();

        return [
            'action' => $url,
            'formKey' => $this->formKey->getFormKey(),
        ];
    }

    /**
     * Get timestamp for current day midnight
     *
     * @return int
     */
    public function getTimestamp()
    {
        return $this->dateTime->gmtTimestamp('today midnight');
    }

    /**
     * Get current category
     *
     * @return Category|null
     * @noinspection PhpDeprecationInspection
     */
    public function getCurrentCategory()
    {
        return $this->registry->registry('current_category');
    }

    /**
     * Get last order
     *
     * @return Order
     */
    public function getLastOrder()
    {
        return $this->checkoutSession->getLastRealOrder();
    }

    /**
     * Get current product
     *
     * @return Product|null
     * @noinspection PhpDeprecationInspection
     */
    public function getCurrentProduct()
    {
        return $this->registry->registry('product');
    }

    public function getPriceKey()
    {
        if ($this->priceKey === null) {
            $currencyCode = $this->getCurrencyCode();
            $this->priceKey = '.' . $currencyCode . '.default';
            if ($this->getConfigHelper()->isCustomerGroupsEnabled($this->getStore()->getStoreId())) {
                $groupId = $this->getGroupId();
                $this->priceKey = '.' . $currencyCode . '.group_' . $groupId;
            }
        }
        return $this->priceKey;
    }

    /**
     * Get store id
     *
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->getStore()->getStoreId();
    }

    /**
     * Get group id
     *
     * @return int|null
     */
    public function getGroupId()
    {
        return $this->httpContext->getValue(CustomerContext::CONTEXT_GROUP);
    }

    /**
     * Get price format
     *
     * @return array
     */
    public function getPriceFormat()
    {
        return $this->format->getPriceFormat();
    }

    /**
     * Get currency symbol
     *
     * @return string
     * @throws \Magento\Framework\Currency\Exception\CurrencyException
     */
    public function getCurrencySymbol()
    {
        return $this->currency->getCurrency($this->getCurrencyCode())->getSymbol();
    }

    /**
     * Get config
     *
     * @return array
     */
    public function getConfig()
    {
        $config = $this->getConfigHelper();
        $meiliSearchJsConfig = [
            'apiUrl' => $config->getApiUrl(),
            'apiKey' => $config->getApiKey(),
        ];
        $transport = new DataObject($meiliSearchJsConfig);
        return $transport->getData();
    }

    /**
     * Get configuration in array
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfiguration()
    {
        $config = $this->getConfigHelper();

        $catalogSearchHelper = $this->getCatalogSearchHelper();

        $coreHelper = $this->getCoreHelper();

        $categoryHelper = $this->getCategoryHelper();

        //$suggestionHelper = $this->getSuggestionHelper();

        $productHelper = $this->getProductHelper();

        $meiliSearchHelper = $this->getMeiliSearchHelper();

        //$personalizeHelper = $this->getPersonalizationHelper();

        $baseUrl = rtrim($this->getBaseUrl(), '/');

        $currencyCode = $this->getCurrencyCode();
        $currencySymbol = $this->getCurrencySymbol();
        $priceFormat = $this->getPriceFormat();

        $customerGroupId = $this->getGroupId();

        $priceKey = $this->getPriceKey();
        $priceGroup = null;
        if ($config->isCustomerGroupsEnabled()) {
            $pricegroupArray = explode('.', $priceKey);
            $priceGroup = $pricegroupArray[2];
        }

        $query = '';
        $refinementKey = '';
        $refinementValue = '';
        $path = '';
        $level = '';
        $categoryId = '';
        $parentCategoryName = '';

        $addToCartParams = $this->getAddToCartParams();

        /** @var Http $request */
        $request = $this->meiliSearchHelper->getRequest();

        /**
         * Handle category replacement
         */

        $isCategoryPage = false;
        if ($config->isInstantEnabled()
            && $config->replaceCategories()
            && $request->getControllerName() === 'category') {
            $category = $this->getCurrentCategory();

            if ($category && $category->getDisplayMode() !== 'PAGE') {
                $category->getUrlInstance()->setStore($this->getStoreId());

                $categoryId = $category->getId();

                $level = -1;
                foreach ($category->getPathIds() as $treeCategoryId) {
                    if ($path !== '') {
                        $path .= ' /// ';
                    }else{
                        $parentCategoryName = $categoryHelper->getCategoryName($treeCategoryId, $this->getStoreId());
                    }

                    $path .= $categoryHelper->getCategoryName($treeCategoryId, $this->getStoreId());

                    if ($path) {
                        $level++;
                    }
                }

                $isCategoryPage = true;
            }
        }

        $productId = null;
        if ($config->isClickConversionAnalyticsEnabled() && $request->getFullActionName() === 'catalog_product_view') {
            $productId = $this->getCurrentProduct()->getId();
        }

        /**
         * Handle search
         */
        $facets = $config->getFacets();

        $areCategoriesInFacets = $this->areCategoriesInFacets($facets);

        if ($config->isInstantEnabled()) {
            $pageIdentifier = $request->getFullActionName();

            if ($pageIdentifier === 'catalogsearch_result_index') {
                $query = $request->getParam($catalogSearchHelper->getQueryParamName());

                if ($query === '__empty__') {
                    $query = '';
                }

                $refinementKey = $request->getParam('refinement_key');

                if ($refinementKey !== null) {
                    $refinementValue = $query;
                    $query = '';
                } else {
                    $refinementKey = '';
                }
            }
        }
        $attributesToFilter = $config->getAttributesToFilter($customerGroupId);
        $searchJsConfig = [
            'instant' => [
                'enabled' => $config->isInstantEnabled(),
                'selector' => $config->getInstantSelector(),
                'isAddToCartEnabled' => $config->isAddToCartEnable(),
                'addToCartParams' => $addToCartParams,
                'infiniteScrollEnabled' => $config->isInfiniteScrollEnabled(),
                'urlTrackedParameters' => $this->getUrlTrackedParameters(),
                'isSearchBoxEnabled' => $config->isInstantSearchBoxEnabled(),
            ],
            'autocomplete' => [
                'enabled' => $config->isAutoCompleteEnabled(),
                'selector' => $config->getAutocompleteSelector(),
                'sections' => $config->getAutocompleteSections(),
                'nbOfProductsSuggestions' => $config->getNumberOfProductsSuggestions(),
                'nbOfCategoriesSuggestions' => $config->getNumberOfCategoriesSuggestions(),
                'nbOfQueriesSuggestions' => $config->getNumberOfQueriesSuggestions(),
                'isDebugEnabled' => $config->isAutocompleteDebugEnabled(),
                'isNavigatorEnabled' => $config->isAutocompleteNavigatorEnabled(),
                'debounceMilliseconds' => $config->getAutocompleteDebounceMilliseconds(),
                'minimumCharacters' => $config->getAutocompleteMinimumCharacterLength()
            ],
            'indexName'       => $coreHelper->getBaseIndexName(),
            'apiUrl'          => $config->getApiUrl(),
            'apiKey'          => $config->getApiKey(),
            'attributeFilter' => $attributesToFilter,
            'facets'          => $facets,
            'areCategoriesInFacets' => $areCategoriesInFacets,
            'hitsPerPage' => (int) $config->getNumberOfProductResults(),
            'sortingIndices' => array_values($config->getSortingIndices(
                $coreHelper->getIndexName($productHelper->getIndexNameSuffix()),
                null,
                $customerGroupId
            )),
            'isSearchPage' => $this->isSearchPage(),
            'isProductPage' => $this->isProductPage(),
            'isCategoryPage' => $isCategoryPage,
            'isLandingPage' => $this->isLandingPage(),
            'removeBranding' => (bool) $config->isRemoveBranding(),
            'productId' => $productId,
            'priceKey' => $priceKey,
            'priceGroup' => $priceGroup,
            'origFormatedVar' => 'price' . $priceKey . '_original_formated',
            'tierFormatedVar' => 'price' . $priceKey . '_tier_formated',
            'currencyCode' => $currencyCode,
            'currencySymbol' => $currencySymbol,
            'priceFormat' => $priceFormat,
            'maxValuesPerFacet' => (int) $config->getMaxValuesPerFacet(),
            'autofocus' => true,
            'resultPageUrl' => $this->getCatalogSearchHelper()->getResultUrl(),
            'request' => [
                'query' => html_entity_decode($query),
                'refinementKey' => $refinementKey,
                'refinementValue' => $refinementValue,
                'categoryId' => $categoryId,
                'landingPageId' => 0,
                'path' => $path,
                'level' => $level,
                'parentCategory' => $parentCategoryName,
            ],
            'showCatsNotIncludedInNavigation' => $config->showCatsNotIncludedInNavigation(),
            'baseUrl' => $baseUrl,
            'useAdaptiveImage' => $config->useAdaptiveImage(),
            'urls' => [
               // 'logo' => $this->getViewFileUrl('MelTheDev_MeiliSearch::images/logo.svg'),
                'logo' => '',
            ],
            'ccAnalytics' => [
                'enabled' => $config->isClickConversionAnalyticsEnabled(),
            ],
            'personalization' => [
                'enabled' => false,
            ],
            'now' => $this->getTimestamp(),
            'queue' => [
                'isEnabled' => $config->isQueueActive($this->getStoreId()),
                'nbOfJobsToRun' => $config->getNumberOfJobToRun($this->getStoreId()),
                'retryLimit' => $config->getRetryLimit($this->getStoreId()),
                'nbOfElementsPerIndexingJob' => $config->getNumberOfElementByPage($this->getStoreId()),
            ],
            'isPreventBackendRenderingEnabled' => $config->preventBackendRendering($this->getStoreId()),
            'areOutOfStockOptionsDisplayed' => $config->indexOutOfStockOptions($this->getStoreId()),
            'translations' => [
                'to' => __('to'),
                'or' => __('or'),
                'go' => __('Go'),
                'popularQueries' => __('You can try one of the popular search queries'),
                'seeAll' => __('See all products'),
                'allDepartments' => __('All departments'),
                'seeIn' => __('See products in'),
                'orIn' => __('or in'),
                'noProducts' => __('No products for query'),
                'noResults' => __('No results'),
                'refine' => __('Refine'),
                'selectedFilters' => __('Selected Filters'),
                'clearAll' => __('Clear all'),
                'previousPage' => __('Previous page'),
                'nextPage' => __('Next page'),
                'searchFor' => __('Search for products'),
                'relevance' => __('Relevance'),
                'categories' => __('Categories'),
                'products' => __('Products'),
                'searchBy' => __('Search by'),
                'searchForFacetValuesPlaceholder' => __('Search for other ...'),
                'showMore' => __('Show more products'),
                'searchTitle' => __('Search results for'),
                'placeholder' => __('Search for products, categories, ...'),
                'addToCart' => __('Add to Cart'),
            ],
        ];

        $transport = new DataObject($searchJsConfig);
        //$this->_eventManager->dispatch('melthedev_meilisearch_after_create_configuration', ['configuration' => $transport]);
        return $transport->getData();
    }

    /**
     * Are categories in facets
     *
     * @param array $facets
     * @return bool
     */
    private function areCategoriesInFacets($facets)
    {
        return in_array('categories', array_column($facets, 'attribute'));
    }

    /**
     * Get currency code
     *
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrencyCode()
    {
        return $this->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Get current store
     *
     * @return \Magento\Store\Model\Store
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore()
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        return $store;
    }

    /**
     * Get configuration helper
     *
     * @return ConfigHelper
     */
    public function getConfigHelper()
    {
        return $this->configHelper;
    }

    /**
     * Get catalog search helper
     *
     * @return CatalogSearchHelper
     */
    public function getCatalogSearchHelper()
    {
        return $this->catalogSearchHelper;
    }

    /**
     * Get core helper
     *
     * @return CoreHelper
     */
    public function getCoreHelper()
    {
        return $this->coreHelper;
    }

    /**
     * Get category helper
     *
     * @return CategoryHelper
     */
    public function getCategoryHelper()
    {
        return $this->categoryHelper;
    }

    /**
     * Get product helper
     *
     * @return ProductHelper
     */
    public function getProductHelper()
    {
        return $this->productHelper;
    }

    /**
     * Get MeiliSearch Helper
     *
     * @return MeiliSearchHelper
     */
    public function getMeiliSearchHelper()
    {
        return $this->meiliSearchHelper;
    }

    /**
     * Get base URL
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->url->getBaseUrl();
    }

    /**
     * Get url tracked parameters
     *
     * @return array
     */
    private function getUrlTrackedParameters()
    {
        $urlTrackedParameters = ['query', 'attribute:*', 'index'];

        if ($this->getConfigHelper()->isInfiniteScrollEnabled() === false) {
            $urlTrackedParameters[] = 'page';
        }

        return $urlTrackedParameters;
    }

    /**
     * Is search page
     *
     * @return bool
     */
    public function isSearchPage()
    {
        if ($this->getConfigHelper()->isInstantEnabled()) {
            /** @var Http $request */
            $request = $this->meiliSearchHelper->getRequest();

            if ($request->getFullActionName() === 'catalogsearch_result_index' || $this->isLandingPage()) {
                return true;
            }

            if ($this->getConfigHelper()->replaceCategories() && $request->getControllerName() === 'category') {
                $category = $this->getCurrentCategory();
                if ($category && $category->getDisplayMode() !== 'PAGE') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Is landing page
     *
     * @return bool
     */
    private function isLandingPage()
    {
        return $this->meiliSearchHelper->getRequest()->getFullActionName() === 'meilisearch_landingpage_view';
    }

    /**
     * Is product page
     *
     * @return bool
     */
    private function isProductPage()
    {
        return $this->meiliSearchHelper->getRequest()->getFullActionName() === 'catalog_product_view';
    }
}