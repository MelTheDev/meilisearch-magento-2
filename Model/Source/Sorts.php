<?php

namespace MelTheDev\MeiliSearch\Model\Source;

class Sorts extends AbstractTable
{
    protected function getTableData()
    {
        $productHelper = $this->productHelper;

        return [
            'attribute' => [
                'label'  => 'Attribute',
                'values' => function () use ($productHelper) {
                    $options = [];

                    $attributes = $productHelper->getAllAttributes();

                    foreach ($attributes as $key => $label) {
                        $options[$key] = $key ? $key : $label;
                    }

                    return $options;
                },
            ],
            'sort' => [
                'label'  => 'Sort',
                'values' => ['asc' => __('Ascending'), 'desc' => __('Descending')],
            ],
            'sortLabel' => [
                'label' => 'Label',
            ],
        ];
    }
}