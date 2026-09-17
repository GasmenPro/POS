<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/suppliers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('error', 'Invalid request.');
    redirect('/suppliers/index.php');
}

require_permission('suppliers.manage');
$action = $_POST['action'] ?? '';

function supplier_form_data()
{
    return [
        'supplier_code' => trim($_POST['supplier_code'] ?? ''),
        'supplier_name' => trim($_POST['supplier_name'] ?? ''),
        'contact_person' => trim($_POST['contact_person'] ?? '') ?: null,
        'phone' => trim($_POST['phone'] ?? '') ?: null,
        'email' => trim($_POST['email'] ?? '') ?: null,
        'address' => trim($_POST['address'] ?? '') ?: null,
        'notes' => trim($_POST['notes'] ?? '') ?: null,
        'status' => $_POST['status'] ?? 'active',
    ];
}

function validate_supplier_data($data, $exclude_id = null)
{
    if ($data['supplier_code'] === '' || $data['supplier_name'] === '') {
        return 'Supplier code and supplier name are required.';
    }
    if (!in_array($data['status'], ['active', 'inactive'], true)) {
        return 'Invalid supplier status.';
    }
    if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }
    if (supplier_code_exists($data['supplier_code'], $exclude_id)) {
        return 'Supplier code already exists.';
    }
    return '';
}

if ($action === 'create') {
    $data = supplier_form_data();
    $_SESSION['old_input'] = $_POST;
    $error = validate_supplier_data($data);
    if ($error !== '') {
        set_flash('error', $error);
        redirect('/suppliers/add.php');
    }

    $supplier_id = create_supplier($data);
    if (!$supplier_id) {
        set_flash('error', 'Unable to create supplier.');
        redirect('/suppliers/add.php');
    }

    unset($_SESSION['old_input']);
    record_activity_log(get_current_user_id(), 'create', 'suppliers',
        'Created supplier #' . $supplier_id . ' ' . $data['supplier_code'] . ' — ' . $data['supplier_name']);
    set_flash('success', 'Supplier created successfully.');
    redirect('/suppliers/index.php');
}

if ($action === 'update') {
    $supplier_id = (int) ($_POST['supplier_id'] ?? 0);
    $supplier = get_supplier_by_id($supplier_id);
    if (!$supplier) {
        set_flash('error', 'Supplier not found.');
        redirect('/suppliers/index.php');
    }

    $data = supplier_form_data();
    $_SESSION['old_input'] = $_POST;
    $error = validate_supplier_data($data, $supplier_id);
    if ($error !== '') {
        set_flash('error', $error);
        redirect('/suppliers/edit.php?id=' . $supplier_id);
    }

    if (!update_supplier($supplier_id, $data)) {
        set_flash('error', 'Unable to update supplier.');
        redirect('/suppliers/edit.php?id=' . $supplier_id);
    }

    unset($_SESSION['old_input']);
    record_activity_log(get_current_user_id(), 'update', 'suppliers',
        'Updated supplier #' . $supplier_id . ' ' . $data['supplier_code'] . ' — ' . $data['supplier_name']);
    if ($supplier['status'] !== $data['status']) {
        $status_action = $data['status'] === 'active' ? 'activate' : 'deactivate';
        record_activity_log(get_current_user_id(), $status_action, 'suppliers',
            ucfirst($status_action) . 'd supplier #' . $supplier_id . ' ' . $data['supplier_code'] . ' — ' . $data['supplier_name']);
    }
    set_flash('success', 'Supplier updated successfully.');
    redirect('/suppliers/index.php');
}

if ($action === 'activate' || $action === 'deactivate') {
    $supplier_id = (int) ($_POST['supplier_id'] ?? 0);
    $supplier = get_supplier_by_id($supplier_id);
    if (!$supplier) {
        set_flash('error', 'Supplier not found.');
        redirect('/suppliers/index.php');
    }

    $status = $action === 'activate' ? 'active' : 'inactive';
    if (!update_supplier_status($supplier_id, $status)) {
        set_flash('error', 'Unable to update supplier status.');
        redirect('/suppliers/index.php');
    }

    record_activity_log(get_current_user_id(), $action, 'suppliers',
        ucfirst($action) . 'd supplier #' . $supplier_id . ' ' . $supplier['supplier_code'] . ' — ' . $supplier['supplier_name']);
    set_flash('success', 'Supplier status updated.');
    redirect('/suppliers/index.php');
}

set_flash('error', 'Invalid action.');
redirect('/suppliers/index.php');
