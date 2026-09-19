<?php
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/suppliers.php';

function format_qty($qty)
{
    return number_format((float) $qty, 3, '.', '');
}

function get_stock_status($quantity, $reorder_level)
{
    $qty = (float) $quantity;
    $reorder = (float) $reorder_level;

    if ($qty <= 0) {
        return 'Out of Stock';
    }
    if ($reorder > 0 && $qty <= $reorder) {
        return 'Low Stock';
    }
    return 'In Stock';
}

function get_stock_status_badge($status)
{
    switch ($status) {
        case 'Out of Stock':
            return 'danger';
        case 'Low Stock':
            return 'warning';
        default:
            return 'success';
    }
}

function ensure_inventory_record($product_id, $db = null)
{
    $own_connection = false;
    if (!$db) {
        $db = get_db_connection();
        $own_connection = true;
    }
    if (!$db) {
        return false;
    }

    $sql = 'INSERT IGNORE INTO inventory (product_id, quantity, reorder_level) VALUES (?, 0, 0)';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $product_id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function sync_missing_inventory_records()
{
    $db = get_db_connection();
    if (!$db) {
        return 0;
    }

    $sql = 'INSERT IGNORE INTO inventory (product_id, quantity, reorder_level)
            SELECT p.product_id, 0, 0 FROM products p
            LEFT JOIN inventory i ON i.product_id = p.product_id
            WHERE i.inventory_id IS NULL';
    $db->query($sql);
    return $db->affected_rows;
}

function get_inventory_row_for_update($product_id, $db)
{
    ensure_inventory_record($product_id, $db);

    $sql = 'SELECT inventory_id, product_id, quantity, reorder_level
            FROM inventory WHERE product_id = ? FOR UPDATE';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function insert_inventory_movement($db, $product_id, $type, $qty, $prev, $new, $ref, $remarks, $user_id, $supplier_id = null)
{
    $sql = 'INSERT INTO inventory_movements
            (product_id, supplier_id, movement_type, quantity, previous_quantity, new_quantity, reference_no, remarks, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('iisdddssi', $product_id, $supplier_id, $type, $qty, $prev, $new, $ref, $remarks, $user_id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function update_inventory_quantity($db, $product_id, $new_qty)
{
    $sql = 'UPDATE inventory SET quantity = ? WHERE product_id = ?';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('di', $new_qty, $product_id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function get_active_product_for_inventory($product_id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT product_id, product_code, product_name, status FROM products WHERE product_id = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function get_active_products_for_inventory_select()
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $sql = "SELECT p.product_id, p.product_code, p.product_name, COALESCE(i.quantity, 0) AS quantity
            FROM products p
            LEFT JOIN inventory i ON i.product_id = p.product_id
            WHERE p.status = 'active'
            ORDER BY p.product_name ASC";
    $result = $db->query($sql);
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function get_inventory_list($search = '', $stock_filter = '')
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    sync_missing_inventory_records();

    $sql = 'SELECT p.product_id, p.product_code, p.barcode, p.product_name, p.status AS product_status,
                   c.category_name, u.unit_name, i.quantity, i.reorder_level, i.inventory_id
            FROM products p
            INNER JOIN inventory i ON i.product_id = p.product_id
            INNER JOIN categories c ON c.category_id = p.category_id
            INNER JOIN units u ON u.unit_id = p.unit_id
            WHERE (p.status = ? OR (p.status = ? AND i.quantity > 0))';
    $active = 'active';
    $inactive = 'inactive';
    $params = [$active, $inactive];
    $types = 'ss';

    $search = trim($search);
    if ($search !== '') {
        $sql .= ' AND (p.product_code LIKE ? OR p.barcode LIKE ? OR p.product_name LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }

    $sql .= ' ORDER BY p.product_name ASC';

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $row['stock_status'] = get_stock_status($row['quantity'], $row['reorder_level']);
        if ($stock_filter !== '' && $row['stock_status'] !== $stock_filter) {
            continue;
        }
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function get_product_movements($product_id)
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT m.movement_id, m.movement_type, m.quantity, m.previous_quantity, m.new_quantity,
                   m.reference_no, m.remarks, m.created_at, m.supplier_id,
                   p.product_code, p.product_name,
                   s.supplier_code, s.supplier_name,
                   CONCAT(u.first_name, " ", u.last_name) AS user_name
            FROM inventory_movements m
            INNER JOIN products p ON p.product_id = m.product_id
            LEFT JOIN suppliers s ON s.supplier_id = m.supplier_id
            INNER JOIN users u ON u.id = m.created_by
            WHERE m.product_id = ?
            ORDER BY m.created_at DESC, m.movement_id DESC';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function process_stock_in($product_id, $qty, $ref, $remarks, $user_id, $supplier_id = null)
{
    $product = get_active_product_for_inventory($product_id);
    if (!$product || $product['status'] !== 'active') {
        return [false, 'Product not found or inactive.'];
    }
    if ($qty <= 0) {
        return [false, 'Quantity must be greater than 0.'];
    }

    $db = get_db_connection();
    if (!$db) {
        return [false, 'Database connection failed.'];
    }

    $db->begin_transaction();
    try {
        $supplier = null;
        if ($supplier_id !== null && (int) $supplier_id > 0) {
            $supplier_id = (int) $supplier_id;
            $sql = 'SELECT supplier_id, supplier_code, supplier_name, status
                    FROM suppliers
                    WHERE supplier_id = ?
                    LIMIT 1
                    FOR UPDATE';
            $stmt = $db->prepare($sql);
            $stmt->bind_param('i', $supplier_id);
            $stmt->execute();
            $supplier = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$supplier || $supplier['status'] !== 'active') {
                $db->rollback();
                return [false, 'Supplier not found or inactive.'];
            }
        } else {
            $supplier_id = null;
        }

        $inv = get_inventory_row_for_update($product_id, $db);
        if (!$inv) {
            throw new Exception('Inventory record not found.');
        }

        $prev = (float) $inv['quantity'];
        $new = $prev + $qty;

        if (!update_inventory_quantity($db, $product_id, $new)) {
            throw new Exception('Failed to update inventory.');
        }

        $ref_val = $ref !== '' ? $ref : null;
        $remarks_val = $remarks !== '' ? $remarks : null;
        if (!insert_inventory_movement($db, $product_id, 'stock_in', $qty, $prev, $new, $ref_val, $remarks_val, $user_id, $supplier_id)) {
            throw new Exception('Failed to record movement.');
        }

        $supplier_text = $supplier ? ' from ' . $supplier['supplier_name'] : '';
        if (!record_activity_log($user_id, 'stock_in', 'inventory',
            'Stock in ' . format_qty($qty) . ' for ' . $product['product_name'] . $supplier_text . ' (' . $prev . ' -> ' . $new . ')')) {
            throw new Exception('Failed to record activity.');
        }

        $db->commit();
        return [true, ''];
    } catch (Exception $e) {
        $db->rollback();
        return [false, 'Stock in failed. Please try again.'];
    }
}

function process_stock_out($product_id, $qty, $ref, $remarks, $user_id)
{
    $product = get_active_product_for_inventory($product_id);
    if (!$product || $product['status'] !== 'active') {
        return [false, 'Product not found or inactive.'];
    }
    if ($qty <= 0) {
        return [false, 'Quantity must be greater than 0.'];
    }

    $db = get_db_connection();
    if (!$db) {
        return [false, 'Database connection failed.'];
    }

    $db->begin_transaction();
    try {
        $inv = get_inventory_row_for_update($product_id, $db);
        if (!$inv) {
            throw new Exception('Inventory record not found.');
        }

        $prev = (float) $inv['quantity'];
        if ($qty > $prev) {
            $db->rollback();
            return [false, 'Insufficient stock. Available: ' . format_qty($prev)];
        }

        $new = $prev - $qty;

        if (!update_inventory_quantity($db, $product_id, $new)) {
            throw new Exception('Failed to update inventory.');
        }

        $ref_val = $ref !== '' ? $ref : null;
        $remarks_val = $remarks !== '' ? $remarks : null;
        if (!insert_inventory_movement($db, $product_id, 'stock_out', $qty, $prev, $new, $ref_val, $remarks_val, $user_id)) {
            throw new Exception('Failed to record movement.');
        }

        if (!record_activity_log($user_id, 'stock_out', 'inventory',
            'Stock out ' . format_qty($qty) . ' for ' . $product['product_name'] . ' (' . $prev . ' -> ' . $new . ')')) {
            throw new Exception('Failed to record activity.');
        }

        $db->commit();
        return [true, ''];
    } catch (Exception $e) {
        $db->rollback();
        if (strpos($e->getMessage(), 'Insufficient') !== false) {
            return [false, $e->getMessage()];
        }
        return [false, 'Stock out failed. Please try again.'];
    }
}

function process_stock_adjustment($product_id, $new_qty, $remarks, $user_id)
{
    $product = get_active_product_for_inventory($product_id);
    if (!$product || $product['status'] !== 'active') {
        return [false, 'Product not found or inactive.'];
    }
    if ($new_qty < 0) {
        return [false, 'Quantity cannot be negative.'];
    }

    $db = get_db_connection();
    if (!$db) {
        return [false, 'Database connection failed.'];
    }

    $db->begin_transaction();
    try {
        $inv = get_inventory_row_for_update($product_id, $db);
        if (!$inv) {
            throw new Exception('Inventory record not found.');
        }

        $prev = (float) $inv['quantity'];
        $new = (float) $new_qty;
        $diff = $new - $prev;

        if (!update_inventory_quantity($db, $product_id, $new)) {
            throw new Exception('Failed to update inventory.');
        }

        $remarks_val = $remarks !== '' ? $remarks : null;
        if (!insert_inventory_movement($db, $product_id, 'adjustment', $diff, $prev, $new, null, $remarks_val, $user_id)) {
            throw new Exception('Failed to record movement.');
        }

        if (!record_activity_log($user_id, 'adjustment', 'inventory',
            'Adjusted ' . $product['product_name'] . ' (' . $prev . ' -> ' . $new . ', diff ' . format_qty($diff) . ')')) {
            throw new Exception('Failed to record activity.');
        }

        $db->commit();
        return [true, ''];
    } catch (Exception $e) {
        $db->rollback();
        return [false, 'Adjustment failed. Please try again.'];
    }
}

function process_reorder_level_update($product_id, $reorder_level, $user_id)
{
    if ($reorder_level < 0) {
        return [false, 'Reorder level cannot be negative.'];
    }

    $db = get_db_connection();
    if (!$db) {
        return [false, 'Database connection failed.'];
    }

    ensure_inventory_record($product_id, $db);
    $product = get_active_product_for_inventory($product_id);
    if (!$product) {
        return [false, 'Product not found.'];
    }

    $sql = 'UPDATE inventory SET reorder_level = ? WHERE product_id = ?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('di', $reorder_level, $product_id);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return [false, 'Unable to update reorder level.'];
    }

    record_activity_log($user_id, 'update', 'inventory',
        'Updated reorder level for ' . $product['product_name'] . ' to ' . format_qty($reorder_level));

    return [true, ''];
}
