DROP TABLE IF EXISTS `#__webtt_hallen`;
DROP TABLE IF EXISTS `#__webtt_kalender`;
DROP TABLE IF EXISTS `#__webtt_mannsch`;
DROP TABLE IF EXISTS `#__webtt_popups`;
DROP TABLE IF EXISTS `#__webtt_spieler`;
DROP TABLE IF EXISTS `#__webtt_staffeln`;
DROP TABLE IF EXISTS `#__webtt_tabellen`;
DROP TABLE IF EXISTS `#__webtt_teams`;
DROP TABLE IF EXISTS `#__webtt_ttr`;


CREATE TABLE `#__webtt_hallen` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `verein` varchar(50) NOT NULL,
  `verein_nr` int (6) NOT NULL,
  `halle_1` varchar(200) NOT NULL,
  `addr_1` varchar(200) NOT NULL,
  `streetview_1` varchar(200) NOT NULL,
  `halle_2` varchar(200) NOT NULL,
  `addr_2` varchar(200) NOT NULL,
  `streetview_2` varchar(200) NOT NULL,
  `halle_3` varchar(200) NOT NULL,
  `addr_3` varchar(200) NOT NULL,
  `streetview_3` varchar(200) NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE `#__webtt_mannsch` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clicktt` varchar(50) NOT NULL,
  `webtt` varchar(50) NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

INSERT INTO `#__webtt_mannsch` (`clicktt`,`webtt`) VALUES

        ('Herren','1.Herren'),
        ('Herren II','2.Herren'),
        ('Herren III','3.Herren'),
        ('Herren IV','4.Herren'),
        ('Herren V','5.Herren'),

        ('Damen','1.Damen'),
        ('Damen II','2.Damen'),
        ('Damen III','3.Damen'),
        ('Damen IV','4.Damen'),
        ('Damen V','5.Damen'),

        ('Jungen','1.Jungen'),
        ('Jungen II','2.Jungen'),
        ('Jungen III','3.Jungen'),

        ('Mädchen','1.Mädchen'),
        ('Mädchen II','2.Mädchen'),
        ('Mädchen III','3.Mädchen'),

        ('Schüler','1.A-Schüler'),
        ('Schüler II','2.A-Schüler'),
        ('Schüler III','3.A-Schüler'),

        ('Schülerinnen','1.A-Schülerinnen'),
        ('Schülerinnen II','2.A-Schülerinnen'),
        ('Schülerinnen III','3.A-Schülerinnen')

        ;

CREATE TABLE `#__webtt_popups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datum` datetime NULL,
  `typ` varchar(15) NOT NULL,
  `idclicktt` int(10) NOT NULL,
  `staffel` varchar(75) NOT NULL,
  `name` varchar(75) NOT NULL,
  `pfad` varchar(250) NOT NULL,
  `xml` mediumtext NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE `#__webtt_kalender` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datum` datetime NULL,
  `mannschaft` varchar(50) NOT NULL,
  `serie` varchar(6) NOT NULL,
  `vcal` mediumtext NOT NULL,
  `ical` mediumtext NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE `#__webtt_spieler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `mannschaften` varchar(50) NOT NULL,
  `ak` varchar(25) NOT NULL,
  `team` varchar(10) NOT NULL,
  `position` varchar(10) NOT NULL,
  `qttr` int(4) NOT NULL,
  `clicktt_nr` varchar(30) NOT NULL,
  `status` varchar(10) NOT NULL,
  `foto` varchar(200) NOT NULL,
  `beschreibung` varchar(1000) NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE `#__webtt_staffeln` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clicktt_kurz` varchar(10) NOT NULL,
  `clicktt_lang` varchar(50) NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE `#__webtt_tabellen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datum` datetime NULL,
  `typ` varchar(20) NOT NULL,
  `team` varchar(25) NOT NULL,
  `xml` mediumtext NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE `#__webtt_teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `typ` varchar(5) NOT NULL,
  `name_webtt` varchar(50) NOT NULL,
  `name_clicktt` varchar(50) NOT NULL,
  `name_sp_clicktt` varchar(50) NOT NULL,
  `path_clicktt` varchar(200) NOT NULL,
  `league_clicktt` varchar(75) NOT NULL,
  `path_league_clicktt` varchar(200) NOT NULL,
  `sk_verband` varchar(50) NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE `#__webtt_ttr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `typ` varchar(5) NOT NULL,
  `verein_nr` int(10) NOT NULL,
  `datum` datetime NULL,
  `werte` text NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
