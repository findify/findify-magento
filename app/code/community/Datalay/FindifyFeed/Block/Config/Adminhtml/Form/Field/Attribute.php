<?php
class Datalay_FindifyFeed_Block_Config_Adminhtml_Form_Field_Attribute extends Mage_Core_Block_Html_Select
{
    public function _toHtml()
    {
	$attributes = Mage::getResourceModel('catalog/product_attribute_collection')
	    ->getItems();

	foreach ($attributes as $attribute){
	    $attributecode = $attribute->getAttributecode();
	    $attributelabel = $attribute->getFrontendLabel();
	    if ($attributelabel == ''){
	        continue;
            }
            $attributelabel = str_replace("'", '', $attributelabel); // if an attribute contains ' it will break the js template so we remove it
            $this->addOption($attributecode, $attributelabel);
        }
 
        return parent::_toHtml();
    }
 
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}
