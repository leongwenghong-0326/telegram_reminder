<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'telegram_reminder_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP default is often empty
define('DB_CHARSET', 'utf8mb4');

define('TELEGRAM_BOT_TOKEN', '');
define('TELEGRAM_API_URL', 'https://api.telegram.org/bot');
define('TELEGRAM_MESSAGE_DELAY_MS', 1000);

define('CRON_SECRET_KEY', 'CHANGE_THIS_TO_A_LONG_RANDOM_STRING');

define('APP_NAME', 'Telegram Reminder');
define('APP_URL', 'http://localhost/telegram_reminder2');
define('APP_TIMEZONE', 'Asia/Kuala_Lumpur');
define('APP_DEBUG', false);

define('MAIL_FROM', 'noreply@example.com');
define('MAIL_FROM_NAME', 'Telegram Reminder');
