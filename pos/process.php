<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/pos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('error', 'Invalid request.');
    redirect('/pos/index.php');
}

$action = $_POST['action'] ?? '';
$user_id = get_current_user_id();

if ($action === 'checkout') {
    require_permission('pos.manage');
    $payment = $_POST['payment_amount'] ?? null;

    list($ok, $message, $sale_id, $sale_no) = process_pos_checkout($payment, $user_id);
    if ($ok) {
        set_flash('success', 'Sale ' . $sale_no . ' completed successfully.');
        redirect('/pos/sales.php?id=' . (int) $sale_id);
    }
    set_flash('error', $message);
    redirect('/pos/index.php');
}

require_permission('pos.manage');

$product_id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($action !== 'clear' && $product_id === false) {
    set_flash('error', 'Invalid product selected.');
    redirect('/pos/index.php');
}
$product_id = $product_id === false ? 0 : (int) $product_id;

if ($action === 'add') {
    $qty_raw = $_POST['quantity'] ?? 1;
    if (!is_numeric($qty_raw) || !is_finite((float) $qty_raw) || (float) $qty_raw <= 0) {
        set_flash('error', 'Quantity must be a valid number greater than 0.');
        redirect('/pos/index.php');
    }
    $qty = (float) $qty_raw;
    list($ok, $message) = pos_add_to_cart($product_id, $qty);
    set_flash($ok ? 'success' : 'error', $ok ? 'Product added to cart.' : $message);
    redirect('/pos/index.php' . (isset($_POST['q']) ? '?q=' . urlencode(trim($_POST['q'])) : ''));
}

if ($action === 'inc') {
    list($ok, $message) = pos_change_cart_quantity($product_id, 1);
    set_flash($ok ? 'success' : 'error', $ok ? 'Quantity updated.' : $message);
    redirect('/pos/index.php');
}

if ($action === 'dec') {
    list($ok, $message) = pos_change_cart_quantity($product_id, -1);
    set_flash($ok ? 'success' : 'error', $ok ? 'Quantity updated.' : $message);
    redirect('/pos/index.php');
}

if ($action === 'set_qty') {
    $qty_raw = $_POST['quantity'] ?? null;
    if (!is_numeric($qty_raw) || !is_finite((float) $qty_raw) || (float) $qty_raw <= 0) {
        set_flash('error', 'Quantity must be a valid number greater than 0.');
        redirect('/pos/index.php');
    }
    $qty = (float) $qty_raw;
    list($ok, $message) = pos_set_cart_quantity($product_id, $qty);
    set_flash($ok ? 'success' : 'error', $ok ? 'Quantity updated.' : $message);
    redirect('/pos/index.php');
}

if ($action === 'remove') {
    list($ok, $message) = pos_remove_from_cart($product_id);
    set_flash($ok ? 'success' : 'error', $ok ? 'Item removed from cart.' : $message);
    redirect('/pos/index.php');
}

if ($action === 'clear') {
    clear_pos_cart();
    set_flash('success', 'Cart cleared.');
    redirect('/pos/index.php');
}

set_flash('error', 'Invalid action.');
redirect('/pos/index.php');
