SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `foodCache`
--

CREATE TABLE IF NOT EXISTS `foodCache` (
  `timestamp` date NOT NULL,
  `dataSource` varchar(128) NOT NULL,
  `date` varchar(128) NOT NULL,
  `price` varchar(128) NOT NULL,
  `data` varchar(16384) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `foodCustomUser`
--

CREATE TABLE IF NOT EXISTS `foodCustomUser` (
  `ip` varchar(128) NOT NULL,
  `custom_userid` varchar(128) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `foodUser`
--

CREATE TABLE IF NOT EXISTS `foodUser` (
  `ip` varchar(128) NOT NULL,
  `name` varchar(128) NOT NULL,
  `email` varchar(128) DEFAULT NULL,
  `vote_reminder` tinyint(1) NOT NULL DEFAULT '0',
  `voted_mail_only` tinyint(1) NOT NULL DEFAULT '0',
  `vote_always_show` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `foodVote`
--

CREATE TABLE IF NOT EXISTS `foodVote` (
  `day` date NOT NULL,
  `ip` varchar(128) NOT NULL,
  `category` varchar(128) NOT NULL,
  `vote` varchar(128) CHARACTER SET utf8mb4 NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `foodCache`
--
ALTER TABLE `foodCache`
  ADD PRIMARY KEY (`timestamp`,`dataSource`);

--
-- Indizes für die Tabelle `foodCustomUser`
--
ALTER TABLE `foodCustomUser`
  ADD PRIMARY KEY (`ip`);

--
-- Indizes für die Tabelle `foodUser`
--
ALTER TABLE `foodUser`
  ADD PRIMARY KEY (`ip`);

--
-- Indizes für die Tabelle `foodVote`
--
ALTER TABLE `foodVote`
  ADD PRIMARY KEY (`day`,`ip`,`category`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
