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
    $payment = (float) ($_POST['payment_amount'] ?? 0);

    list($ok, $message, $sale_id, $sale_no) = process_pos_checkout($payment, $user_id);
    if ($ok) {
        set_flash('success', 'Sale ' . $sale_no . ' completed successfully.');
        redirect('/pos/sales.php?id=' . (int) $sale_id);
    }
    set_flash('error', $message);
    redirect('/pos/index.php');
}

require_permission('pos.manage');

$product_id = (int) ($_POST['product_id'] ?? 0);

if ($action === 'add') {
    $qty = (float) ($_POST['quantity'] ?? 1);
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
    $qty = (float) ($_POST['quantity'] ?? 0);
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
