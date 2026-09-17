<?php
require_once BASE_PATH . '/config/database.php';

function get_all_suppliers($search = '', $status = '')
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT supplier_id, supplier_code, supplier_name, contact_person, phone, email,
                   address, notes, status, created_at, updated_at
            FROM suppliers
            WHERE 1=1';
    $params = [];
    $types = '';

    $search = trim($search);
    if ($search !== '') {
        $sql .= ' AND (supplier_code LIKE ? OR supplier_name LIKE ? OR contact_person LIKE ? OR phone LIKE ? OR email LIKE ?)';
        $like = '%' . $search . '%';
        $params = [$like, $like, $like, $like, $like];
        $types = 'sssss';
    }

    if (in_array($status, ['active', 'inactive'], true)) {
        $sql .= ' AND status = ?';
        $params[] = $status;
        $types .= 's';
    }

    $sql .= ' ORDER BY supplier_name ASC';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function get_supplier_by_id($supplier_id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT supplier_id, supplier_code, supplier_name, contact_person, phone, email,
                   address, notes, status, created_at, updated_at
            FROM suppliers
            WHERE supplier_id = ?
            LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $supplier_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function get_active_suppliers()
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $status = 'active';
    $sql = 'SELECT supplier_id, supplier_code, supplier_name
            FROM suppliers
            WHERE status = ?
            ORDER BY supplier_name ASC';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $status);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function supplier_code_exists($supplier_code, $exclude_id = null)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    if ($exclude_id !== null) {
        $sql = 'SELECT supplier_id FROM suppliers WHERE supplier_code = ? AND supplier_id != ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('si', $supplier_code, $exclude_id);
    } else {
        $sql = 'SELECT supplier_id FROM suppliers WHERE supplier_code = ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $supplier_code);
    }

    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

function create_supplier($data)
{
    $db = get_db_connection();
    if (!$db) {
        return 0;
    }

    $sql = 'INSERT INTO suppliers
            (supplier_code, supplier_name, contact_person, phone, email, address, notes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = $db->prepare($sql);
    $stmt->bind_param(
        'ssssssss',
        $data['supplier_code'],
        $data['supplier_name'],
        $data['contact_person'],
        $data['phone'],
        $data['email'],
        $data['address'],
        $data['notes'],
        $data['status']
    );
    $ok = $stmt->execute();
    $supplier_id = $ok ? (int) $stmt->insert_id : 0;
    $stmt->close();
    return $supplier_id;
}

function update_supplier($supplier_id, $data)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'UPDATE suppliers
            SET supplier_code = ?, supplier_name = ?, contact_person = ?, phone = ?, email = ?,
                address = ?, notes = ?, status = ?
            WHERE supplier_id = ?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param(
        'ssssssssi',
        $data['supplier_code'],
        $data['supplier_name'],
        $data['contact_person'],
        $data['phone'],
        $data['email'],
        $data['address'],
        $data['notes'],
        $data['status'],
        $supplier_id
    );
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function update_supplier_status($supplier_id, $status)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'UPDATE suppliers SET status = ? WHERE supplier_id = ?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('si', $status, $supplier_id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}
