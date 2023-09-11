<?php

namespace MelTheDev\MeiliSearch\Block;

use Magento\Search\Helper\Data as CatalogSearchHelper;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;
use Magento\Framework\Data\CollectionDataSourceInterface;
use Magento\Framework\View\Element\Template;

class MeiliSearch extends Template implements CollectionDataSourceInterface
{
    /**
     * @param Template\Context $context
     * @param ConfigHelper $configHelper
     * @param CatalogSearchHelper $catalogSearchHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private ConfigHelper $configHelper,
        private CatalogSearchHelper $catalogSearchHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get config helper
     *
     * @return ConfigHelper
     */
    public function getConfigHelper()
    {
        return $this->configHelper;
    }

    public function getCatalogSearchHelper()
    {
        return $this->catalogSearchHelper;
    }
}
