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

CREATE TABLE IF NOT EXISTS `neolims_bridge_order_link` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `connection_key` VARCHAR(100) NOT NULL,
  `local_order_id` VARCHAR(100) NOT NULL,
  `local_patient_id` VARCHAR(100) NOT NULL,
  `local_encounter_id` VARCHAR(100) NOT NULL,
  `openemr_order_uuid` CHAR(36) NOT NULL,
  `openemr_order_id` BIGINT NOT NULL,
  `openemr_pid` BIGINT NOT NULL,
  `openemr_encounter_id` BIGINT NOT NULL,
  `link_source` VARCHAR(50) NOT NULL,
  `external_identifier_system` VARCHAR(255) NOT NULL DEFAULT '',
  `external_identifier_value` VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_neolims_order_local` (`connection_key`,`local_order_id`),
  KEY `idx_neolims_order_uuid` (`openemr_order_uuid`),
  KEY `idx_neolims_order_external` (`external_identifier_system`(140),`external_identifier_value`(140))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `neolims_bridge_result_link` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `connection_key` VARCHAR(100) NOT NULL,
  `local_report_id` VARCHAR(150) NOT NULL,
  `local_order_id` VARCHAR(100) NOT NULL,
  `openemr_report_uuid` CHAR(36) NOT NULL,
  `openemr_report_id` BIGINT NOT NULL,
  `openemr_order_id` BIGINT NOT NULL,
  `revision` INT NOT NULL DEFAULT 1,
  `report_status` VARCHAR(32) NOT NULL,
  `link_source` VARCHAR(50) NOT NULL,
  `external_identifier_system` VARCHAR(255) NOT NULL DEFAULT '',
  `external_identifier_value` VARCHAR(255) NOT NULL DEFAULT '',
  `payload_hash` CHAR(64) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_neolims_result_local` (`connection_key`,`local_report_id`),
  KEY `idx_neolims_result_report_uuid` (`openemr_report_uuid`),
  KEY `idx_neolims_result_order` (`openemr_order_id`),
  KEY `idx_neolims_result_external` (`external_identifier_system`(140),`external_identifier_value`(140))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `neolims_bridge_document_link` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `connection_key` VARCHAR(100) NOT NULL,
  `local_document_id` VARCHAR(150) NOT NULL,
  `local_report_id` VARCHAR(150) NOT NULL,
  `openemr_document_id` BIGINT NOT NULL,
  `openemr_document_uuid` CHAR(36) NOT NULL,
  `openemr_report_id` BIGINT NOT NULL,
  `openemr_pid` BIGINT NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `mimetype` VARCHAR(100) NOT NULL,
  `content_hash` CHAR(64) NOT NULL,
  `external_identifier_system` VARCHAR(255) NOT NULL DEFAULT '',
  `external_identifier_value` VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_neolims_document_local` (`connection_key`,`local_document_id`),
  KEY `idx_neolims_document_uuid` (`openemr_document_uuid`),
  KEY `idx_neolims_document_report` (`openemr_report_id`),
  KEY `idx_neolims_document_external` (`external_identifier_system`(140),`external_identifier_value`(140))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `neolims_bridge_billing_link` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `connection_key` VARCHAR(100) NOT NULL,
 `local_charge_id` VARCHAR(100) NOT NULL,
 `local_order_id` VARCHAR(100) NOT NULL DEFAULT '',
 `local_report_id` VARCHAR(100) NOT NULL DEFAULT '',
 `openemr_billing_id` BIGINT NOT NULL,
 `openemr_pid` BIGINT NOT NULL,
 `openemr_encounter_id` BIGINT NOT NULL,
 `code_type` VARCHAR(15) NOT NULL,
 `code` VARCHAR(20) NOT NULL,
 `modifier` VARCHAR(12) NOT NULL DEFAULT '',
 `units` INT NOT NULL DEFAULT 1,
 `fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
 `payload_hash` CHAR(64) NOT NULL,
 `link_source` VARCHAR(50) NOT NULL,
 `external_identifier_system` VARCHAR(255) NOT NULL DEFAULT '',
 `external_identifier_value` VARCHAR(255) NOT NULL DEFAULT '',
 `created_at` DATETIME NOT NULL,
 `updated_at` DATETIME NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uq_neolims_billing_local` (`connection_key`,`local_charge_id`),
 UNIQUE KEY `uq_neolims_billing_openemr` (`openemr_billing_id`),
 KEY `idx_neolims_billing_external` (`external_identifier_system`(140),`external_identifier_value`(140))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `neolims_bridge_profile` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_key` VARCHAR(100) NOT NULL,
  `display_name` VARCHAR(255) NOT NULL,
  `connection_key` VARCHAR(100) NOT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `default_direction` VARCHAR(32) NOT NULL DEFAULT 'receive',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_neolims_profile_key` (`profile_key`),
  KEY `idx_neolims_profile_connection` (`connection_key`), KEY `idx_neolims_profile_default` (`is_default`,`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `neolims_bridge_profile_resource` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id` BIGINT UNSIGNED NOT NULL,
  `resource_name` VARCHAR(64) NOT NULL,
  `mode` VARCHAR(32) NOT NULL DEFAULT 'disabled',
  `receive_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `send_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `operations_json` LONGTEXT NOT NULL,
  `transports_json` LONGTEXT NOT NULL,
  `config_json` LONGTEXT NOT NULL,
  `created_at` DATETIME NOT NULL, `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_neolims_profile_resource` (`profile_id`,`resource_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `neolims_bridge_profile_mapping` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id` BIGINT UNSIGNED NOT NULL,
  `mapping_key` VARCHAR(100) NOT NULL,
  `mapping_value` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL, `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_neolims_profile_mapping` (`profile_id`,`mapping_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO neolims_bridge_profile
