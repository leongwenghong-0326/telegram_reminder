<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
$pageTitle   = lang('page.users');
$pageDesc    = lang('page.users_desc');
$active      = 'users';
$pageScripts = ['assets/js/users.js'];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="panel">
    <div class="panel-head">
        <h2><?= e(lang('users.heading')) ?></h2>
        <div class="ms-auto d-flex gap-2">
            <div class="search-field"><i class="bi bi-search"></i><input class="form-control form-control-sm" id="searchInput" placeholder="<?= e(lang('users.search')) ?>"></div>
            <button class="btn btn-sm btn-teal" type="button" id="btnCreate"><?= e(lang('users.new')) ?></button>
        </div>
    </div>
    <div class="panel-body">
        <table class="table table-hover align-middle">
            <thead><tr>
                <th><?= e(lang('users.col_name')) ?></th>
                <th><?= e(lang('users.col_chat')) ?></th>
                <th><?= e(lang('users.col_linked')) ?></th>
                <th><?= e(lang('users.col_actions')) ?></th>
            </tr></thead>
            <tbody id="userTable"></tbody>
        </table>
        <ul class="pagination pagination-sm" id="pager"></ul>
    </div>
</div>
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" id="userForm">
            <div class="modal-header"><h5 class="modal-title"><?= e(lang('users.modal')) ?></h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="userId">
                <div class="mb-3"><label class="form-label"><?= e(lang('users.name')) ?></label><input class="form-control" id="name" required></div>
                <div class="mb-3"><label class="form-label"><?= e(lang('users.chat')) ?></label><input class="form-control" id="chat_id" required></div>
            </div>
            <div class="modal-footer"><button class="btn btn-teal" type="submit"><?= e(lang('users.save')) ?></button></div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
