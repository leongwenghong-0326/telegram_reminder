<?php

declare(strict_types=1);

$configPath = dirname(__DIR__) . '/config/config.php';
if (!is_file($configPath)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Missing config/config.php';
    exit;
}

require_once $configPath;
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/telegram.php';
require_once __DIR__ . '/i18n.php';

date_default_timezone_set(APP_TIMEZONE);

$script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
$isCron = PHP_SAPI === 'cli' || str_contains($script, '/cron/');

if (!$isCron && session_status() === PHP_SESSION_NONE) {
    session_name('trms_sess');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

i18n_boot();

ini_set('display_errors', APP_DEBUG ? '1' : '0');
error_reporting(E_ALL);
