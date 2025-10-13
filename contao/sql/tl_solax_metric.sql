CREATE TABLE `tl_solax_metric` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `metric_key` varchar(255) NOT NULL default '',
  `metric_value` varchar(255) NOT NULL default '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `metric_key` (`metric_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
