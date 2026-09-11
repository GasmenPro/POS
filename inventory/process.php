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

if ($action === 'stock_in') {
    require_permission('inventory.manage');
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $qty = (float) ($_POST['quantity'] ?? 0);
    $ref = trim($_POST['reference_no'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    list($ok, $message) = process_stock_in($product_id, $qty, $ref, $remarks, $user_id);
    set_flash($ok ? 'success' : 'error', $ok ? 'Stock in recorded successfully.' : $message);
    redirect($ok ? '/inventory/index.php' : '/inventory/stock_in.php');
}

if ($action === 'stock_out') {
    require_permission('inventory.manage');
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $qty = (float) ($_POST['quantity'] ?? 0);
    $ref = trim($_POST['reference_no'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    list($ok, $message) = process_stock_out($product_id, $qty, $ref, $remarks, $user_id);
    set_flash($ok ? 'success' : 'error', $ok ? 'Stock out recorded successfully.' : $message);
    redirect($ok ? '/inventory/index.php' : '/inventory/stock_out.php');
}

if ($action === 'adjust') {
    require_permission('inventory.manage');
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $new_qty = (float) ($_POST['new_quantity'] ?? -1);
    $remarks = trim($_POST['remarks'] ?? '');

    list($ok, $message) = process_stock_adjustment($product_id, $new_qty, $remarks, $user_id);
    set_flash($ok ? 'success' : 'error', $ok ? 'Stock adjustment recorded successfully.' : $message);
    redirect($ok ? '/inventory/index.php' : '/inventory/adjust.php');
}

if ($action === 'reorder') {
    require_permission('inventory.manage');
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $reorder = (float) ($_POST['reorder_level'] ?? -1);

    list($ok, $message) = process_reorder_level_update($product_id, $reorder, $user_id);
    set_flash($ok ? 'success' : 'error', $ok ? 'Reorder level updated.' : $message);
    redirect('/inventory/index.php');
}

set_flash('error', 'Invalid action.');
redirect('/inventory/index.php');
