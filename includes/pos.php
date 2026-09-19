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
        $sql .= ' ORDER BY (p.barcode = ?) DESC, (p.product_code = ?) DESC, p.product_name ASC';
        $params[] = $search;
        $params[] = $search;
        $types .= 'ss';
    } else {
        $sql .= ' ORDER BY p.product_name ASC';
    }
    $sql .= ' LIMIT 50';

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

function insert_sale_record($db, $sale_no, $subtotal, $total, $payment, $change, $user_id, $offline_transaction_id = null, &$error_code = null)
{
    $sql = 'INSERT INTO sales (sale_no, offline_transaction_id, sale_date, subtotal, total_amount, payment_amount, change_amount, created_by)
            VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        $error_code = $db->errno;
        return 0;
    }
    $stmt->bind_param('ssddddi', $sale_no, $offline_transaction_id, $subtotal, $total, $payment, $change, $user_id);
    $ok = $stmt->execute();
    $error_code = $stmt->errno;
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

class PosTransactionException extends Exception
{
    private $error_type;

    public function __construct($message, $error_type = 'validation_error')
    {
        parent::__construct($message);
        $this->error_type = $error_type;
    }

    public function get_error_type()
    {
        return $this->error_type;
    }
}

function get_sale_by_offline_transaction_id($offline_transaction_id, $db = null, $for_update = false)
{
    $db = $db ?: get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT sale_id, sale_no, total_amount, payment_amount, change_amount
            FROM sales
            WHERE offline_transaction_id = ?
            LIMIT 1';
    if ($for_update) {
        $sql .= ' FOR UPDATE';
    }

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $offline_transaction_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Process a server-authoritative sale transaction for online or offline-origin items.
 *
 * @param array $items
 * @param mixed $payment_amount
 * @param int $user_id
 * @param string|null $offline_transaction_id
 * @param bool $enforce_cached_price
 * @return array
 */
function process_sale_transaction($items, $payment_amount, $user_id, $offline_transaction_id = null, $enforce_cached_price = false)
{
    if (!is_array($items) || empty($items)) {
        return ['ok' => false, 'message' => 'Sale has no items.', 'error_type' => 'validation_error'];
    }

    if (!is_numeric($payment_amount)) {
        return ['ok' => false, 'message' => 'Invalid payment amount.', 'error_type' => 'validation_error'];
    }

    $payment = round((float) $payment_amount, 2);
    if (!is_finite($payment) || $payment < 0 || $payment > 9999999999.99) {
        return ['ok' => false, 'message' => 'Invalid payment amount.', 'error_type' => 'validation_error'];
    }

    $db = get_db_connection();
    if (!$db) {
        return ['ok' => false, 'message' => 'Server database is unavailable.', 'error_type' => 'server_error'];
    }

    usort($items, static function ($left, $right) {
        return ((int) $left['product_id']) <=> ((int) $right['product_id']);
    });

    $db->begin_transaction();
    try {
        if ($offline_transaction_id !== null) {
            $existing = get_sale_by_offline_transaction_id($offline_transaction_id, $db, true);
            if ($existing) {
                $db->rollback();
                return [
                    'ok' => true,
                    'already_synchronized' => true,
                    'message' => 'Already synchronized as ' . $existing['sale_no'] . '.',
                    'sale_id' => (int) $existing['sale_id'],
                    'sale_no' => $existing['sale_no'],
                    'total_amount' => (float) $existing['total_amount'],
                    'payment_amount' => (float) $existing['payment_amount'],
                    'change_amount' => (float) $existing['change_amount'],
                ];
            }
        }

        $verified_items = [];
        $subtotal = 0.0;

        foreach ($items as $item) {
            $product_id = (int) ($item['product_id'] ?? 0);
            $cart_qty = (float) $item['quantity'];
            if ($product_id <= 0 || !is_finite($cart_qty) || $cart_qty <= 0 || $cart_qty > 999999999.999) {
                throw new PosTransactionException('Invalid product or quantity.', 'validation_error');
            }

            $product = get_product_for_checkout($product_id, $db);
            if (!$product || $product['status'] !== 'active') {
                $name = $product ? $product['product_name'] : 'Product';
                throw new PosTransactionException($name . ' is no longer active or available.', 'product_conflict');
            }

            $unit_price = round((float) $product['selling_price'], 2);
            if ($enforce_cached_price) {
                $cached_price = round((float) ($item['cached_unit_price'] ?? -1), 2);
                if ($cached_price < 0 || (int) round($cached_price * 100) !== (int) round($unit_price * 100)) {
                    throw new PosTransactionException('Product price changed for ' . $product['product_name'] . '. Review the pending sale.', 'price_conflict');
                }
            }

            $inv = get_inventory_row_for_update($product_id, $db);
            if (!$inv) {
                throw new PosTransactionException('Inventory is unavailable for ' . $product['product_name'] . '.', 'inventory_conflict');
            }

            $prev = (float) $inv['quantity'];
            if ($cart_qty > $prev) {
                throw new PosTransactionException('Insufficient current stock for ' . $product['product_name'] . '. Available: ' . format_qty($prev), 'inventory_conflict');
            }

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
            throw new PosTransactionException('Insufficient payment. Total: ' . format_money($total), 'payment_conflict');
        }

        $change = round($payment - $total, 2);
        $sale_no = null;
        $sale_id = 0;

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $sale_no = generate_sale_no($db);
            $insert_error = 0;
            $sale_id = insert_sale_record($db, $sale_no, $subtotal, $total, $payment, $change, $user_id, $offline_transaction_id, $insert_error);
            if ($sale_id > 0) {
                break;
            }
            if ($insert_error !== 1062) {
                throw new PosTransactionException('Unable to create the sale.', 'server_error');
            }
            if ($offline_transaction_id !== null) {
                $existing = get_sale_by_offline_transaction_id($offline_transaction_id, $db, true);
                if ($existing) {
                    $db->rollback();
                    return [
                        'ok' => true,
                        'already_synchronized' => true,
                        'message' => 'Already synchronized as ' . $existing['sale_no'] . '.',
                        'sale_id' => (int) $existing['sale_id'],
                        'sale_no' => $existing['sale_no'],
                        'total_amount' => (float) $existing['total_amount'],
                        'payment_amount' => (float) $existing['payment_amount'],
                        'change_amount' => (float) $existing['change_amount'],
                    ];
                }
            }
        }

        if ($sale_id <= 0) {
            throw new PosTransactionException('Unable to generate a unique sale number.', 'server_error');
        }

        foreach ($verified_items as $vi) {
            if (!insert_sale_item($db, $sale_id, $vi['product_id'], $vi['quantity'], $vi['unit_price'], $vi['line_total'])) {
                throw new PosTransactionException('Unable to record the sale item.', 'server_error');
            }

            if (!update_inventory_quantity($db, $vi['product_id'], $vi['new_qty'])) {
                throw new PosTransactionException('Unable to update inventory.', 'server_error');
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
                throw new PosTransactionException('Unable to record inventory movement.', 'server_error');
            }
        }

        $activity_action = $offline_transaction_id === null ? 'sale_completed' : 'offline_sale_synchronized';
        $activity_description = $offline_transaction_id === null
            ? 'Sale #' . $sale_no . ' (ID ' . $sale_id . ') total ' . format_money($total)
            : 'Offline transaction ' . $offline_transaction_id . ' synchronized as ' . $sale_no . ' (ID ' . $sale_id . ') total ' . format_money($total);
        if (!record_activity_log(
            $user_id,
            $activity_action,
            'pos',
            $activity_description
        )) {
            throw new PosTransactionException('Unable to record sale activity.', 'server_error');
        }

        $db->commit();
        return [
            'ok' => true,
            'already_synchronized' => false,
            'message' => 'Sale completed successfully.',
            'sale_id' => $sale_id,
            'sale_no' => $sale_no,
            'total_amount' => $total,
            'payment_amount' => $payment,
            'change_amount' => $change,
        ];
    } catch (PosTransactionException $e) {
        $db->rollback();
        return ['ok' => false, 'message' => $e->getMessage(), 'error_type' => $e->get_error_type()];
    } catch (Throwable $e) {
        $db->rollback();
        return ['ok' => false, 'message' => 'Sale processing failed. Please try again.', 'error_type' => 'server_error'];
    }
}

