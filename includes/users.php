<?php
/**
 * User management helpers
 */

require_once BASE_PATH . '/config/database.php';

/**
 * Get all users with assigned roles.
 *
 * @return array
 */
function get_all_users()
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT u.id, u.first_name, u.last_name, u.username, u.email, u.status, u.created_at,
                   GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ", ") AS roles
            FROM users u
            LEFT JOIN user_roles ur ON ur.user_id = u.id
            LEFT JOIN roles r ON r.id = ur.role_id
            GROUP BY u.id
            ORDER BY u.created_at DESC';

    $result = $db->query($sql);
    if (!$result) {
        return [];
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    return $users;
}

/**
 * Get a user by ID (no password hash).
 *
 * @param int $user_id
 * @return array|null
 */
function get_user_by_id($user_id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT id, first_name, last_name, username, email, status, created_at, updated_at
            FROM users WHERE id = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $user ?: null;
}

/**
 * Get role ID assigned to a user.
 *
 * @param int $user_id
 * @return int|null
 */
function get_user_role_id($user_id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT role_id FROM user_roles WHERE user_id = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ? (int) $row['role_id'] : null;
}

/**
 * Get all roles for select dropdowns.
 *
 * @return array
 */
function get_all_roles()
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $result = $db->query('SELECT id, name FROM roles ORDER BY name ASC');
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
 * Check if username exists.
 *
 * @param string $username
 * @param int|null $exclude_id
 * @return bool
 */
function username_exists($username, $exclude_id = null)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    if ($exclude_id) {
        $sql = 'SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('si', $username, $exclude_id);
    } else {
        $sql = 'SELECT id FROM users WHERE username = ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $username);
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
 * Check if email exists.
 *
 * @param string $email
 * @param int|null $exclude_id
 * @return bool
 */
function email_exists($email, $exclude_id = null)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    if ($exclude_id) {
        $sql = 'SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('si', $email, $exclude_id);
    } else {
        $sql = 'SELECT id FROM users WHERE email = ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $email);
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
 * Create a new user and assign a role.
 *
 * @param array $data
 * @param int $role_id
 * @return int|false
 */
function create_user($data, $role_id)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $db->begin_transaction();

    try {
        $sql = 'INSERT INTO users (first_name, last_name, username, email, password_hash, status)
                VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare failed');
        }

        $stmt->bind_param(
            'ssssss',
            $data['first_name'],
            $data['last_name'],
            $data['username'],
            $data['email'],
            $data['password_hash'],
            $data['status']
        );

        if (!$stmt->execute()) {
            throw new Exception('Insert failed');
        }

        $user_id = (int) $stmt->insert_id;
        $stmt->close();

        $sql = 'INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('ii', $user_id, $role_id);
        if (!$stmt->execute()) {
            throw new Exception('Role assign failed');
        }
        $stmt->close();

        $db->commit();
        return $user_id;
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}

/**
 * Update user and role assignment.
 *
 * @param int $user_id
 * @param array $data
 * @param int $role_id
 * @param string|null $password_hash
 * @return bool
 */
function update_user($user_id, $data, $role_id, $password_hash = null)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $db->begin_transaction();

    try {
        if ($password_hash) {
            $sql = 'UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?,
                    password_hash = ?, status = ? WHERE id = ?';
            $stmt = $db->prepare($sql);
            $stmt->bind_param(
                'ssssssi',
                $data['first_name'],
                $data['last_name'],
                $data['username'],
                $data['email'],
                $password_hash,
                $data['status'],
                $user_id
            );
        } else {
            $sql = 'UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?,
                    status = ? WHERE id = ?';
            $stmt = $db->prepare($sql);
            $stmt->bind_param(
                'sssssi',
                $data['first_name'],
                $data['last_name'],
                $data['username'],
                $data['email'],
                $data['status'],
                $user_id
            );
        }

        if (!$stmt->execute()) {
            throw new Exception('Update failed');
        }
        $stmt->close();

        $sql = 'DELETE FROM user_roles WHERE user_id = ?';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();

        $sql = 'INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('ii', $user_id, $role_id);
        if (!$stmt->execute()) {
            throw new Exception('Role assign failed');
        }
        $stmt->close();

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}

/**
 * Update user status only.
 *
 * @param int $user_id
 * @param string $status
 * @return bool
 */
function update_user_status($user_id, $status)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'UPDATE users SET status = ? WHERE id = ?';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('si', $status, $user_id);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}

/**
 * Check if user is the only active administrator.
 *
 * @param int $user_id
 * @return bool
 */
function is_only_active_administrator($user_id)
{
    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'SELECT COUNT(*) AS total
            FROM users u
            INNER JOIN user_roles ur ON ur.user_id = u.id
            INNER JOIN roles r ON r.id = ur.role_id
            WHERE r.name = ? AND u.status = ? AND u.id != ?';

    $role = 'Administrator';
    $status = 'active';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ssi', $role, $status, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row && (int) $row['total'] === 0;
}

/**
 * Get role name by ID.
 *
 * @param int $role_id
 * @return string|null
 */
function get_role_name($role_id)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT name FROM roles WHERE id = ? LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ? $row['name'] : null;
}
