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
        'list'      => users_list(),
        'save'      => users_save(),
        'delete'    => users_delete(),
        'options'   => users_options(),
        'test_send' => users_test_send(),
        default     => json_response(['success' => false, 'message' => lang('err.unknown')], 400),
    };
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => APP_DEBUG ? $e->getMessage() : lang('err.server')], 500);
}

function users_list(): void
{
    $search = trim((string) ($_POST['search'] ?? ''));
    $page   = max(1, (int) ($_POST['page'] ?? 1));
    $where  = ['1=1'];
    $params = [];
    if ($search !== '') {
        $like = '%' . $search . '%';
        $where[] = '(name LIKE ? OR chat_id LIKE ?)';
        array_push($params, $like, $like);
    }
    $whereSql = implode(' AND ', $where);
    $c = db()->prepare("SELECT COUNT(*) FROM users WHERE {$whereSql}");
    $c->execute($params);
    $pager = paginate($page, 10, (int) $c->fetchColumn());
    $sql = "SELECT u.*, (SELECT COUNT(*) FROM reminder_recipients rr WHERE rr.chat_id = u.chat_id) AS reminder_count
            FROM users u WHERE {$whereSql} ORDER BY u.id DESC LIMIT {$pager['per_page']} OFFSET {$pager['offset']}";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    json_response(['success' => true, 'data' => $stmt->fetchAll(), 'pager' => $pager]);
}

function users_save(): void
{
    $id     = (int) ($_POST['id'] ?? 0);
    $name   = trim((string) ($_POST['name'] ?? ''));
    $chatId = trim((string) ($_POST['chat_id'] ?? ''));
    if ($name === '') {
        $name = lang('user.default_name');
    }
    if (!is_valid_chat_id($chatId)) {
        json_response(['success' => false, 'message' => lang('api.invalid_chat')], 422);
    }
    $dup = db()->prepare('SELECT id FROM users WHERE chat_id = ? AND id <> ?');
    $dup->execute([$chatId, $id]);
    $existing = $dup->fetch();
    if ($existing) {
        if ($id < 1) {
            json_response(['success' => true, 'message' => lang('api.chat_kept'), 'id' => (int) $existing['id']]);
        }
        json_response(['success' => false, 'message' => lang('api.chat_exists')], 422);
    }
    if ($id > 0) {
        db()->prepare('UPDATE users SET name = ?, chat_id = ? WHERE id = ?')->execute([$name, $chatId, $id]);
        json_response(['success' => true, 'message' => lang('api.user_updated'), 'id' => $id]);
    }
    db()->prepare('INSERT INTO users (name, chat_id) VALUES (?, ?)')->execute([$name, $chatId]);
    json_response(['success' => true, 'message' => lang('api.user_saved'), 'id' => (int) db()->lastInsertId()]);
}

function users_delete(): void
{
    db()->prepare('DELETE FROM users WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
    json_response(['success' => true, 'message' => lang('api.user_deleted')]);
}

function users_options(): void
{
    json_response(['success' => true, 'data' => db()->query('SELECT id, name, chat_id FROM users ORDER BY id DESC')->fetchAll()]);
}

function users_test_send(): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
        json_response(['success' => false, 'message' => lang('api.user_missing')], 404);
    }
    $result = sendTelegramMessage($user['chat_id'], lang('api.test_text', ['app' => APP_NAME]));
    if (!$result['success']) {
        json_response(['success' => false, 'message' => lang('api.send_fail', ['error' => $result['error'] ?? ''])], 422);
    }
    json_response(['success' => true, 'message' => lang('api.test_sent')]);
}
