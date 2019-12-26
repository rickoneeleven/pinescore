CREATE DATABASE  IF NOT EXISTS `novascore` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `novascore`;
-- MySQL dump 10.13  Distrib 5.7.17, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: novascore
-- ------------------------------------------------------
-- Server version	5.7.24

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `alerts`
--

DROP TABLE IF EXISTS `alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(254) NOT NULL,
  `ping_ip_id` int(11) DEFAULT NULL,
  `unsub_ref` varchar(16) DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alerts`
--

LOCK TABLES `alerts` WRITE;
/*!40000 ALTER TABLE `alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ci_cookies`
--

DROP TABLE IF EXISTS `ci_cookies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ci_cookies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cookie_id` varchar(255) DEFAULT NULL,
  `netid` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `orig_page_requested` varchar(120) DEFAULT NULL,
  `php_session_id` varchar(40) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ci_cookies`
--

LOCK TABLES `ci_cookies` WRITE;
/*!40000 ALTER TABLE `ci_cookies` DISABLE KEYS */;
/*!40000 ALTER TABLE `ci_cookies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ci_sessions`
--

DROP TABLE IF EXISTS `ci_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ci_sessions` (
  `session_id` varchar(40) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `user_agent` varchar(120) NOT NULL,
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0',
  `user_data` text NOT NULL,
  PRIMARY KEY (`session_id`),
  KEY `last_activity_idx` (`last_activity`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ci_sessions`
--

LOCK TABLES `ci_sessions` WRITE;
/*!40000 ALTER TABLE `ci_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ci_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `control`
--

DROP TABLE IF EXISTS `control`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `control` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `status` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `control`
--

LOCK TABLES `control` WRITE;
/*!40000 ALTER TABLE `control` DISABLE KEYS */;
/*!40000 ALTER TABLE `control` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grouped_reports`
--

DROP TABLE IF EXISTS `grouped_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grouped_reports` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `name` varchar(16) DEFAULT NULL,
  `ping_ip_ids` mediumtext,
  `datetime` datetime DEFAULT NULL,
  `owner_id` int(5) DEFAULT NULL,
  `public` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grouped_reports`
--

LOCK TABLES `grouped_reports` WRITE;
/*!40000 ALTER TABLE `grouped_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `grouped_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historic_novascore`
--

DROP TABLE IF EXISTS `historic_novascore`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historic_novascore` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `logged` datetime DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `ms` varchar(5) DEFAULT NULL,
  `novaScore` varchar(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historic_novascore`
--

LOCK TABLES `historic_novascore` WRITE;
/*!40000 ALTER TABLE `historic_novascore` DISABLE KEYS */;
/*!40000 ALTER TABLE `historic_novascore` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `history_email_alerts`
--

DROP TABLE IF EXISTS `history_email_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `history_email_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner` int(11) NOT NULL,
  `note` text NOT NULL,
  `datetime` datetime NOT NULL,
  `status` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `history_email_alerts`
--

LOCK TABLES `history_email_alerts` WRITE;
/*!40000 ALTER TABLE `history_email_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `history_email_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `node_locks`
--

DROP TABLE IF EXISTS `node_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `node_locks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(70) NOT NULL,
  `locked` tinyint(4) NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=175472 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `node_locks`
--

LOCK TABLES `node_locks` WRITE;
/*!40000 ALTER TABLE `node_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `node_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `other`
--

DROP TABLE IF EXISTS `other`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `other` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` tinytext NOT NULL,
  `value` varchar(500) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `other`
--

LOCK TABLES `other` WRITE;
/*!40000 ALTER TABLE `other` DISABLE KEYS */;
INSERT INTO `other` VALUES (1,'percent difference required to highlight on main page','50'),(2,'ms difference required to highlight on main page. is used on conjuction with percent difference','3'),(3,'hasStatusChanged query time','2019-12-23 04:16:10 | query took 0 seconds | updated to: Online | IP: 84.21.152.114'),(4,'perf_dupComplete','2019-12-24 09:26:26 | query took 0 seconds'),(5,'perf_preCheckIP','2019-12-24 09:26:26 | query took 0 seconds'),(6,'perf_checkICMP','2019-12-24 09:26:27 | query took 0 seconds');
/*!40000 ALTER TABLE `other` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `perfmon`
--

DROP TABLE IF EXISTS `perfmon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perfmon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(25) NOT NULL,
  `seconds` smallint(6) NOT NULL,
  `datetime` datetime NOT NULL,
  `other` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `perfmon`
--

LOCK TABLES `perfmon` WRITE;
/*!40000 ALTER TABLE `perfmon` DISABLE KEYS */;
INSERT INTO `perfmon` VALUES (1,'checkICMP, out of loop',0,'2019-12-25 18:05:10','proc id: 5e03a4d6f08a1'),(2,'checkICMP, out of loop',0,'2019-12-25 18:05:13','proc id: 5e03a4d918f5c'),(3,'checkICMP, out of loop',0,'2019-12-25 18:05:15','proc id: 5e03a4db373f4'),(4,'checkICMP, out of loop',0,'2019-12-25 18:05:17','proc id: 5e03a4dd540ec'),(5,'checkICMP, out of loop',0,'2019-12-25 18:05:19','proc id: 5e03a4df7220a'),(6,'checkICMP, out of loop',0,'2019-12-25 18:05:21','proc id: 5e03a4e18e6a6'),(7,'checkICMP, out of loop',0,'2019-12-25 18:05:23','proc id: 5e03a4e3ab352'),(8,'checkICMP, out of loop',0,'2019-12-25 18:05:25','proc id: 5e03a4e5c7881'),(9,'checkICMP, out of loop',0,'2019-12-25 18:05:27','proc id: 5e03a4e7f0556'),(10,'checkICMP, out of loop',0,'2019-12-25 18:05:30','proc id: 5e03a4ea187f4'),(11,'checkICMP, out of loop',0,'2019-12-25 18:05:32','proc id: 5e03a4ec34936'),(12,'checkICMP, out of loop',0,'2019-12-25 18:05:34','proc id: 5e03a4ee5063e'),(13,'checkICMP, out of loop',0,'2019-12-25 18:05:36','proc id: 5e03a4f0781dc'),(14,'checkICMP, out of loop',0,'2019-12-25 18:05:38','proc id: 5e03a4f294047'),(15,'checkICMP, out of loop',0,'2019-12-25 18:05:40','proc id: 5e03a4f4b2c52');
/*!40000 ALTER TABLE `perfmon` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ping_ip_table`
--

DROP TABLE IF EXISTS `ping_ip_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ping_ip_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(70) NOT NULL,
  `last_ran` datetime NOT NULL,
  `note` text NOT NULL,
  `alert` text,
  `last_email_status` varchar(15) DEFAULT NULL,
  `count` int(1) DEFAULT NULL,
  `owner` int(11) NOT NULL,
  `last_ms` smallint(4) DEFAULT NULL,
  `last_online_toggle` datetime NOT NULL,
  `public` int(11) NOT NULL,
  `novaScore` smallint(6) DEFAULT NULL,
  `novaScore_change` datetime DEFAULT NULL,
  `average_daily_ms` smallint(6) DEFAULT NULL,
  `average_longterm_ms` smallint(6) DEFAULT NULL,
  `lta_difference_algo` smallint(6) DEFAULT NULL,
  `count_direction` varchar(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ping_ip_table`
--

LOCK TABLES `ping_ip_table` WRITE;
/*!40000 ALTER TABLE `ping_ip_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `ping_ip_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ping_result_table`
--

DROP TABLE IF EXISTS `ping_result_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ping_result_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(50) NOT NULL,
  `datetime` datetime NOT NULL,
  `result` varchar(15) NOT NULL,
  `ms` int(5) NOT NULL,
  `change` tinyint(1) NOT NULL,
  `email_sent` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `datetime` (`datetime`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ping_result_table`
--

LOCK TABLES `ping_result_table` WRITE;
/*!40000 ALTER TABLE `ping_result_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `ping_result_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats`
--

DROP TABLE IF EXISTS `stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats` (
  `ip` varchar(70) NOT NULL,
  `datetime` datetime NOT NULL,
  `score` tinyint(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats`
--

LOCK TABLES `stats` WRITE;
/*!40000 ALTER TABLE `stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats_total`
--

DROP TABLE IF EXISTS `stats_total`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats_total` (
  `ip` varchar(70) NOT NULL,
  `score` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  UNIQUE KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats_total`
--

LOCK TABLES `stats_total` WRITE;
/*!40000 ALTER TABLE `stats_total` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats_total` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(50) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `lastlogin` datetime DEFAULT NULL,
  `hideOffline` tinyint(4) DEFAULT NULL,
  `default_EA` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verify_email`
--

DROP TABLE IF EXISTS `verify_email`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `verify_email` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` text NOT NULL,
  `code` text NOT NULL,
  `datetime` datetime NOT NULL,
  `password` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verify_email`
--

LOCK TABLES `verify_email` WRITE;
/*!40000 ALTER TABLE `verify_email` DISABLE KEYS */;
/*!40000 ALTER TABLE `verify_email` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-12-25 18:05:41
