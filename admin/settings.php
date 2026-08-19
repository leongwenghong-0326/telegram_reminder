<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
$pageTitle   = lang('page.settings');
$pageDesc    = lang('page.settings_desc');
$active      = 'settings';
$pageScripts = ['assets/js/settings.js'];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="panel" style="max-width:640px">
    <div class="panel-head"><h2><?= e(lang('set.heading')) ?></h2></div>
    <div class="panel-body">
        <ol class="mb-4">
            <li><?= e(lang('set.step1')) ?></li>
            <li><?= e(lang('set.step2')) ?></li>
            <li><?= e(lang('set.step3')) ?></li>
        </ol>
        <form id="settingsForm">
            <div class="mb-3">
                <label class="form-label"><?= e(lang('set.token')) ?></label>
                <input class="form-control" id="bot_token">
                <div class="form-text" id="tokenHint"><?= e(lang('set.not_saved')) ?></div>
            </div>
            <div class="mb-3">
                <label class="form-label"><?= e(lang('set.my_chat')) ?></label>
                <input class="form-control" id="chat_id">
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-teal" type="submit"><?= e(lang('set.save')) ?></button>
                <button class="btn btn-outline-secondary" type="button" id="btnTest"><?= e(lang('set.test')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
