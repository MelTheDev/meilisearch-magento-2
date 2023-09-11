<?php

namespace MelTheDev\MeiliSearch\Model\Indexer;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use MelTheDev\MeiliSearch\Helper\CategoryHelper;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;
use MelTheDev\MeiliSearch\Helper\Data;
use MelTheDev\MeiliSearch\Model\IndicesConfigurator;
use MelTheDev\MeiliSearch\Model\Queue;
use Symfony\Component\Console\Output\ConsoleOutput;

class Category implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @var ConfigHelper
     */
    private ConfigHelper $configHelper;
    /**
     * @var ConsoleOutput
     */
    private ConsoleOutput $output;
    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;
    /**
     * @var Data
     */
    private Data $dataHelper;
    /**
     * @var Queue
     */
    private Queue $queue;
    /**
     * @var CategoryHelper
     */
    private CategoryHelper $categoryHelper;
    /**
     * @var array
     */
    public static $affectedProductIds = [];

    /**
     * Category constructor.
     * @param ConfigHelper $configHelper
     * @param ConsoleOutput $output
     * @param ManagerInterface $messageManager
     * @param StoreManagerInterface $storeManager
     * @param Data $dataHelper
     * @param Queue $queue
     * @param CategoryHelper $categoryHelper
     */
    public function __construct(
        ConfigHelper          $configHelper,
        ConsoleOutput         $output,
        ManagerInterface      $messageManager,
        StoreManagerInterface $storeManager,
        Data                  $dataHelper,
        Queue                 $queue,
        CategoryHelper        $categoryHelper
    ) {
        $this->configHelper   = $configHelper;
        $this->output         = $output;
        $this->messageManager = $messageManager;
        $this->storeManager   = $storeManager;
        $this->dataHelper     = $dataHelper;
        $this->queue          = $queue;
        $this->categoryHelper = $categoryHelper;
    }

    public function execute($ids)
    {
        $categoryIds = $ids;
        if (!$this->configHelper->getApiUrl()
            || !$this->configHelper->getApiKey()
        ) {
            $errorMessage = 'MeiliSearch reindexing failed:
                You need to configure your MeiliSearch credentials in Stores > Configuration > Mel MeiliSearch Search.';
            if (php_sapi_name() === 'cli') {
                $this->output->writeln($errorMessage);
                return;
            }
            $this->messageManager->addErrorMessage($errorMessage);
            return;
        }
        $storeIds = array_keys($this->storeManager->getStores());
        foreach ($storeIds as $storeId) {
            if ($this->dataHelper->isIndexingEnabled($storeId) === false) {
                continue;
            }

            $this->rebuildAffectedProducts($storeId);

            $categoriesPerPage = $this->configHelper->getNumberOfElementByPage();

            if (is_array($categoryIds) && count($categoryIds) > 0) {
                $this->processSpecificCategories($categoryIds, $categoriesPerPage, $storeId);

                continue;
            }

            $this->processFullReindex($storeId, $categoriesPerPage);
        }
    }

    public function executeFull()
    {
        $this->execute(null);
    }

    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    public function executeRow($id)
    {
        $this->execute([$id]);
    }

    /**
     * @param int $storeId
     */
    private function rebuildAffectedProducts($storeId)
    {
        $affectedProducts = self::$affectedProductIds;
        $affectedProductsCount = count($affectedProducts);

        if ($affectedProductsCount > 0 && $this->configHelper->indexProductOnCategoryProductsUpdate($storeId)) {
            $productsPerPage = $this->configHelper->getNumberOfElementByPage();
            foreach (array_chunk($affectedProducts, $productsPerPage) as $chunk) {
                /** @uses Data::rebuildStoreProductIndex() */
                $this->queue->addToQueue(
                    Data::class,
                    'rebuildStoreProductIndex',
                    [
                        'storeId' => $storeId,
                        'productIds' => $chunk,
                    ],
                    count($chunk)
                );
            }
        }
    }

    /**
     * @param array $categoryIds
     * @param int $categoriesPerPage
     * @param int $storeId
     */
    private function processSpecificCategories($categoryIds, $categoriesPerPage, $storeId)
    {
        foreach (array_chunk($categoryIds, $categoriesPerPage) as $chunk) {
            /** @uses Data::rebuildStoreCategoryIndex() */
            $this->queue->addToQueue(
                Data::class,
                'rebuildStoreCategoryIndex',
                [
                    'storeId' => $storeId,
                    'categoryIds' => $chunk,
                ],
                count($chunk)
            );
        }
    }

    /**
     * @param int $storeId
     * @param int $categoriesPerPage
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function processFullReindex($storeId, $categoriesPerPage)
    {
        /** @uses IndicesConfigurator::saveConfigurationToMeiliSearch() */
        $this->queue->addToQueue(IndicesConfigurator::class, 'saveConfigurationToMeiliSearch', ['storeId' => $storeId]);

        $collection = $this->categoryHelper->getCategoryCollectionQuery($storeId);
        $size = $collection->getSize();

        $pages = ceil($size / $categoriesPerPage);

        for ($i = 1; $i <= $pages; $i++) {
            $data = [
                'storeId' => $storeId,
                'page' => $i,
                'pageSize' => $categoriesPerPage,
            ];

            /** @uses Data::rebuildCategoryIndex() */
            $this->queue->addToQueue(Data::class, 'rebuildCategoryIndex', $data, $categoriesPerPage, true);
        }
    }
}