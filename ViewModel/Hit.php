<?php

namespace MelTheDev\MeiliSearch\ViewModel;

use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;

class Hit implements ArgumentInterface
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;
    /**
     * @var ConfigHelper
     */
    private ConfigHelper $configHelper;
    /**
     * @var HttpContext
     */
    private HttpContext $httpContext;
    /**
     * @var string
     */
    private $priceKey;
    /**
     * Hit constructor.
     * @param StoreManagerInterface $storeManager
     * @param ConfigHelper $configHelper
     * @param HttpContext $httpContext
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ConfigHelper $configHelper,
        HttpContext $httpContext
    ) {
        $this->storeManager = $storeManager;
        $this->configHelper = $configHelper;
        $this->httpContext = $httpContext;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPriceKey()
    {
        if ($this->priceKey === null) {
            $groupId = $this->getGroupId();

            /** @var Store $store */
            $store = $this->storeManager->getStore();

            $currencyCode = $store->getCurrentCurrencyCode();
            $this->priceKey = $this->configHelper->isCustomerGroupsEnabled($store->getStoreId())
                ? '.' . $currencyCode . '.group_' . $groupId : '.' . $currencyCode . '.default';
        }
        return $this->priceKey;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrencyCode()
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();

        return $store->getCurrentCurrencyCode();
    }

    /**
     * @return int|null
     */
    public function getGroupId()
    {
        return $this->httpContext->getValue(CustomerContext::CONTEXT_GROUP);
    }
}
