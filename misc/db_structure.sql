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
-- Table structure for table `foodCache`
--

DROP TABLE IF EXISTS `foodCache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `foodCache` (
  `venue` varchar(32) NOT NULL,
  `date` date NOT NULL,
  `changed` datetime NOT NULL,
  `data` mediumtext CHARACTER SET utf8mb4 NOT NULL,
  `price` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`venue`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `foodUser`
--

DROP TABLE IF EXISTS `foodUser`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `foodUser` (
  `ip` varchar(128) CHARACTER SET utf8mb4 NOT NULL,
  `name` varchar(128) CHARACTER SET utf8mb4 NOT NULL,
  `email` varchar(128) CHARACTER SET utf8mb4 DEFAULT NULL,
  `vote_reminder` tinyint(1) NOT NULL DEFAULT '0',
  `voted_mail_only` tinyint(1) NOT NULL DEFAULT '0',
  `vote_always_show` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `foodVote`
--

DROP TABLE IF EXISTS `foodVote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `foodVote` (
  `day` date NOT NULL,
  `ip` varchar(64) CHARACTER SET utf8mb4 NOT NULL,
  `category` varchar(128) CHARACTER SET utf8mb4 NOT NULL,
  `vote` varchar(128) CHARACTER SET utf8mb4 NOT NULL,
  PRIMARY KEY (`day`,`ip`,`category`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

--
-- Table structure for table `foodVote`
--

DROP TABLE IF EXISTS `foodBanner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `foodBanner` (
  `venue` varchar(128) CHARACTER SET utf8mb4,
  `weekday` tinyint(1) NOT NULL,
  `url` varchar(128) CHARACTER SET utf8mb4 NOT NULL,
  PRIMARY KEY (`weekday`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
