<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/inventory.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('error', 'Invalid request.');
    redirect('/inventory/index.php');
}

$action = $_POST['action'] ?? '';
$user_id = get_current_user_id();

function inventory_positive_id($value)
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? 0 : (int) $id;
}

function inventory_decimal($value, $minimum)
{
    if (!is_int($value) && !is_float($value) && !is_string($value)) {
        return null;
    }
    $value = trim((string) $value);
    if ($value === '' || !is_numeric($value)) {
        return null;
    }
    $number = (float) $value;
    if (!is_finite($number) || $number < $minimum || $number > 999999999.999) {
        return null;
    }
    return $number;
}

if ($action === 'stock_in') {
    require_permission('inventory.manage');
    $product_id = inventory_positive_id($_POST['product_id'] ?? null);
    $qty = inventory_decimal($_POST['quantity'] ?? null, 0.001);
    if ($product_id <= 0 || $qty === null) {
        set_flash('error', 'Select a valid product and enter a quantity greater than 0.');
        redirect('/inventory/stock_in.php');
    }
    $supplier_value = trim((string) ($_POST['supplier_id'] ?? ''));
    $supplier_id = null;
    if ($supplier_value !== '') {
        $supplier_id = filter_var($supplier_value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($supplier_id === false) {
            set_flash('error', 'Invalid supplier selected.');
            redirect('/inventory/stock_in.php');
        }
    }
    $ref = trim($_POST['reference_no'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    list($ok, $message) = process_stock_in($product_id, $qty, $ref, $remarks, $user_id, $supplier_id);
    set_flash($ok ? 'success' : 'error', $ok ? 'Stock in recorded successfully.' : $message);
    redirect($ok ? '/inventory/index.php' : '/inventory/stock_in.php');
}

if ($action === 'stock_out') {
    require_permission('inventory.manage');
    $product_id = inventory_positive_id($_POST['product_id'] ?? null);
    $qty = inventory_decimal($_POST['quantity'] ?? null, 0.001);
    if ($product_id <= 0 || $qty === null) {
        set_flash('error', 'Select a valid product and enter a quantity greater than 0.');
        redirect('/inventory/stock_out.php');
    }
    $ref = trim($_POST['reference_no'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    list($ok, $message) = process_stock_out($product_id, $qty, $ref, $remarks, $user_id);
    set_flash($ok ? 'success' : 'error', $ok ? 'Stock out recorded successfully.' : $message);
    redirect($ok ? '/inventory/index.php' : '/inventory/stock_out.php');
}

if ($action === 'adjust') {
    require_permission('inventory.manage');
    $product_id = inventory_positive_id($_POST['product_id'] ?? null);
    $new_qty = inventory_decimal($_POST['new_quantity'] ?? null, 0);
    if ($product_id <= 0 || $new_qty === null) {
        set_flash('error', 'Select a valid product and enter a nonnegative quantity.');
        redirect('/inventory/adjust.php');
    }
    $remarks = trim($_POST['remarks'] ?? '');

    list($ok, $message) = process_stock_adjustment($product_id, $new_qty, $remarks, $user_id);
    set_flash($ok ? 'success' : 'error', $ok ? 'Stock adjustment recorded successfully.' : $message);
    redirect($ok ? '/inventory/index.php' : '/inventory/adjust.php');
}

if ($action === 'reorder') {
    require_permission('inventory.manage');
    $product_id = inventory_positive_id($_POST['product_id'] ?? null);
    $reorder = inventory_decimal($_POST['reorder_level'] ?? null, 0);
    if ($product_id <= 0 || $reorder === null) {
        set_flash('error', 'Enter a valid nonnegative reorder level.');
        redirect('/inventory/index.php');
    }

    list($ok, $message) = process_reorder_level_update($product_id, $reorder, $user_id);
    set_flash($ok ? 'success' : 'error', $ok ? 'Reorder level updated.' : $message);
    redirect('/inventory/index.php');
}

set_flash('error', 'Invalid action.');
redirect('/inventory/index.php');
