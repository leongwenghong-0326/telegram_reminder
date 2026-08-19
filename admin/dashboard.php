<?php
require_once __DIR__ . '/../includes/init.php';
require_login();

$pageTitle   = lang('page.dashboard');
$pageDesc    = lang('page.dashboard_desc');
$active      = 'dashboard';
$pageScripts = ['assets/js/home.js'];

$pdo   = db();
$today = date('Y-m-d');
$me    = current_admin();
$hour  = (int) date('G');
$hello = $hour < 12 ? lang('hello.morning') : ($hour < 18 ? lang('hello.afternoon') : lang('hello.evening'));

$total     = (int) $pdo->query('SELECT COUNT(*) FROM reminders')->fetchColumn();
$pending   = (int) $pdo->query("SELECT COUNT(*) FROM reminders WHERE status = 'pending'")->fetchColumn();
$failed    = (int) $pdo->query("SELECT COUNT(*) FROM reminders WHERE status IN ('failed','partially_sent')")->fetchColumn();
$sentStmt  = $pdo->prepare('SELECT COUNT(*) FROM message_logs WHERE status = ? AND DATE(sent_time) = ?');
$sentStmt->execute(['sent', $today]);
$sentToday = (int) $sentStmt->fetchColumn();
$users     = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

$upcoming = $pdo->query(
    "SELECT r.*,
        (SELECT COUNT(*) FROM reminder_messages m WHERE m.reminder_id = r.id) AS message_count,
        (SELECT GROUP_CONCAT(rr.chat_id SEPARATOR ', ') FROM reminder_recipients rr WHERE rr.reminder_id = r.id) AS chat_ids
     FROM reminders r
     WHERE r.status = 'pending'
     ORDER BY r.scheduled_time ASC
     LIMIT 8"
)->fetchAll();

$recentLogs = $pdo->query(
    "SELECT l.*, r.title
     FROM message_logs l
     INNER JOIN reminders r ON r.id = l.reminder_id
     ORDER BY l.id DESC
     LIMIT 8"
)->fetchAll();

$next = $upcoming[0] ?? null;
$tokenOk = telegram_token() !== '';
$chatId  = (string) (telegram_settings()['default_chat_id'] ?? '');
$cronUrl = base_url('cron/send_reminders.php?key=' . urlencode(CRON_SECRET_KEY));

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="stat-card d-flex justify-content-between">
            <div>
                <div class="label"><?= e(lang('dash.stat_all')) ?></div>
                <div class="value"><?= $total ?></div>
                <div class="hint"><?= e(lang('dash.stat_all_hint')) ?></div>
            </div>
            <div class="icon icon-navy"><i class="bi bi-collection"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card d-flex justify-content-between">
            <div>
                <div class="label"><?= e(lang('dash.stat_pending')) ?></div>
                <div class="value"><?= $pending ?></div>
                <div class="hint"><?= e(lang('dash.stat_pending_hint')) ?></div>
            </div>
            <div class="icon icon-amber"><i class="bi bi-hourglass-split"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card d-flex justify-content-between">
            <div>
                <div class="label"><?= e(lang('dash.stat_today')) ?></div>
                <div class="value"><?= $sentToday ?></div>
                <div class="hint"><?= e(lang('dash.stat_today_hint')) ?></div>
            </div>
            <div class="icon icon-green"><i class="bi bi-check2-circle"></i></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card d-flex justify-content-between">
            <div>
                <div class="label"><?= e(lang('dash.stat_failed')) ?></div>
                <div class="value"><?= $failed ?></div>
                <div class="hint"><?= e(lang('dash.stat_failed_hint')) ?></div>
            </div>
            <div class="icon icon-red"><i class="bi bi-exclamation-triangle"></i></div>
        </div>
    </div>
</div>

<div class="hero-banner">
    <div>
        <p class="eyebrow"><?= e($hello) ?>，<?= e($me['username'] ?? lang('hello.admin')) ?></p>
        <h2><?= $pending > 0 ? e(lang('dash.queued', ['count' => (string) $pending])) : e(lang('dash.none_today')) ?></h2>
        <p>
            <?php if ($next): ?>
                <?= e(lang('dash.next', ['title' => $next['title'], 'time' => format_datetime($next['scheduled_time'])])) ?>
            <?php else: ?>
                <?= e(lang('dash.create_hint')) ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="hero-actions">
        <?php if ($next): ?>
            <div class="countdown" id="nextCountdown" data-time="<?= e($next['scheduled_time']) ?>">
                <span class="countdown-label"><?= e(lang('dash.until_send')) ?></span>
                <strong id="countdownText"><?= e(lang('dash.counting')) ?></strong>
            </div>
        <?php endif; ?>
        <a class="btn btn-ghost" href="<?= e(base_url('admin/create.php')) ?>"><i class="bi bi-plus-lg"></i> <?= e(lang('dash.new_reminder')) ?></a>
    </div>
