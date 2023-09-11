<?php

namespace MelTheDev\MeiliSearch\Helper\Entity\Product\PriceManager;

use Magento\Catalog\Model\Product;

class Bundle extends ProductWithChildren
{
    /**
     * Overide parent addAdditionalData function
     * @param $product
     * @param $withTax
     * @param $subProducts
     * @param $currencyCode
     * @param $field
     * @return void
     */
    protected function addAdditionalData($product, $withTax, $subProducts, $currencyCode, $field) {
        $data = $this->getMinMaxPrices($product, $withTax, $subProducts, $currencyCode);
        $dashedFormat = $this->getDashedPriceFormat($data['min_price'], $data['max'], $currencyCode);
        if ($data['min_price'] !== $data['max']) {
            $this->handleBundleNonEqualMinMaxPrices($field, $currencyCode, $data['min_price'], $data['max'], $dashedFormat);
        }
        $this->handleOriginalPrice($field, $currencyCode, $data['min_price'], $data['max'], $data['min_original'], $data['max_original']);
        if (!$this->customData[$field][$currencyCode]['default']) {
            $this->handleZeroDefaultPrice($field, $currencyCode, $data['min_price'], $data['max']);
        }
        if ($this->areCustomersGroupsEnabled) {
            $groupedDashedFormat = $this->getBundleDashedPriceFormat($data['min'], $data['max'], $currencyCode);
            $this->setFinalGroupPricesBundle($field, $currencyCode, $data['min'], $data['max'], $groupedDashedFormat);
        }
    }

    /**
     * @param Product $product
     * @param $withTax
     * @param $subProducts
     * @param $currencyCode
     * @return array
     */
    protected function getMinMaxPrices(Product $product, $withTax, $subProducts, $currencyCode)
    {
        $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue();
        $minPrice = $product->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue();
        $minOriginalPrice = $product->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue();
        $maxOriginalPrice = $product->getPriceInfo()->getPrice('regular_price')->getMaximalPrice()->getValue();
        $max = $product->getPriceInfo()->getPrice('final_price')->getMaximalPrice()->getValue();
        $minArray = [];
        foreach ($this->groups as $group) {
            $groupId = (int) $group->getData('customer_group_id');
            foreach ($subProducts as $subProduct) {
                $subProduct->setData('customer_group_id', $groupId);
                $subProductFinalPrice = $this->getTaxPrice($product, $subProduct->getPriceModel()->getFinalPrice(1, $subProduct), $withTax);
                $priceDiff = $subProduct->getPrice() - $subProductFinalPrice;
                $minArray[$groupId][] = $regularPrice - $priceDiff;
            }
        }

        $minPriceArray = [];
        foreach ($minArray as $groupId => $min) {
            $minPriceArray[$groupId] = min($min);
        }

        if ($currencyCode !== $this->baseCurrencyCode) {
            $minPrice = $this->convertPrice($minPrice, $currencyCode);
            $minOriginalPrice = $this->convertPrice($minOriginalPrice, $currencyCode);
            $maxOriginalPrice = $this->convertPrice($maxOriginalPrice, $currencyCode);
            foreach ($minPriceArray as $groupId => $price) {
                $minPriceArray[$groupId] = $this->convertPrice($price, $currencyCode);
            }
            if ($min !== $max) {
                $max = $this->convertPrice($max, $currencyCode);
            }
        }

        return [
            'min' => $minPriceArray,
            'max' => $max,
            'min_price' => $minPrice,
            'min_original' => $minOriginalPrice,
            'max_original' => $maxOriginalPrice
        ];
    }

    /**
     * @param $field
     * @param $currencyCode
     * @param $min
     * @param $max
     * @param $dashedFormat
     * @return void
     */
    protected function handleBundleNonEqualMinMaxPrices($field, $currencyCode, $min, $max, $dashedFormat) {
        if (isset($this->customData[$field][$currencyCode]['default_original_formated']) === false
            || $min <= $this->customData[$field][$currencyCode]['default']) {
            $this->customData[$field][$currencyCode]['default_formated'] = $dashedFormat;
            //// Do not keep special price that is already taken into account in min max
            unset(
                $this->customData['price']['special_from_date'],
                $this->customData['price']['special_to_date'],
                $this->customData['price']['default_original_formated']
            );
            $this->customData[$field][$currencyCode]['default'] = 0; // will be reset just after
        }

        $this->customData[$field][$currencyCode]['default_max'] = $max;
    }

    /**
     * @param $minPrices
     * @param $max
     * @param $currencyCode
     * @return array
     */
    protected function getBundleDashedPriceFormat($minPrices, $max, $currencyCode) {
        $dashedFormatPrice = [];
        foreach ($minPrices as $groupId => $min) {
            if ($min === $max) {
                $dashedFormatPrice [$groupId] =  '';
            }
            $dashedFormatPrice[$groupId] = $this->formatPrice($min, $currencyCode) . ' - ' . $this->formatPrice($max, $currencyCode);
        }
        return $dashedFormatPrice;
    }

    /**
     * @param $field
     * @param $currencyCode
     * @param $min
     * @param $max
     * @param $dashedFormat
     * @return void
     */
    protected function setFinalGroupPricesBundle($field, $currencyCode, $min, $max, $dashedFormat)
    {
        /** @var \Magento\Customer\Model\Group $group */
        foreach ($this->groups as $group) {
            $groupId = (int) $group->getData('customer_group_id');
            $this->customData[$field][$currencyCode]['group_' . $groupId] = $min[$groupId];
            if ($min === $max) {
                $this->customData[$field][$currencyCode]['group_' . $groupId . '_formated'] =
                    $this->customData[$field][$currencyCode]['default_formated'];
            } else {
                $this->customData[$field][$currencyCode]['group_' . $groupId . '_formated'] = $dashedFormat[$groupId];
            }
        }
    }
}