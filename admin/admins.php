<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
$pageTitle   = lang('page.admins');
$active      = 'admins';
$pageScripts = ['assets/js/admins.js'];
$me          = current_admin();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-head"><h2><?= e(lang('adm.accounts')) ?></h2><button class="btn btn-sm btn-teal ms-auto" id="btnCreate" type="button"><?= e(lang('adm.new')) ?></button></div>
            <table class="table mb-0 align-middle">
                <thead><tr>
                    <th><?= e(lang('adm.col_user')) ?></th>
                    <th><?= e(lang('adm.col_email')) ?></th>
                    <th><?= e(lang('adm.col_login')) ?></th>
                    <th></th>
                </tr></thead>
                <tbody id="adminTable"></tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-head"><h2><?= e(lang('adm.change_pass')) ?></h2></div>
            <div class="panel-body">
                <p class="text-muted small"><?= e($me['username']) ?></p>
                <form id="passwordForm">
                    <div class="mb-3"><input class="form-control" type="password" name="old_password" placeholder="<?= e(lang('adm.old_pass')) ?>" required></div>
                    <div class="mb-3"><input class="form-control" type="password" name="new_password" placeholder="<?= e(lang('adm.new_pass')) ?>" required minlength="8"></div>
                    <button class="btn btn-teal" type="submit"><?= e(lang('adm.update')) ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="adminModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" id="adminForm">
            <div class="modal-header"><h5 class="modal-title"><?= e(lang('adm.add_title')) ?></h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><input class="form-control" name="username" placeholder="<?= e(lang('adm.username')) ?>" required></div>
                <div class="mb-3"><input class="form-control" type="email" name="email" placeholder="<?= e(lang('adm.email')) ?>" required></div>
                <div class="mb-3"><input class="form-control" type="password" name="password" placeholder="<?= e(lang('adm.password')) ?>" required minlength="8"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-teal" type="submit"><?= e(lang('adm.create')) ?></button></div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