</div>

<div class="panel mb-3">
    <div class="panel-head">
        <h2><?= e(lang('dash.upcoming')) ?></h2>
        <a class="btn btn-sm btn-teal ms-auto" href="<?= e(base_url('admin/reminders.php')) ?>"><?= e(lang('dash.all_reminders')) ?></a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th><?= e(lang('dash.col_title')) ?></th>
                    <th><?= e(lang('dash.col_time')) ?></th>
                    <th><?= e(lang('dash.col_messages')) ?></th>
                    <th><?= e(lang('dash.col_recipients')) ?></th>
                    <th><?= e(lang('dash.col_status')) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$upcoming): ?>
                <tr><td colspan="5" class="empty-state"><i class="bi bi-moon-stars d-block mb-2"></i><?= e(lang('dash.no_upcoming')) ?></td></tr>
            <?php else: foreach ($upcoming as $row): ?>
                <tr>
                    <td class="fw-semibold"><?= e($row['title']) ?></td>
                    <td><?= e(format_datetime($row['scheduled_time'])) ?></td>
                    <td><?= e(lang('dash.msg_count', ['count' => (string) (int) $row['message_count']])) ?></td>
                    <td><small><?= e($row['chat_ids'] ?: '—') ?></small></td>
                    <td><?= status_badge($row['status']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-head">
                <h2><?= e(lang('dash.recent_logs')) ?></h2>
                <a class="btn btn-sm btn-outline-secondary ms-auto" href="<?= e(base_url('admin/logs.php')) ?>"><?= e(lang('dash.view_all')) ?></a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?= e(lang('dash.col_reminder')) ?></th>
                            <th><?= e(lang('dash.col_chat')) ?></th>
                            <th><?= e(lang('dash.col_status')) ?></th>
                            <th><?= e(lang('logs.col_time')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$recentLogs): ?>
                        <tr><td colspan="4" class="empty-state"><?= e(lang('dash.no_logs')) ?></td></tr>
                    <?php else: foreach ($recentLogs as $log): ?>
                        <tr>
                            <td><?= e($log['title']) ?></td>
                            <td><code><?= e($log['chat_id']) ?></code></td>
                            <td><?= status_badge($log['status']) ?></td>
                            <td><?= e(format_datetime($log['sent_time'])) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="panel mb-3">
            <div class="panel-head"><h2><?= e(lang('dash.system')) ?></h2></div>
            <div class="panel-body">
                <div class="overview-list">
                    <div class="overview-item">
                        <span><?= e(lang('dash.bot_token')) ?></span>
                        <strong class="<?= $tokenOk ? 'text-success' : 'text-danger' ?>"><?= $tokenOk ? e(lang('dash.connected')) : e(lang('dash.unset')) ?></strong>
                    </div>
                    <div class="overview-item">
                        <span><?= e(lang('dash.default_chat')) ?></span>
                        <strong><?= $chatId !== '' ? e($chatId) : e(lang('dash.unset')) ?></strong>
                    </div>
                    <div class="overview-item">
                        <span><?= e(lang('dash.tg_users')) ?></span>
                        <strong><?= $users ?></strong>
                    </div>
                    <div class="overview-item">
                        <span><?= e(lang('dash.timezone')) ?></span>
                        <strong><?= e(APP_TIMEZONE) ?></strong>
                    </div>
                </div>
                <?php if (!$tokenOk): ?>
                    <a class="btn btn-teal w-100 mt-3" href="<?= e(base_url('admin/settings.php')) ?>"><?= e(lang('dash.go_token')) ?></a>
                <?php else: ?>
                    <a class="btn btn-outline-secondary w-100 mt-3" href="<?= e(base_url('admin/settings.php')) ?>"><?= e(lang('nav.settings')) ?></a>
                <?php endif; ?>
            </div>
        </div>
        <div class="panel cron-box">
            <div class="panel-head">
                <h2><?= e(lang('dash.cron')) ?></h2>
                <button class="btn btn-sm btn-outline-secondary ms-auto" type="button" id="copyCron"><?= e(lang('dash.copy')) ?></button>
            </div>
            <div class="panel-body">
                <p class="small text-muted"><?= e(lang('dash.cron_hint')) ?></p>
                <code id="cronUrl"><?= e($cronUrl) ?></code>
                <a class="btn btn-sm btn-teal w-100 mt-3" href="<?= e($cronUrl) ?>" target="_blank"><?= e(lang('dash.run_now')) ?></a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
