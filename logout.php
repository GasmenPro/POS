<?php
/**
 * Logout handler
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/login.php');
}

if (!verify_csrf()) {
    set_flash('error', 'Invalid request. Please try again.');
    redirect('/login.php');
}

if (is_logged_in()) {
    $user_id = get_current_user_id();
    record_activity_log($user_id, 'logout', 'auth', 'User logged out');
}

logout_user();
set_flash('success', 'You have been logged out.');
redirect('/login.php');
