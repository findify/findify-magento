<?php


class Datalay_FindifyFeed_Block_Adminhtml_Findifymodel extends Mage_Adminhtml_Block_Widget_Grid_Container{

	public function __construct()
	{

	$this->_controller = "adminhtml_findifymodel";
	$this->_blockGroup = "findifyfeed";
	$this->_headerText = Mage::helper("findifyfeed")->__("Findifymodel Manager");
	$this->_addButtonLabel = Mage::helper("findifyfeed")->__("Add New Item");
	parent::__construct();
	
	}

}