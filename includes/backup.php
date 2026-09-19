<?php
/**
 * Database backup and restore helpers.
 */

/**
 * Return the protected application backup directory.
 *
 * @return string
 */
function backup_storage_directory()
{
    return BACKUP_STORAGE_PATH;
}

/**
 * Ensure the backup directory exists and is writable.
 *
 * @return bool
 */
function ensure_backup_storage()
{
    $directory = backup_storage_directory();

    if (!is_dir($directory) && !@mkdir($directory, 0750, true)) {
        return false;
    }

    return is_writable($directory);
}

/**
 * Find a checked XAMPP MySQL utility without invoking a shell.
 *
 * @param string $name mysqldump or mysql
 * @return string|null
 */
function find_mysql_utility($name)
{
    if (!in_array($name, ['mysqldump', 'mysql'], true)) {
        return null;
    }

    $configured_path = $name === 'mysqldump' ? MYSQLDUMP_PATH : MYSQL_CLIENT_PATH;
    $extension = PHP_OS_FAMILY === 'Windows' ? '.exe' : '';
    $xampp_root = dirname(dirname(BASE_PATH));
    $candidates = array_filter([
        $configured_path,
        $xampp_root . '/mysql/bin/' . $name . $extension,
        '/usr/bin/' . $name,
        '/usr/local/bin/' . $name,
    ]);

    foreach ($candidates as $candidate) {
        if (is_file($candidate) && is_readable($candidate)) {
            return $candidate;
        }
    }

    return null;
}

/**
 * Return environment readiness without exposing physical paths.
 *
 * @return array
 */
function get_backup_environment_status()
{
    return [
        'storage_ready' => ensure_backup_storage(),
        'mysqldump_ready' => find_mysql_utility('mysqldump') !== null,
        'mysql_ready' => find_mysql_utility('mysql') !== null,
        'proc_open_ready' => function_exists('proc_open'),
    ];
}

/**
 * Create a temporary MySQL option file so credentials are not placed on the command line.
 *
 * @return string|null
 */
function create_mysql_option_file()
{
    $values = [DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_CHARSET];
    foreach ($values as $value) {
        if (preg_match('/[\r\n]/', (string) $value)) {
            return null;
        }
    }

    $path = tempnam(sys_get_temp_dir(), 'pos_mysql_');
    if ($path === false) {
        return null;
    }

    $escape = static function ($value) {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $value);
    };

    $contents = "[client]\n"
        . 'host="' . $escape(DB_HOST) . "\"\n"
        . 'port="' . $escape(DB_PORT) . "\"\n"
        . 'user="' . $escape(DB_USER) . "\"\n"
        . 'password="' . $escape(DB_PASS) . "\"\n"
        . 'default-character-set="' . $escape(DB_CHARSET) . "\"\n";

    if (file_put_contents($path, $contents, LOCK_EX) === false) {
        @unlink($path);
        return null;
    }

    @chmod($path, 0600);
    return $path;
}

/**
 * Check an internal database identifier used by the command helpers.
 *
 * @param string $database_name
 * @return bool
 */
function is_safe_database_identifier($database_name)
{
    return preg_match('/^[A-Za-z0-9_]+$/', $database_name) === 1;
}

/**
 * Run mysqldump into a server-generated path.
 *
 * @param string $output_path
 * @param string $database_name
 * @param string|null $executable_override Internal testing only.
 * @return array
 */
