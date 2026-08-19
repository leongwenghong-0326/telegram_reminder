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
        'list'   => reminders_list(),
        'get'    => reminders_get(),
        'save'   => reminders_save(),
        'delete' => reminders_delete(),
        'retry'  => reminders_retry(),
        default  => json_response(['success' => false, 'message' => lang('err.unknown')], 400),
    };
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => APP_DEBUG ? $e->getMessage() : lang('err.server')], 500);
}

function reminders_list(): void
{
    $search = trim((string) ($_POST['search'] ?? ''));
    $filter = trim((string) ($_POST['filter'] ?? 'all'));
    $page   = max(1, (int) ($_POST['page'] ?? 1));
    $where  = ['1=1'];
    $params = [];

    if ($search !== '') {
        $like = '%' . $search . '%';
        $where[] = '(r.title LIKE ?
            OR EXISTS (SELECT 1 FROM reminder_recipients rr WHERE rr.reminder_id = r.id AND rr.chat_id LIKE ?)
            OR EXISTS (SELECT 1 FROM reminder_messages rm WHERE rm.reminder_id = r.id AND rm.message_text LIKE ?))';
        array_push($params, $like, $like, $like);
    }
    switch ($filter) {
        case 'today':
            $where[] = 'DATE(r.scheduled_time) = ?';
            $params[] = date('Y-m-d');
            break;
        case 'pending':
            $where[] = "r.status = 'pending'";
            break;
        case 'sent':
            $where[] = "r.status = 'sent'";
            break;
        case 'failed':
            $where[] = "r.status IN ('failed','partially_sent')";
            break;
        case 'week':
            $where[] = 'r.scheduled_time >= ?';
            $params[] = date('Y-m-d H:i:s', strtotime('-7 days'));
            break;
    }

    $whereSql = implode(' AND ', $where);
    $count = db()->prepare("SELECT COUNT(*) FROM reminders r WHERE {$whereSql}");
    $count->execute($params);
    $pager = paginate($page, 10, (int) $count->fetchColumn());

    $sql = "SELECT r.*,
                (SELECT COUNT(*) FROM reminder_messages m WHERE m.reminder_id = r.id) AS message_count,
                (SELECT GROUP_CONCAT(rr.chat_id ORDER BY rr.id SEPARATOR ', ') FROM reminder_recipients rr WHERE rr.reminder_id = r.id) AS chat_ids
            FROM reminders r WHERE {$whereSql}
            ORDER BY r.scheduled_time DESC, r.id DESC
            LIMIT {$pager['per_page']} OFFSET {$pager['offset']}";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    json_response(['success' => true, 'data' => $stmt->fetchAll(), 'pager' => $pager]);
}

function reminders_get(): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = db()->prepare('SELECT * FROM reminders WHERE id = ?');
    $stmt->execute([$id]);
    $reminder = $stmt->fetch();
    if (!$reminder) {
        json_response(['success' => false, 'message' => lang('api.reminder_missing')], 404);
    }
    $msg = db()->prepare('SELECT message_text, sort_order FROM reminder_messages WHERE reminder_id = ? ORDER BY sort_order, id');
    $msg->execute([$id]);
    $rec = db()->prepare('SELECT chat_id FROM reminder_recipients WHERE reminder_id = ?');
    $rec->execute([$id]);
    $chatIds = $rec->fetchAll(PDO::FETCH_COLUMN);
    $userIds = [];
    if ($chatIds) {
        $ph = implode(',', array_fill(0, count($chatIds), '?'));
        $u = db()->prepare("SELECT id FROM users WHERE chat_id IN ({$ph})");
        $u->execute($chatIds);
        $userIds = array_map('intval', $u->fetchAll(PDO::FETCH_COLUMN));
    }
    json_response(['success' => true, 'data' => [
        'reminder' => $reminder,
        'messages' => $msg->fetchAll(),
        'user_ids' => $userIds,
        'chat_ids' => $chatIds,
    ]]);
}

