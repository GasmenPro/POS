<?php
/**
 * User management form processor
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/users.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/users/index.php');
}

if (!verify_csrf()) {
    set_flash('error', 'Invalid request. Please try again.');
    redirect('/users/index.php');
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        require_permission('users.manage');
        handle_create_user();
        break;
    case 'update':
        require_permission('users.manage');
        handle_update_user();
        break;
    case 'activate':
    case 'deactivate':
        require_permission('users.manage');
        handle_status_change($action);
        break;
    default:
        set_flash('error', 'Invalid action.');
        redirect('/users/index.php');
}

function save_old_input()
{
    $_SESSION['old_input'] = $_POST;
}

function clear_old_input()
{
    unset($_SESSION['old_input']);
}

function validate_user_fields($require_password = true)
{
    $fields = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'status' => $_POST['status'] ?? 'active',
        'role_id' => (int) ($_POST['role_id'] ?? 0),
    ];

    if ($fields['first_name'] === '' || $fields['last_name'] === '' ||
        $fields['username'] === '' || $fields['email'] === '') {
        return [false, 'All required fields must be filled in.', $fields, ''];
    }

    if (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        return [false, 'Please enter a valid email address.', $fields, ''];
    }

    if (!in_array($fields['status'], ['active', 'inactive', 'suspended'], true)) {
        return [false, 'Invalid status selected.', $fields, ''];
    }

    if ($fields['role_id'] <= 0 || !get_role_name($fields['role_id'])) {
        return [false, 'Please select a valid role.', $fields, ''];
    }

    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($require_password) {
        if ($password === '' || $password_confirm === '') {
            return [false, 'Password and confirmation are required.', $fields, ''];
        }
        if ($password !== $password_confirm) {
            return [false, 'Passwords do not match.', $fields, ''];
        }
        if (strlen($password) < 8) {
            return [false, 'Password must be at least 8 characters.', $fields, ''];
        }
    } elseif ($password !== '' || $password_confirm !== '') {
        if ($password !== $password_confirm) {
            return [false, 'Passwords do not match.', $fields, ''];
        }
        if (strlen($password) < 8) {
            return [false, 'Password must be at least 8 characters.', $fields, ''];
        }
    }

    return [true, '', $fields, $password];
}

function would_lock_out_current_user($target_user_id, $new_status, $new_role_id)
{
    $current_id = get_current_user_id();
    if ($current_id !== $target_user_id) {
        return false;
    }

    if ($new_status !== 'active') {
        return true;
    }

    $role_name = get_role_name($new_role_id);
    if ($role_name !== 'Administrator') {
        return true;
    }

    return false;
}

function handle_create_user()
{
    list($valid, $message, $fields, $password) = validate_user_fields(true);
    if (!$valid) {
        save_old_input();
        set_flash('error', $message);
        redirect('/users/add.php');
    }

    if (username_exists($fields['username'])) {
        save_old_input();
        set_flash('error', 'Username is already taken.');
        redirect('/users/add.php');
    }

    if (email_exists($fields['email'])) {
        save_old_input();
        set_flash('error', 'Email is already registered.');
        redirect('/users/add.php');
    }

    $fields['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    $user_id = create_user($fields, $fields['role_id']);

    if (!$user_id) {
        save_old_input();
        set_flash('error', 'Unable to create user. Please try again.');
        redirect('/users/add.php');
    }

    clear_old_input();
    $role_name = get_role_name($fields['role_id']);
    record_activity_log(
        get_current_user_id(),
        'create',
        'users',
        'Created user ' . $fields['username'] . ' with role ' . $role_name
    );

    set_flash('success', 'User created successfully.');
    redirect('/users/index.php');
}

function handle_update_user()
{
    $user_id = (int) ($_POST['user_id'] ?? 0);
    $user = get_user_by_id($user_id);

    if (!$user) {
        set_flash('error', 'User not found.');
        redirect('/users/index.php');
    }

    list($valid, $message, $fields, $password) = validate_user_fields(false);
    if (!$valid) {
        save_old_input();
        set_flash('error', $message);
        redirect('/users/edit.php?id=' . $user_id);
    }

    if (username_exists($fields['username'], $user_id)) {
        save_old_input();
        set_flash('error', 'Username is already taken.');
        redirect('/users/edit.php?id=' . $user_id);
    }

    if (email_exists($fields['email'], $user_id)) {
        save_old_input();
        set_flash('error', 'Email is already registered.');
        redirect('/users/edit.php?id=' . $user_id);
    }

    if (would_lock_out_current_user($user_id, $fields['status'], $fields['role_id'])) {
        save_old_input();
        set_flash('error', 'You cannot remove your own active administrator access.');
        redirect('/users/edit.php?id=' . $user_id);
    }

    if ($user_id !== get_current_user_id() && $fields['status'] !== 'active' && is_only_active_administrator($user_id)) {
        save_old_input();
        set_flash('error', 'Cannot deactivate the only active administrator.');
        redirect('/users/edit.php?id=' . $user_id);
    }

    $old_role_id = get_user_role_id($user_id);
    $password_hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;

    if (!update_user($user_id, $fields, $fields['role_id'], $password_hash)) {
        save_old_input();
        set_flash('error', 'Unable to update user. Please try again.');
        redirect('/users/edit.php?id=' . $user_id);
    }

    clear_old_input();

    $changes = [];
    if ($old_role_id !== $fields['role_id']) {
        $changes[] = 'role changed to ' . get_role_name($fields['role_id']);
    }
    if ($user['status'] !== $fields['status']) {
        $changes[] = 'status changed to ' . $fields['status'];
    }
    if ($password_hash) {
        $changes[] = 'password updated';
    }

    $description = 'Updated user ' . $fields['username'];
    if ($changes) {
        $description .= ' (' . implode(', ', $changes) . ')';
    }

    record_activity_log(get_current_user_id(), 'update', 'users', $description);

    if ($user_id === get_current_user_id()) {
        $_SESSION['username'] = $fields['username'];
        $_SESSION['first_name'] = $fields['first_name'];
        $_SESSION['last_name'] = $fields['last_name'];
        $_SESSION['email'] = $fields['email'];
        $_SESSION['roles'] = get_user_roles($user_id);
    }

    set_flash('success', 'User updated successfully.');
    redirect('/users/index.php');
}

function handle_status_change($action)
{
    $user_id = (int) ($_POST['user_id'] ?? 0);
    $user = get_user_by_id($user_id);

    if (!$user) {
        set_flash('error', 'User not found.');
        redirect('/users/index.php');
    }

    if ($user_id === get_current_user_id()) {
        set_flash('error', 'You cannot change your own account status.');
        redirect('/users/index.php');
    }

    $new_status = $action === 'activate' ? 'active' : 'inactive';

    if ($new_status !== 'active' && is_only_active_administrator($user_id)) {
        set_flash('error', 'Cannot deactivate the only active administrator.');
        redirect('/users/index.php');
    }

    if (!update_user_status($user_id, $new_status)) {
        set_flash('error', 'Unable to update user status.');
        redirect('/users/index.php');
    }

    record_activity_log(
        get_current_user_id(),
        $action,
        'users',
        ucfirst($action) . 'd user ' . $user['username']
    );

    set_flash('success', 'User status updated successfully.');
    redirect('/users/index.php');
}
