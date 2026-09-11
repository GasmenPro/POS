<?php
/**
 * Common helper functions
 */

/**
 * Escape output for safe HTML display.
 *
 * @param mixed $value
 * @return string
 */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a path relative to BASE_URL.
 *
 * @param string $path
 * @return void
 */
function redirect($path)
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

/**
 * Get a flash message and clear it from session.
 *
 * @param string $key
 * @return string|null
 */
function get_flash($key)
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}

/**
 * Set a flash message in session.
 *
 * @param string $key
 * @param string $message
 * @return void
 */
function set_flash($key, $message)
{
    $_SESSION['flash'][$key] = $message;
}

/**
 * Generate or retrieve CSRF token for the current session.
 *
 * @return string
 */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field.
 *
 * @return void
 */
function csrf_field()
{
    echo '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify CSRF token from POST request.
 *
 * @return bool
 */
function verify_csrf()
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && hash_equals(csrf_token(), $token);
}

/**
 * Get client IP address.
 *
 * @return string|null
 */
function get_client_ip()
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    return is_string($ip) ? $ip : null;
}

/**
 * Get client user agent.
 *
 * @return string|null
 */
function get_user_agent()
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    if (!is_string($ua)) {
        return null;
    }
    return substr($ua, 0, 255);
}
