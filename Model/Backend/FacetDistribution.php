<?php

namespace MelTheDev\MeiliSearch\Model\Backend;

use Magento\Config\Model\Config\Backend\Serialized;

class FacetDistribution extends Serialized
{
    /**
     * @inheritDoc
     */
    public function beforeSave()
    {
        $values = $this->getValue();
        if (is_array($values)) {
            unset($values['__empty']);
        }

        // Adding query rule config (set to "no") in case the select doesn't appear in the form
        foreach ($values as &$facet) {
            if (!isset($facet['create_rule'])) {
                $facet['create_rule'] = '2';
            }
        }

        $this->setValue($values);

        return parent::beforeSave();
    }
}