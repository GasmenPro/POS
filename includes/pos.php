<?php
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/inventory.php';

function format_money($amount)
{
    return '₱' . number_format((float) $amount, 2, '.', ',');
}

function get_pos_cart()
{
    if (!isset($_SESSION['pos_cart']) || !is_array($_SESSION['pos_cart'])) {
        $_SESSION['pos_cart'] = [];
    }
    return $_SESSION['pos_cart'];
}

function save_pos_cart($cart)
{
    $_SESSION['pos_cart'] = $cart;
}

function clear_pos_cart()
{
    $_SESSION['pos_cart'] = [];
}

function get_cart_subtotal($cart = null)
{
    if ($cart === null) {
        $cart = get_pos_cart();
    }
    $subtotal = 0.0;
    foreach ($cart as $item) {
        $subtotal += (float) $item['line_total'];
    }
    return round($subtotal, 2);
}

function recalculate_cart_line($item)
{
    $qty = (float) $item['quantity'];
    $price = (float) $item['unit_price'];
    $item['line_total'] = round($qty * $price, 2);
    return $item;
}

function get_pos_product($product_id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    sync_missing_inventory_records();

    $sql = 'SELECT p.product_id, p.product_code, p.barcode, p.product_name, p.selling_price, p.status,
                   u.unit_name, COALESCE(i.quantity, 0) AS stock
            FROM products p
            INNER JOIN units u ON u.unit_id = p.unit_id
            LEFT JOIN inventory i ON i.product_id = p.product_id
            WHERE p.product_id = ?
            LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function search_pos_products($search = '')
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    sync_missing_inventory_records();

    $sql = 'SELECT p.product_id, p.product_code, p.barcode, p.product_name, p.selling_price,
                   u.unit_name, COALESCE(i.quantity, 0) AS stock
            FROM products p
            INNER JOIN units u ON u.unit_id = p.unit_id
            INNER JOIN inventory i ON i.product_id = p.product_id
            WHERE p.status = ? AND i.quantity > 0';
    $active = 'active';
    $params = [$active];
    $types = 's';

    $search = trim($search);
    if ($search !== '') {
        $sql .= ' AND (p.product_code LIKE ? OR p.barcode LIKE ? OR p.product_name LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }

    $sql .= ' ORDER BY p.product_name ASC LIMIT 50';

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function validate_cart_quantity($product, $qty)
{
    if ($qty <= 0) {
        return [false, 'Quantity must be greater than 0.'];
    }
    if (!$product || $product['status'] !== 'active') {
        return [false, 'Product not found or inactive.'];
    }
    $stock = (float) $product['stock'];
    if ($qty > $stock) {
        return [false, 'Insufficient stock. Available: ' . format_qty($stock)];
    }
    return [true, ''];
}

function pos_add_to_cart($product_id, $qty = 1)
{
    $product = get_pos_product($product_id);
    if (!$product || $product['status'] !== 'active') {
        return [false, 'Product not found or inactive.'];
    }
    if ((float) $product['stock'] <= 0) {
        return [false, 'Product is out of stock.'];
    }

    $cart = get_pos_cart();
    $current_qty = isset($cart[$product_id]) ? (float) $cart[$product_id]['quantity'] : 0;
    $new_qty = $current_qty + (float) $qty;

    list($ok, $message) = validate_cart_quantity($product, $new_qty);
    if (!$ok) {
        return [false, $message];
    }

    $price = (float) $product['selling_price'];
    $item = [
        'product_id' => (int) $product['product_id'],
        'product_code' => $product['product_code'],
        'product_name' => $product['product_name'],
        'unit_name' => $product['unit_name'],
        'quantity' => $new_qty,
        'unit_price' => $price,
        'line_total' => round($new_qty * $price, 2),
    ];
    $cart[$product_id] = $item;
    save_pos_cart($cart);
    return [true, ''];
}

function pos_set_cart_quantity($product_id, $qty)
{
    $cart = get_pos_cart();
    if (!isset($cart[$product_id])) {
        return [false, 'Item not in cart.'];
    }

    if ($qty == 0) {
        return pos_remove_from_cart($product_id);
    }
    if ($qty < 0) {
        return [false, 'Quantity must be greater than 0.'];
    }

    $product = get_pos_product($product_id);
    list($ok, $message) = validate_cart_quantity($product, $qty);
    if (!$ok) {
        return [false, $message];
    }

    $cart[$product_id]['quantity'] = (float) $qty;
    $cart[$product_id]['unit_price'] = (float) $product['selling_price'];
    $cart[$product_id] = recalculate_cart_line($cart[$product_id]);
    save_pos_cart($cart);
    return [true, ''];
}

function pos_remove_from_cart($product_id)
{
    $cart = get_pos_cart();
    if (!isset($cart[$product_id])) {
        return [false, 'Item not in cart.'];
    }
    unset($cart[$product_id]);
    save_pos_cart($cart);
    return [true, ''];
}

function pos_change_cart_quantity($product_id, $delta)
{
    $cart = get_pos_cart();
    if (!isset($cart[$product_id])) {
        return [false, 'Item not in cart.'];
    }
    $new_qty = (float) $cart[$product_id]['quantity'] + (float) $delta;
    if ($new_qty <= 0) {
        return pos_remove_from_cart($product_id);
    }
    return pos_set_cart_quantity($product_id, $new_qty);
}

function refresh_cart_prices()
{
    $cart = get_pos_cart();
    foreach ($cart as $product_id => $item) {
        $product = get_pos_product($product_id);
        if ($product && $product['status'] === 'active') {
            $cart[$product_id]['unit_price'] = (float) $product['selling_price'];
            $cart[$product_id] = recalculate_cart_line($cart[$product_id]);
        }
    }
    save_pos_cart($cart);
}

function generate_sale_no($db)
{
    $prefix = 'SALE-' . date('Ymd') . '-';
    $like = $prefix . '%';

    $sql = 'SELECT sale_no FROM sales WHERE sale_no LIKE ? ORDER BY sale_no DESC LIMIT 1 FOR UPDATE';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $next = 1;
    if ($row) {
        $parts = explode('-', $row['sale_no']);
        $next = (int) end($parts) + 1;
    }

    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

function get_product_for_checkout($product_id, $db)
{
    $sql = 'SELECT product_id, product_code, product_name, selling_price, status
            FROM products WHERE product_id = ? LIMIT 1 FOR UPDATE';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function insert_sale_record($db, $sale_no, $subtotal, $total, $payment, $change, $user_id)
{
    $sql = 'INSERT INTO sales (sale_no, sale_date, subtotal, total_amount, payment_amount, change_amount, created_by)
            VALUES (?, NOW(), ?, ?, ?, ?, ?)';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param('sddddi', $sale_no, $subtotal, $total, $payment, $change, $user_id);
    $ok = $stmt->execute();
    $sale_id = $ok ? (int) $stmt->insert_id : 0;
    $stmt->close();
    return $sale_id;
}

function insert_sale_item($db, $sale_id, $product_id, $qty, $unit_price, $line_total)
{
    $sql = 'INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, line_total)
            VALUES (?, ?, ?, ?, ?)';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('iiddd', $sale_id, $product_id, $qty, $unit_price, $line_total);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function process_pos_checkout($payment_amount, $user_id)
{
    $cart = get_pos_cart();
    if (empty($cart)) {
        return [false, 'Cart is empty.', null, null];
    }

    $payment = round((float) $payment_amount, 2);
    if ($payment < 0) {
        return [false, 'Invalid payment amount.', null, null];
    }

    $db = get_db_connection();
    if (!$db) {
        return [false, 'Database connection failed.', null, null];
    }

    $db->begin_transaction();
    try {
        $verified_items = [];
        $subtotal = 0.0;

        foreach ($cart as $product_id => $item) {
            $product_id = (int) $product_id;
            $cart_qty = (float) $item['quantity'];
            if ($cart_qty <= 0) {
                throw new Exception('Invalid cart quantity.');
            }

            $product = get_product_for_checkout($product_id, $db);
            if (!$product || $product['status'] !== 'active') {
                $name = $product ? $product['product_name'] : 'Product';
                throw new Exception($name . ' is no longer available.');
            }

            $inv = get_inventory_row_for_update($product_id, $db);
            if (!$inv) {
                throw new Exception('Inventory record not found for ' . $product['product_name'] . '.');
            }

            $prev = (float) $inv['quantity'];
            if ($cart_qty > $prev) {
                throw new Exception('Insufficient stock for ' . $product['product_name'] . '. Available: ' . format_qty($prev));
            }

            $unit_price = round((float) $product['selling_price'], 2);
            $line_total = round($cart_qty * $unit_price, 2);
            $subtotal += $line_total;

            $verified_items[] = [
                'product_id' => $product_id,
                'product_name' => $product['product_name'],
                'quantity' => $cart_qty,
                'unit_price' => $unit_price,
                'line_total' => $line_total,
                'prev_qty' => $prev,
                'new_qty' => $prev - $cart_qty,
            ];
        }

        $subtotal = round($subtotal, 2);
        $total = $subtotal;

        if ($payment < $total) {
            $db->rollback();
            return [false, 'Insufficient payment. Total: ' . format_money($total), null, null];
        }

        $change = round($payment - $total, 2);
        $sale_no = null;
        $sale_id = 0;

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $sale_no = generate_sale_no($db);
            $sale_id = insert_sale_record($db, $sale_no, $subtotal, $total, $payment, $change, $user_id);
            if ($sale_id > 0) {
                break;
            }
            if ($db->errno !== 1062) {
                throw new Exception('Failed to create sale record.');
            }
        }

        if ($sale_id <= 0) {
            throw new Exception('Failed to generate unique sale number.');
        }

        foreach ($verified_items as $vi) {
            if (!insert_sale_item($db, $sale_id, $vi['product_id'], $vi['quantity'], $vi['unit_price'], $vi['line_total'])) {
                throw new Exception('Failed to record sale item.');
            }

            if (!update_inventory_quantity($db, $vi['product_id'], $vi['new_qty'])) {
                throw new Exception('Failed to update inventory.');
            }

            if (!insert_inventory_movement(
                $db,
                $vi['product_id'],
                'stock_out',
                $vi['quantity'],
                $vi['prev_qty'],
                $vi['new_qty'],
                $sale_no,
                'POS Sale',
                $user_id
            )) {
                throw new Exception('Failed to record inventory movement.');
            }
        }

        record_activity_log(
            $user_id,
            'sale_completed',
            'pos',
            'Sale #' . $sale_no . ' (ID ' . $sale_id . ') total ' . format_money($total)
        );

        $db->commit();
        clear_pos_cart();
        return [true, 'Sale completed successfully.', $sale_id, $sale_no];
    } catch (Exception $e) {
        $db->rollback();
        return [false, $e->getMessage(), null, null];
    }
}

function get_sales_list($search = '', $limit = 100)
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT s.sale_id, s.sale_no, s.sale_date, s.total_amount, s.payment_amount, s.change_amount,
                   CONCAT(u.first_name, " ", u.last_name) AS cashier_name
            FROM sales s
            INNER JOIN users u ON u.id = s.created_by
            WHERE 1=1';
    $params = [];
    $types = '';

    $search = trim($search);
    if ($search !== '') {
        $sql .= ' AND (s.sale_no LIKE ? OR u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)';
        $like = '%' . $search . '%';
        $params = [$like, $like, $like, $like];
        $types = 'ssss';
    }

    $sql .= ' ORDER BY s.sale_date DESC, s.sale_id DESC LIMIT ?';
    $params[] = (int) $limit;
    $types .= 'i';

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function get_sale_by_id($sale_id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT s.sale_id, s.sale_no, s.sale_date, s.subtotal, s.total_amount, s.payment_amount, s.change_amount,
                   CONCAT(u.first_name, " ", u.last_name) AS cashier_name, u.username AS cashier_username
            FROM sales s
            INNER JOIN users u ON u.id = s.created_by
            WHERE s.sale_id = ?
            LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $sale_id);
    $stmt->execute();
    $sale = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$sale) {
        return null;
    }

    $sql = 'SELECT si.sale_item_id, si.product_id, si.quantity, si.unit_price, si.line_total,
                   p.product_code, p.product_name, un.unit_name
            FROM sale_items si
            INNER JOIN products p ON p.product_id = si.product_id
            INNER JOIN units un ON un.unit_id = p.unit_id
            WHERE si.sale_id = ?
            ORDER BY si.sale_item_id ASC';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $sale_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();

    $sale['items'] = $items;
    return $sale;
}
