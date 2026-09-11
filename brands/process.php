<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/brands.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('error', 'Invalid request.');
    redirect('/brands/index.php');
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    require_permission('brands.manage');
    $name = trim($_POST['brand_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $_SESSION['old_input'] = $_POST;
    if ($name === '' || !in_array($status, ['active', 'inactive'], true)) {
        set_flash('error', 'Please fill in all required fields.');
        redirect('/brands/add.php');
    }
    if (brand_name_exists($name)) {
        set_flash('error', 'Brand name already exists.');
        redirect('/brands/add.php');
    }
    if (!create_brand($name, $desc !== '' ? $desc : null, $status)) {
        set_flash('error', 'Unable to create brand.');
        redirect('/brands/add.php');
    }
    unset($_SESSION['old_input']);
    record_activity_log(get_current_user_id(), 'create', 'brands', 'Created brand ' . $name);
    set_flash('success', 'Brand created successfully.');
    redirect('/brands/index.php');
}

if ($action === 'update') {
    require_permission('brands.manage');
    $id = (int) ($_POST['brand_id'] ?? 0);
    $item = get_brand_by_id($id);
    $name = trim($_POST['brand_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $_SESSION['old_input'] = $_POST;
    if (!$item || $name === '') {
        set_flash('error', 'Invalid brand data.');
        redirect('/brands/index.php');
    }
    if (brand_name_exists($name, $id)) {
        set_flash('error', 'Brand name already exists.');
        redirect('/brands/edit.php?id=' . $id);
    }
    if (!update_brand($id, $name, $desc !== '' ? $desc : null, $status)) {
        set_flash('error', 'Unable to update brand.');
        redirect('/brands/edit.php?id=' . $id);
    }
    unset($_SESSION['old_input']);
    record_activity_log(get_current_user_id(), 'update', 'brands', 'Updated brand ' . $name);
    set_flash('success', 'Brand updated successfully.');
    redirect('/brands/index.php');
}

if ($action === 'activate' || $action === 'deactivate') {
    require_permission('brands.manage');
    $id = (int) ($_POST['brand_id'] ?? 0);
    $item = get_brand_by_id($id);
    if (!$item) {
        set_flash('error', 'Brand not found.');
        redirect('/brands/index.php');
    }
    $status = $action === 'activate' ? 'active' : 'inactive';
    update_brand_status($id, $status);
    record_activity_log(get_current_user_id(), $action, 'brands', ucfirst($action) . 'd brand ' . $item['brand_name']);
    set_flash('success', 'Brand status updated.');
    redirect('/brands/index.php');
}

set_flash('error', 'Invalid action.');
redirect('/brands/index.php');
