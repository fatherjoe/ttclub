--
-- Table Tennis Club Manager (com_ttclub) - Uninstallation SQL
-- Drop tables in correct order to respect foreign key constraints
--

DROP TABLE IF EXISTS `#__ttclub_player_club_ids`;
DROP TABLE IF EXISTS `#__ttclub_import_logs`;
DROP TABLE IF EXISTS `#__ttclub_schedule_cache`;
DROP TABLE IF EXISTS `#__ttclub_ranking_cache`;
DROP TABLE IF EXISTS `#__ttclub_schedules`;
DROP TABLE IF EXISTS `#__ttclub_rosters`;
DROP TABLE IF EXISTS `#__ttclub_team_photos`;
DROP TABLE IF EXISTS `#__ttclub_player_images`;
DROP TABLE IF EXISTS `#__ttclub_teams`;
DROP TABLE IF EXISTS `#__ttclub_half_seasons`;
DROP TABLE IF EXISTS `#__ttclub_seasons`;
DROP TABLE IF EXISTS `#__ttclub_age_classes`;
DROP TABLE IF EXISTS `#__ttclub_leagues`;
DROP TABLE IF EXISTS `#__ttclub_club_ids`;
DROP TABLE IF EXISTS `#__ttclub_players`;
