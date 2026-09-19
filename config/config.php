<?php
/**
 * Main application configuration
 */

require_once __DIR__ . '/constants.php';

if (!@date_default_timezone_set(APP_TIMEZONE)) {
    date_default_timezone_set('Asia/Manila');
}

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

if (APP_LOG_PATH !== '') {
    $log_directory = dirname(APP_LOG_PATH);
    if ((is_dir($log_directory) || @mkdir($log_directory, 0750, true)) && is_writable($log_directory)) {
        ini_set('error_log', APP_LOG_PATH);
    }
}

$direct_https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
$forwarded_proto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
$is_https = $direct_https || (TRUST_PROXY_HTTPS && $forwarded_proto === 'https');
$session_path = BASE_URL !== '' ? BASE_URL . '/' : '/';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', $is_https ? '1' : '0');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => $session_path,
    'secure' => $is_https,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cache-Control: private, no-store, no-cache, must-revalidate');
    if (APP_ENV === 'production' && ENABLE_HSTS && $is_https) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

require_once BASE_PATH . '/includes/functions.php';
