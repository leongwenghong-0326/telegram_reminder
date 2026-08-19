<?php

require_once __DIR__ . '/../includes/init.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => lang('err.post_only')], 405);
}
require_csrf();

$action = $_POST['action'] ?? '';
try {
    match ($action) {
        'get'         => settings_get(),
        'save'        => settings_save(),
        'test'        => settings_test(),
        default       => json_response(['success' => false, 'message' => lang('err.unknown')], 400),
    };
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => APP_DEBUG ? $e->getMessage() : lang('err.server')], 500);
}

function settings_get(): void
{
    $s = telegram_settings();
    json_response(['success' => true, 'data' => [
        'has_token'     => telegram_token() !== '',
        'masked_token'  => telegram_token_masked(),
        'default_chat'  => (string) ($s['default_chat_id'] ?? ''),
        'cron_url'      => base_url('cron/send_reminders.php?key=' . urlencode(CRON_SECRET_KEY)),
    ]]);
}

function settings_save(): void
{
    $token  = trim((string) ($_POST['bot_token'] ?? ''));
    $chatId = trim((string) ($_POST['chat_id'] ?? ''));
    $name   = trim((string) ($_POST['name'] ?? lang('user.default_name')));
    $s      = telegram_settings();

    if ($token !== '') {
        if (!preg_match('/^\d{6,}:[A-Za-z0-9_-]{20,}$/', $token)) {
            json_response(['success' => false, 'message' => lang('api.bad_token')], 422);
        }
        $s['bot_token'] = $token;
        telegram_settings_save($s);
        $me = telegram_api('getMe', [], false);
        if (!$me['success']) {
            json_response(['success' => false, 'message' => lang('api.token_check_fail', ['error' => $me['error'] ?? ''])], 422);
        }
    } elseif (telegram_token() === '') {
        json_response(['success' => false, 'message' => lang('api.need_bot')], 422);
    }

    if ($chatId !== '') {
        if (!is_valid_chat_id($chatId)) {
            json_response(['success' => false, 'message' => lang('api.invalid_chat')], 422);
        }
        $s['default_chat_id'] = $chatId;
        telegram_settings_save($s);
        $dup = db()->prepare('SELECT id FROM users WHERE chat_id = ?');
        $dup->execute([$chatId]);
        if (!$dup->fetch()) {
            db()->prepare('INSERT INTO users (name, chat_id) VALUES (?, ?)')->execute([$name !== '' ? $name : lang('user.default_name'), $chatId]);
        }
    }

    json_response(['success' => true, 'message' => lang('api.settings_saved'), 'data' => [
        'has_token'    => telegram_token() !== '',
        'masked_token' => telegram_token_masked(),
        'default_chat' => (string) (telegram_settings()['default_chat_id'] ?? ''),
    ]]);
}

function settings_test(): void
{
    $chatId = trim((string) ($_POST['chat_id'] ?? telegram_settings()['default_chat_id'] ?? ''));
    if (!is_valid_chat_id($chatId)) {
        json_response(['success' => false, 'message' => lang('api.need_chat_id')], 422);
    }
    $result = sendTelegramMessage($chatId, lang('api.test_connect', ['app' => APP_NAME]));
    if (!$result['success']) {
        json_response(['success' => false, 'message' => lang('api.send_fail', ['error' => $result['error'] ?? ''])], 422);
    }
    json_response(['success' => true, 'message' => lang('api.test_ok')]);
}
