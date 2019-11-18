-- --------------------------------------------------------
-- Host:                         localhost
-- Server version:               5.7.24 - MySQL Community Server (GPL)
-- Server OS:                    Win64
-- HeidiSQL Version:             10.2.0.5599
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


-- Dumping database structure for novascore
CREATE DATABASE IF NOT EXISTS `novascore` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `novascore`;

-- Dumping structure for table novascore.alerts
CREATE TABLE IF NOT EXISTS `alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(254) NOT NULL,
  `ping_ip_id` int(11) DEFAULT NULL,
  `unsub_ref` varchar(16) DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=313 DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table novascore.ci_cookies
CREATE TABLE IF NOT EXISTS `ci_cookies` (
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

-- Data exporting was unselected.

-- Dumping structure for table novascore.ci_sessions
CREATE TABLE IF NOT EXISTS `ci_sessions` (
  `session_id` varchar(40) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `user_agent` varchar(120) NOT NULL,
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0',
  `user_data` text NOT NULL,
  PRIMARY KEY (`session_id`),
  KEY `last_activity_idx` (`last_activity`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table novascore.control
CREATE TABLE IF NOT EXISTS `control` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `status` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3719 DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table novascore.grouped_reports
CREATE TABLE IF NOT EXISTS `grouped_reports` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `name` varchar(16) DEFAULT NULL,
  `ping_ip_ids` mediumtext,
  `datetime` datetime DEFAULT NULL,
  `owner_id` int(5) DEFAULT NULL,
  `public` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=67 DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table novascore.historic_novascore
CREATE TABLE IF NOT EXISTS `historic_novascore` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `logged` datetime DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `ms` varchar(5) DEFAULT NULL,
  `novaScore` varchar(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=594467 DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table novascore.history_email_alerts
CREATE TABLE IF NOT EXISTS `history_email_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner` int(11) NOT NULL,
  `note` text NOT NULL,
  `datetime` datetime NOT NULL,
  `status` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7669 DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table novascore.node_locks
CREATE TABLE IF NOT EXISTS `node_locks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(70) NOT NULL,
  `locked` tinyint(4) NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=152941489 DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table novascore.other
CREATE TABLE IF NOT EXISTS `other` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` tinytext NOT NULL,
  `value` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table novascore.ping_ip_table
CREATE TABLE IF NOT EXISTS `ping_ip_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(70) NOT NULL,
  `last_ran` datetime NOT NULL,
  `note` text NOT NULL,
  `alert` text NOT NULL,
  `last_email_status` varchar(15) NOT NULL,
  `count` int(1) NOT NULL,
  `owner` int(11) NOT NULL,
  `last_ms` smallint(4) NOT NULL,
  `last_online_toggle` datetime NOT NULL,
  `public` int(11) NOT NULL,
  `novaScore` smallint(6) DEFAULT NULL,
  `novaScore_change` datetime DEFAULT NULL,
  `average_daily_ms` smallint(6) DEFAULT NULL,
  `average_longterm_ms` smallint(6) DEFAULT NULL,
  `lta_difference_algo` smallint(6) DEFAULT NULL,
  `count_direction` varchar(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=580 DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table novascore.ping_result_table
CREATE TABLE IF NOT EXISTS `ping_result_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(50) NOT NULL,
  `datetime` datetime NOT NULL,
  `result` varchar(15) NOT NULL,
  `ms` int(5) NOT NULL,
  `change` tinyint(1) NOT NULL,
  `email_sent` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `datetime` (`datetime`)
) ENGINE=MyISAM AUTO_INCREMENT=1610919919 DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table novascore.stats
CREATE TABLE IF NOT EXISTS `stats` (
  `ip` varchar(70) NOT NULL,
  `datetime` datetime NOT NULL,
  `score` tinyint(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table novascore.stats_total
CREATE TABLE IF NOT EXISTS `stats_total` (
  `ip` varchar(70) NOT NULL,
  `score` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  UNIQUE KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table novascore.user
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(50) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `lastlogin` datetime NOT NULL,
  `hideOffline` tinyint(4) NOT NULL,
  `default_EA` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table novascore.verify_email
CREATE TABLE IF NOT EXISTS `verify_email` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` text NOT NULL,
  `code` text NOT NULL,
  `datetime` datetime NOT NULL,
  `password` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=58 DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
