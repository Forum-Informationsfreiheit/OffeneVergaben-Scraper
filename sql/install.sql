# create database
CREATE DATABASE `vergaben_scraper` DEFAULT CHARACTER SET `utf8mb4`;

# create quellen table
CREATE TABLE `vergaben_scraper`.`quellen` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `alias` varchar(50) NOT NULL,
  `reference_id` varchar(72) DEFAULT NULL COMMENT 'Eindeutiger Identifkator des Metadatensatzes auf data.gv.at',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `name` varchar(255) DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `last_scraped_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alias` (`alias`),
  UNIQUE KEY `reference_id` (`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

# create kerndaten table
CREATE TABLE `vergaben_scraper`.`kerndaten` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `quelle` varchar(20) NOT NULL,
  `version` int(11) NOT NULL DEFAULT '1',
  `item_id` varchar(72) DEFAULT NULL,
  `item_url` varchar(500) DEFAULT NULL,
  `item_lastmod` timestamp(6) NULL DEFAULT NULL,
  `xml` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kerndaten_quelle_foreign` (`quelle`),
  CONSTRAINT `kerndaten_quelle_foreign` FOREIGN KEY (`quelle`) REFERENCES `quellen` (`alias`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;