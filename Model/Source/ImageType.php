<?php

namespace MelTheDev\MeiliSearch\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ImageType implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'product_base_image',      'label' => __('Base Image')],
            ['value' => 'product_small_image',     'label' => __('Small Image')],
            ['value' => 'product_thumbnail_image', 'label' => __('Thumbnail')],
        ];
    }
}