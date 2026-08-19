-- Telegram Reminder Management System
CREATE DATABASE IF NOT EXISTS `telegram_reminder_db` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `telegram_reminder_db`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `reset_token` VARCHAR(64) DEFAULT NULL,
  `reset_expires` DATETIME DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_username` (`username`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `chat_id` VARCHAR(50) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_chat_id` (`chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reminders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `scheduled_time` DATETIME NOT NULL,
  `status` ENUM('pending','sent','failed','partially_sent') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_time` (`status`, `scheduled_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reminder_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reminder_id` INT UNSIGNED NOT NULL,
  `message_text` TEXT NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_reminder_sort` (`reminder_id`, `sort_order`),
  CONSTRAINT `fk_rm_reminder` FOREIGN KEY (`reminder_id`) REFERENCES `reminders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reminder_recipients` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reminder_id` INT UNSIGNED NOT NULL,
  `chat_id` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_reminder_chat` (`reminder_id`, `chat_id`),
  CONSTRAINT `fk_rr_reminder` FOREIGN KEY (`reminder_id`) REFERENCES `reminders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `message_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reminder_id` INT UNSIGNED NOT NULL,
  `chat_id` VARCHAR(50) NOT NULL,
  `message_text` TEXT NOT NULL,
  `status` ENUM('sent','failed') NOT NULL,
  `sent_time` DATETIME DEFAULT NULL,
  `error_message` TEXT DEFAULT NULL,
  `telegram_response_code` INT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reminder` (`reminder_id`),
  CONSTRAINT `fk_ml_reminder` FOREIGN KEY (`reminder_id`) REFERENCES `reminders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` (`username`, `email`, `password`)
SELECT 'admin', 'admin@localhost', '$2y$10$rRz6fNVzaMcejG1tQM2XNehvQJ0T6HwUZybTlEDsK97hQrwYraiOi'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `admins` LIMIT 1);

CREATE TABLE IF NOT EXISTS `reminder_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `message_text` TEXT NOT NULL,
  `offset_minutes` INT NOT NULL DEFAULT 30,
  `icon` VARCHAR(16) DEFAULT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;