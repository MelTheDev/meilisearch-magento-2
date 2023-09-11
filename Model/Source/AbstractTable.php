<?php

namespace MelTheDev\MeiliSearch\Model\Source;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use MelTheDev\MeiliSearch\Block\System\Form\Field\Select;
use MelTheDev\MeiliSearch\Helper\CategoryHelper;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;
use MelTheDev\MeiliSearch\Helper\ProductHelper;

abstract class AbstractTable extends AbstractFieldArray
{
    protected ConfigHelper $configHelper;
    protected ProductHelper $productHelper;
    protected CategoryHelper $categoryHelper;
    protected $selectFields = [];

    /**
     * AbstractTable constructor.
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param ProductHelper $productHelper
     * @param CategoryHelper $categoryHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        ProductHelper $productHelper,
        CategoryHelper $categoryHelper,
        array $data = []
    ) {
        $this->configHelper   = $configHelper;
        $this->productHelper  = $productHelper;
        $this->categoryHelper = $categoryHelper;
        parent::__construct($context, $data);
    }

    protected function getRenderer($columnId, $columnData)
    {
        if (!array_key_exists($columnId, $this->selectFields) || !$this->selectFields[$columnId]) {
            /** @var Select $select */
            $select = $this->getLayout()
                ->createBlock(Select::class, '', [
                    'data' => ['is_render_to_js_template' => true],
                ]);

            $options = $columnData['values'];

            if (is_callable($options)) {
                $options = $options();
            }

            $extraParams = $columnId === 'attribute' ? 'style="width:160px;"' : 'style="width:100px;"';
            $select->setData('extra_params', $extraParams);
            $select->setOptions($options);

            $this->selectFields[$columnId] = $select;
        }

        return $this->selectFields[$columnId];
    }

    protected function _construct()
    {
        $data = $this->getTableData();

        foreach (array_keys($data) as $columnId) {
            $columnData = $data[$columnId];

            $column = [
                'label' => __($columnData['label']),
            ];

            if (isset($columnData['values'])) {
                $column['renderer'] = $this->getRenderer($columnId, $columnData);
            }

            if (isset($columnData['class'])) {
                $column['class'] = $columnData['class'];
            }

            if (isset($columnData['style'])) {
                $column['style'] = $columnData['style'];
            }

            $this->addColumn($columnId, $column);
        }

        $this->_addAfter = false;
        parent::_construct();
    }

    protected function _prepareArrayRow(DataObject $row)
    {
        $data = $this->getTableData();
        $options = [];
        foreach (array_keys($data) as $columnId) {
            $columnData = $data[$columnId];

            if (isset($columnData['values'])) {
                $index = 'option_' . $this->getRenderer($columnId, $columnData)
                        ->calcOptionHash($row->getData($columnId));

                $options[$index] = 'selected="selected"';
            }
        }

        if ($row['_id'] === null || is_int($row['_id'])) {
            $row->setData('_id', '_' . random_int(1000000000, 9999999999) . '_' . random_int(0, 999));
        }

        $row->setData('option_extra_attrs', $options);
    }
}