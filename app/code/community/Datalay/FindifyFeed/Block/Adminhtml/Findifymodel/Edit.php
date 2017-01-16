<?php
	
class Datalay_FindifyFeed_Block_Adminhtml_Findifymodel_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
		public function __construct()
		{

				parent::__construct();
				$this->_objectId = "id";
				$this->_blockGroup = "findifyfeed";
				$this->_controller = "adminhtml_findifymodel";
				$this->_updateButton("save", "label", Mage::helper("findifyfeed")->__("Save Item"));
				$this->_updateButton("delete", "label", Mage::helper("findifyfeed")->__("Delete Item"));

				$this->_addButton("saveandcontinue", array(
					"label"     => Mage::helper("findifyfeed")->__("Save And Continue Edit"),
					"onclick"   => "saveAndContinueEdit()",
					"class"     => "save",
				), -100);



				$this->_formScripts[] = "

							function saveAndContinueEdit(){
								editForm.submit($('edit_form').action+'back/edit/');
							}
						";
		}

		public function getHeaderText()
		{
				if( Mage::registry("findifymodel_data") && Mage::registry("findifymodel_data")->getId() ){

				    return Mage::helper("findifyfeed")->__("Edit Item '%s'", $this->htmlEscape(Mage::registry("findifymodel_data")->getId()));

				} 
				else{

				     return Mage::helper("findifyfeed")->__("Add Item");

				}
		}
}