function reminders_save(): void
{
    $id        = (int) ($_POST['id'] ?? 0);
    $title     = trim((string) ($_POST['title'] ?? ''));
    $scheduled = str_replace('T', ' ', trim((string) ($_POST['scheduled_time'] ?? '')));
    $userIds   = $_POST['user_ids'] ?? [];
    $chatIdsIn = $_POST['chat_ids'] ?? [];
    $messages  = $_POST['messages'] ?? [];
    $token     = trim((string) ($_POST['bot_token'] ?? ''));

    if ($title === '' || mb_strlen($title) > 200) {
        json_response(['success' => false, 'message' => lang('api.need_title')], 422);
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i', $scheduled) ?: DateTime::createFromFormat('Y-m-d H:i:s', $scheduled);
    if (!$dt) {
        json_response(['success' => false, 'message' => lang('api.need_time')], 422);
    }

    if ($token !== '') {
        $settings = telegram_settings();
        $settings['bot_token'] = $token;
        telegram_settings_save($settings);
    }
    if (telegram_token() === '') {
        json_response(['success' => false, 'message' => lang('api.need_token')], 422);
    }

    $chatIds = [];
    if (is_array($userIds) && $userIds) {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ($userIds) {
            $ph = implode(',', array_fill(0, count($userIds), '?'));
            $stmt = db()->prepare("SELECT chat_id FROM users WHERE id IN ({$ph})");
            $stmt->execute($userIds);
            $chatIds = array_merge($chatIds, $stmt->fetchAll(PDO::FETCH_COLUMN));
        }
    }
    if (!is_array($chatIdsIn)) {
        $chatIdsIn = $chatIdsIn !== '' && $chatIdsIn !== null ? [trim((string) $chatIdsIn)] : [];
    }
    foreach ($chatIdsIn as $cid) {
        $cid = trim((string) $cid);
        if ($cid === '') {
            continue;
        }
        if (!is_valid_chat_id($cid)) {
            json_response(['success' => false, 'message' => lang('api.bad_chat', ['id' => $cid])], 422);
        }
        $chatIds[] = $cid;
        $exists = db()->prepare('SELECT id FROM users WHERE chat_id = ?');
        $exists->execute([$cid]);
        if (!$exists->fetch()) {
            db()->prepare('INSERT INTO users (name, chat_id) VALUES (?, ?)')->execute([lang('user.default_name'), $cid]);
        }
    }
    $chatIds = array_values(array_unique($chatIds));
    if (!$chatIds) {
        json_response(['success' => false, 'message' => lang('api.need_chat')], 422);
    }

    $clean = [];
    foreach ((array) $messages as $text) {
        $text = trim((string) $text);
        if ($text === '') {
            continue;
        }
        if (mb_strlen($text) > 4096) {
            json_response(['success' => false, 'message' => lang('api.msg_long')], 422);
        }
        $clean[] = $text;
    }
    if (!$clean) {
        json_response(['success' => false, 'message' => lang('api.need_msg')], 422);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $scheduledSql = $dt->format('Y-m-d H:i:00');
        if ($id > 0) {
            $cur = $pdo->prepare('SELECT status FROM reminders WHERE id = ?');
            $cur->execute([$id]);
            $row = $cur->fetch();
            if (!$row) {
                throw new RuntimeException(lang('api.reminder_missing'));
            }
            if ($row['status'] !== 'pending') {
                throw new RuntimeException(lang('api.edit_pending_only'));
            }
            $pdo->prepare('UPDATE reminders SET title = ?, scheduled_time = ? WHERE id = ?')->execute([$title, $scheduledSql, $id]);
            $pdo->prepare('DELETE FROM reminder_messages WHERE reminder_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM reminder_recipients WHERE reminder_id = ?')->execute([$id]);
            $reminderId = $id;
        } else {
            $pdo->prepare('INSERT INTO reminders (title, scheduled_time, status) VALUES (?, ?, ?)')->execute([$title, $scheduledSql, 'pending']);
            $reminderId = (int) $pdo->lastInsertId();
        }
        $insM = $pdo->prepare('INSERT INTO reminder_messages (reminder_id, message_text, sort_order) VALUES (?, ?, ?)');
        foreach ($clean as $i => $text) {
            $insM->execute([$reminderId, $text, $i + 1]);
        }
        $insR = $pdo->prepare('INSERT INTO reminder_recipients (reminder_id, chat_id) VALUES (?, ?)');
        foreach ($chatIds as $cid) {
            $insR->execute([$reminderId, $cid]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    json_response(['success' => true, 'message' => lang('api.reminder_saved'), 'id' => $reminderId]);
}

function reminders_delete(): void
{
    $id = (int) ($_POST['id'] ?? 0);
    db()->prepare('DELETE FROM reminders WHERE id = ?')->execute([$id]);
    json_response(['success' => true, 'message' => lang('api.reminder_deleted')]);
}

function reminders_retry(): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = db()->prepare("UPDATE reminders SET status = 'pending' WHERE id = ? AND status IN ('failed','partially_sent')");
    $stmt->execute([$id]);
    if ($stmt->rowCount() < 1) {
        json_response(['success' => false, 'message' => lang('api.retry_only')], 422);
    }
    json_response(['success' => true, 'message' => lang('api.retried')]);
}
