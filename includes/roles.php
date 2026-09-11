<?php
/**
 * Role management helpers
 */

require_once BASE_PATH . '/config/database.php';

/** @var string[] */
$PROTECTED_ROLE_NAMES = ['Administrator', 'Staff'];

/**
 * Check if a role is protected from deletion/renaming.
 *
 * @param string $role_name
 * @return bool
 */
function is_protected_role_name($role_name)
{
    global $PROTECTED_ROLE_NAMES;
    return in_array($role_name, $PROTECTED_ROLE_NAMES, true);
}

/**
 * Get all roles with permission summary.
 *
 * @return array
 */
function get_all_roles_with_permissions()
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT r.id, r.name, r.description, r.created_at,
                   COUNT(rp.permission_id) AS permission_count,
                   GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ", ") AS permissions
            FROM roles r
            LEFT JOIN role_permissions rp ON rp.role_id = r.id
            LEFT JOIN permissions p ON p.id = rp.permission_id
            GROUP BY r.id
            ORDER BY r.name ASC';

    $result = $db->query($sql);
    if (!$result) {
        return [];
    }

    $roles = [];
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }

    return $roles;
}

/**
 * Get a role by ID.
 *
 * @param int $role_id
 * @return array|null
 */
function get_role_by_id($role_id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT id, name, description, created_at, updated_at FROM roles WHERE id = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $role = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $role ?: null;
}

/**
 * Get all permissions.
 *
 * @return array
 */
function get_all_permissions()
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $result = $db->query('SELECT id, code, name, description FROM permissions ORDER BY name ASC');
    if (!$result) {
        return [];
    }

    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row;
    }

    return $permissions;
}

/**
 * Get permission IDs assigned to a role.
 *
 * @param int $role_id
 * @return array
 */
function get_role_permission_ids($role_id)
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT permission_id FROM role_permissions WHERE role_id = ?';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $role_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $ids = [];
    while ($row = $result->fetch_assoc()) {
        $ids[] = (int) $row['permission_id'];
    }

    $stmt->close();
    return $ids;
}

/**
 * Check if role name exists.
 *
 * @param string $name
 * @param int|null $exclude_id
 * @return bool
 */
function role_name_exists($name, $exclude_id = null)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    if ($exclude_id) {
        $sql = 'SELECT id FROM roles WHERE name = ? AND id != ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('si', $name, $exclude_id);
    } else {
        $sql = 'SELECT id FROM roles WHERE name = ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $name);
    }

    if (!$stmt) {
        return false;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();

    return $exists;
}

/**
 * Count users assigned to a role.
 *
 * @param int $role_id
 * @return int
 */
function count_users_with_role($role_id)
{
    $db = get_db_connection();
    if (!$db) {
        return 0;
    }

    $sql = 'SELECT COUNT(*) AS total FROM user_roles WHERE role_id = ?';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('i', $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ? (int) $row['total'] : 0;
}

/**
 * Create a role.
 *
 * @param string $name
 * @param string|null $description
 * @return int|false
 */
function create_role($name, $description)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'INSERT INTO roles (name, description) VALUES (?, ?)';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $name, $description);
    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }

    $role_id = (int) $stmt->insert_id;
    $stmt->close();
    return $role_id;
}

/**
 * Update a role.
 *
 * @param int $role_id
 * @param string $name
 * @param string|null $description
 * @return bool
 */
function update_role($role_id, $name, $description)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'UPDATE roles SET name = ?, description = ? WHERE id = ?';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ssi', $name, $description, $role_id);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}

/**
 * Set permissions for a role.
 *
 * @param int $role_id
 * @param array $permission_ids
 * @return bool
 */
function set_role_permissions($role_id, $permission_ids)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $permission_ids = array_unique(array_map('intval', $permission_ids));
    $valid_ids = [];
    foreach ($permission_ids as $pid) {
        if ($pid > 0) {
            $valid_ids[] = $pid;
        }
    }

    $db->begin_transaction();

    try {
        $sql = 'DELETE FROM role_permissions WHERE role_id = ?';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $role_id);
        $stmt->execute();
        $stmt->close();

        if (!empty($valid_ids)) {
            $sql = 'INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)';
            foreach ($valid_ids as $pid) {
                $stmt = $db->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Prepare failed');
                }
                $stmt->bind_param('ii', $role_id, $pid);
                if (!$stmt->execute()) {
                    $stmt->close();
                    throw new Exception('Insert failed');
                }
                $stmt->close();
            }
        }

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}

/**
 * Delete a role.
 *
 * @param int $role_id
 * @return bool
 */
function delete_role($role_id)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'DELETE FROM roles WHERE id = ?';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('i', $role_id);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}

/**
 * Get permission names by IDs.
 *
 * @param array $permission_ids
 * @return array
 */
function get_permission_names_by_ids($permission_ids)
{
    if (empty($permission_ids)) {
        return [];
    }

    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $ids = array_map('intval', $permission_ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $sql = "SELECT name FROM permissions WHERE id IN ($placeholders) ORDER BY name ASC";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();

    $names = [];
    while ($row = $result->fetch_assoc()) {
        $names[] = $row['name'];
    }

    $stmt->close();
    return $names;
}
