-- ============================================================================
-- OSSZ ATELIER — manual database update for the daily-report feature
-- (NO-TERMINAL fallback: run in cPanel → phpMyAdmin, see DEPLOYMENT.md §10B)
--
-- This is the EXACT MySQL equivalent of the Laravel migration
-- 2024_01_01_000032_create_daily_report_tables.php:
--   * creates 3 NEW tables (nothing existing is altered or dropped)
--   * seeds the default settings row and the default recipient
--     info@osszcollection.com
--   * records the migration as run so future `php artisan migrate`
--     does not try to create the tables twice
--
-- Idempotent: safe to run more than once (IF NOT EXISTS / NOT EXISTS guards).
-- ============================================================================

CREATE TABLE IF NOT EXISTS `daily_report_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `send_time` VARCHAR(5) NOT NULL DEFAULT '07:00',
  `report_timezone` VARCHAR(64) NOT NULL DEFAULT 'Africa/Douala',
  `reply_to` VARCHAR(190) NULL,
  `include_production` TINYINT(1) NOT NULL DEFAULT 1,
  `include_financial` TINYINT(1) NOT NULL DEFAULT 1,
  `include_staff` TINYINT(1) NOT NULL DEFAULT 1,
  `include_alerts` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_by_id` BIGINT UNSIGNED NULL,
  `last_sent_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `daily_report_recipients` (
  `id` CHAR(36) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `name` VARCHAR(120) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `added_by_id` BIGINT UNSIGNED NULL,
  `added_by_name` VARCHAR(120) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `daily_report_recipients_email_unique` (`email`),
  KEY `daily_report_recipients_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `daily_report_logs` (
  `id` CHAR(36) NOT NULL,
  `report_date` DATE NOT NULL,
  `trigger_type` VARCHAR(12) NOT NULL,
  `status` VARCHAR(10) NOT NULL,
  `recipient_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `success_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `failure_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `recipients` TEXT NULL,
  `error` TEXT NULL,
  `summary` TEXT NULL,
  `sent_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `daily_report_logs_report_date_index` (`report_date`),
  KEY `daily_report_logs_trigger_type_index` (`trigger_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `daily_report_settings`
  (`id`, `enabled`, `send_time`, `report_timezone`, `reply_to`,
   `include_production`, `include_financial`, `include_staff`, `include_alerts`,
   `updated_by_id`, `last_sent_at`, `created_at`, `updated_at`)
SELECT 1, 1, '07:00', 'Africa/Douala', NULL, 1, 1, 1, 1, NULL, NULL, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `daily_report_settings`);

INSERT INTO `daily_report_recipients`
  (`id`, `email`, `name`, `is_active`, `added_by_id`, `added_by_name`,
   `created_at`, `updated_at`)
SELECT UUID(), 'info@osszcollection.com', 'OSSZ Collection', 1, NULL,
       'System (default)', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `daily_report_recipients`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2024_01_01_000032_create_daily_report_tables', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2024_01_01_000032_create_daily_report_tables'
);
