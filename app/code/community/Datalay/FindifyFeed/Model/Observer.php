<?php

class Datalay_FindifyFeed_Model_Observer
{
    public function checkMessages($observer)
    {
        $notifications = Mage::getSingleton('findifyfeed/notification');
        //$notifications->addMessage("Findify Feed extension: <strong>new version available</strong>. Please visit <a href=\"https://github.com/findify\">our extensions website</a> to update");
        return $observer;
    }
}
