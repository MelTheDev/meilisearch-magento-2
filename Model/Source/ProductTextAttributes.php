<?php

namespace MelTheDev\MeiliSearch\Model\Source;

class ProductTextAttributes extends AbstractTable
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
        ];
    }
}