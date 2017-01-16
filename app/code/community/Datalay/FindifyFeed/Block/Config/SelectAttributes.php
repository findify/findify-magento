<?php
class Datalay_FindifyFeed_Block_Config_SelectAttributes extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

    protected $_itemRenderer;
    
    public function _prepareToRender()
    {
        //Mage::log('SelectAttributes.php - _prepareToRender()');
        $this->addColumn('attributename', array(
            'label' => Mage::helper('findifyfeed')->__('Magento Attribute'),
            'renderer' => $this->_getRenderer(),
        ));

        $this->addColumn('attributejson', array(
            'label' => Mage::helper('findifyfeed')->__('Name in the Feed'),
            'style' => 'width:200px',
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('findifyfeed')->__('Add');
    }
    
    protected function  _getRenderer() 
    {
        //Mage::log('SelectAttributes.php - _getRenderer()');
        if (!$this->_itemRenderer) {
            //Mage::log('SelectAttributes.php - _getRenderer() - if (!$this->_itemRenderer)');
            $this->_itemRenderer = $this->getLayout()->createBlock(
                'findifyfeed/config_adminhtml_form_field_attribute', '',
                array('is_render_to_js_template' => true)
            );
        }
        return $this->_itemRenderer;
    }
 
    protected function _prepareArrayRow(Varien_Object $row)
    {
        //Mage::log('SelectAttributes.php - _prepareArrayRow()');
        $row->setData(
            'option_extra_attr_' . $this->_getRenderer()
                ->calcOptionHash($row->getData('attributename')),
            'selected="selected"'
        );
    }
    
}