<?php
class Datalay_FindifyFeed_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function getFeedUrl()
    {
        $mediapath = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);

        $storeId = Mage_Core_Model_App::ADMIN_STORE_ID;
        $storeCode = (string)Mage::getSingleton('adminhtml/config_data')->getStore();
        $websiteCode = (string)Mage::getSingleton('adminhtml/config_data')->getWebsite();
        if ('' !== $storeCode) { // store level
            try {
                $storeId = Mage::getModel('core/store')->load( $storeCode )->getId();
            } catch (Exception $ex) {  }
        } elseif ('' !== $websiteCode) { // website level
            try {
                $storeId = Mage::getModel('core/website')->load( $websiteCode )->getDefaultStore()->getId();
            } catch (Exception $ex) {  }
        }

        $configfilename = Mage::getStoreConfig('attributes/feedinfo/feedfilename',$storeId);
        $filename = str_replace("/", "", $configfilename);

        if(empty($filename)){
            $storeCode = Mage::app()->getStore($storeId)->getCode();
            $filename = 'jsonl_feed-'.$storeCode;
        }

        $fileurl = $mediapath.'findify/'.$filename.'.gz';

        return (string) $fileurl;
    }
    
    
    public function getFeedFileDate()
    {
        $storeId = Mage_Core_Model_App::ADMIN_STORE_ID;
        $storeCode = (string)Mage::getSingleton('adminhtml/config_data')->getStore();
        $websiteCode = (string)Mage::getSingleton('adminhtml/config_data')->getWebsite();
        if ('' !== $storeCode) { // store level
            try {
                $storeId = Mage::getModel('core/store')->load( $storeCode )->getId();
            } catch (Exception $ex) {  }
        } elseif ('' !== $websiteCode) { // website level
            try {
                $storeId = Mage::getModel('core/website')->load( $websiteCode )->getDefaultStore()->getId();
            } catch (Exception $ex) {  }
        }

        $mediapath = Mage::getBaseDir('media');

        $configfilename = Mage::getStoreConfig('attributes/feedinfo/feedfilename',$storeId);
        $filename = str_replace("/", "", $configfilename);

        if(empty($filename)){
            $storeCode = Mage::app()->getStore($storeId)->getCode();
            $filename = 'jsonl_feed-'.$storeCode;
        }

        $filepath = $mediapath.'/findify/'.$filename.'.gz';

        if (file_exists($filepath)) {
            $timezone = Mage::getStoreConfig('general/locale/timezone');
            date_default_timezone_set($timezone);
            return date("F d Y H:i:s", filemtime($filepath));
        }else{
            return "$filepath does not exist yet";
        }
    }
    
    public function getFeedIsRunning()
    {
    	$pendingSchedules = Mage::getModel('cron/schedule')->getCollection()
	    ->addFieldToFilter('job_code', array('eq' => 'findifyfeed_crongeneratefeed'))
            ->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_RUNNING)
            ->addFieldToFilter('executed_at', array('neq' => 'NULL'))
            ->load();

	//Mage::log('pendingSchedules: '.$pendingSchedules->getSize());
	if($pendingSchedules->getSize() > 0) {
	    $isRunning = "Yes";
	}else{
	    $isRunning = "No";
        }

	return $isRunning;
    
    }
    

}
	 