function run_database_dump($output_path, $database_name = DB_NAME, $executable_override = null)
{
    if (!function_exists('proc_open') || !is_safe_database_identifier($database_name)) {
        return ['ok' => false, 'error' => 'Database backup is unavailable.'];
    }

    $executable = $executable_override ?: find_mysql_utility('mysqldump');
    if (!$executable || !is_file($executable)) {
        return ['ok' => false, 'error' => 'The database backup utility is unavailable.'];
    }

    $option_file = create_mysql_option_file();
    if ($option_file === null) {
        return ['ok' => false, 'error' => 'Unable to prepare the database backup.'];
    }

    $command = [
        $executable,
        '--defaults-extra-file=' . $option_file,
        '--single-transaction',
        '--routines',
        '--triggers',
        '--events',
        '--add-drop-table',
        '--default-character-set=' . DB_CHARSET,
        $database_name,
    ];

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['file', $output_path, 'wb'],
        2 => ['pipe', 'w'],
    ];

    $process = @proc_open($command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        @unlink($option_file);
        return ['ok' => false, 'error' => 'Unable to start the database backup utility.'];
    }

    fclose($pipes[0]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exit_code = proc_close($process);
    @unlink($option_file);

    return [
        'ok' => $exit_code === 0,
        'error' => $exit_code === 0 ? '' : 'The database backup utility reported an error.',
        'diagnostic' => substr(trim((string) $stderr), 0, 2000),
    ];
}

/**
 * Run the MySQL client with an SQL file as standard input.
 *
 * @param string $sql_path
 * @param string $database_name
 * @param string|null $executable_override Internal testing only.
 * @return array
 */
