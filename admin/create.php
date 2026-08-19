<?php
require_once __DIR__ . '/../includes/init.php';
require_login();

$pageTitle   = lang('page.create');
$pageDesc    = lang('page.create_desc');
$active      = 'create';
$pageScripts = ['assets/js/dashboard.js'];
$s           = telegram_settings();
$savedToken  = telegram_token_masked();
$savedChat   = (string) ($s['default_chat_id'] ?? '');
$notes = db()->query('SELECT id, title, message_text FROM reminder_templates WHERE is_system = 0 ORDER BY id DESC')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-head"><h2><?= e(lang('create.heading')) ?></h2></div>
            <div class="panel-body">
                <form id="quickForm">
                    <div class="mb-3">
                        <label class="form-label"><?= e(lang('create.token')) ?></label>
                        <input class="form-control" id="bot_token" placeholder="<?= $savedToken ? e(lang('create.token_saved', ['token' => $savedToken])) : e(lang('create.token_new')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= e(lang('create.chat')) ?></label>
                        <input class="form-control" id="chat_id" value="<?= e($savedChat) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= e(lang('create.title')) ?></label>
                        <input class="form-control" id="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= e(lang('create.time')) ?></label>
                        <input class="form-control" type="datetime-local" id="scheduled_time" required>
                    </div>
                    <div class="mb-2 d-flex justify-content-between align-items-center">
                        <label class="form-label mb-0"><?= e(lang('create.content')) ?></label>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" type="button" id="tplToggle">😊 <?= e(lang('create.tpl_btn')) ?></button>
                            <button class="btn btn-sm btn-teal" type="button" id="addQuickMsg"><?= e(lang('create.add_msg')) ?></button>
                        </div>
                    </div>
                    <div id="quickMessages"></div>
                    <?php $pickerId = 'msgTpl'; require __DIR__ . '/../includes/emoji_picker.php'; ?>
                    <button class="btn btn-teal mt-2" type="submit"><?= e(lang('create.save')) ?></button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-head">
                <h2><?= e(lang('create.notes')) ?></h2>
                <button class="btn btn-sm btn-outline-secondary ms-auto" type="button" id="noteTplToggle">😊 <?= e(lang('create.tpl_btn')) ?></button>
            </div>
            <div class="panel-body">
                <p class="text-muted small mb-2"><?= e(lang('create.notes_hint')) ?></p>
                <?php $pickerId = 'noteTpl'; require __DIR__ . '/../includes/emoji_picker.php'; ?>
                <textarea class="form-control" id="noteText" rows="4"></textarea>
                <button class="btn btn-teal w-100 mt-2" type="button" id="saveNote"><?= e(lang('create.save_note')) ?></button>
                <div class="note-list mt-3" id="noteList">
                    <?php foreach ($notes as $note): ?>
                        <?php $text = implode("\n", shortcut_messages((string) $note['message_text'])); ?>
                        <div class="note-item">
                            <button class="note-use" type="button">
                                <strong><?= e($note['title'] !== '' ? $note['title'] : mb_strimwidth($text, 0, 24, '…')) ?></strong>
                                <small><?= e(mb_strimwidth($text, 0, 60, '…')) ?></small>
                            </button>
                            <textarea class="note-raw" hidden><?= e($text) ?></textarea>
                            <button class="note-del" type="button" data-id="<?= (int) $note['id'] ?>" aria-label="<?= e(lang('create.delete')) ?>">&times;</button>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$notes): ?>
                        <p class="text-muted small mb-0" id="noteEmpty"><?= e(lang('create.no_notes')) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
