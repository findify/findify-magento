<?php

class Datalay_FindifyFeed_Adminhtml_FindifymodelController extends Mage_Adminhtml_Controller_Action
{

		protected function _isAllowed()
		{
		//return Mage::getSingleton('admin/session')->isAllowed('findifyfeed/findifymodel');
			return true;
		}

		protected function _initAction()
		{
				$this->loadLayout()->_setActiveMenu("findifyfeed/findifymodel")->_addBreadcrumb(Mage::helper("adminhtml")->__("Findifymodel  Manager"),Mage::helper("adminhtml")->__("Findifymodel Manager"));
				return $this;
		}
		public function indexAction() 
		{
			    $this->_title($this->__("FindifyFeed"));
			    $this->_title($this->__("Manager Findifymodel"));

				$this->_initAction();
				$this->renderLayout();
		}
		public function editAction()
		{			    
			    $this->_title($this->__("FindifyFeed"));
				$this->_title($this->__("Findifymodel"));
			    $this->_title($this->__("Edit Item"));
				
				$id = $this->getRequest()->getParam("id");
				$model = Mage::getModel("findifyfeed/findifymodel")->load($id);
				if ($model->getId()) {
					Mage::register("findifymodel_data", $model);
					$this->loadLayout();
					$this->_setActiveMenu("findifyfeed/findifymodel");
					$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Findifymodel Manager"), Mage::helper("adminhtml")->__("Findifymodel Manager"));
					$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Findifymodel Description"), Mage::helper("adminhtml")->__("Findifymodel Description"));
					$this->getLayout()->getBlock("head")->setCanLoadExtJs(true);
					$this->_addContent($this->getLayout()->createBlock("findifyfeed/adminhtml_findifymodel_edit"))->_addLeft($this->getLayout()->createBlock("findifyfeed/adminhtml_findifymodel_edit_tabs"));
					$this->renderLayout();
				} 
				else {
					Mage::getSingleton("adminhtml/session")->addError(Mage::helper("findifyfeed")->__("Item does not exist."));
					$this->_redirect("*/*/");
				}
		}

		public function newAction()
		{

		$this->_title($this->__("FindifyFeed"));
		$this->_title($this->__("Findifymodel"));
		$this->_title($this->__("New Item"));

	        $id   = $this->getRequest()->getParam("id");
		$model  = Mage::getModel("findifyfeed/findifymodel")->load($id);

		$data = Mage::getSingleton("adminhtml/session")->getFormData(true);
		if (!empty($data)) {
			$model->setData($data);
		}

		Mage::register("findifymodel_data", $model);

		$this->loadLayout();
		$this->_setActiveMenu("findifyfeed/findifymodel");

		$this->getLayout()->getBlock("head")->setCanLoadExtJs(true);

		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Findifymodel Manager"), Mage::helper("adminhtml")->__("Findifymodel Manager"));
		$this->_addBreadcrumb(Mage::helper("adminhtml")->__("Findifymodel Description"), Mage::helper("adminhtml")->__("Findifymodel Description"));


		$this->_addContent($this->getLayout()->createBlock("findifyfeed/adminhtml_findifymodel_edit"))->_addLeft($this->getLayout()->createBlock("findifyfeed/adminhtml_findifymodel_edit_tabs"));

		$this->renderLayout();

		}

		public function saveAction()
		{

			$post_data=$this->getRequest()->getPost();


				if ($post_data) {

					try {

						

						$model = Mage::getModel("findifyfeed/findifymodel")
						->addData($post_data)
						->setId($this->getRequest()->getParam("id"))
						->save();

						Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Findifymodel was successfully saved"));
						Mage::getSingleton("adminhtml/session")->setFindifymodelData(false);

						if ($this->getRequest()->getParam("back")) {
							$this->_redirect("*/*/edit", array("id" => $model->getId()));
							return;
						}
						$this->_redirect("*/*/");
						return;
					} 
					catch (Exception $e) {
						Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
						Mage::getSingleton("adminhtml/session")->setFindifymodelData($this->getRequest()->getPost());
						$this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id")));
					return;
					}

				}
				$this->_redirect("*/*/");
		}



		public function deleteAction()
		{
				if( $this->getRequest()->getParam("id") > 0 ) {
					try {
						$model = Mage::getModel("findifyfeed/findifymodel");
						$model->setId($this->getRequest()->getParam("id"))->delete();
						Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Item was successfully deleted"));
						$this->_redirect("*/*/");
					} 
					catch (Exception $e) {
						Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
						$this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id")));
					}
				}
				$this->_redirect("*/*/");
		}

		
}
