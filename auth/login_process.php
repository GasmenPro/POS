<?php
/**
 * Login form processor
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/login.php');
}

if (!verify_csrf()) {
    set_flash('error', 'Invalid request. Please try again.');
    redirect('/login.php');
}

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if ($login === '' || $password === '') {
    set_flash('error', 'Invalid credentials.');
    redirect('/login.php');
}

$user = find_user_by_login($login);
$valid = $user && password_verify($password, $user['password_hash']);
$active = $valid && $user['status'] === 'active';

if (!$valid || !$active) {
    $user_id = $user ? (int) $user['id'] : null;
    record_login_log($user_id, 'failed');
    set_flash('error', 'Invalid credentials.');
    redirect('/login.php');
}

login_user($user);
record_login_log((int) $user['id'], 'success');
record_activity_log((int) $user['id'], 'login', 'auth', 'User logged in successfully');

redirect('/account.php');
