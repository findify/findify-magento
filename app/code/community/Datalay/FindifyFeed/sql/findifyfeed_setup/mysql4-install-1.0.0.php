<?php
$installer = $this;
$installer->startSetup();
$sql=<<<SQLTEXT
create table IF NOT EXISTS findify(findify_id int not null auto_increment, name varchar(100), primary key(findify_id));
    insert ignore into findify values(1,'findify1');
    insert ignore into findify values(2,'findify2');
SQLTEXT;

//$installer->run($sql);

//Mage::getModel('core/url_rewrite')->setId(null);

//$io = new Varien_Io_File();
//$io->checkAndCreateFolder(Mage::getBaseDir('media').DS.'findify');

$installer->endSetup();
	 