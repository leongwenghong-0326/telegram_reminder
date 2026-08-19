<?php

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsnNoDb = sprintf('mysql:host=%s;charset=%s', DB_HOST, DB_CHARSET);
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET),
            DB_USER,
            DB_PASS,
            $options
        );
    } catch (PDOException $e) {
        $root = new PDO($dsnNoDb, DB_USER, DB_PASS, $options);
        $root->exec('CREATE DATABASE IF NOT EXISTS `' . preg_replace('/[^A-Za-z0-9_]/', '', DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET),
            DB_USER,
            DB_PASS,
            $options
        );
    }

    ensure_schema($pdo);
    ensure_templates($pdo);
    return $pdo;
}

function ensure_templates(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `reminder_templates` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(200) NOT NULL,
            `message_text` TEXT NOT NULL,
            `chat_id` VARCHAR(50) DEFAULT NULL,
            `offset_minutes` INT NOT NULL DEFAULT 30,
            `icon` VARCHAR(16) DEFAULT NULL,
            `is_system` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $col = $pdo->query("SHOW COLUMNS FROM reminder_templates LIKE 'chat_id'")->fetch();
    if (!$col) {
        $pdo->exec('ALTER TABLE reminder_templates ADD `chat_id` VARCHAR(50) DEFAULT NULL AFTER `message_text`');
    }
    $pdo->exec('DELETE FROM reminder_templates WHERE is_system = 1');
}

function ensure_schema(PDO $pdo): void
{
    $exists = $pdo->query("SHOW TABLES LIKE 'admins'")->fetchColumn();
    if ($exists) {
        return;
    }

    $file = dirname(__DIR__) . '/schema.sql';
    $sql  = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException('找不到 schema.sql');
    }
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if ($stmt !== '') {
            $pdo->exec($stmt);
        }
    }
}
