<?php

namespace MelTheDev\MeiliSearch\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;

class Wrapper implements ArgumentInterface
{
    /**
     * @var ConfigHelper
     */
    private ConfigHelper $configHelper;

    /**
     * Wrapper constructor.
     * @param ConfigHelper $configHelper
     */
    public function __construct(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * Has facets
     *
     * @return bool
     */
    public function hasFacets()
    {
        return count($this->configHelper->getFacets()) > 0;
    }
}