<?php

namespace MelTheDev\MeiliSearch\Block\System\Form\Field;

class Select extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @inheritDoc
     */
    protected function _toHtml()
    {
        $this->setData('name', $this->getData('input_name'));
        $this->setClass('select');

        return trim(preg_replace('/\s+/', ' ', parent::_toHtml()));
    }
}