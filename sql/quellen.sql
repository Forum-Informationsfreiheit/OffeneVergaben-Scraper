# ************************************************************
# Host: 127.0.0.1 (MySQL 5.6.21)
# Database: vergaben_scraper
# Generation Time: 2020-01-22 20:57:56 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table quellen
# ------------------------------------------------------------

LOCK TABLES `quellen` WRITE;
/*!40000 ALTER TABLE `quellen` DISABLE KEYS */;

INSERT INTO `quellen` (`id`, `alias`, `reference_id`, `active`, `name`, `url`, `created_at`, `last_scraped_at`)
VALUES
	(5,'asfinag','41a809d9-0b61-4991-86b8-74dc07973af3',1,'Asfinag','https://www.provia.at/bieterportal/ExternalProViaOpenDataService/KerndatenQuelle/asfinag','2020-01-22 21:53:00',NULL),
	(6,'evergabe.at','86dd87bd-7426-40c5-946b-62b2af638aab',1,'eVergabe.at','https://evergabe.at/NetServer/kerndaten','2020-01-22 21:53:00',NULL),
	(7,'bmbwf','fd56466b-ced8-47ab-8d1a-9ce798948bad',1,'BMBWF','https://extapp.noc-science.at/apex/shibb/api/vergabe','2020-01-22 21:53:00',NULL),
	(8,'energie burgenland','76efecc9-e509-4c6b-849b-f742ed3e258a',1,'Energie Burgenland','https://vergabe.energieburgenland.at/NetServer/kerndaten','2020-01-22 21:53:00',NULL),
	(9,'bundes beschaffung','009b7802-0b04-4fee-8dac-3922bd5098ae',1,'Bundesbeschaffungs GesmbH','https://opendata.bbg.gv.at/kerndaten/bbg_kerndaten_viii-2-1.xml','2020-01-22 21:53:00',NULL),
	(10,'öbb','505019f6-4c66-4ce4-9700-a5ed3cf664c3',1,'ÖBB','https://www.provia.at/bieterportal/ExternalProViaOpenDataService/KerndatenQuelle/oebb','2020-01-22 21:53:00',NULL),
	(11,'vemap','fde58043-87ff-44b0-b6b0-3d089adfba4c',1,'vemap','https://bekanntmachungen.vemap.com/vemap-kdq-01.xml','2020-01-22 21:53:00',NULL),
	(12,'wienerzeitung','23602b0b-c7b0-4f10-9910-7ee5329433bc',1,'Wiener Zeitung','https://kdq.kerndaten.at','2020-01-22 21:53:00',NULL),
	(13,'ankö','a2c49245-23b2-46e1-acc7-c5a78913a090',1,'ANKÖ Services GesmbH','http://ogd.ankoe.at/api/v1/notices','2020-01-22 21:53:00',NULL),
	(14,'ausschreibung.at','78b227dc-78f6-4085-b4fb-772e040a14e0',1,'Ausschreibung.at','https://www.ausschreibung.at/OpenData/kdq?id=87BA5ED1','2020-01-22 21:53:00',NULL),
	(15,'wko ö','7fffba00-9b87-4fb4-8c71-68399d2a4291',1,'Wirtschaftskammer Österreich','https://apppool.wko.at/data/ab/2/KDQ_%C3%96sterreich.xml','2020-01-22 21:53:00',NULL),
	(16,'wko st','5e84d2ab-b5ca-48c3-b15f-3d71e4bca4ee',1,'Wirtschaftskammer Steiermark','https://apppool.wko.at/data/ab/5/KDQ_Steiermark.xml','2020-01-22 21:53:00',NULL),
	(17,'wko w','e5f51f9d-9ddb-4fc7-95a8-ef0d9eb2144d',1,'Wirtschaftskammer Wien','https://apppool.wko.at/data/ab/0/KDQ_Wien.xml','2020-01-22 21:53:00',NULL),
	(18,'post','39e2a593-d508-4f64-8a04-8d6dbf730f9d',1,'Österreichische Post AG','https://www.post.at/kdq/KDQ.xml','2020-01-22 21:53:00',NULL),
	(19,'wko vo','3a520b08-7351-479c-9232-628727066f55',1,'Wirtschaftskammer Vorarlberg','https://apppool.wko.at/data/ab/8/KDQ_Vorarlberg.xml','2020-01-22 21:53:00',NULL),
	(20,'wko ktn','ef47695d-7aa3-41cf-8c4e-d76aa4dae7d5',1,'Wirtschaftskammer Kärnten','https://apppool.wko.at/data/ab/6/KDQ_K%C3%A4rnten.xml','2020-01-22 21:53:00',NULL),
	(21,'wko oö','9543be56-7309-4788-8de2-fbdd5006282b',1,'Wirtschaftskammer Oberösterreich','https://apppool.wko.at/data/ab/3/KDQ_ober%C3%B6sterreich.xml','2020-01-22 21:53:00',NULL),
	(22,'wko inhouse','7e80bc4b-3537-42fd-881f-d9290b34782e',1,'WKO Inhouse GmbH der Wirtschaftskammern Österreichs','https://apppool.wko.at/data/ab/10/KDQ_WKO%20Inhouse%20GmbH%20der%20Wirtschaftskammern%20%C3%96sterreichs.xml','2020-01-22 21:53:00',NULL),
	(23,'rechungshof','3ad7b1eb-cac6-47b2-b957-ffba17606425',1,'Rechnungshof','http://bekanntmachung.rechnungshof.gv.at/KDQ.xml','2020-01-22 21:53:00',NULL),
	(24,'evn beschaffung','8df6b76b-a1c8-45a4-ab7b-3ff8676e4b04',1,'EVN AG Beschaffung und Einkauf','https://beschaffung.evn.at/cdp/api/kd','2020-01-22 21:53:00',NULL);

/*!40000 ALTER TABLE `quellen` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
