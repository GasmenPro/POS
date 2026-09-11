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

    $connection = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($connection->connect_errno) {
        $connection = null;
        return null;
    }

    $connection->set_charset(DB_CHARSET);

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
