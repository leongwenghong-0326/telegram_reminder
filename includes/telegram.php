<?php

function telegram_settings_path(): string
{
    return dirname(__DIR__) . '/storage/settings.json';
}

function telegram_settings(?array $override = null): array
{
    static $cache = null;
    if ($override !== null) {
        $cache = $override;
        return $cache;
    }
    if (is_array($cache)) {
        return $cache;
    }
    $path = telegram_settings_path();
    if (!is_file($path)) {
        $cache = [];
        return $cache;
    }
    $data  = json_decode((string) file_get_contents($path), true);
    $cache = is_array($data) ? $data : [];
    return $cache;
}

function telegram_settings_save(array $settings): void
{
    $dir = dirname(telegram_settings_path());
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $ok = file_put_contents(
        telegram_settings_path(),
        json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
    if ($ok === false) {
        throw new RuntimeException('无法保存 Telegram 设置');
    }
    telegram_settings($settings);
}

function telegram_token(): string
{
    $saved = trim((string) (telegram_settings()['bot_token'] ?? ''));
    if ($saved !== '') {
        return $saved;
    }
    return trim((string) TELEGRAM_BOT_TOKEN);
}

function telegram_token_masked(): string
{
    $token = telegram_token();
    if ($token === '') {
        return '';
    }
    if (strlen($token) < 16) {
        return str_repeat('*', strlen($token));
    }
    return substr($token, 0, 8) . str_repeat('*', 10) . substr($token, -4);
}

function telegram_api(string $method, array $params = [], bool $asPost = true): array
{
    $token = telegram_token();
    if ($token === '') {
        return ['success' => false, 'http_code' => 0, 'error' => lang('tg.no_token'), 'response' => null];
    }
    $url = TELEGRAM_API_URL . $token . '/' . ltrim($method, '/');
    $ch  = curl_init();
    if ($asPost) {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    } else {
        $query = http_build_query($params);
        curl_setopt($ch, CURLOPT_URL, $query !== '' ? $url . '?' . $query : $url);
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $raw      = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        return ['success' => false, 'http_code' => $httpCode, 'error' => $error !== '' ? $error : lang('tg.curl_fail'), 'response' => null];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['success' => false, 'http_code' => $httpCode, 'error' => lang('tg.parse_fail'), 'response' => null];
    }
    $ok = !empty($data['ok']);
    return [
        'success'   => $ok,
        'http_code' => $httpCode,
        'error'     => $ok ? null : (string) ($data['description'] ?? lang('tg.api_fail')),
        'response'  => $data,
    ];
}

function sendTelegramMessage(string $chat_id, string $message_text): array
{
    if ($message_text === '') {
        return ['success' => false, 'http_code' => 0, 'error' => lang('tg.empty'), 'response' => null];
    }
    if (mb_strlen($message_text) > 4096) {
        return ['success' => false, 'http_code' => 0, 'error' => lang('tg.too_long'), 'response' => null];
    }
    return telegram_api('sendMessage', [
        'chat_id'                  => $chat_id,
        'text'                     => $message_text,
        'disable_web_page_preview' => true,
    ]);
}

function telegram_delay(): void
{
    $ms = (int) TELEGRAM_MESSAGE_DELAY_MS;
    if ($ms > 0) {
        usleep($ms * 1000);
    }
}
