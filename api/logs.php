<?php

require_once __DIR__ . '/../includes/init.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => lang('err.post_only')], 405);
}
require_csrf();

$action = $_POST['action'] ?? 'list';
try {
    match ($action) {
        'list'   => logs_list(),
        'detail' => logs_detail(),
        default  => json_response(['success' => false, 'message' => lang('err.unknown')], 400),
    };
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => APP_DEBUG ? $e->getMessage() : lang('err.server')], 500);
}

function logs_list(): void
{
    $search = trim((string) ($_POST['search'] ?? ''));
    $filter = trim((string) ($_POST['filter'] ?? 'all'));
    $page   = max(1, (int) ($_POST['page'] ?? 1));
    $where  = ['1=1'];
    $params = [];
    if ($search !== '') {
        $like = '%' . $search . '%';
        $where[] = '(r.title LIKE ? OR l.chat_id LIKE ? OR l.message_text LIKE ? OR l.error_message LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    if ($filter === 'sent' || $filter === 'failed') {
        $where[] = 'l.status = ?';
        $params[] = $filter;
    } elseif ($filter === 'today') {
        $where[] = 'DATE(l.sent_time) = ?';
        $params[] = date('Y-m-d');
    } elseif ($filter === 'week') {
        $where[] = 'l.sent_time >= ?';
        $params[] = date('Y-m-d H:i:s', strtotime('-7 days'));
    }
    $whereSql = implode(' AND ', $where);
    $c = db()->prepare("SELECT COUNT(*) FROM message_logs l INNER JOIN reminders r ON r.id = l.reminder_id WHERE {$whereSql}");
    $c->execute($params);
    $pager = paginate($page, 15, (int) $c->fetchColumn());
    $sql = "SELECT l.*, r.title AS reminder_title, u.name AS user_name
            FROM message_logs l
            INNER JOIN reminders r ON r.id = l.reminder_id
            LEFT JOIN users u ON u.chat_id = l.chat_id
            WHERE {$whereSql} ORDER BY l.id DESC LIMIT {$pager['per_page']} OFFSET {$pager['offset']}";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    json_response(['success' => true, 'data' => $stmt->fetchAll(), 'pager' => $pager]);
}

function logs_detail(): void
{
    $stmt = db()->prepare('SELECT l.*, u.name AS user_name FROM message_logs l LEFT JOIN users u ON u.chat_id = l.chat_id WHERE l.reminder_id = ? ORDER BY l.id');
    $stmt->execute([(int) ($_POST['reminder_id'] ?? 0)]);
    json_response(['success' => true, 'data' => $stmt->fetchAll()]);
}
