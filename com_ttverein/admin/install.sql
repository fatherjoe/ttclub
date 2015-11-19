CREATE TABLE IF NOT EXISTS `#__ttverein_altersklassen` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `maxalter` int(11) default NULL,
  `minalter` int(11) default NULL,
  `reihenfolge` tinyint(4) NOT NULL default '99',
  PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS `#__ttverein_aufstellungen` (
  `mannschafts_id` int(11) NOT NULL,
  `spieler_id` int(11) NOT NULL,
  `position` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`mannschafts_id`,`spieler_id`),
  KEY `spieler_id` (`spieler_id`)
);

CREATE TABLE IF NOT EXISTS `#__ttverein_config` (
  `name` varchar(255) NOT NULL,
  `value` varchar(255) default NULL,
  `show_in_config` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`name`)
);

CREATE TABLE IF NOT EXISTS `#__ttverein_felder` (
  `id` int(11) NOT NULL auto_increment,
  `name_backend` varchar(100) NOT NULL,
  `name_frontend` varchar(100) NOT NULL,
  `typ` set('text','email','telefon','datum','jahre seit') NOT NULL default 'text',
  `tooltip` varchar(255) default NULL,
  `zeige_in_uebersicht` tinyint(1) NOT NULL default '0',
  `reihenfolge` tinyint(2) NOT NULL default '99',
  PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS `#__ttverein_ligen` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `reihenfolge` tinyint(4) NOT NULL default '99',
  PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS `#__ttverein_mannschaften` (
  `id` int(11) NOT NULL auto_increment,
  `nummer` int(11) NOT NULL,
  `saisonstart` year(4) NOT NULL,
  `altersklasse` int(11) NOT NULL,
  `published` tinyint(1) NOT NULL default '1',
  `hinrunde` tinyint(1) NOT NULL default '1',
  `mannschaftsfuehrer` int(11) default NULL,
  `image_orginal` varchar(255) default NULL,
  `image_resize` varchar(255) default NULL,
  `image_thumb` varchar(255) default NULL,
  `image_text` text,
  `liga` int(11) default NULL,
  `clicktt_championship` varchar(127) default NULL,
  `clicktt_group` int(11) default NULL,
  `clicktt_teamtable` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `altersklasse` (`altersklasse`)
);

CREATE TABLE IF NOT EXISTS `#__ttverein_spieler` (
  `id` int(11) NOT NULL auto_increment,
  `vorname` varchar(100) NOT NULL,
  `nachname` varchar(100) NOT NULL,
  `published_gebutstag` tinyint(1) NOT NULL default '0',
  `published` tinyint(1) NOT NULL default '1',
  `image_orginal` varchar(255) default NULL,
  `image_resize` varchar(255) default NULL,
  `image_thumb` varchar(255) default NULL,
  `clicktt_person_id` int(11) default NULL,
  PRIMARY KEY  (`id`)
);

CREATE TABLE IF NOT EXISTS `#__ttverein_spieler_felder` (
  `felder_id` int(11) NOT NULL,
  `spieler_id` int(11) NOT NULL,
  `kurz_text` varchar(255) default NULL,
  `datum` date default NULL,
  `text` text,
  PRIMARY KEY  (`felder_id`,`spieler_id`)
);
