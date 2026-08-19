<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
$pageTitle   = lang('page.logs');
$pageDesc    = lang('page.logs_desc');
$active      = 'logs';
$pageScripts = ['assets/js/logs.js'];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="panel">
    <div class="panel-head">
        <h2><?= e(lang('logs.heading')) ?></h2>
        <div class="search-field ms-auto"><i class="bi bi-search"></i><input class="form-control form-control-sm" id="searchInput" placeholder="<?= e(lang('logs.search')) ?>"></div>
    </div>
    <div class="panel-body pt-2">
        <div class="d-flex flex-wrap gap-2 mb-3" id="filterGroup">
            <button class="filter-chip active" data-filter="all" type="button"><?= e(lang('rem.filter_all')) ?></button>
            <button class="filter-chip" data-filter="today" type="button"><?= e(lang('rem.filter_today')) ?></button>
            <button class="filter-chip" data-filter="sent" type="button"><?= e(lang('rem.filter_sent')) ?></button>
            <button class="filter-chip" data-filter="failed" type="button"><?= e(lang('rem.filter_failed')) ?></button>
            <button class="filter-chip" data-filter="week" type="button"><?= e(lang('rem.filter_week')) ?></button>
        </div>
        <table class="table table-hover align-middle">
            <thead><tr>
                <th><?= e(lang('logs.col_reminder')) ?></th>
                <th><?= e(lang('logs.col_to')) ?></th>
                <th><?= e(lang('logs.col_text')) ?></th>
                <th><?= e(lang('logs.col_status')) ?></th>
                <th><?= e(lang('logs.col_time')) ?></th>
                <th><?= e(lang('logs.col_error')) ?></th>
            </tr></thead>
            <tbody id="logTable"></tbody>
        </table>
        <ul class="pagination pagination-sm" id="pager"></ul>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
