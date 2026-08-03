CREATE TABLE IF NOT EXISTS `neolims_bridge_dead_letter` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `workflow_uuid` CHAR(36) NOT NULL,
  `connection_key` VARCHAR(100) NOT NULL,
  `external_id` VARCHAR(190) NOT NULL,
  `accession_number` VARCHAR(100) NOT NULL DEFAULT '',
  `current_step` VARCHAR(64) NOT NULL,
  `payload_json` LONGTEXT NOT NULL,
  `payload_hash` CHAR(64) NOT NULL,
  `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_error` TEXT DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'open',
  `resolution_note` TEXT DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_neolims_dead_letter_uuid` (`workflow_uuid`),
  KEY `idx_neolims_dead_letter_status` (`status`, `updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `neolims_bridge_reconciliation_run` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `connection_key` VARCHAR(100) NOT NULL DEFAULT '',
  `scope` VARCHAR(64) NOT NULL DEFAULT 'all',
  `checked_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `ok_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `mismatch_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `missing_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `detail_json` LONGTEXT NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_neolims_reconcile_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
