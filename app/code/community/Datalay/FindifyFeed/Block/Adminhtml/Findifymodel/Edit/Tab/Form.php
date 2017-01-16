<?php
class Datalay_FindifyFeed_Block_Adminhtml_Findifymodel_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
		protected function _prepareForm()
		{

				$form = new Varien_Data_Form();
				$this->setForm($form);
				$fieldset = $form->addFieldset("findifyfeed_form", array("legend"=>Mage::helper("findifyfeed")->__("Item information")));

				
						$fieldset->addField("id", "text", array(
						"label" => Mage::helper("findifyfeed")->__("id"),					
						"class" => "required-entry",
						"required" => true,
						"name" => "id",
						));
					

				if (Mage::getSingleton("adminhtml/session")->getFindifymodelData())
				{
					$form->setValues(Mage::getSingleton("adminhtml/session")->getFindifymodelData());
					Mage::getSingleton("adminhtml/session")->setFindifymodelData(null);
				} 
				elseif(Mage::registry("findifymodel_data")) {
				    $form->setValues(Mage::registry("findifymodel_data")->getData());
				}
				return parent::_prepareForm();
		}
}
