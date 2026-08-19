<?php
require_once __DIR__ . '/../includes/init.php';
if (is_logged_in()) {
    redirect('admin/dashboard.php');
}
$message = $error = $debugUrl = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = lang('auth.csrf_short');
    } else {
        $account = trim((string) ($_POST['account'] ?? ''));
        if ($account === '') {
            $error = lang('auth.need_account');
        } else {
            $r = request_password_reset($account);
            $message = $r['message'];
            $debugUrl = $r['debug_url'] ?? '';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e(lang('html_lang')) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(lang('auth.forgot_title')) ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
</head>
<body class="auth-page">
<div class="auth-lang"><?= lang_switcher() ?></div>
<div class="auth-shell">
    <div class="auth-card">
        <h1><?= e(lang('auth.forgot_title')) ?></h1>
        <p class="lead-text"><?= e(lang('auth.forgot_lead')) ?></p>
        <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
        <?php if ($message): ?>
            <div class="alert alert-success py-2"><?= e($message) ?></div>
            <?php if ($debugUrl): ?><div class="alert alert-warning py-2"><?= e(lang('auth.debug')) ?><a href="<?= e($debugUrl) ?>"><?= e($debugUrl) ?></a></div><?php endif; ?>
        <?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3"><input class="form-control" name="account" required placeholder="<?= e(lang('auth.user')) ?>"></div>
            <button class="btn btn-teal w-100" type="submit"><?= e(lang('auth.send')) ?></button>
        </form>
        <div class="text-center mt-3"><a href="<?= e(base_url('admin/login.php')) ?>"><?= e(lang('auth.back')) ?></a></div>
    </div>
</div>
</body>
</html>
