<?php
require_once __DIR__ . '/../includes/init.php';
if (is_logged_in()) {
    redirect('admin/dashboard.php');
}
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = lang('auth.csrf_short');
    } else {
        $p = (string) ($_POST['password'] ?? '');
        $c = (string) ($_POST['password_confirm'] ?? '');
        if (strlen($p) < 8) {
            $error = lang('auth.pass_short');
        } elseif ($p !== $c) {
            $error = lang('auth.pass_mismatch');
        } else {
            $r = reset_password_with_token($token, $p);
            $r['success'] ? $success = $r['message'] : $error = $r['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e(lang('html_lang')) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(lang('auth.reset_title')) ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
</head>
<body class="auth-page">
<div class="auth-lang"><?= lang_switcher() ?></div>
<div class="auth-shell">
    <div class="auth-card">
        <h1><?= e(lang('auth.reset_heading')) ?></h1>
        <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success py-2"><?= e($success) ?></div>
            <a class="btn btn-teal w-100" href="<?= e(base_url('admin/login.php')) ?>"><?= e(lang('auth.go_login')) ?></a>
        <?php else: ?>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="mb-3"><label class="form-label"><?= e(lang('auth.new_password')) ?></label><input class="form-control" type="password" name="password" required minlength="8"></div>
                <div class="mb-3"><label class="form-label"><?= e(lang('auth.confirm_password')) ?></label><input class="form-control" type="password" name="password_confirm" required minlength="8"></div>
                <button class="btn btn-teal w-100" type="submit"><?= e(lang('auth.save')) ?></button>
            </form>
        <?php endif; ?>
        <div class="text-center mt-3"><a href="<?= e(base_url('admin/login.php')) ?>"><?= e(lang('auth.back')) ?></a></div>
    </div>
</div>
</body>
</html>
