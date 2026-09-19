<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/pos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    pos_json_response(['ok' => false, 'error_type' => 'method', 'message' => 'Method not allowed.'], 405);
}

require_pos_api_permission('pos.view');

pos_json_response([
    'ok' => true,
    'cached_at' => date(DATE_ATOM),
    'products' => get_offline_pos_products(),
]);
