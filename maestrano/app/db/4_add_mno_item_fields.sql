ALTER TABLE  `0_stock_master` ADD  `ref` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE  `0_item_codes` ADD  `mno_id` VARCHAR( 255 ) NULL DEFAULT NULL ;
ALTER TABLE  `0_purch_data` ADD `mno_id` VARCHAR( 255 ) NULL DEFAULT NULL ;
ALTER TABLE  `0_prices` ADD `mno_id` VARCHAR( 255 ) NULL DEFAULT NULL ;

ALTER TABLE  `0_purch_data` DROP PRIMARY KEY, ADD UNIQUE KEY(`supplier_id`,`stock_id`);
ALTER TABLE  `0_purch_data` ADD `id` INT auto_increment PRIMARY KEY;