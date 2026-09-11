<?php
require_once BASE_PATH . '/config/database.php';

function get_all_products($search = '', $status = '')
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT p.product_id, p.product_code, p.barcode, p.product_name, p.selling_price, p.status,
                   c.category_name, b.brand_name, u.unit_name, p.created_at
            FROM products p
            INNER JOIN categories c ON c.category_id = p.category_id
            LEFT JOIN brands b ON b.brand_id = p.brand_id
            INNER JOIN units u ON u.unit_id = p.unit_id
            WHERE 1=1';
    $params = [];
    $types = '';

    $search = trim($search);
    if ($search !== '') {
        $sql .= ' AND (p.product_code LIKE ? OR p.barcode LIKE ? OR p.product_name LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }

    if ($status === 'active' || $status === 'inactive') {
        $sql .= ' AND p.status = ?';
        $params[] = $status;
        $types .= 's';
    }

    $sql .= ' ORDER BY p.product_name ASC';

    if ($params) {
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $db->query($sql);
    }

    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    if (isset($stmt)) {
        $stmt->close();
    }
    return $rows;
}

function get_product_by_id($id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT product_id, product_code, barcode, product_name, category_id, brand_id, unit_id,
                   description, selling_price, status, created_at, updated_at
            FROM products WHERE product_id = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function product_code_exists($code, $exclude_id = null)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    if ($exclude_id) {
        $sql = 'SELECT product_id FROM products WHERE product_code = ? AND product_id != ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('si', $code, $exclude_id);
    } else {
        $sql = 'SELECT product_id FROM products WHERE product_code = ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $code);
    }

    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

function product_barcode_exists($barcode, $exclude_id = null)
{
    if ($barcode === '' || $barcode === null) {
        return false;
    }

    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    if ($exclude_id) {
        $sql = 'SELECT product_id FROM products WHERE barcode = ? AND product_id != ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('si', $barcode, $exclude_id);
    } else {
        $sql = 'SELECT product_id FROM products WHERE barcode = ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $barcode);
    }

    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

function get_active_categories_for_select()
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $result = $db->query("SELECT category_id, category_name FROM categories WHERE status = 'active' ORDER BY category_name ASC");
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function get_active_brands_for_select()
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $result = $db->query("SELECT brand_id, brand_name FROM brands WHERE status = 'active' ORDER BY brand_name ASC");
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function get_active_units_for_select()
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $result = $db->query("SELECT unit_id, unit_name, unit_code FROM units WHERE status = 'active' ORDER BY unit_name ASC");
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function get_category_by_id_raw($id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT category_id, category_name, status FROM categories WHERE category_id = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function get_brand_by_id_raw($id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT brand_id, brand_name, status FROM brands WHERE brand_id = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function get_unit_by_id_raw($id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT unit_id, unit_name, unit_code, status FROM units WHERE unit_id = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function create_product($data)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'INSERT INTO products (product_code, barcode, product_name, category_id, brand_id, unit_id, description, selling_price, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = $db->prepare($sql);

    $brand_id = $data['brand_id'];
    $stmt->bind_param(
        'sssiiisds',
        $data['product_code'],
        $data['barcode'],
        $data['product_name'],
        $data['category_id'],
        $brand_id,
        $data['unit_id'],
        $data['description'],
        $data['selling_price'],
        $data['status']
    );

    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }

    $id = (int) $stmt->insert_id;
    $stmt->close();
    return $id;
}

function update_product($id, $data)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'UPDATE products SET product_code = ?, barcode = ?, product_name = ?, category_id = ?, brand_id = ?,
            unit_id = ?, description = ?, selling_price = ?, status = ? WHERE product_id = ?';
    $stmt = $db->prepare($sql);

    $brand_id = $data['brand_id'];
    $stmt->bind_param(
        'sssiiisdsi',
        $data['product_code'],
        $data['barcode'],
        $data['product_name'],
        $data['category_id'],
        $brand_id,
        $data['unit_id'],
        $data['description'],
        $data['selling_price'],
        $data['status'],
        $id
    );

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function update_product_status($id, $status)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'UPDATE products SET status = ? WHERE product_id = ?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('si', $status, $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function format_price($amount)
{
    return number_format((float) $amount, 2);
}
