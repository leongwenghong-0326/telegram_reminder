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
        'list'     => templates_list(),
        'save'     => templates_save(),
        'delete'   => templates_delete(),
        'schedule' => templates_schedule(),
        default    => json_response(['success' => false, 'message' => lang('err.unknown')], 400),
    };
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => APP_DEBUG ? $e->getMessage() : lang('err.server')], 500);
}

function templates_list(): void
{
    $rows = db()->query('SELECT id, title, message_text, chat_id, created_at FROM reminder_templates WHERE is_system = 0 ORDER BY id DESC')->fetchAll();
    json_response(['success' => true, 'data' => $rows]);
}

function templates_save(): void
{
    $id      = (int) ($_POST['id'] ?? 0);
    $title   = trim((string) ($_POST['title'] ?? ''));
    $message = trim((string) ($_POST['message_text'] ?? ''));
    $offset  = max(1, (int) ($_POST['offset_minutes'] ?? 30));
    $icon    = trim((string) ($_POST['icon'] ?? '📝'));

    if ($message === '') {
        json_response(['success' => false, 'message' => lang('api.need_content')], 422);
    }
    if ($title === '') {
        $title = mb_strimwidth(preg_replace('/\s+/', ' ', $message) ?? $message, 0, 40, '…');
    }
    if (mb_strlen($title) > 200 || mb_strlen($message) > 4096) {
        json_response(['success' => false, 'message' => lang('api.content_long')], 422);
    }
    if (mb_strlen($icon) > 16) {
        $icon = '⭐';
    }

    if ($id > 0) {
        $cur = db()->prepare('SELECT is_system FROM reminder_templates WHERE id = ?');
        $cur->execute([$id]);
        $row = $cur->fetch();
        if (!$row) {
            json_response(['success' => false, 'message' => lang('api.note_missing')], 404);
        }
        db()->prepare('UPDATE reminder_templates SET title = ?, message_text = ?, offset_minutes = ?, icon = ? WHERE id = ?')
            ->execute([$title, $message, $offset, $icon, $id]);
        json_response(['success' => true, 'message' => lang('api.saved')]);
    }

    db()->prepare('INSERT INTO reminder_templates (title, message_text, offset_minutes, icon, is_system) VALUES (?, ?, ?, ?, 0)')
        ->execute([$title, $message, $offset, $icon]);
    json_response(['success' => true, 'message' => lang('api.saved'), 'id' => (int) db()->lastInsertId()]);
}

function templates_delete(): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = db()->prepare('SELECT is_system FROM reminder_templates WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        json_response(['success' => false, 'message' => lang('api.note_missing')], 404);
    }
    db()->prepare('DELETE FROM reminder_templates WHERE id = ?')->execute([$id]);
    json_response(['success' => true, 'message' => lang('api.deleted')]);
}

function templates_schedule(): void
{
    $id        = (int) ($_POST['id'] ?? 0);
    $scheduled = str_replace('T', ' ', trim((string) ($_POST['scheduled_time'] ?? '')));
    $stmt = db()->prepare('SELECT * FROM reminder_templates WHERE id = ?');
    $stmt->execute([$id]);
    $tpl = $stmt->fetch();
    if (!$tpl) {
        json_response(['success' => false, 'message' => lang('api.note_missing')], 404);
    }

    $dt = DateTime::createFromFormat('Y-m-d H:i', $scheduled) ?: DateTime::createFromFormat('Y-m-d H:i:s', $scheduled);
    if (!$dt) {
        json_response(['success' => false, 'message' => lang('api.need_time')], 422);
    }

    $messages = shortcut_messages((string) $tpl['message_text']);
    if (!$messages) {
        json_response(['success' => false, 'message' => lang('api.need_content')], 422);
    }
    if (telegram_token() === '') {
        json_response(['success' => false, 'message' => lang('api.need_token')], 422);
    }

    $chatId = trim((string) ($_POST['chat_id'] ?? ''));
    if ($chatId === '') {
        $chatId = trim((string) ($tpl['chat_id'] ?? ''));
    }
    if ($chatId === '') {
        $chatId = trim((string) (telegram_settings()['default_chat_id'] ?? ''));
    }
    if (!is_valid_chat_id($chatId)) {
        json_response(['success' => false, 'message' => lang('api.need_chat_id')], 422);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO reminders (title, scheduled_time, status) VALUES (?, ?, ?)')
            ->execute([$tpl['title'], $dt->format('Y-m-d H:i:00'), 'pending']);
        $reminderId = (int) $pdo->lastInsertId();
        $insM = $pdo->prepare('INSERT INTO reminder_messages (reminder_id, message_text, sort_order) VALUES (?, ?, ?)');
        foreach ($messages as $i => $text) {
            $insM->execute([$reminderId, $text, $i + 1]);
        }
        $pdo->prepare('INSERT INTO reminder_recipients (reminder_id, chat_id) VALUES (?, ?)')->execute([$reminderId, $chatId]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    json_response([
        'success' => true,
        'message' => lang('api.reminder_saved'),
        'id'      => $reminderId,
    ]);
}
