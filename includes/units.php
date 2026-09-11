<?php
require_once BASE_PATH . '/config/database.php';

function get_all_units($search = '')
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $search = trim($search);
    if ($search !== '') {
        $like = '%' . $search . '%';
        $sql = 'SELECT unit_id, unit_name, unit_code, description, status, created_at
                FROM units
                WHERE unit_name LIKE ? OR unit_code LIKE ? OR description LIKE ?
                ORDER BY unit_name ASC';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('sss', $like, $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $db->query('SELECT unit_id, unit_name, unit_code, description, status, created_at FROM units ORDER BY unit_name ASC');
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

function get_unit_by_id($id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT unit_id, unit_name, unit_code, description, status, created_at, updated_at FROM units WHERE unit_id = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function unit_name_exists($name, $exclude_id = null)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    if ($exclude_id) {
        $sql = 'SELECT unit_id FROM units WHERE unit_name = ? AND unit_id != ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('si', $name, $exclude_id);
    } else {
        $sql = 'SELECT unit_id FROM units WHERE unit_name = ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $name);
    }

    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

function unit_code_exists($code, $exclude_id = null)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    if ($exclude_id) {
        $sql = 'SELECT unit_id FROM units WHERE unit_code = ? AND unit_id != ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('si', $code, $exclude_id);
    } else {
        $sql = 'SELECT unit_id FROM units WHERE unit_code = ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $code);
    }

    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

function create_unit($name, $code, $description, $status)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'INSERT INTO units (unit_name, unit_code, description, status) VALUES (?, ?, ?, ?)';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ssss', $name, $code, $description, $status);
    $ok = $stmt->execute();
    $id = $ok ? (int) $stmt->insert_id : false;
    $stmt->close();
    return $id;
}

function update_unit($id, $name, $code, $description, $status)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'UPDATE units SET unit_name = ?, unit_code = ?, description = ?, status = ? WHERE unit_id = ?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ssssi', $name, $code, $description, $status, $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function update_unit_status($id, $status)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'UPDATE units SET status = ? WHERE unit_id = ?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('si', $status, $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}
