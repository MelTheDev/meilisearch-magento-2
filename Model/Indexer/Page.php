<?php

namespace MelTheDev\MeiliSearch\Model\Indexer;

use Magento\Framework\Message\ManagerInterface;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;
use MelTheDev\MeiliSearch\Helper\Entity\PageHelper;
use MelTheDev\MeiliSearch\Helper\Data;
use MelTheDev\MeiliSearch\Model\Queue;
use Symfony\Component\Console\Output\ConsoleOutput;

class Page implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    public function __construct(
        private ConfigHelper     $configHelper,
        private ConsoleOutput    $output,
        private ManagerInterface $messageManager,
        private PageHelper       $pageHelper,
        private Data            $helper ,
        private Queue           $queue
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute($ids)
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

        $storeIds = $this->pageHelper->getStores();

        foreach ($storeIds as $storeId) {
            if ($this->helper->isIndexingEnabled($storeId) === false) {
                continue;
            }

            if ($this->isPagesInAdditionalSections($storeId)) {
                $data = ['storeId' => $storeId];
                if (is_array($ids) && count($ids) > 0) {
                    $data['pageIds'] = $ids;
                }

                $this->queue->addToQueue(
                    $this->helper,
                    'rebuildStorePageIndex',
                    $data,
                    is_array($ids) ? count($ids) : 1
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    /**
     * @inheritDoc
     */
    public function executeFull()
    {
        $this->execute([]);
    }

    /**
     * @inheritDoc
     */
    public function executeRow($id)
    {
        $this->execute([$id]);
    }

    /**
     * Is page in addition section (autocomplete)
     *
     * @param int $storeId
     * @return bool
     */
    private function isPagesInAdditionalSections($storeId)
    {
        $sections = $this->configHelper->getAutocompleteSections($storeId);
        foreach ($sections as $section) {
            if ($section['name'] === 'pages') {
                return true;
            }
        }
        return false;
    }
}
