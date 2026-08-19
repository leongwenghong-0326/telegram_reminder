<?php

function app_root_url(): string
{
    static $root = null;
    if ($root !== null) {
        return $root;
    }

    if (PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $https = $https
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on');
        $scheme = $https ? 'https' : 'http';

        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
        $appRoot = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: '');
        $basePath = '';
        if ($docRoot !== '' && $appRoot !== '' && str_starts_with($appRoot, $docRoot)) {
            $basePath = substr($appRoot, strlen($docRoot));
        }
        $basePath = '/' . trim(str_replace('\\', '/', $basePath), '/');
        if ($basePath === '/') {
            $basePath = '';
        }

        $root = rtrim($scheme . '://' . $_SERVER['HTTP_HOST'] . $basePath, '/');
        return $root;
    }

    $root = rtrim(APP_URL, '/');
    return $root;
}

function base_url(string $path = ''): string
{
    $root = app_root_url();
    if ($path === '') {
        return $root . '/';
    }
    return $root . '/' . ltrim($path, '/');
}

function asset_url(string $path): string
{
    $file = dirname(__DIR__) . '/' . ltrim($path, '/');
    $v = is_file($file) ? (string) filemtime($file) : (string) time();
    return base_url($path) . '?v=' . $v;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
}

function require_csrf(): void
{
    if (!verify_csrf()) {
        json_response(['success' => false, 'message' => lang('err.csrf')], 403);
    }
}

function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

function status_label(string $status): string
{
    return match ($status) {
        'pending'        => lang('status.pending'),
        'sent'           => lang('status.sent'),
        'failed'         => lang('status.failed'),
        'partially_sent' => lang('status.partially_sent'),
        default          => $status,
    };
}

function status_badge(string $status): string
{
    $map = [
        'pending'        => 'badge-pending',
        'sent'           => 'badge-sent',
        'failed'         => 'badge-failed',
        'partially_sent' => 'badge-partial',
    ];
    $class = $map[$status] ?? 'badge-pending';
    return '<span class="status-badge ' . $class . '">' . e(status_label($status)) . '</span>';
}

function format_datetime(?string $value, string $format = 'Y-m-d H:i'): string
{
    if (!$value) {
        return '—';
    }
    $ts = strtotime($value);
    return $ts ? date($format, $ts) : e($value);
}

function is_valid_chat_id(string $chatId): bool
{
    return (bool) preg_match('/^-?\d{5,20}$/', $chatId);
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function paginate(int $page, int $perPage, int $total): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = max(1, min($page, $pages));
    return [
        'page'     => $page,
        'per_page' => $perPage,
        'total'    => $total,
        'pages'    => $pages,
        'offset'   => ($page - 1) * $perPage,
    ];
}

function send_reset_mail(string $to, string $resetUrl): bool
{
    $subject = '=?UTF-8?B?' . base64_encode(lang('auth.mail_subject')) . '?=';
    $body    = lang('auth.mail_body', ['url' => $resetUrl]);
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
    ];
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

function log_reset_link(string $email, string $resetUrl): void
{
    $dir = dirname(__DIR__) . '/storage';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(
        $dir . '/password_resets.log',
        sprintf("[%s] %s %s\n", date('Y-m-d H:i:s'), $email, $resetUrl),
        FILE_APPEND | LOCK_EX
    );
}

function shortcut_messages(string $raw): array
{
    $decoded = json_decode($raw, true);
    if (is_array($decoded) && $decoded !== []) {
        return array_values(array_filter(array_map('strval', $decoded), static fn ($t) => trim($t) !== ''));
    }
    $raw = trim($raw);
    return $raw === '' ? [] : [$raw];
}

function upsert_shortcut(string $title, array $messages, string $chatId = ''): void
{
    $payload = json_encode(array_values($messages), JSON_UNESCAPED_UNICODE);
    $stmt = db()->prepare('SELECT id FROM reminder_templates WHERE title = ? LIMIT 1');
    $stmt->execute([$title]);
    $id = $stmt->fetchColumn();
    if ($id) {
        db()->prepare('UPDATE reminder_templates SET message_text = ?, chat_id = COALESCE(NULLIF(?, ""), chat_id) WHERE id = ?')
            ->execute([$payload, $chatId, $id]);
        return;
    }
    db()->prepare('INSERT INTO reminder_templates (title, message_text, chat_id, offset_minutes, icon, is_system) VALUES (?, ?, ?, 30, ?, 0)')
        ->execute([$title, $payload, $chatId !== '' ? $chatId : null, '📝']);
}
