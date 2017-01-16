<?php
class Datalay_FindifyFeed_Model_Adminhtml_System_Config_Backend_Findifymodel_Cron extends Mage_Core_Model_Config_Data
{
    const CRON_STRING_PATH = 'crontab/jobs/findifyfeed_crongeneratefeed/schedule/cron_expr';
 
    protected function _afterSave()
    {
        $hour = $this->getData('groups/schedule/fields/cronhour/value');
        $minutes = $this->getData('groups/schedule/fields/cronminutes/value');
       
        // add basic checks here (no spaces, only digits, comma, / and *, etc)
        $cronpattern = '/^(?:[1-9]?\d|\*)(?:(?:[\/-][1-9]?\d)|(?:,[1-9]?\d)+)?$/';

        if((!preg_match($cronpattern,$hour)) || ($hour == '*') || is_null($hour) || ($hour == '')){
            $hour = '1';
        }

        if((!preg_match($cronpattern,$minutes)) || ($minutes == '*') || is_null($minutes) || ($minutes == '')){
            $minutes = '30';
        }
 
        //$frequencyWeekly = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_WEEKLY;
        //$frequencyMonthly = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_MONTHLY;
 
        $cronExprArray = array(
            $minutes,				# Minute
            $hour,                              # Hour
            '*',				# Day of the Month
            '*',                                # Month of the Year
            '*',				# Day of the Week
        );
        $cronExprString = join(' ', $cronExprArray);
 
        //Mage::log('$cronExprString: '.$cronExprString);

        try {
            Mage::getModel('core/config_data')
                ->load(self::CRON_STRING_PATH, 'path')
                ->setValue($cronExprString)
                ->setPath(self::CRON_STRING_PATH)
                ->save();
        }
        catch (Exception $e) {
            throw new Exception(Mage::helper('cron')->__('Unable to save the cron expression.'));
        }
    }
}
