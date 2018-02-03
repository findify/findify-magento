<?php

class Datalay_FindifyFeed_Model_Observer extends Varien_Event_Observer
{
    public function prepareLayoutBefore(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        if ("head" == $block->getNameInLayout()) {
	    $block->addExternalItem('external_js','//cdnjs.cloudflare.com/ajax/libs/babel-polyfill/6.23.0/polyfill.min.js');
	    //$block->addJs('polyfill.min.js');
        }else{
            //Mage::log('getNameInLayout is not head. It is: '. $block->getNameInLayout());
        }
        return $this;
    }

	    
    public function afterOrderPlace(Varien_Event_Observer $observer){
        $order = $observer->getEvent()->getOrder();
        Mage::getModel("findifyfeed/api")->sendOrderData($order);
    }
	
}
