<?php
class Datalay_FindifyFeed_Model_Mysql4_Findifymodel extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("findifyfeed/findifymodel", "id");
    }
}