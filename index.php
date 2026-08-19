<?php
require_once __DIR__ . '/includes/init.php';
if (is_logged_in()) {
    redirect('admin/dashboard.php');
}
redirect('admin/login.php');
