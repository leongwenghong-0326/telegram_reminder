<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
$pageTitle   = lang('page.reminders');
$pageDesc    = lang('page.reminders_desc');
$active      = 'reminders';
$pageScripts = ['assets/js/reminders.js'];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="panel">
    <div class="panel-head">
        <h2><?= e(lang('rem.list')) ?></h2>
        <div class="ms-auto d-flex gap-2">
            <div class="search-field">
                <i class="bi bi-search"></i>
                <input class="form-control form-control-sm" id="searchInput" placeholder="<?= e(lang('rem.search')) ?>">
            </div>
            <a class="btn btn-sm btn-teal" href="<?= e(base_url('admin/create.php')) ?>"><?= e(lang('rem.new')) ?></a>
        </div>
    </div>
    <div class="panel-body pt-2">
        <div class="d-flex flex-wrap gap-2 mb-3" id="filterGroup">
            <button class="filter-chip active" data-filter="all" type="button"><?= e(lang('rem.filter_all')) ?></button>
            <button class="filter-chip" data-filter="today" type="button"><?= e(lang('rem.filter_today')) ?></button>
            <button class="filter-chip" data-filter="pending" type="button"><?= e(lang('rem.filter_pending')) ?></button>
            <button class="filter-chip" data-filter="sent" type="button"><?= e(lang('rem.filter_sent')) ?></button>
            <button class="filter-chip" data-filter="failed" type="button"><?= e(lang('rem.filter_failed')) ?></button>
            <button class="filter-chip" data-filter="week" type="button"><?= e(lang('rem.filter_week')) ?></button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th><?= e(lang('rem.col_title')) ?></th>
                        <th><?= e(lang('rem.col_time')) ?></th>
                        <th><?= e(lang('rem.col_count')) ?></th>
                        <th><?= e(lang('rem.col_to')) ?></th>
                        <th><?= e(lang('rem.col_status')) ?></th>
                        <th><?= e(lang('rem.col_actions')) ?></th>
                    </tr>
                </thead>
                <tbody id="reminderTable"><tr><td colspan="6" class="empty-state"><?= e(lang('rem.loading')) ?></td></tr></tbody>
            </table>
        </div>
        <ul class="pagination pagination-sm" id="pager"></ul>
    </div>
</div>

<div class="modal fade" id="reminderModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="reminderForm">
                <div class="modal-header">
                    <h5 class="modal-title"><?= e(lang('rem.edit')) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reminderId">
                    <div class="mb-3"><label class="form-label"><?= e(lang('rem.title')) ?></label><input class="form-control" id="title" required></div>
                    <div class="mb-3"><label class="form-label"><?= e(lang('rem.time')) ?></label><input class="form-control" type="datetime-local" id="scheduled_time" required></div>
                    <div class="mb-3">
                        <label class="form-label"><?= e(lang('rem.recipients')) ?></label>
                        <div class="d-flex gap-2 mb-2">
                            <input class="form-control form-control-sm" id="userSearch" placeholder="<?= e(lang('rem.search_user')) ?>">
                            <button class="btn btn-sm btn-teal" type="button" id="btnAddChat"><?= e(lang('rem.add')) ?></button>
                        </div>
                        <div class="user-picker" id="userPicker"></div>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <label class="form-label mb-0"><?= e(lang('rem.messages')) ?></label>
                        <button class="btn btn-sm btn-teal" type="button" id="addMessage"><?= e(lang('rem.add_msg')) ?></button>
                    </div>
                    <div id="messageList"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal"><?= e(lang('rem.cancel')) ?></button>
                    <button class="btn btn-teal" type="submit"><?= e(lang('rem.save')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
