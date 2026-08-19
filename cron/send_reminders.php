<?php

require_once __DIR__ . '/../includes/init.php';

$isCli = PHP_SAPI === 'cli';
$key   = $isCli ? (string) ($argv[1] ?? '') : (string) ($_GET['key'] ?? $_POST['key'] ?? '');

if (!hash_equals((string) CRON_SECRET_KEY, $key)) {
    http_response_code(403);
    echo 'Forbidden: invalid cron key';
    exit($isCli ? 1 : 0);
}
if (CRON_SECRET_KEY === '' || CRON_SECRET_KEY === 'CHANGE_THIS_TO_A_LONG_RANDOM_STRING') {
    http_response_code(500);
    echo 'Please set CRON_SECRET_KEY';
    exit;
}

$lockDir  = dirname(__DIR__) . '/storage';
$lockFile = $lockDir . '/cron.lock';
if (!is_dir($lockDir)) {
    mkdir($lockDir, 0755, true);
}
$lock = fopen($lockFile, 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo 'Cron already running';
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
$now = date('Y-m-d H:i:s');
$stats = ['processed' => 0, 'sent' => 0, 'failed' => 0, 'partial' => 0];

try {
    $stmt = db()->prepare("SELECT * FROM reminders WHERE status = 'pending' AND scheduled_time <= ? ORDER BY scheduled_time, id");
    $stmt->execute([$now]);
    foreach ($stmt->fetchAll() as $reminder) {
        process_reminder($reminder, $stats);
    }
    $output = sprintf("[%s] processed=%d sent=%d failed=%d partial=%d\n", $now, $stats['processed'], $stats['sent'], $stats['failed'], $stats['partial']);
    echo $output;
    file_put_contents($lockDir . '/cron.log', $output, FILE_APPEND | LOCK_EX);
} catch (Throwable $e) {
    echo 'Cron error: ' . $e->getMessage();
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}

function process_reminder(array $reminder, array &$stats): void
{
    $id  = (int) $reminder['id'];
    $pdo = db();
    $messages = $pdo->prepare('SELECT message_text FROM reminder_messages WHERE reminder_id = ? ORDER BY sort_order, id');
    $messages->execute([$id]);
    $messages = $messages->fetchAll(PDO::FETCH_COLUMN);
    $recs = $pdo->prepare('SELECT chat_id FROM reminder_recipients WHERE reminder_id = ? ORDER BY id');
    $recs->execute([$id]);
    $recipients = $recs->fetchAll(PDO::FETCH_COLUMN);
    $log = $pdo->prepare('INSERT INTO message_logs (reminder_id, chat_id, message_text, status, sent_time, error_message, telegram_response_code) VALUES (?, ?, ?, ?, ?, ?, ?)');

    $sent = 0;
    $failed = 0;
    if (!$messages || !$recipients) {
        $failed = 1;
        $log->execute([$id, $recipients[0] ?? '0', $messages[0] ?? '', 'failed', date('Y-m-d H:i:s'), '缺少消息或接收人', 0]);
    } else {
        $total = count($recipients) * count($messages);
        $i = 0;
        foreach ($recipients as $chatId) {
            foreach ($messages as $text) {
                $result = sendTelegramMessage($chatId, $text);
                $ok = $result['success'];
                $log->execute([$id, $chatId, $text, $ok ? 'sent' : 'failed', date('Y-m-d H:i:s'), $ok ? null : ($result['error'] ?? ''), $result['http_code']]);
                $ok ? $sent++ : $failed++;
                $i++;
                if ($i < $total) {
                    telegram_delay();
                }
            }
        }
    }

    if ($failed === 0 && $sent > 0) {
        $status = 'sent';
        $stats['sent']++;
    } elseif ($sent === 0) {
        $status = 'failed';
        $stats['failed']++;
    } else {
        $status = 'partially_sent';
        $stats['partial']++;
    }
    $pdo->prepare('UPDATE reminders SET status = ? WHERE id = ?')->execute([$status, $id]);
    $stats['processed']++;
}
