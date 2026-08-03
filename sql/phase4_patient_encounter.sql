CREATE TABLE IF NOT EXISTS `neolims_bridge_patient_link` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `connection_key` VARCHAR(100) NOT NULL,
  `local_patient_id` VARCHAR(100) NOT NULL,
  `openemr_patient_uuid` CHAR(36) NOT NULL,
  `openemr_pid` BIGINT NOT NULL,
  `link_source` VARCHAR(50) NOT NULL,
  `external_identifier_system` VARCHAR(255) NOT NULL DEFAULT '',
  `external_identifier_value` VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_neolims_patient_local` (`connection_key`,`local_patient_id`),
  KEY `idx_neolims_patient_uuid` (`openemr_patient_uuid`),
  KEY `idx_neolims_patient_external` (`external_identifier_system`(140),`external_identifier_value`(140))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `neolims_bridge_encounter_link` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `connection_key` VARCHAR(100) NOT NULL,
  `local_encounter_id` VARCHAR(100) NOT NULL,
  `local_patient_id` VARCHAR(100) NOT NULL,
  `openemr_encounter_uuid` CHAR(36) NOT NULL,
  `openemr_encounter_id` BIGINT NOT NULL,
  `openemr_patient_uuid` CHAR(36) NOT NULL,
  `openemr_pid` BIGINT NOT NULL,
  `link_source` VARCHAR(50) NOT NULL,
  `external_identifier` VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_neolims_encounter_local` (`connection_key`,`local_encounter_id`),
  KEY `idx_neolims_encounter_uuid` (`openemr_encounter_uuid`),
  KEY `idx_neolims_encounter_patient` (`openemr_patient_uuid`),
  KEY `idx_neolims_encounter_external` (`external_identifier`(190))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
