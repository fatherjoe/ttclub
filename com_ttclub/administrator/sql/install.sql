--
-- Table Tennis Club Manager (com_ttclub) - Installation SQL
--

CREATE TABLE IF NOT EXISTS `#__ttclub_players` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `published` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME NOT NULL,
    `modified` DATETIME NOT NULL,
    `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
    `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    INDEX `idx_last_name` (`last_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ttclub_leagues` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `published` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME NOT NULL,
    `modified` DATETIME NOT NULL,
    `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
    `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uix_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ttclub_age_classes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `max_age` INT UNSIGNED NULL,
    `published` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME NOT NULL,
    `modified` DATETIME NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ttclub_seasons` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `start_year` SMALLINT UNSIGNED NOT NULL,
    `label` VARCHAR(50) NOT NULL DEFAULT '',
    `published` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME NOT NULL,
    `modified` DATETIME NOT NULL,
    `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
    `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uix_start_year_label` (`start_year`, `label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ttclub_half_seasons` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `season_id` INT UNSIGNED NOT NULL,
    `half` TINYINT(1) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uix_season_half` (`season_id`, `half`),
    CONSTRAINT `fk_half_seasons_season` FOREIGN KEY (`season_id`)
        REFERENCES `#__ttclub_seasons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ttclub_teams` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `season_id` INT UNSIGNED NOT NULL,
    `league_id` INT UNSIGNED NOT NULL,
    `age_class_id` INT UNSIGNED NOT NULL,
    `club_id_source` INT UNSIGNED NULL,
    `championship_id` VARCHAR(100) NULL,
    `group_id` VARCHAR(20) NULL,
    `teamtable_id` VARCHAR(20) NULL,
    `team_number` INT UNSIGNED NOT NULL,
    `published` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME NOT NULL,
    `modified` DATETIME NOT NULL,
    `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
    `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    INDEX `idx_season` (`season_id`),
    CONSTRAINT `fk_teams_season` FOREIGN KEY (`season_id`)
        REFERENCES `#__ttclub_seasons` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_teams_league` FOREIGN KEY (`league_id`)
        REFERENCES `#__ttclub_leagues` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_teams_age_class` FOREIGN KEY (`age_class_id`)
        REFERENCES `#__ttclub_age_classes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ttclub_player_images` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `player_id` INT UNSIGNED NOT NULL,
    `half_season_id` INT UNSIGNED NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `created` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uix_player_half_season` (`player_id`, `half_season_id`),
    CONSTRAINT `fk_player_images_player` FOREIGN KEY (`player_id`)
        REFERENCES `#__ttclub_players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_player_images_half_season` FOREIGN KEY (`half_season_id`)
        REFERENCES `#__ttclub_half_seasons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ttclub_team_photos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `team_id` INT UNSIGNED NOT NULL,
    `half_season_id` INT UNSIGNED NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `created` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uix_team_half_season` (`team_id`, `half_season_id`),
    CONSTRAINT `fk_team_photos_team` FOREIGN KEY (`team_id`)
        REFERENCES `#__ttclub_teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_team_photos_half_season` FOREIGN KEY (`half_season_id`)
        REFERENCES `#__ttclub_half_seasons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ttclub_rosters` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `player_id` INT UNSIGNED NOT NULL,
    `team_id` INT UNSIGNED NOT NULL,
    `half_season_id` INT UNSIGNED NOT NULL,
    `position` INT UNSIGNED NULL,
    `created` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uix_player_team_halfseason` (`player_id`, `team_id`, `half_season_id`),
    INDEX `idx_team_halfseason` (`team_id`, `half_season_id`),
    CONSTRAINT `fk_rosters_player` FOREIGN KEY (`player_id`)
        REFERENCES `#__ttclub_players` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_rosters_team` FOREIGN KEY (`team_id`)
        REFERENCES `#__ttclub_teams` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_rosters_half_season` FOREIGN KEY (`half_season_id`)
        REFERENCES `#__ttclub_half_seasons` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ttclub_schedules` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `team_id` INT UNSIGNED NOT NULL,
    `season_id` INT UNSIGNED NOT NULL,
    `match_date` DATE NOT NULL,
    `match_time` TIME NULL,
    `opponent` VARCHAR(150) NOT NULL,
    `venue` VARCHAR(200) NOT NULL,
    `home_away` TINYINT(1) NOT NULL,
    `result` VARCHAR(20) NULL,
    `published` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME NOT NULL,
    `modified` DATETIME NOT NULL,
    `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
    `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    INDEX `idx_team_season_date` (`team_id`, `season_id`, `match_date`),
    CONSTRAINT `fk_schedules_team` FOREIGN KEY (`team_id`)
        REFERENCES `#__ttclub_teams` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_schedules_season` FOREIGN KEY (`season_id`)
        REFERENCES `#__ttclub_seasons` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ttclub_import_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `import_date` DATETIME NOT NULL,
    `import_type` VARCHAR(50) NOT NULL,
    `records_created` INT UNSIGNED NOT NULL DEFAULT 0,
    `records_updated` INT UNSIGNED NOT NULL DEFAULT 0,
    `records_unchanged` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` TINYINT(1) NOT NULL,
    `message` TEXT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `#__ttclub_player_club_ids` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `player_id` INT UNSIGNED NOT NULL,
    `club_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uix_player_club` (`player_id`, `club_id`),
    CONSTRAINT `fk_player_club_player` FOREIGN KEY (`player_id`)
        REFERENCES `#__ttclub_players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_player_club_club` FOREIGN KEY (`club_id`)
        REFERENCES `#__ttclub_club_ids` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `#__ttclub_schedule_cache` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `team_id` INT UNSIGNED NOT NULL,
    `half_season_id` INT UNSIGNED NOT NULL,
    `schedule_data` TEXT NOT NULL,
    `fetched_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uix_team_halfseason` (`team_id`, `half_season_id`),
    CONSTRAINT `fk_schedule_cache_team` FOREIGN KEY (`team_id`)
        REFERENCES `#__ttclub_teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_schedule_cache_half_season` FOREIGN KEY (`half_season_id`)
        REFERENCES `#__ttclub_half_seasons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Seed data: Pre-fill seasons for the last 5 years
--
INSERT INTO `#__ttclub_seasons` (`start_year`, `label`, `published`, `created`, `modified`, `created_by`, `modified_by`) VALUES
(2021, '', 1, NOW(), NOW(), 0, 0),
(2022, '', 1, NOW(), NOW(), 0, 0),
(2023, '', 1, NOW(), NOW(), 0, 0),
(2024, '', 1, NOW(), NOW(), 0, 0),
(2025, '', 1, NOW(), NOW(), 0, 0);

INSERT INTO `#__ttclub_half_seasons` (`season_id`, `half`) VALUES
((SELECT `id` FROM `#__ttclub_seasons` WHERE `start_year` = 2021 AND `label` = ''), 1),
((SELECT `id` FROM `#__ttclub_seasons` WHERE `start_year` = 2021 AND `label` = ''), 2),
((SELECT `id` FROM `#__ttclub_seasons` WHERE `start_year` = 2022 AND `label` = ''), 1),
((SELECT `id` FROM `#__ttclub_seasons` WHERE `start_year` = 2022 AND `label` = ''), 2),
((SELECT `id` FROM `#__ttclub_seasons` WHERE `start_year` = 2023 AND `label` = ''), 1),
((SELECT `id` FROM `#__ttclub_seasons` WHERE `start_year` = 2023 AND `label` = ''), 2),
((SELECT `id` FROM `#__ttclub_seasons` WHERE `start_year` = 2024 AND `label` = ''), 1),
((SELECT `id` FROM `#__ttclub_seasons` WHERE `start_year` = 2024 AND `label` = ''), 2),
((SELECT `id` FROM `#__ttclub_seasons` WHERE `start_year` = 2025 AND `label` = ''), 1),
((SELECT `id` FROM `#__ttclub_seasons` WHERE `start_year` = 2025 AND `label` = ''), 2);
