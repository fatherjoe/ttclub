-- com_ttclub 1.1.0 - Add club_ids and ranking_cache tables, add club_id_source to teams

-- Create club_ids table with full club information
CREATE TABLE IF NOT EXISTS `#__ttclub_club_ids` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `click_tt_club_id` INT UNSIGNED NOT NULL,
    `legacy_club_id` INT UNSIGNED NULL,
    `club_name` VARCHAR(200) NOT NULL DEFAULT '',
    `federation` VARCHAR(20) NOT NULL DEFAULT '',
    `label` VARCHAR(100) NOT NULL,
    `ordering` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create ranking_cache table
CREATE TABLE IF NOT EXISTS `#__ttclub_ranking_cache` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `team_id` INT UNSIGNED NOT NULL,
    `half_season_id` INT UNSIGNED NOT NULL,
    `ranking_html` TEXT NOT NULL,
    `fetched_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uix_team_halfseason` (`team_id`, `half_season_id`),
    CONSTRAINT `fk_ranking_cache_team` FOREIGN KEY (`team_id`)
        REFERENCES `#__ttclub_teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ranking_cache_half_season` FOREIGN KEY (`half_season_id`)
        REFERENCES `#__ttclub_half_seasons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add club_id_source column to teams table
ALTER TABLE `#__ttclub_teams`
    ADD COLUMN `club_id_source` INT UNSIGNED NULL AFTER `age_class_id`;
