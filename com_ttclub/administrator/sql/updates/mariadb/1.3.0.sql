-- com_ttclub 1.3.0 - Add player_club_ids junction table

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
