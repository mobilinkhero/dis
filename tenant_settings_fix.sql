-- Fix for missing tenant_settings table
-- Run this SQL directly in your database

CREATE TABLE IF NOT EXISTS `tenant_settings` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `tenant_id` bigint(20) unsigned NOT NULL,
    `key` varchar(255) NOT NULL,
    `value` text DEFAULT NULL,
    `group` varchar(100) DEFAULT NULL,
    `type` varchar(50) DEFAULT 'string',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tenant_settings_tenant_key_unique` (`tenant_id`, `key`),
    KEY `tenant_settings_tenant_id_index` (`tenant_id`),
    KEY `tenant_settings_group_index` (`group`),
    CONSTRAINT `tenant_settings_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some default settings for existing tenants
INSERT IGNORE INTO `tenant_settings` (`tenant_id`, `key`, `value`, `group`, `type`, `created_at`, `updated_at`) 
SELECT 
    `id` as tenant_id,
    'timezone' as `key`,
    'UTC' as `value`,
    'system' as `group`,
    'string' as `type`,
    NOW() as created_at,
    NOW() as updated_at
FROM `tenants`;

INSERT IGNORE INTO `tenant_settings` (`tenant_id`, `key`, `value`, `group`, `type`, `created_at`, `updated_at`) 
SELECT 
    `id` as tenant_id,
    'currency' as `key`,
    'USD' as `value`,
    'system' as `group`,
    'string' as `type`,
    NOW() as created_at,
    NOW() as updated_at
FROM `tenants`;

INSERT IGNORE INTO `tenant_settings` (`tenant_id`, `key`, `value`, `group`, `type`, `created_at`, `updated_at`) 
SELECT 
    `id` as tenant_id,
    'date_format' as `key`,
    'Y-m-d' as `value`,
    'system' as `group`,
    'string' as `type`,
    NOW() as created_at,
    NOW() as updated_at
FROM `tenants`;

-- Update migrations table
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
('2025_01_16_000006_create_tenant_settings_table', 999);

-- Verify the table was created
SELECT 'tenant_settings' as table_name, COUNT(*) as record_count FROM tenant_settings;
