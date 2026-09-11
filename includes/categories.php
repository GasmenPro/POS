<?php
require_once BASE_PATH . '/config/database.php';

function get_all_categories($search = '')
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $search = trim($search);
    if ($search !== '') {
        $like = '%' . $search . '%';
        $sql = 'SELECT category_id, category_name, description, status, created_at
                FROM categories
                WHERE category_name LIKE ? OR description LIKE ?
                ORDER BY category_name ASC';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('ss', $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $db->query('SELECT category_id, category_name, description, status, created_at FROM categories ORDER BY category_name ASC');
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

function get_category_by_id($id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT category_id, category_name, description, status, created_at, updated_at FROM categories WHERE category_id = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function category_name_exists($name, $exclude_id = null)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    if ($exclude_id) {
        $sql = 'SELECT category_id FROM categories WHERE category_name = ? AND category_id != ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('si', $name, $exclude_id);
    } else {
        $sql = 'SELECT category_id FROM categories WHERE category_name = ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $name);
    }

    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

function create_category($name, $description, $status)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'INSERT INTO categories (category_name, description, status) VALUES (?, ?, ?)';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('sss', $name, $description, $status);
    $ok = $stmt->execute();
    $id = $ok ? (int) $stmt->insert_id : false;
    $stmt->close();
    return $id;
}

function update_category($id, $name, $description, $status)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'UPDATE categories SET category_name = ?, description = ?, status = ? WHERE category_id = ?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('sssi', $name, $description, $status, $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function update_category_status($id, $status)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'UPDATE categories SET status = ? WHERE category_id = ?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('si', $status, $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}