function process_pos_checkout($payment_amount, $user_id)
{
    $cart = get_pos_cart();
    if (empty($cart)) {
        return [false, 'Cart is empty.', null, null];
    }

    $items = [];
    foreach ($cart as $product_id => $item) {
        $items[] = [
            'product_id' => (int) $product_id,
            'quantity' => $item['quantity'],
        ];
    }

    $result = process_sale_transaction($items, $payment_amount, $user_id);
    if (!$result['ok']) {
        return [false, $result['message'], null, null];
    }

    clear_pos_cart();
    return [true, 'Sale completed successfully.', $result['sale_id'], $result['sale_no']];
}

function process_offline_sale_sync($payload, $user_id)
{
    if (!is_array($payload)) {
        return ['ok' => false, 'message' => 'Invalid synchronization data.', 'error_type' => 'validation_error'];
    }

    $offline_id = strtoupper(trim((string) ($payload['offline_transaction_id'] ?? '')));
    if (preg_match('/^OFFLINE-[0-9]{13}-[A-F0-9]{16}$/', $offline_id) !== 1) {
        return ['ok' => false, 'message' => 'Invalid offline transaction ID.', 'error_type' => 'validation_error'];
    }

    $existing = get_sale_by_offline_transaction_id($offline_id);
    if ($existing) {
        record_activity_log($user_id, 'offline_sale_already_synchronized', 'pos', 'Offline transaction ' . $offline_id . ' already synchronized as ' . $existing['sale_no']);
        return [
            'ok' => true,
            'already_synchronized' => true,
            'message' => 'Already synchronized as ' . $existing['sale_no'] . '.',
            'sale_id' => (int) $existing['sale_id'],
            'sale_no' => $existing['sale_no'],
            'total_amount' => (float) $existing['total_amount'],
            'payment_amount' => (float) $existing['payment_amount'],
            'change_amount' => (float) $existing['change_amount'],
        ];
    }

    $items = $payload['items'] ?? null;
    if (!is_array($items) || empty($items) || count($items) > 100) {
        $result = ['ok' => false, 'message' => 'Offline sale must contain 1 to 100 items.', 'error_type' => 'validation_error'];
        record_activity_log($user_id, 'offline_sale_sync_failed', 'pos', 'Offline transaction ' . $offline_id . ' failed: validation_error');
        return $result;
    }

    $normalized = [];
    $seen_products = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            $normalized = [];
            break;
        }
        $product_id = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $quantity = $item['quantity'] ?? null;
        $cached_price = $item['cached_unit_price'] ?? null;
        if ($product_id === false || isset($seen_products[$product_id]) || !is_numeric($quantity) || !is_numeric($cached_price)) {
            $normalized = [];
            break;
        }
        $quantity = (float) $quantity;
        $cached_price = round((float) $cached_price, 2);
        if (!is_finite($quantity) || !is_finite($cached_price) || $quantity <= 0 || $quantity > 999999999.999 || $cached_price < 0 || $cached_price > 9999999999.99) {
            $normalized = [];
            break;
        }
        $seen_products[$product_id] = true;
        $normalized[] = [
            'product_id' => (int) $product_id,
            'quantity' => $quantity,
            'cached_unit_price' => $cached_price,
        ];
    }

    if (count($normalized) !== count($items)) {
        $result = ['ok' => false, 'message' => 'Offline sale contains invalid or duplicate items.', 'error_type' => 'validation_error'];
        record_activity_log($user_id, 'offline_sale_sync_failed', 'pos', 'Offline transaction ' . $offline_id . ' failed: validation_error');
        return $result;
    }

    $result = process_sale_transaction(
        $normalized,
        $payload['payment_amount'] ?? null,
        $user_id,
        $offline_id,
        true
    );

    if (!$result['ok']) {
        record_activity_log($user_id, 'offline_sale_sync_failed', 'pos', 'Offline transaction ' . $offline_id . ' failed: ' . $result['error_type']);
    } elseif (!empty($result['already_synchronized'])) {
        record_activity_log($user_id, 'offline_sale_already_synchronized', 'pos', 'Offline transaction ' . $offline_id . ' already synchronized as ' . $result['sale_no']);
    }

    return $result;
}

