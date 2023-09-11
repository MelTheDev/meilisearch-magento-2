<?php

namespace MelTheDev\MeiliSearch\Model\Source;

class CustomRankingCategory extends AbstractTable
{
    /**
     * Get table data
     *
     * @return array[]
     */
    protected function getTableData()
    {
        $categoryHelper = $this->categoryHelper;
        return [
            'attribute' => [
                'label'  => 'Attribute',
                'values' => function () use ($categoryHelper) {
                    $options = [];

                    $attributes = $categoryHelper->getAllAttributes();

                    foreach ($attributes as $key => $label) {
                        $options[$key] = $key ? $key : $label;
                    }

                    return $options;
                },
            ],
            'order' => [
                'label'  => 'Order',
                'values' => ['asc' => 'Ascending', 'desc' => 'Descending'],
            ],
        ];
    }
}