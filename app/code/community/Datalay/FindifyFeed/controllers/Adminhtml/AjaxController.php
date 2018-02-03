<?php

class Datalay_FindifyFeed_AjaxController extends Mage_Core_Controller_Front_Action {
    
    public function refreshAction(){
        /* submitted values */
        $findify_uniq = $this->getRequest()->getParam('_findify_uniq', false);
        $findify_visit = $this->getRequest()->getParam('_findify_visit', false);
        /* session value */
        $session = Mage::getSingleton('core/session', array('name' => $this->_sessionNamespace));
        $session_id = $session->getSessionId();
        /* associate in database */
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('findify_session');

        $query=   "delete from $tableName where findify_uniq = :findify_uniq "
            . " or findify_visit = :findify_visit or session_id = :session_id; "
            . "insert into $tableName(findify_uniq, findify_visit, session_id) "
            . " values(:findify_uniq, :findify_visit,:session_id)";
        
        $binds = array(
            'findify_uniq' => $findify_uniq,
            'findify_visit' => $findify_visit,
            'session_id' => $session_id
        );

        $writeConnection->query($query, $binds);
       
    }
    
}

