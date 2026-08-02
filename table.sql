CREATE TABLE IF NOT EXISTS `neolims_bridge_message` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_uuid` CHAR(36) NOT NULL,
  `message_type` VARCHAR(64) NOT NULL,
  `transport` VARCHAR(32) NOT NULL,
  `identifier_system` VARCHAR(255) NOT NULL,
  `identifier_value` VARCHAR(255) NOT NULL,
  `patient_reference` VARCHAR(255) DEFAULT NULL,
  `encounter_reference` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'queued',
  `payload_json` LONGTEXT NOT NULL,
  `raw_payload` LONGTEXT DEFAULT NULL,
  `payload_hash` CHAR(64) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_neolims_bridge_uuid` (`message_uuid`),
  UNIQUE KEY `uq_neolims_bridge_identifier`
    (`message_type`, `identifier_system`(140), `identifier_value`(140)),
  KEY `idx_neolims_bridge_status` (`status`),
  KEY `idx_neolims_bridge_updated` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `neolims_bridge_audit` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_uuid` CHAR(36) NOT NULL,
  `action` VARCHAR(32) NOT NULL,
  `payload_hash` CHAR(64) NOT NULL,
  `detail` TEXT DEFAULT NULL,
  `actor_user_id` BIGINT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_neolims_bridge_audit_uuid` (`message_uuid`),
  KEY `idx_neolims_bridge_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
