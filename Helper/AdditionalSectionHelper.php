<?php

namespace MelTheDev\MeiliSearch\Helper;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\DataObject;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Exception\LocalizedException;

class AdditionalSectionHelper
{
    /**
     * @param ProductCollectionFactory $collectionFactory
     * @param EavConfig $eavConfig
     */
    public function __construct(
        private ProductCollectionFactory $collectionFactory,
        private EavConfig $eavConfig
    ) {
    }

    /**
     * @param int $storeId
     * @param array $section
     * @return array
     * @throws LocalizedException
     */
    public function getAttributeValues($storeId, $section)
    {
        $attributeCode = $section['name'];

        /** @var ProductCollection $products */
        $products = $this->collectionFactory->create()
            ->addStoreFilter($storeId)
            ->addAttributeToFilter($attributeCode, ['notnull' => true])
            ->addAttributeToFilter($attributeCode, ['neq' => ''])
            ->addAttributeToSelect($attributeCode);

        $usedAttributeValues = array_unique($products->getColumnValues($attributeCode));

        $attributeModel = $this->eavConfig->getAttribute('catalog_product', $attributeCode)->setStoreId($storeId);

        $values = $attributeModel->getSource()->getOptionText(
            implode(',', $usedAttributeValues)
        );

        if ($values && is_array($values) === false) {
            $values = [$values];
        }

        if (!$values || count($values) === 0) {
            $values = array_unique($products->getColumnValues($attributeCode));
        }

        return array_map(function ($value) use ($section, $storeId) {
            $record = [
                'objectID' => $value,
                'value'    => $value,
            ];

            $transport = new DataObject($record);
            $record = $transport->getData();

            return $record;
        }, $values);
    }

    /**
     * @return array
     */
    public function getIndexSettings()
    {
        $indexSettings = [
            'searchableAttributes' => ['unordered(value)'],
        ];

        $transport = new DataObject($indexSettings);
        $indexSettings = $transport->getData();
        return $indexSettings;
    }

    /**
     * @return string
     */
    public function getIndexNameSuffix()
    {
        return '_section';
    }
}
