-- Migration / Setup script for Log Manager
-- Run this if applying updates or executing initial setup on an existing DB

-- Create tables
CREATE TABLE IF NOT EXISTS `log_sources` (
    `source_id` INT UNSIGNED AUTO_INCREMENT,
    `hostname` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `vendor` VARCHAR(64) DEFAULT 'Unknown',
    `first_seen` DATETIME NOT NULL,
    `last_seen` DATETIME NOT NULL,
    `enabled` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`source_id`),
    UNIQUE KEY `uk_ip_hostname` (`ip_address`, `hostname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE IF NOT EXISTS `network_logs` (
    `log_id` BIGINT UNSIGNED AUTO_INCREMENT,
    `source_id` INT UNSIGNED DEFAULT NULL,
    `received_at` DATETIME(3) NOT NULL,
    `severity` TINYINT UNSIGNED NOT NULL,
    `facility` TINYINT UNSIGNED NOT NULL,
    `hostname` VARCHAR(255) NOT NULL,
    `source_ip` VARCHAR(45) NOT NULL,
    `message` TEXT NOT NULL,
    `raw_message` TEXT NOT NULL,
    PRIMARY KEY (`log_id`),
    KEY `idx_received_at` (`received_at`),
    KEY `idx_source_id` (`source_id`),
    KEY `idx_severity` (`severity`),
    KEY `idx_facility` (`facility`),
    FULLTEXT KEY `idx_message_fulltext` (`message`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE IF NOT EXISTS `log_alert_rules` (
    `rule_id` INT UNSIGNED AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `regex_pattern` VARCHAR(512) NOT NULL,
    `severity` TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `enabled` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE IF NOT EXISTS `log_alert_history` (
    `alert_id` BIGINT UNSIGNED AUTO_INCREMENT,
    `rule_id` INT UNSIGNED NOT NULL,
    `log_id` BIGINT UNSIGNED NOT NULL,
    `matched_at` DATETIME NOT NULL,
    PRIMARY KEY (`alert_id`),
    KEY `idx_rule_id` (`rule_id`),
    KEY `idx_log_id` (`log_id`),
    KEY `idx_matched_at` (`matched_at`),
    FOREIGN KEY (`rule_id`) REFERENCES `log_alert_rules` (`rule_id`) ON DELETE CASCADE,
    FOREIGN KEY (`log_id`) REFERENCES `network_logs` (`log_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE IF NOT EXISTS `log_retention` (
    `retention_days` INT UNSIGNED NOT NULL DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

INSERT INTO `log_retention` (`retention_days`) SELECT 30 WHERE NOT EXISTS (SELECT * FROM `log_retention`);
