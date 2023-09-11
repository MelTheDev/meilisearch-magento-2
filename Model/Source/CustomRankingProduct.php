<?php

namespace MelTheDev\MeiliSearch\Model\Source;

class CustomRankingProduct extends AbstractTable
{
    protected function getTableData()
    {
        $productHelper = $this->productHelper;
        return [
            'attribute' => [
                'label'  => __('Attribute'),
                'values' => function () use ($productHelper) {
                    $options    = [];
                    $attributes = $productHelper->getAllAttributes();
                    foreach ($attributes as $key => $label) {
                        $options[$key] = $key ? $key : $label;
                    }
                    return $options;
                },
            ],
            'order' => [
                'label'  => 'Order',
                'values' => ['asc' => __('Ascending'), 'desc' => __('Descending')],
            ],
        ];
    }
}