function run_database_restore($sql_path, $database_name = DB_NAME, $executable_override = null)
{
    if (!function_exists('proc_open') || !is_safe_database_identifier($database_name) || !is_file($sql_path) || !is_readable($sql_path)) {
        return ['ok' => false, 'error' => 'Database restoration is unavailable.'];
    }

    $executable = $executable_override ?: find_mysql_utility('mysql');
    if (!$executable || !is_file($executable)) {
        return ['ok' => false, 'error' => 'The database restore utility is unavailable.'];
    }

    $option_file = create_mysql_option_file();
    if ($option_file === null) {
        return ['ok' => false, 'error' => 'Unable to prepare the database restoration.'];
    }

    $command = [
        $executable,
        '--defaults-extra-file=' . $option_file,
        '--default-character-set=' . DB_CHARSET,
        '--database=' . $database_name,
        '--binary-mode',
    ];

    $descriptors = [
        0 => ['file', $sql_path, 'rb'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = @proc_open($command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        @unlink($option_file);
        return ['ok' => false, 'error' => 'Unable to start the database restore utility.'];
    }

    stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exit_code = proc_close($process);
    @unlink($option_file);

    return [
        'ok' => $exit_code === 0,
        'error' => $exit_code === 0 ? '' : 'The database restore utility reported an error.',
        'diagnostic' => substr(trim((string) $stderr), 0, 2000),
    ];
}

/**
 * Build a unique server-controlled backup filename.
 *
 * @param string $prefix database or pre_restore
 * @return string
 */
function generate_backup_filename($prefix = 'database')
{
    $safe_database = preg_replace('/[^A-Za-z0-9_-]/', '_', DB_NAME);
    $safe_prefix = $prefix === 'pre_restore' ? 'pre_restore' : $safe_database;

    return $safe_prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.sql';
}

/**
 * Check whether a browser-supplied filename matches an application-managed backup.
 *
 * @param string $filename
 * @return bool
 */
function is_managed_backup_filename($filename)
{
    if (!is_string($filename) || $filename === '' || basename($filename) !== $filename) {
        return false;
    }

    $safe_database = preg_quote(preg_replace('/[^A-Za-z0-9_-]/', '_', DB_NAME), '/');
    return preg_match('/^(?:' . $safe_database . '|pre_restore)_[0-9]{8}_[0-9]{6}_[a-f0-9]{8}\.sql$/', $filename) === 1;
}

/**
 * Resolve a managed backup strictly inside the protected directory.
 *
 * @param string $filename
 * @return string|null
 */
function resolve_managed_backup($filename)
{
    if (!is_managed_backup_filename($filename) || !ensure_backup_storage()) {
        return null;
    }

    $directory = realpath(backup_storage_directory());
    $path = realpath(backup_storage_directory() . DIRECTORY_SEPARATOR . $filename);

    if ($directory === false || $path === false || dirname($path) !== $directory || !is_file($path) || !is_readable($path)) {
        return null;
    }

    return $path;
}

/**
 * Perform basic structural checks on a generated application dump.
 *
 * @param string $path
 * @return bool
 */
function validate_database_backup($path)
{
    if (!is_file($path) || !is_readable($path) || filesize($path) < 100) {
        return false;
    }

    $handle = @fopen($path, 'rb');
    if (!$handle) {
        return false;
    }

    $found_create = false;
    $found_users = false;
    $found_permissions = false;
    $found_settings = false;
    $carry = '';

    while (!feof($handle)) {
        $chunk = fread($handle, 65536);
        if ($chunk === false) {
            fclose($handle);
            return false;
        }

        $contents = $carry . $chunk;
        $found_create = $found_create || stripos($contents, 'CREATE TABLE') !== false;
        $found_users = $found_users || preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?users`?/i', $contents) === 1;
        $found_permissions = $found_permissions || preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?permissions`?/i', $contents) === 1;
        $found_settings = $found_settings || preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?settings`?/i', $contents) === 1;
        $carry = substr($contents, -256);

        if ($found_create && $found_users && $found_permissions && $found_settings) {
            break;
        }
    }

    fclose($handle);
    return $found_create && $found_users && $found_permissions && $found_settings;
}

/**
 * Generate and verify a managed backup file.
 *
 * @param string $prefix
 * @param string|null $executable_override Internal testing only.
 * @return array
 */
function create_database_backup_file($prefix = 'database', $executable_override = null)
{
    if (!ensure_backup_storage()) {
        return ['ok' => false, 'error' => 'The backup directory is unavailable.'];
    }

    $filename = generate_backup_filename($prefix);
    $path = backup_storage_directory() . DIRECTORY_SEPARATOR . $filename;
    $result = run_database_dump($path, DB_NAME, $executable_override);

    if (!$result['ok'] || !validate_database_backup($path)) {
        if (is_file($path)) {
            @unlink($path);
        }
        return ['ok' => false, 'error' => 'The database backup could not be created or verified.'];
    }

    clearstatcache(true, $path);
    return [
        'ok' => true,
        'filename' => $filename,
        'size' => filesize($path),
        'created_at' => filemtime($path),
    ];
}

/**
 * Return only verified, managed backup files.
 *
 * @return array
 */
function list_database_backups()
{
    if (!ensure_backup_storage()) {
        return [];
    }

    $backups = [];
    $items = scandir(backup_storage_directory());
    if ($items === false) {
        return [];
    }

    foreach ($items as $filename) {
        if (!is_managed_backup_filename($filename)) {
            continue;
        }

        $path = resolve_managed_backup($filename);
        if ($path === null || !validate_database_backup($path)) {
            continue;
        }

        $backups[] = [
            'filename' => $filename,
            'size' => filesize($path),
            'created_at' => filemtime($path),
            'is_safety_backup' => str_starts_with($filename, 'pre_restore_'),
        ];
    }

    usort($backups, static function ($left, $right) {
        return $right['created_at'] <=> $left['created_at'];
    });

    return $backups;
}

/**
 * Format a byte count for the backup list.
 *
 * @param int $bytes
 * @return string
 */
function format_file_size($bytes)
{
    $bytes = max(0, (int) $bytes);
    $units = ['B', 'KB', 'MB', 'GB'];
    $size = (float) $bytes;
    $unit = 0;

    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024;
        $unit++;
    }

    return ($unit === 0 ? number_format($size, 0) : number_format($size, 2)) . ' ' . $units[$unit];
}

/**
 * Acquire a non-blocking server-side operation lock.
 *
 * @return resource|null
 */
function acquire_backup_operation_lock()
{
    if (!ensure_backup_storage()) {
        return null;
    }

    $handle = @fopen(backup_storage_directory() . '/.backup_restore.lock', 'c+');
    if (!$handle || !flock($handle, LOCK_EX | LOCK_NB)) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        return null;
    }

    return $handle;
}

/**
 * Release a server-side operation lock.
 *
 * @param resource|null $handle
 * @return void
 */
function release_backup_operation_lock($handle)
{
    if (is_resource($handle)) {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
