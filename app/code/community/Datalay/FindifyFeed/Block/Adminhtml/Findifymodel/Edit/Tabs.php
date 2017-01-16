<?php
class Datalay_FindifyFeed_Block_Adminhtml_Findifymodel_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
		public function __construct()
		{
				parent::__construct();
				$this->setId("findifymodel_tabs");
				$this->setDestElementId("edit_form");
				$this->setTitle(Mage::helper("findifyfeed")->__("Item Information"));
		}
		protected function _beforeToHtml()
		{
				$this->addTab("form_section", array(
				"label" => Mage::helper("findifyfeed")->__("Item Information"),
				"title" => Mage::helper("findifyfeed")->__("Item Information"),
				"content" => $this->getLayout()->createBlock("findifyfeed/adminhtml_findifymodel_edit_tab_form")->toHtml(),
				));
				return parent::_beforeToHtml();
		}

}
