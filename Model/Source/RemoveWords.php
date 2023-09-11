<?php

namespace MelTheDev\MeiliSearch\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class RemoveWords implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'none',          'label' => __('None')],
            ['value' => 'allOptional',   'label' => __('AllOptional')],
            ['value' => 'lastWords',     'label' => __('LastWords')],
            ['value' => 'firstWords',    'label' => __('FirstWords')],
        ];
    }
}