<?php
/**
 * Application constants and environment-aware production configuration.
 *
 * Local XAMPP defaults are preserved. Production values should be supplied
 * through server environment variables; no production secrets belong here.
 */

function pos_env($name, $default = '')
{
    $value = getenv($name);
    if ($value === false) {
        return $default;
    }
    if (preg_match('/[\x00\r\n]/', (string) $value)) {
        throw new RuntimeException('Invalid application environment configuration.');
    }
    return (string) $value;
}

function pos_env_bool($name, $default = false)
{
    $value = getenv($name);
    if ($value === false || $value === '') {
        return (bool) $default;
    }
    $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($parsed === null) {
        throw new RuntimeException('Invalid application environment configuration.');
    }
    return $parsed;
}

function pos_normalize_path($path)
{
    $path = trim((string) $path);
    if ($path === '/' || preg_match('#^[A-Za-z]:[\\\\/]$#', $path)) {
        return $path;
    }
    return rtrim($path, '/\\');
}

function pos_is_absolute_path($path)
{
    return is_string($path)
        && $path !== ''
        && ($path[0] === '/' || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1 || str_starts_with($path, '\\\\'));
}

define('BASE_PATH', dirname(__DIR__));
define('APP_NAME', 'Sari-Sari Store POS');
define('APP_VERSION', '1.0.0');

$app_environment = strtolower(trim(pos_env('POS_APP_ENV', 'local')));
if (!in_array($app_environment, ['local', 'development', 'staging', 'production'], true)) {
    throw new RuntimeException('Invalid application environment configuration.');
}
define('APP_ENV', $app_environment);
define('APP_DEBUG', APP_ENV !== 'production' && pos_env_bool('POS_APP_DEBUG', false));
define('APP_TIMEZONE', pos_env('POS_TIMEZONE', 'Asia/Manila'));

$base_url = trim(pos_env('POS_BASE_URL', '/pos'));
if ($base_url === '/') {
    $base_url = '';
}
if ($base_url !== '' && ($base_url[0] !== '/' || preg_match('#[^A-Za-z0-9/_\-.~]#', $base_url))) {
    throw new RuntimeException('Invalid application base path configuration.');
}
define('BASE_URL', rtrim($base_url, '/'));

$db_port = filter_var(pos_env('POS_DB_PORT', '3306'), FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 65535],
]);
if ($db_port === false) {
    throw new RuntimeException('Invalid database port configuration.');
}
define('DB_HOST', pos_env('POS_DB_HOST', 'localhost'));
define('DB_PORT', (int) $db_port);
define('DB_USER', pos_env('POS_DB_USER', 'root'));
define('DB_PASS', pos_env('POS_DB_PASSWORD', ''));
define('DB_NAME', pos_env('POS_DB_NAME', 'pos_db'));
define('DB_CHARSET', 'utf8mb4');

$product_upload_path = pos_normalize_path(pos_env('POS_PRODUCT_UPLOAD_DIR', BASE_PATH . '/assets/uploads/products'));
if (!pos_is_absolute_path($product_upload_path)) {
    throw new RuntimeException('Invalid product upload path configuration.');
}
define('PRODUCT_UPLOAD_PATH', $product_upload_path);
$product_upload_url = rtrim(pos_env('POS_PRODUCT_UPLOAD_URL', BASE_URL . '/assets/uploads/products'), '/');
if ($product_upload_url === '' || $product_upload_url[0] !== '/' || preg_match('#[^A-Za-z0-9/_\-.~]#', $product_upload_url)) {
    throw new RuntimeException('Invalid product upload URL configuration.');
}
define('PRODUCT_UPLOAD_URL', $product_upload_url);
$backup_storage_path = pos_normalize_path(pos_env('POS_BACKUP_DIR', BASE_PATH . '/storage/backups'));
if (!pos_is_absolute_path($backup_storage_path)) {
    throw new RuntimeException('Invalid backup storage path configuration.');
}
define('BACKUP_STORAGE_PATH', $backup_storage_path);
define('APP_LOG_PATH', pos_env('POS_LOG_PATH', BASE_PATH . '/storage/logs/php-error.log'));
define('MYSQLDUMP_PATH', trim(pos_env('POS_MYSQLDUMP_PATH', '')));
define('MYSQL_CLIENT_PATH', trim(pos_env('POS_MYSQL_PATH', '')));

define('TRUST_PROXY_HTTPS', pos_env_bool('POS_TRUST_PROXY_HTTPS', false));
define('ENABLE_HSTS', pos_env_bool('POS_ENABLE_HSTS', false));
