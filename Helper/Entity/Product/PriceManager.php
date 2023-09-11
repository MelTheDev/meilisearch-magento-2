<?php

namespace MelTheDev\MeiliSearch\Helper\Entity\Product;

use Magento\Catalog\Model\Product;
use MelTheDev\MeiliSearch\Helper\Entity\Product\PriceManager\Bundle as PriceManagerBundle;
use MelTheDev\MeiliSearch\Helper\Entity\Product\PriceManager\Configurable as PriceManagerConfigurable;
use MelTheDev\MeiliSearch\Helper\Entity\Product\PriceManager\Downloadable as PriceManagerDownloadable;
use MelTheDev\MeiliSearch\Helper\Entity\Product\PriceManager\Grouped as PriceManagerGrouped;
use MelTheDev\MeiliSearch\Helper\Entity\Product\PriceManager\Simple as PriceManagerSimple;
use MelTheDev\MeiliSearch\Helper\Entity\Product\PriceManager\Virtual as PriceManagerVirtual;

class PriceManager
{
    /**
     * @var PriceManagerSimple
     */
    private PriceManagerSimple $priceManagerSimple;
    /**
     * @var PriceManagerVirtual
     */
    private PriceManagerVirtual $priceManagerVirtual;
    /**
     * @var PriceManagerGrouped
     */
    private PriceManagerGrouped $priceManagerGrouped;
    /**
     * @var PriceManagerDownloadable
     */
    private PriceManagerDownloadable $priceManagerDownloadable;
    /**
     * @var PriceManagerConfigurable
     */
    private PriceManagerConfigurable $priceManagerConfigurable;
    /**
     * @var PriceManagerBundle
     */
    private PriceManagerBundle $priceManagerBundle;

    /**
     * PriceManager constructor.
     * @param PriceManagerSimple $priceManagerSimple
     * @param PriceManagerVirtual $priceManagerVirtual
     * @param PriceManagerGrouped $priceManagerGrouped
     * @param PriceManagerDownloadable $priceManagerDownloadable
     * @param PriceManagerConfigurable $priceManagerConfigurable
     * @param PriceManagerBundle $priceManagerBundle
     */
    public function __construct(
        PriceManagerSimple       $priceManagerSimple,
        PriceManagerVirtual      $priceManagerVirtual,
        PriceManagerGrouped      $priceManagerGrouped,
        PriceManagerDownloadable $priceManagerDownloadable,
        PriceManagerConfigurable $priceManagerConfigurable,
        PriceManagerBundle       $priceManagerBundle
    ) {
        $this->priceManagerSimple       = $priceManagerSimple;
        $this->priceManagerVirtual      = $priceManagerVirtual;
        $this->priceManagerGrouped      = $priceManagerGrouped;
        $this->priceManagerDownloadable = $priceManagerDownloadable;
        $this->priceManagerConfigurable = $priceManagerConfigurable;
        $this->priceManagerBundle       = $priceManagerBundle;
    }

    /**
     * @param $customData
     * @param Product $product
     * @param $subProducts
     * @return mixed
     */
    public function addPriceDataByProductType($customData, Product $product, $subProducts)
    {
        $priceManager = 'priceManager' . ucfirst($product->getTypeId());
        if (!property_exists($this, $priceManager)) {
            $priceManager = 'priceManagerSimple';
        }
        /** @see \MelTheDev\MeiliSearch\Helper\Entity\Product\PriceManager\ProductWithChildren::addPriceData() */
        /** @see \MelTheDev\MeiliSearch\Helper\Entity\Product\PriceManager\ProductWithoutChildren::addPriceData() */
        return $this->{$priceManager}->addPriceData($customData, $product, $subProducts);
    }
}
