-- =========================================
-- WHATSMARK E-COMMERCE MIGRATION SQL
-- Complete database setup for e-commerce system
-- =========================================

-- Drop tables if they exist (in reverse order due to foreign keys)
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `ecommerce_bots`;

-- =========================================
-- CREATE ECOMMERCE_BOTS TABLE
-- =========================================
CREATE TABLE `ecommerce_bots` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `tenant_id` bigint(20) unsigned DEFAULT NULL,
    `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
    `google_sheets_product_url` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `google_sheets_order_url` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `sheets_product_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `sheets_order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `sync_settings` json DEFAULT NULL,
    `upselling_rules` json DEFAULT NULL,
    `reminder_settings` json DEFAULT NULL,
    `last_sync_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `ecommerce_bots_tenant_id_index` (`tenant_id`),
    CONSTRAINT `ecommerce_bots_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- CREATE PRODUCTS TABLE
-- =========================================
CREATE TABLE `products` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `tenant_id` bigint(20) unsigned DEFAULT NULL,
    `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `price` decimal(10,2) NOT NULL,
    `compare_price` decimal(10,2) DEFAULT NULL,
    `stock_quantity` int(11) NOT NULL DEFAULT 0,
    `image_url` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `status` enum('active','draft','archived','out_of_stock') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
    `variants` json DEFAULT NULL,
    `metadata` json DEFAULT NULL,
    `sheets_row_index` int(11) DEFAULT NULL,
    `last_synced_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `products_tenant_id_status_index` (`tenant_id`,`status`),
    KEY `products_tenant_id_category_index` (`tenant_id`,`category`),
    KEY `products_tenant_id_sku_index` (`tenant_id`,`sku`),
    KEY `products_stock_quantity_index` (`stock_quantity`),
    CONSTRAINT `products_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- CREATE ORDERS TABLE
-- =========================================
CREATE TABLE `orders` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `tenant_id` bigint(20) unsigned DEFAULT NULL,
    `order_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `contact_id` bigint(20) unsigned DEFAULT NULL,
    `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `customer_phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `customer_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `delivery_address` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
    `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `shipping_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` decimal(10,2) NOT NULL,
    `status` enum('pending','confirmed','processing','shipped','delivered','cancelled','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
    `payment_status` enum('pending','paid','failed','refunded','partial') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
    `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `payment_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `metadata` json DEFAULT NULL,
    `sheets_row_index` int(11) DEFAULT NULL,
    `order_date` timestamp NULL DEFAULT NULL,
    `shipped_at` timestamp NULL DEFAULT NULL,
    `delivered_at` timestamp NULL DEFAULT NULL,
    `last_synced_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `orders_order_number_unique` (`order_number`),
    KEY `orders_tenant_id_status_index` (`tenant_id`,`status`),
    KEY `orders_tenant_id_customer_phone_index` (`tenant_id`,`customer_phone`),
    KEY `orders_order_number_index` (`order_number`),
    KEY `orders_created_at_index` (`created_at`),
    KEY `orders_contact_id_foreign` (`contact_id`),
    CONSTRAINT `orders_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `orders_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- CREATE ORDER_ITEMS TABLE
-- =========================================
CREATE TABLE `order_items` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `tenant_id` bigint(20) unsigned DEFAULT NULL,
    `order_id` bigint(20) unsigned NOT NULL,
    `product_id` bigint(20) unsigned DEFAULT NULL,
    `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `product_sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `price` decimal(10,2) NOT NULL,
    `quantity` int(11) NOT NULL,
    `total_amount` decimal(10,2) NOT NULL,
    `product_metadata` json DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `order_items_tenant_id_index` (`tenant_id`),
    KEY `order_items_order_id_index` (`order_id`),
    KEY `order_items_product_id_index` (`product_id`),
    CONSTRAINT `order_items_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- INSERT SAMPLE DATA (OPTIONAL)
-- =========================================

-- Sample ecommerce bot configuration
-- INSERT INTO `ecommerce_bots` (`tenant_id`, `is_enabled`, `sync_settings`, `upselling_rules`, `reminder_settings`, `created_at`, `updated_at`) VALUES
-- (1, 0, '{"auto_sync": false, "sync_interval": 60}', '{"enabled": false, "minimum_order_value": 100, "discount_percentage": 10}', '{"enabled": false, "intervals": [1, 24, 72]}', NOW(), NOW());

-- =========================================
-- CLEANUP COMMANDS (if needed)
-- =========================================

-- To completely reset the e-commerce system:
-- DELETE FROM order_items WHERE tenant_id IS NOT NULL;
-- DELETE FROM orders WHERE tenant_id IS NOT NULL;  
-- DELETE FROM products WHERE tenant_id IS NOT NULL;
-- DELETE FROM ecommerce_bots WHERE tenant_id IS NOT NULL;

-- To reset auto increment:
-- ALTER TABLE ecommerce_bots AUTO_INCREMENT = 1;
-- ALTER TABLE products AUTO_INCREMENT = 1;
-- ALTER TABLE orders AUTO_INCREMENT = 1;
-- ALTER TABLE order_items AUTO_INCREMENT = 1;

-- =========================================
-- VERIFICATION QUERIES
-- =========================================

-- Check if tables are created successfully:
-- SHOW TABLES LIKE '%ecommerce%';
-- SHOW TABLES LIKE '%products%';
-- SHOW TABLES LIKE '%orders%';
-- SHOW TABLES LIKE '%order_items%';

-- Check table structures:
-- DESCRIBE ecommerce_bots;
-- DESCRIBE products;
-- DESCRIBE orders;
-- DESCRIBE order_items;

-- =========================================
-- MIGRATION COMMANDS
-- =========================================

-- If you want to run this via Laravel migration:
-- php artisan migrate:reset
-- php artisan migrate

-- Or run this SQL directly in your database:
-- mysql -u your_username -p your_database < ecommerce_migrations.sql
