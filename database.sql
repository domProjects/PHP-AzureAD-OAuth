CREATE TABLE `tblauthsessions` (
  `intAuthID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `txtSessionKey` varchar(255) DEFAULT NULL,
  `dtExpires` datetime DEFAULT NULL,
  `txtRedir` varchar(255) DEFAULT NULL,
  `txtRefreshToken` text,
  `txtCodeVerifier` varchar(255) DEFAULT NULL,
  `txtToken` text,
  `txtIDToken` text,
  PRIMARY KEY (`intAuthID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;