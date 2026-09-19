<?php
/**
 * MySQLi database connection foundation
 */

require_once __DIR__ . '/constants.php';

/**
 * Get a MySQLi connection instance.
 *
 * @return mysqli|null Returns mysqli on success, null on failure.
 */
function get_db_connection()
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    mysqli_report(MYSQLI_REPORT_OFF);

    $candidate = mysqli_init();
    if (!$candidate) {
        error_log('Database initialization failed.');
        return null;
    }

    $candidate->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    if (!@$candidate->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT)) {
        error_log('Database connection failed.');
        $candidate->close();
        return null;
    }

    if (!$candidate->set_charset(DB_CHARSET)) {
        error_log('Database character-set initialization failed.');
        $candidate->close();
        return null;
    }

    $connection = $candidate;
    return $connection;
}

/**
 * Check if database connection is available.
 *
 * @return bool
 */
function db_is_connected()
{
    return get_db_connection() instanceof mysqli;
}