function get_offline_pos_products()
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    sync_missing_inventory_records();
    $status = 'active';
    $sql = 'SELECT p.product_id, p.product_code, p.barcode, p.product_name, u.unit_name,
                   p.selling_price, COALESCE(i.quantity, 0) AS quantity, p.status,
                   GREATEST(p.updated_at, COALESCE(i.updated_at, p.updated_at)) AS updated_at
            FROM products p
            INNER JOIN units u ON u.unit_id = p.unit_id
            LEFT JOIN inventory i ON i.product_id = p.product_id
            WHERE p.status = ?
            ORDER BY p.product_name ASC';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $status);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'product_id' => (int) $row['product_id'],
            'product_code' => $row['product_code'],
            'barcode' => $row['barcode'],
            'product_name' => $row['product_name'],
            'unit_name' => $row['unit_name'],
            'selling_price' => (float) $row['selling_price'],
            'quantity' => (float) $row['quantity'],
            'status' => $row['status'],
            'updated_at' => $row['updated_at'],
        ];
    }
    $stmt->close();
    return $products;
}

function pos_json_response($payload, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function require_pos_api_permission($permission)
{
    if (!is_logged_in()) {
        pos_json_response(['ok' => false, 'error_type' => 'authentication', 'message' => 'Authentication required. Reconnect and sign in again.'], 401);
    }
    if (!user_has_permission($permission)) {
        pos_json_response(['ok' => false, 'error_type' => 'authorization', 'message' => 'You do not have permission to perform this action.'], 403);
    }
}

function verify_pos_api_csrf()
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
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

function get_receipt_store_info()
{
    $info = [
        'name' => APP_NAME,
        'address' => '',
        'contact' => '',
    ];

    $db = get_db_connection();
    if (!$db) {
        return $info;
    }

    $keys = ['app_name', 'store_address', 'store_contact'];
    $sql = 'SELECT setting_key, setting_value
            FROM settings
            WHERE setting_key IN (?, ?, ?)';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return $info;
    }

    $stmt->bind_param('sss', $keys[0], $keys[1], $keys[2]);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $value = trim((string) $row['setting_value']);
        if ($value === '') {
            continue;
        }

        if ($row['setting_key'] === 'app_name') {
            $info['name'] = $value;
        } elseif ($row['setting_key'] === 'store_address') {
            $info['address'] = $value;
        } elseif ($row['setting_key'] === 'store_contact') {
            $info['contact'] = $value;
        }
    }

    $stmt->close();
    return $info;
}
