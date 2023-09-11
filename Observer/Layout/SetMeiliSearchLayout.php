<?php

namespace MelTheDev\MeiliSearch\Observer\Layout;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Layout;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;

class SetMeiliSearchLayout implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;
    /**
     * @var ConfigHelper
     */
    private ConfigHelper $configHelper;

    /**
     * SetMeiliSearchLayout constructor.
     * @param RequestInterface $request
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        RequestInterface $request,
        ConfigHelper $configHelper
    ) {
        $this->request = $request;
        $this->configHelper = $configHelper;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $actionName = $this->request->getFullActionName();
        if ($actionName === 'swagger_index_index') {
            return $this;
        }
        if ($this->configHelper->isEnabledFrontEnd() &&
            $this->configHelper->getApiKey() &&
            $this->configHelper->getApiUrl() &&
            $this->configHelper->getApiSearchKey()
        ) {
            if ($this->configHelper->isInstantEnabled()) {
                /** @var Layout $layout */
                $layout = $observer->getData('layout');
                $layout->getUpdate()->addHandle('meilisearch_search_handle');
            }
        }
        return $this;
    }
}
