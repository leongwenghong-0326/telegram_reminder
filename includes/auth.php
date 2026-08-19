<?php

function is_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_login(): void
{
    if (is_logged_in()) {
        return;
    }
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (str_contains($script, '/api/')) {
        json_response(['success' => false, 'message' => lang('auth.unauthorized')], 401);
    }
    redirect('admin/login.php');
}

function current_admin(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    static $admin = false;
    if ($admin !== false) {
        return $admin;
    }
    $stmt = db()->prepare('SELECT id, username, email, created_at, last_login FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['admin_id']]);
    $admin = $stmt->fetch() ?: null;
    if (!$admin) {
        logout_admin();
        redirect('admin/login.php');
    }
    return $admin;
}

function login_throttle_check(): ?string
{
    $fails = array_values(array_filter($_SESSION['login_fails'] ?? [], static fn ($t) => $t > time() - 900));
    $_SESSION['login_fails'] = $fails;
    if (count($fails) >= 5) {
        return lang('auth.throttle');
    }
    return null;
}

function login_admin(string $username, string $password): array
{
    $throttled = login_throttle_check();
    if ($throttled) {
        return ['success' => false, 'message' => $throttled];
    }
    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$username, $username]);
    $admin = $stmt->fetch();
    if (!$admin || !password_verify($password, $admin['password'])) {
        $_SESSION['login_fails'][] = time();
        return ['success' => false, 'message' => lang('auth.login_fail')];
    }
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];
    unset($_SESSION['login_fails']);
    db()->prepare('UPDATE admins SET last_login = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $admin['id']]);
    return ['success' => true];
}

function logout_admin(): void
{
    $lang = $_SESSION['lang'] ?? ($_COOKIE['trms_lang'] ?? 'zh');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    if (function_exists('i18n_set_cookie')) {
        i18n_set_cookie($lang);
    }
}

function request_password_reset(string $account): array
{
    $generic = lang('auth.reset_sent');
    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$account, $account]);
    $admin = $stmt->fetch();
    if (!$admin) {
        return ['success' => true, 'message' => $generic];
    }
    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 1800);
    db()->prepare('UPDATE admins SET reset_token = ?, reset_expires = ? WHERE id = ?')->execute([$token, $expires, $admin['id']]);
    $url = base_url('admin/reset_password.php?token=' . $token);
    if (!send_reset_mail($admin['email'], $url)) {
        log_reset_link($admin['email'], $url);
    }
    $payload = ['success' => true, 'message' => $generic];
    if (APP_DEBUG) {
        $payload['debug_url'] = $url;
    }
    return $payload;
}

function reset_password_with_token(string $token, string $password): array
{
    if (strlen($token) !== 64) {
        return ['success' => false, 'message' => lang('auth.reset_invalid')];
    }
    $stmt = db()->prepare('SELECT * FROM admins WHERE reset_token = ? AND reset_expires >= ? LIMIT 1');
    $stmt->execute([$token, date('Y-m-d H:i:s')]);
    $admin = $stmt->fetch();
    if (!$admin) {
        return ['success' => false, 'message' => lang('auth.reset_invalid')];
    }
    db()->prepare('UPDATE admins SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?')
        ->execute([password_hash($password, PASSWORD_BCRYPT), $admin['id']]);
    return ['success' => true, 'message' => lang('auth.reset_ok')];
}
