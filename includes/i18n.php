<?php

function i18n_boot(): void
{
    $allowed = ['zh', 'en'];
    $lang = 'zh';

    if (session_status() === PHP_SESSION_ACTIVE && isset($_GET['lang']) && in_array($_GET['lang'], $allowed, true)) {
        $lang = $_GET['lang'];
        $_SESSION['lang'] = $lang;
        i18n_set_cookie($lang);
        $qs = $_GET;
        unset($qs['lang']);
        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $target = $path . ($qs ? ('?' . http_build_query($qs)) : '');
        header('Location: ' . $target);
        exit;
    }

    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed, true)) {
        $lang = $_SESSION['lang'];
    } elseif (!empty($_COOKIE['trms_lang']) && in_array($_COOKIE['trms_lang'], $allowed, true)) {
        $lang = $_COOKIE['trms_lang'];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['lang'] = $lang;
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['lang'] = $lang;
    }

    $file = dirname(__DIR__) . '/lang/' . $lang . '.php';
    $GLOBALS['I18N_LANG'] = $lang;
    $GLOBALS['I18N'] = is_file($file) ? require $file : [];
}

function i18n_set_cookie(string $lang): void
{
    setcookie('trms_lang', $lang, [
        'expires'  => time() + 86400 * 365,
        'path'     => '/',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

function current_lang(): string
{
    return (string) ($GLOBALS['I18N_LANG'] ?? 'zh');
}

function lang(string $key, array $replace = []): string
{
    $text = $GLOBALS['I18N'][$key] ?? $key;
    foreach ($replace as $name => $value) {
        $text = str_replace(':' . $name, (string) $value, $text);
    }
    return $text;
}

function lang_url(string $code): string
{
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $qs = $_GET;
    $qs['lang'] = $code;
    return $path . '?' . http_build_query($qs);
}

function lang_switcher(): string
{
    $cur = current_lang();
    return '<div class="lang-switch">'
        . '<a class="' . ($cur === 'zh' ? 'active' : '') . '" href="' . e(lang_url('zh')) . '">中文</a>'
        . '<a class="' . ($cur === 'en' ? 'active' : '') . '" href="' . e(lang_url('en')) . '">EN</a>'
        . '</div>';
}
