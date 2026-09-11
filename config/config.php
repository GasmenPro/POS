<?php
/**
 * Main application configuration
 */

require_once __DIR__ . '/constants.php';

date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once BASE_PATH . '/includes/functions.php';
