<?php

namespace MelTheDev\MeiliSearch\Model\Indexer;

use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;
use MelTheDev\MeiliSearch\Helper\Data;
use MelTheDev\MeiliSearch\Model\Queue;
use Symfony\Component\Console\Output\ConsoleOutput;

class Suggestion implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @param ConfigHelper $configHelper
     * @param ConsoleOutput $output
     * @param ManagerInterface $messageManager
     * @param Data $helper
     * @param Queue $queue
     * @param StoreManagerInterface $storeManager
     * @noinspection PhpPropertyCanBeReadonlyInspection
     */
    public function __construct(
        private ConfigHelper $configHelper,
        private ConsoleOutput $output,
        private ManagerInterface $messageManager,
        private Data $helper ,
        private Queue $queue,
        private StoreManagerInterface $storeManager,
    ) {
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

            /** @see Data::rebuildStoreSuggestionIndex() */
            $this->queue->addToQueue($this->helper, 'rebuildStoreSuggestionIndex', ['storeId' => $storeId], 1);
        }
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
