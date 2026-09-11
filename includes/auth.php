<?php
/**
 * Authentication and access control helpers
 */

require_once BASE_PATH . '/config/database.php';

/**
 * Check if a user is logged in.
 *
 * @return bool
 */
function is_logged_in()
{
    return !empty($_SESSION['user_id']);
}

/**
 * Require authentication or redirect to login.
 *
 * @return void
 */
function require_auth()
{
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to continue.');
        redirect('/login.php');
    }
}

/**
 * Require a specific permission or deny access.
 *
 * @param string $permission_code
 * @return void
 */
function require_permission($permission_code)
{
    require_auth();

    if (!user_has_permission($permission_code)) {
        set_flash('error', 'You do not have permission to access this page.');
        redirect('/account.php');
    }
}

/**
 * Get current logged-in user ID.
 *
 * @return int|null
 */
function get_current_user_id()
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Get current logged-in user basic info from session.
 *
 * @return array|null
 */
function get_logged_in_user()
{
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'first_name' => $_SESSION['first_name'] ?? '',
        'last_name' => $_SESSION['last_name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'roles' => $_SESSION['roles'] ?? [],
    ];
}

/**
 * Check if current user has a role by name.
 *
 * @param string $role_name
 * @return bool
 */
function user_has_role($role_name)
{
    if (!is_logged_in()) {
        return false;
    }

    $roles = $_SESSION['roles'] ?? [];
    return in_array($role_name, $roles, true);
}

/**
 * Check if current user has a permission by code.
 *
 * @param string $permission_code
 * @return bool
 */
function user_has_permission($permission_code)
{
    $user_id = get_current_user_id();
    if (!$user_id) {
        return false;
    }

    $db = get_db_connection();
    if (!$db) {
        return false;
    }

    $sql = 'SELECT p.id
            FROM permissions p
            INNER JOIN role_permissions rp ON rp.permission_id = p.id
            INNER JOIN user_roles ur ON ur.role_id = rp.role_id
            WHERE ur.user_id = ? AND p.code = ?
            LIMIT 1';

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('is', $user_id, $permission_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $found = $result && $result->num_rows > 0;
    $stmt->close();

    return $found;
}

/**
 * Load role names for a user.
 *
 * @param int $user_id
 * @return array
 */
function get_user_roles($user_id)
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }

    $sql = 'SELECT r.name
            FROM roles r
            INNER JOIN user_roles ur ON ur.role_id = r.id
            WHERE ur.user_id = ?';

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $roles = [];
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row['name'];
    }

    $stmt->close();
    return $roles;
}

/**
 * Find user by username or email.
 *
 * @param string $login
 * @return array|null
 */
function find_user_by_login($login)
{
    $db = get_db_connection();
    if (!$db) {
        return null;
    }

    $sql = 'SELECT id, first_name, last_name, username, email, password_hash, status
            FROM users
            WHERE username = ? OR email = ?
            LIMIT 1';

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('ss', $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $user ?: null;
}

/**
 * Establish authenticated session for a user.
 *
 * @param array $user
 * @return void
 */
function login_user($user)
{
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['roles'] = get_user_roles((int) $user['id']);
}

/**
 * Log out the current user.
 *
 * @return void
 */
function logout_user()
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
    session_start();
    session_regenerate_id(true);
}

/**
 * Record a login attempt.
 *
 * @param int|null $user_id
 * @param string $status success|failed
 * @return void
 */
function record_login_log($user_id, $status)
{
    $db = get_db_connection();
    if (!$db) {
        return;
    }

    $ip = get_client_ip();
    $ua = get_user_agent();

    if ($user_id === null) {
        $sql = 'INSERT INTO login_logs (user_id, login_status, ip_address, user_agent) VALUES (NULL, ?, ?, ?)';
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('sss', $status, $ip, $ua);
    } else {
        $sql = 'INSERT INTO login_logs (user_id, login_status, ip_address, user_agent) VALUES (?, ?, ?, ?)';
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('isss', $user_id, $status, $ip, $ua);
    }

    $stmt->execute();
    $stmt->close();
}

/**
 * Record user activity.
 *
 * @param int|null $user_id
 * @param string $action
 * @param string $module
 * @param string|null $description
 * @return void
 */
function record_activity_log($user_id, $action, $module, $description = null)
{
    $db = get_db_connection();
    if (!$db) {
        return;
    }

    $ip = get_client_ip();
    $ua = get_user_agent();

    $sql = 'INSERT INTO activity_logs (user_id, action, module, description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return;
    }

    $stmt->bind_param('isssss', $user_id, $action, $module, $description, $ip, $ua);
    $stmt->execute();
    $stmt->close();
}
