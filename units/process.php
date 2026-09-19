<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/units.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('error', 'Invalid request.');
    redirect('/units/index.php');
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    require_permission('units.manage');
    $name = trim($_POST['unit_name'] ?? '');
    $code = trim($_POST['unit_code'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $_SESSION['old_input'] = $_POST;
    if ($name === '' || $code === '' || !in_array($status, ['active', 'inactive'], true)) {
        set_flash('error', 'Please fill in all required fields.');
        redirect('/units/add.php');
    }
    if (unit_name_exists($name)) {
        set_flash('error', 'Unit name already exists.');
        redirect('/units/add.php');
    }
    if (unit_code_exists($code)) {
        set_flash('error', 'Unit code already exists.');
        redirect('/units/add.php');
    }
    if (!create_unit($name, $code, $desc !== '' ? $desc : null, $status)) {
        set_flash('error', 'Unable to create unit.');
        redirect('/units/add.php');
    }
    unset($_SESSION['old_input']);
    record_activity_log(get_current_user_id(), 'create', 'units', 'Created unit ' . $name . ' (' . $code . ')');
    set_flash('success', 'Unit created successfully.');
    redirect('/units/index.php');
}

if ($action === 'update') {
    require_permission('units.manage');
    $id = (int) ($_POST['unit_id'] ?? 0);
    $item = get_unit_by_id($id);
    $name = trim($_POST['unit_name'] ?? '');
    $code = trim($_POST['unit_code'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $_SESSION['old_input'] = $_POST;
    if (!$item || $name === '' || $code === '' || !in_array($status, ['active', 'inactive'], true)) {
        set_flash('error', 'Invalid unit data.');
        redirect('/units/index.php');
    }
    if (unit_name_exists($name, $id)) {
        set_flash('error', 'Unit name already exists.');
        redirect('/units/edit.php?id=' . $id);
    }
    if (unit_code_exists($code, $id)) {
        set_flash('error', 'Unit code already exists.');
        redirect('/units/edit.php?id=' . $id);
    }
    if (!update_unit($id, $name, $code, $desc !== '' ? $desc : null, $status)) {
        set_flash('error', 'Unable to update unit.');
        redirect('/units/edit.php?id=' . $id);
    }
    unset($_SESSION['old_input']);
    record_activity_log(get_current_user_id(), 'update', 'units', 'Updated unit ' . $name . ' (' . $code . ')');
    set_flash('success', 'Unit updated successfully.');
    redirect('/units/index.php');
}

if ($action === 'activate' || $action === 'deactivate') {
    require_permission('units.manage');
    $id = (int) ($_POST['unit_id'] ?? 0);
    $item = get_unit_by_id($id);
    if (!$item) {
        set_flash('error', 'Unit not found.');
        redirect('/units/index.php');
    }
    $status = $action === 'activate' ? 'active' : 'inactive';
    update_unit_status($id, $status);
    record_activity_log(get_current_user_id(), $action, 'units', ucfirst($action) . 'd unit ' . $item['unit_name']);
    set_flash('success', 'Unit status updated.');
    redirect('/units/index.php');
}

set_flash('error', 'Invalid action.');
redirect('/units/index.php');
