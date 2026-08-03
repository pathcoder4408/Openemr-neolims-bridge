CREATE TABLE IF NOT EXISTS `neolims_bridge_workflow` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `workflow_uuid` CHAR(36) NOT NULL,
  `connection_key` VARCHAR(100) NOT NULL,
  `external_id` VARCHAR(190) NOT NULL,
  `accession_number` VARCHAR(100) NOT NULL DEFAULT '',
  `status` VARCHAR(32) NOT NULL DEFAULT 'queued',
  `current_step` VARCHAR(64) NOT NULL DEFAULT 'queued',
  `payload_json` LONGTEXT NOT NULL,
  `payload_hash` CHAR(64) NOT NULL,
  `result_json` LONGTEXT DEFAULT NULL,
  `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts` INT UNSIGNED NOT NULL DEFAULT 5,
  `next_attempt_at` DATETIME NOT NULL,
  `last_error` TEXT DEFAULT NULL,
  `started_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `failed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_neolims_workflow_uuid` (`workflow_uuid`),
  UNIQUE KEY `uq_neolims_workflow_external` (`connection_key`, `external_id`),
  KEY `idx_neolims_workflow_queue` (`status`, `next_attempt_at`),
  KEY `idx_neolims_workflow_accession` (`accession_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `neolims_bridge_workflow_event` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `workflow_uuid` CHAR(36) NOT NULL,
  `event_name` VARCHAR(64) NOT NULL,
  `detail` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_neolims_workflow_event_uuid` (`workflow_uuid`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
