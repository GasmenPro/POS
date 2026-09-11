<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/products.php';
require_once dirname(__DIR__) . '/includes/inventory.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('error', 'Invalid request.');
    redirect('/products/index.php');
}

$action = $_POST['action'] ?? '';

function save_old_input()
{
    $_SESSION['old_input'] = $_POST;
}

function clear_old_input()
{
    unset($_SESSION['old_input']);
}

function validate_product_input($is_create, $product_id = 0)
{
    $existing = $product_id ? get_product_by_id($product_id) : null;

    $code = trim($_POST['product_code'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');
    $name = trim($_POST['product_name'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $brand_id = (int) ($_POST['brand_id'] ?? 0);
    $unit_id = (int) ($_POST['unit_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price_raw = trim($_POST['selling_price'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($code === '' || $name === '' || $category_id <= 0 || $unit_id <= 0 || $price_raw === '') {
        return [false, 'Please fill in all required fields.', null];
    }

    if (!is_numeric($price_raw) || (float) $price_raw < 0) {
        return [false, 'Selling price must be a number greater than or equal to 0.', null];
    }

    if (!in_array($status, ['active', 'inactive'], true)) {
        return [false, 'Invalid status selected.', null];
    }

    if (product_code_exists($code, $product_id ?: null)) {
        return [false, 'Product code already exists.', null];
    }

    if ($barcode !== '' && product_barcode_exists($barcode, $product_id ?: null)) {
        return [false, 'Barcode already exists.', null];
    }

    $category = get_category_by_id_raw($category_id);
    if (!$category) {
        return [false, 'Selected category does not exist.', null];
    }
    if ($is_create && $category['status'] !== 'active') {
        return [false, 'Selected category must be active.', null];
    }
    if (!$is_create && $category['status'] !== 'active' && (int) $existing['category_id'] !== $category_id) {
        return [false, 'Selected category must be active.', null];
    }

    $unit = get_unit_by_id_raw($unit_id);
    if (!$unit) {
        return [false, 'Selected unit does not exist.', null];
    }
    if ($is_create && $unit['status'] !== 'active') {
        return [false, 'Selected unit must be active.', null];
    }
    if (!$is_create && $unit['status'] !== 'active' && (int) $existing['unit_id'] !== $unit_id) {
        return [false, 'Selected unit must be active.', null];
    }

    $brand_id_val = null;
    if ($brand_id > 0) {
        $brand = get_brand_by_id_raw($brand_id);
        if (!$brand) {
            return [false, 'Selected brand does not exist.', null];
        }
        $brand_id_val = $brand_id;
    }

    $data = [
        'product_code' => $code,
        'barcode' => $barcode !== '' ? $barcode : null,
        'product_name' => $name,
        'category_id' => $category_id,
        'brand_id' => $brand_id_val,
        'unit_id' => $unit_id,
        'description' => $description !== '' ? $description : null,
        'selling_price' => round((float) $price_raw, 2),
        'status' => $status,
    ];

    return [true, '', $data];
}

if ($action === 'create') {
    require_permission('products.manage');
    save_old_input();
    list($valid, $message, $data) = validate_product_input(true);
    if (!$valid) {
        set_flash('error', $message);
        redirect('/products/add.php');
    }

    $id = create_product($data);
    if (!$id) {
        set_flash('error', 'Unable to create product.');
        redirect('/products/add.php');
    }

    ensure_inventory_record($id);
    clear_old_input();
    record_activity_log(get_current_user_id(), 'create', 'products', 'Created product ' . $data['product_name'] . ' (' . $data['product_code'] . ')');
    record_activity_log(get_current_user_id(), 'init', 'inventory', 'Initialized inventory for ' . $data['product_name']);
    set_flash('success', 'Product created successfully.');
    redirect('/products/index.php');
}

if ($action === 'update') {
    require_permission('products.manage');
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $item = get_product_by_id($product_id);
    if (!$item) {
        set_flash('error', 'Product not found.');
        redirect('/products/index.php');
    }

    save_old_input();
    list($valid, $message, $data) = validate_product_input(false, $product_id);
    if (!$valid) {
        set_flash('error', $message);
        redirect('/products/edit.php?id=' . $product_id);
    }

    if (!update_product($product_id, $data)) {
        set_flash('error', 'Unable to update product.');
        redirect('/products/edit.php?id=' . $product_id);
    }

    clear_old_input();
    $log = 'Updated product ' . $data['product_name'] . ' (' . $data['product_code'] . ')';
    if ($item['status'] !== $data['status']) {
        $log .= ' status: ' . $data['status'];
    }
    record_activity_log(get_current_user_id(), 'update', 'products', $log);
    set_flash('success', 'Product updated successfully.');
    redirect('/products/index.php');
}

if ($action === 'activate' || $action === 'deactivate') {
    require_permission('products.manage');
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $item = get_product_by_id($product_id);
    if (!$item) {
        set_flash('error', 'Product not found.');
        redirect('/products/index.php');
    }

    $status = $action === 'activate' ? 'active' : 'inactive';
    update_product_status($product_id, $status);
    record_activity_log(get_current_user_id(), $action, 'products', ucfirst($action) . 'd product ' . $item['product_name']);
    set_flash('success', 'Product status updated.');
    redirect('/products/index.php');
}

set_flash('error', 'Invalid action.');
redirect('/products/index.php');
