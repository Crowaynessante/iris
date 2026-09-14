<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header("Location: " . (is_admin() ? "admin/dashboard.php" : "user/dashboard.php"));
} else {
    header("Location: auth/login.php");
}
exit();
