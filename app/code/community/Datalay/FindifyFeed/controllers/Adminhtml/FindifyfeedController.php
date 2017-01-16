<?php

class Datalay_FindifyFeed_Adminhtml_FindifyfeedController extends Mage_Adminhtml_Controller_Action
{
	public function checkAction()
	{
		$timecreated   = strftime("%Y-%m-%d %H:%M:%S",  mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y")));
		$timescheduled = strftime("%Y-%m-%d %H:%M:%S", mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y")));
		$jobCode = 'findifyfeed_crongeneratefeed';
		//Mage::log('Adding scheduled job - JobCode: '.$jobCode.' - $timecreated: '.$timecreated.' - $timescheduled: '.$timescheduled);
		try {
		        $schedule = Mage::getModel('cron/schedule');
		        $schedule->setJobCode($jobCode)
		                ->setCreatedAt($timecreated)
		                ->setScheduledAt($timescheduled)
		                ->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)
		                ->save();
		} catch (Exception $e) {
		         throw new Exception(Mage::helper('cron')->__('Unable to save Cron expression'));
		}

       		$result = 1;
        	Mage::app()->getResponse()->setBody($result);
	}
		
}
