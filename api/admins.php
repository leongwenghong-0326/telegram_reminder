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
        'list'            => admins_list(),
        'create'          => admins_create(),
        'delete'          => admins_delete(),
        'change_password' => admins_change_password(),
        default           => json_response(['success' => false, 'message' => lang('err.unknown')], 400),
    };
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => APP_DEBUG ? $e->getMessage() : lang('err.server')], 500);
}

function admins_list(): void
{
    json_response(['success' => true, 'data' => db()->query('SELECT id, username, email, last_login, created_at FROM admins ORDER BY id')->fetchAll()]);
}

function admins_create(): void
{
    $username = trim((string) ($_POST['username'] ?? ''));
    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        json_response(['success' => false, 'message' => lang('api.bad_username')], 422);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        json_response(['success' => false, 'message' => lang('api.bad_email_pass')], 422);
    }
    $dup = db()->prepare('SELECT id FROM admins WHERE username = ? OR email = ?');
    $dup->execute([$username, $email]);
    if ($dup->fetch()) {
        json_response(['success' => false, 'message' => lang('api.user_taken')], 422);
    }
    db()->prepare('INSERT INTO admins (username, email, password) VALUES (?, ?, ?)')
        ->execute([$username, $email, password_hash($password, PASSWORD_BCRYPT)]);
    json_response(['success' => true, 'message' => lang('api.admin_created')]);
}

function admins_delete(): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $me = current_admin();
    if ($id === (int) $me['id']) {
        json_response(['success' => false, 'message' => lang('api.no_self_delete')], 422);
    }
    if ((int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() <= 1) {
        json_response(['success' => false, 'message' => lang('api.keep_admin')], 422);
    }
    db()->prepare('DELETE FROM admins WHERE id = ?')->execute([$id]);
    json_response(['success' => true, 'message' => lang('api.admin_deleted')]);
}

function admins_change_password(): void
{
    $me  = current_admin();
    $old = (string) ($_POST['old_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    if (strlen($new) < 8) {
        json_response(['success' => false, 'message' => lang('api.pass_short')], 422);
    }
    $stmt = db()->prepare('SELECT password FROM admins WHERE id = ?');
    $stmt->execute([(int) $me['id']]);
    if (!password_verify($old, (string) $stmt->fetchColumn())) {
        json_response(['success' => false, 'message' => lang('api.pass_wrong')], 422);
    }
    db()->prepare('UPDATE admins SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_BCRYPT), $me['id']]);
    json_response(['success' => true, 'message' => lang('api.pass_updated')]);
}
