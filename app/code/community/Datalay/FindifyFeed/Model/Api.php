<?php

class Datalay_FindifyFeed_Model_Api extends Mage_Core_Model_Abstract
{

    public function sendOrderData($_order) {

        $_url = "https://api-v3.findify.io/v3/feedback";

        /* get order data */
        $_data = array();
        $_data["properties"] = $this->_getOrderData($_order);
        $_data["event"] = "purchase";
        /* add key, user id , session id */
        $storeId = $_order->getStoreId();
        $findifyKey = Mage::getStoreConfig('attributes/analytics/apikey',$storeId);

        $_data["key"] = $findifyKey;
        $_data["t_client"] = Mage::getModel('core/date')->timestamp(time());
        
        /* session value - frontend */
        $session = Mage::getSingleton('core/session', array('name' => 'frontend'));
        $session_id = $session->getSessionId();
        
        /* get _uniq _visit association based on session id */
        $resource = Mage::getSingleton('core/resource');
	$readConnection = $resource->getConnection('core_read');
	$query = "SELECT * FROM " . $resource->getTableName('findify_session') . " where session_id = :session_id;";


        $binds = array(
            'session_id' => $session_id
        );
        
        $results = $readConnection->fetchAll($query, $binds);
	#Mage::log($results);
        
        if(!count($results)){ 
            Mage::log("no results!!!");
            return false;
        }
            
        $_data["user"] = array(
            "uid"=> $results[0]["findify_uniq"] ,
            "sid"=>$results[0]["findify_visit"] 
        );
        
        
        //Mage::log($_data);
        $_query = http_build_query($_data);
        // create a new cURL resource
        $ch = curl_init($_url . "?" . $_query);

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);

        // Execute the request.
        $result = curl_exec($ch);
        $succeeded = curl_errno($ch) == 0 ? true : false;

        // close cURL resource, and free up system resources
        curl_close($ch);

        // If Communication was not successful set error result, otherwise
        if (!$succeeded) {
            Mage::log("api request error");
        }
    }

    protected function _getOrderData($_order) {

        $_data = array();
        $_data["order_id"] = $_order->getIncrementId();
        $_data["currency"] = $_order->getOrderCurrencyCode();
        $_data["revenue"] = $_order->getGrandTotal();

        $_line_items = array();
        /* add only parent items first */
        foreach ($_order->getAllItems() AS $item){
            if(!$item->getParentItemId()){
                $_line_items[ $item->getId()] = array(
                    "item_id" => $item->getProductId(),
                    "unit_price" => $item->getPrice(),
                    "quantity" => $item->getQtyOrdered()
                    );
            }
        }

        /* assign variants for configurable product */
        foreach ($_order->getAllItems() AS $item){
            if($item->getParentItemId() && isset($_line_items[ $item->getParentItemId()]) ){
                /* check if item is configurable */	
                $_parent = $_order->getItemById( $item->getParentItemId() );
                if($_parent->getProductType()=="configurable"){
		    /* add as variant*/
                    $_line_items[ $item->getParentItemId()]["variant_item_id"] = $item->getProductId();
                }else{
			/* add all item data */
                    $_line_items[ $item->getId()] = array(
                        "item_id" => $item->getProductId(),
                        "unit_price" => $item->getPrice(),
                        "quantity" => $item->getQtyOrdered()
                    );
                }
            }
        }

        /* add line items to array, remove keys */
        foreach($_line_items as $_item){
            $_data["line_items"][] = $_item;
        }
	    
        return $_data;
    }

    
}
