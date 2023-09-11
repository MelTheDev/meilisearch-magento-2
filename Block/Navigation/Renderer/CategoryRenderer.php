<?php

namespace MelTheDev\MeiliSearch\Block\Navigation\Renderer;

use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\Framework\View\Element\Template;
use Magento\LayeredNavigation\Block\Navigation\FilterRendererInterface;

class CategoryRenderer extends Template implements FilterRendererInterface
{
    /** @var string */
    protected $_template = 'MelTheDev_MeiliSearch::layer/filter/category.phtml';

    /** @var FilterInterface */
    protected $filter;

    public function isMultipleSelectEnabled()
    {
        return false;
    }

    public function render(FilterInterface $filter)
    {
        $html = '';
        $this->filter = $filter;

        if ($this->canRenderFilter()) {
            $this->assign('filterItems', $filter->getItems());
            $html = $this->_toHtml();
            $this->assign('filterItems', []);
        }

        return $html;
    }

    /**
     * @inheritdoc
     */
    protected function canRenderFilter()
    {
        return true;
    }
}
