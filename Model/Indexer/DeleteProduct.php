<?php
namespace MelTheDev\MeiliSearch\Model\Indexer;

use MelTheDev\MeiliSearch\Helper\ConfigHelper;
use MelTheDev\MeiliSearch\Helper\Data;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class DeleteProduct implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     * @param ConfigHelper $configHelper
     * @param ManagerInterface $messageManager
     * @param ConsoleOutput $output
     * @noinspection PhpPropertyCanBeReadonlyInspection
     */
    public function __construct(
        private StoreManagerInterface $storeManager,
        private Data $helper,
        private ConfigHelper $configHelper,
        private ManagerInterface $messageManager,
        private ConsoleOutput $output
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute($ids)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function executeFull()
    {
        if (!$this->configHelper->getApiUrl()
            || !$this->configHelper->getApiKey()
        ) {
            $errorMessage = 'MeiliSearch reindexing failed: 
                You need to configure your MeiliSearch credentials in Stores > Configuration > MeiliSearch Search.';

            if (php_sapi_name() === 'cli') {
                $this->output->writeln($errorMessage);

                return;
            }

            $this->messageManager->addErrorMessage($errorMessage);

            return;
        }

        $storeIds = array_keys($this->storeManager->getStores());

        foreach ($storeIds as $storeId) {
            if ($this->helper->isIndexingEnabled($storeId) === false) {
                continue;
            }

            $this->helper->deleteInactiveProducts($storeId);
        }
    }

    /**
     * @inheritDoc
     */
    public function executeList(array $ids)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function executeRow($id)
    {
        return $this;
    }
}