(profile_key,display_name,connection_key,enabled,is_default,default_direction,created_at,updated_at)
VALUES ('envision_billing','Envision Pathology Billing','envision_openemr',1,1,'receive',NOW(),NOW())
ON DUPLICATE KEY UPDATE display_name=VALUES(display_name), connection_key=VALUES(connection_key), enabled=1, is_default=1, updated_at=NOW();
SET @profile_id=(SELECT id FROM neolims_bridge_profile WHERE profile_key='envision_billing' LIMIT 1);
INSERT INTO neolims_bridge_profile_resource
(profile_id,resource_name,mode,receive_enabled,send_enabled,operations_json,transports_json,config_json,created_at,updated_at) VALUES
(@profile_id,'patient','receive',1,0,'["resolve","sync","read"]','["standard_api","fhir"]','{}',NOW(),NOW()),
(@profile_id,'insurance','receive',1,0,'["resolve","sync","read"]','["standard_api","fhir"]','{}',NOW(),NOW()),
(@profile_id,'encounter','receive',1,0,'["sync","read"]','["standard_api","fhir"]','{}',NOW(),NOW()),
(@profile_id,'billing','receive',1,0,'["validate","sync","read"]','["standard_api"]','{"create_unbilled":true,"authorize_automatically":false}',NOW(),NOW()),
(@profile_id,'document','receive',1,0,'["validate","sync","read"]','["standard_api","fhir"]','{"category_path":"/Lab Results"}',NOW(),NOW()),
(@profile_id,'order','disabled',0,0,'[]','["fhir","hl7_v2","standard_api"]','{}',NOW(),NOW()),
(@profile_id,'result','disabled',0,0,'[]','["fhir","hl7_v2","standard_api"]','{}',NOW(),NOW()),
(@profile_id,'hl7','fallback',0,0,'["receive"]','["hl7_v2"]','{}',NOW(),NOW())
ON DUPLICATE KEY UPDATE mode=VALUES(mode),receive_enabled=VALUES(receive_enabled),send_enabled=VALUES(send_enabled),operations_json=VALUES(operations_json),transports_json=VALUES(transports_json),config_json=VALUES(config_json),updated_at=NOW();
INSERT INTO neolims_bridge_profile_mapping
(profile_id,mapping_key,mapping_value,created_at,updated_at) VALUES
(@profile_id,'document_category','/Lab Results',NOW(),NOW()),
(@profile_id,'billing_create_unbilled','1',NOW(),NOW()),
(@profile_id,'billing_authorize_automatically','0',NOW(),NOW())
ON DUPLICATE KEY UPDATE mapping_value=VALUES(mapping_value),updated_at=NOW();
