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
