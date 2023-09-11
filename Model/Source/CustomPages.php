<?php

namespace MelTheDev\MeiliSearch\Model\Source;

use Magento\Framework\App\ObjectManager;

class CustomPages extends AbstractTable
{
    protected function getTableData()
    {
        $objectManager = ObjectManager::getInstance();
        $pageCollection = $objectManager->create(\Magento\Cms\Model\ResourceModel\Page\Collection::class);
        return [
            'attribute' => [
                'label'  => 'Page',
                'values' => function () use ($pageCollection) {
                    $options = [];
                    $magentoPages = $pageCollection->addFieldToFilter('is_active', 1);

                    foreach ($magentoPages as $page) {
                        $options[$page->getData('identifier')] = $page->getData('identifier');
                    }

                    return $options;
                },
            ],
        ];
    }
}