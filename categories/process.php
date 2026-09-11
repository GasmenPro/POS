<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/categories.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('error', 'Invalid request.');
    redirect('/categories/index.php');
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    require_permission('categories.manage');
    $name = trim($_POST['category_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $_SESSION['old_input'] = $_POST;

    if ($name === '' || !in_array($status, ['active', 'inactive'], true)) {
        set_flash('error', 'Please fill in all required fields.');
        redirect('/categories/add.php');
    }
    if (category_name_exists($name)) {
        set_flash('error', 'Category name already exists.');
        redirect('/categories/add.php');
    }

    $id = create_category($name, $desc !== '' ? $desc : null, $status);
    unset($_SESSION['old_input']);
    if (!$id) {
        set_flash('error', 'Unable to create category.');
        redirect('/categories/add.php');
    }
    record_activity_log(get_current_user_id(), 'create', 'categories', 'Created category ' . $name);
    set_flash('success', 'Category created successfully.');
    redirect('/categories/index.php');
}

if ($action === 'update') {
    require_permission('categories.manage');
    $id = (int) ($_POST['category_id'] ?? 0);
    $item = get_category_by_id($id);
    $name = trim($_POST['category_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $_SESSION['old_input'] = $_POST;

    if (!$item || $name === '' || !in_array($status, ['active', 'inactive'], true)) {
        set_flash('error', 'Invalid category data.');
        redirect('/categories/index.php');
    }
    if (category_name_exists($name, $id)) {
        set_flash('error', 'Category name already exists.');
        redirect('/categories/edit.php?id=' . $id);
    }

    if (!update_category($id, $name, $desc !== '' ? $desc : null, $status)) {
        set_flash('error', 'Unable to update category.');
        redirect('/categories/edit.php?id=' . $id);
    }
    unset($_SESSION['old_input']);
    $log = 'Updated category ' . $name;
    if ($item['status'] !== $status) {
        $log .= ' (status: ' . $status . ')';
    }
    record_activity_log(get_current_user_id(), 'update', 'categories', $log);
    set_flash('success', 'Category updated successfully.');
    redirect('/categories/index.php');
}

if ($action === 'activate' || $action === 'deactivate') {
    require_permission('categories.manage');
    $id = (int) ($_POST['category_id'] ?? 0);
    $item = get_category_by_id($id);
    if (!$item) {
        set_flash('error', 'Category not found.');
        redirect('/categories/index.php');
    }
    $status = $action === 'activate' ? 'active' : 'inactive';
    update_category_status($id, $status);
    record_activity_log(get_current_user_id(), $action, 'categories', ucfirst($action) . 'd category ' . $item['category_name']);
    set_flash('success', 'Category status updated.');
    redirect('/categories/index.php');
}

set_flash('error', 'Invalid action.');
redirect('/categories/index.php');
