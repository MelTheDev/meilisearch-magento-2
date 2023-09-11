<?php

namespace MelTheDev\MeiliSearch\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class BackendRenderingDisplayMode implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'all',           'label' => __('All categories')],
            ['value' => 'only_products', 'label' => __('Categories without static blocks')],
        ];
    }
}