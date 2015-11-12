-- ttclub.install.sql:
-- create tables for the com_ttclub component
--
--

CREATE TABLE IF NOT EXISTS `#__ttclub_teams` (
  `club_id` int(11) NOT NULL AUTO_INCREMENT,
  `clicktt_club` int(11) NOT NULL,
  PRIMARY KEY (`club_id`)
);
