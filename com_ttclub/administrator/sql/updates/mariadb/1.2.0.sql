-- com_ttclub 1.2.0 - Add schedule_cache table

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
