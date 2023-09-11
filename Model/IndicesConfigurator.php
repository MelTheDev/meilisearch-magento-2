<?php

namespace MelTheDev\MeiliSearch\Model;

use MelTheDev\MeiliSearch\Helper\CategoryHelper;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;
use MelTheDev\MeiliSearch\Helper\Data;
use MelTheDev\MeiliSearch\Helper\ProductHelper;
use MelTheDev\MeiliSearch\Logger\Logger;

class IndicesConfigurator
{
    private Data $dataHelper;
    private ConfigHelper $configHelper;
    private Logger $logger;
    private CategoryHelper $categoryHelper;
    private ProductHelper $productHelper;

    /**
     * IndicesConfigurator constructor.
     * @param Data $dataHelper
     * @param ConfigHelper $configHelper
     * @param ProductHelper $productHelper
     * @param CategoryHelper $categoryHelper
     * @param Logger $logger
     */
    public function __construct(
        Data         $dataHelper,
        ConfigHelper $configHelper,
        ProductHelper $productHelper,
        CategoryHelper $categoryHelper,
        Logger       $logger
    ) {
        $this->dataHelper   = $dataHelper;
        $this->configHelper = $configHelper;
        $this->productHelper = $productHelper;
        $this->categoryHelper = $categoryHelper;
        $this->logger       = $logger;
    }

    public function saveConfigurationToMeiliSearch($storeId, $useTmpIndex = false)
    {
        $logEventName = 'Save configuration to MeiliSearch for store: ' . $this->logger->getStoreName($storeId);
        $this->logger->start($logEventName);

        if (!($this->configHelper->getApiUrl($storeId) && $this->configHelper->getAPIKey($storeId))) {
            $this->logger->log('MeiliSearch credentials are not filled.');
            $this->logger->stop($logEventName);
            return;
        }
        if ($this->dataHelper->isIndexingEnabled($storeId) === false) {
            $this->logger->log('Indexing is not enabled for the store.');
            $this->logger->stop($logEventName);
            return;
        }

        $this->setCategoriesSettings($storeId);
        $this->setProductsSettings($storeId, $useTmpIndex);
    }

    /**
     * @param int $storeId
     *
     * @throws \Exception
     */
    private function setCategoriesSettings($storeId)
    {
        $this->logger->start('Pushing settings for categories indices.');

        $indexName = $this->dataHelper->getIndexName($this->categoryHelper->getIndexNameSuffix(), $storeId);

        $this->logger->log('Index name: ' . $indexName);
        $this->categoryHelper->setSettings($indexName, $storeId);

        $this->logger->stop('Pushing settings for categories indices.');
    }

    /**
     * @param int $storeId
     * @param bool $useTmpIndex
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function setProductsSettings($storeId, $useTmpIndex)
    {
        $this->logger->start('Pushing settings for products indices.');

        $indexName = $this->dataHelper->getIndexName($this->productHelper->getIndexNameSuffix(), $storeId);
        $indexNameTmp = $this->dataHelper->getIndexName($this->productHelper->getIndexNameSuffix(), $storeId, true);

        $this->logger->log('Index name: ' . $indexName);
        $this->logger->log('TMP Index name: ' . $indexNameTmp);

        $this->productHelper->setSettings($indexName, $indexNameTmp, $storeId, $useTmpIndex);

        $sortingAttributes = $this->configHelper->getSorting();
        foreach ($sortingAttributes as $sortingAttribute) {
            if ($sortingAttribute['attribute'] == 'price') {
                $nIndexName =
                    $indexName .
                    '_' . $sortingAttribute['attribute'] . '_' .
                    $this->configHelper->getStoreCode($storeId) . '_' .
                    $sortingAttribute['sort'];
            } else {
                $nIndexName =
                    $indexName .
                    '_' . $sortingAttribute['attribute'] . '_' .
                    $sortingAttribute['sort'];
            }

            $this->productHelper->setSettings($nIndexName, $indexNameTmp, $storeId, $useTmpIndex);
        }

        $this->logger->stop('Pushing settings for products indices.');
    }
}