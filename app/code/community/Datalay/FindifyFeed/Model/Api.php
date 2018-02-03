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

        $prodarr = array();
        $i = 0;

        foreach ($_order->getAllItems() AS $item){
            $isconfigurable = 0;
            $eachProductId = $item->getProductId();
            $eachProductType = $item->getProductType();
            $eachProductPrice = $item->getPrice();
            $eachProductQty = $item->getQtyOrdered();
            //$pro=Mage::getModel('catalog/product')->load($eachProductId);
            if ($eachProductType == "simple") {
                $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($eachProductId);
                if (!$parentIds)
                    $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($eachProductId);
                if (isset($parentIds[0]) && $prodarr[$i - 1] && $prodarr[$i - 1]['type'] == "configurable") {
                    $isconfigurable = 1;
                    $childId = $eachProductId;
                    $parent = Mage::getModel('catalog/product')->load($parentIds[0]);
                    $prid = $parent->getId();
                } else
                    $prid = $eachProductId;
            } else
                $prid = $eachProductId;
            $prodarr[$i]['id'] = $prid;
            $prodarr[$i]['type'] = $eachProductType;
            $prodarr[$i]['qty'] = $eachProductQty;
            $prodarr[$i]['price'] = $eachProductPrice;
            $qty = $eachProductQty;
            //Mage::log($prodarr[$i]);
            if ($eachProductType != "configurable") {
                if ($prodarr[$i - 1] && $prodarr[$i - 1]['type'] == "configurable") {
                    $pric = $prodarr[$i - 1]['price'];
                    $prid = $prodarr[$i - 1]['id'];
                    $qty = $prodarr[$i - 1]['qty'];
                } else {
                    $pric = $eachProductPrice;
                    $qty = $eachProductQty;
                }


                $_item_data = array(
                    "item_id" => $prid,
                    "unit_price" => $pric,
                    "quantity" => $qty
                );

                if ($isconfigurable) {
                    $_item_data["variant_item_id"] = $childId;
                }

                $_data["line_items"][$i] = $_item_data;
            }
            $i++;
        }
        return $_data;
    }

    
}
