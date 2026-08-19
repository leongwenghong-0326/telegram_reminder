<?php
require_once __DIR__ . '/../includes/init.php';
if (is_logged_in()) {
    redirect('admin/dashboard.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = lang('auth.csrf');
    } else {
        $result = login_admin(trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''));
        if ($result['success']) {
            redirect('admin/dashboard.php');
        }
        $error = $result['message'];
    }
}
$bubbles = [
    lang('auth.bubble1'),
    lang('auth.bubble2'),
    lang('auth.bubble3'),
    lang('auth.bubble4'),
    lang('auth.bubble5'),
];
?>
<!DOCTYPE html>
<html lang="<?= e(lang('html_lang')) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(lang('auth.login_title')) ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
</head>
<body class="auth-page">
<div class="auth-lang"><?= lang_switcher() ?></div>
<div class="auth-shell">
    <section class="auth-form-wrap">
        <div class="auth-card" id="authCard">
            <div class="auth-copy mb-3">
                <span class="brand-mark auth-mark"><i class="bi bi-send-fill"></i></span>
                <h1 class="h4 mt-2 mb-1"><?= e(lang('auth.heading')) ?></h1>
                <p class="lead-text mb-0"><?= e(lang('auth.lead')) ?></p>
            </div>
            <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
            <form method="post" id="loginForm">
                <?= csrf_field() ?>
                <div class="mb-3 auth-field">
                    <label class="form-label"><?= e(lang('auth.user')) ?></label>
                    <input class="form-control" name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
                </div>
                <div class="mb-3 auth-field">
                    <label class="form-label"><?= e(lang('auth.pass')) ?></label>
                    <div class="password-wrap">
                        <input class="form-control" type="password" name="password" id="password" required>
                        <button class="toggle-pass" type="button" id="togglePass"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <button class="btn btn-teal w-100 py-2 auth-login-btn" type="submit">
                    <span><?= e(lang('auth.login')) ?></span>
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
            <div class="text-center mt-3"><a href="<?= e(base_url('admin/forgot_password.php')) ?>"><?= e(lang('auth.forgot')) ?></a></div>
        </div>
        <div class="auth-orbit mt-3">
            <?php foreach ($bubbles as $i => $text): ?>
                <div class="auth-bubble" style="--i:<?= $i ?>"><?= e($text) ?></div>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="auth-visual d-none" id="authVisual">
        <canvas id="authCanvas" aria-hidden="true"></canvas>
    </section>
</div>
<script src="<?= e(asset_url('assets/js/login.js')) ?>"></script>
</body>
</html>
