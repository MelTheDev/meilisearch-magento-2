<?php

namespace MelTheDev\MeiliSearch\Model\Source;

class FacetDistribution extends AbstractTable
{
    protected function getTableData()
    {
        $productHelper = $this->productHelper;

        $config = [
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
            'type' => [
                'label'  => 'Facet type',
                'values' => [
                    'conjunctive' => 'Conjunctive',
                    'disjunctive' => 'Disjunctive',
                    'slider'      => 'Slider',
                    'priceRanges' => 'Price Range',
                ],
            ],
            'label' => [
                'label' => 'Label',
            ],
            'searchable' => [
                'label'  => 'Options',
                'values' => ['1' => 'Searchable', '2' => 'Not Searchable', '3' => 'Filter Only'],
            ],
        ];

        $config['create_rule'] =  [
            'label'  => 'Create Query rule?',
            'values' => ['2' => 'No', '1' => 'Yes'],
        ];

        return $config;
    }
}