<?php

$installer = $this;

$installer->startSetup();

$installer->run(
    "DROP TABLE IF EXISTS {$this->getTable('findify_session')};
CREATE TABLE {$this->getTable('findify_session')} (
  `findify_uniq` varchar(255) NOT NULL default '',
  `findify_visit` varchar(255) NOT NULL default '',
  `session_id` varchar(255) NOT NULL default '',
  `created_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`findify_uniq`, `findify_visit`, `session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
);

$installer->endSetup(); 
