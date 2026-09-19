<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/pos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    pos_json_response(['ok' => false, 'error_type' => 'method', 'message' => 'Method not allowed.'], 405);
}

require_pos_api_permission('pos.manage');

if (!verify_pos_api_csrf()) {
    pos_json_response(['ok' => false, 'error_type' => 'csrf', 'message' => 'Security token expired. Reconnect and reload Offline POS.'], 403);
}

$content_length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($content_length > 1048576) {
    pos_json_response(['ok' => false, 'error_type' => 'validation_error', 'message' => 'Synchronization request is too large.'], 413);
}

$raw = file_get_contents('php://input');
$payload = json_decode((string) $raw, true);
if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
    pos_json_response(['ok' => false, 'error_type' => 'validation_error', 'message' => 'Invalid synchronization data.'], 400);
}

$result = process_offline_sale_sync($payload, get_current_user_id());
$status = $result['ok'] ? 200 : (($result['error_type'] ?? '') === 'server_error' ? 500 : 422);
pos_json_response($result, $status);
