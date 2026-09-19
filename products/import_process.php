<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/product_import.php';

require_permission('products.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('error', 'Invalid import request.');
    redirect('/products/import.php');
}

$file = $_FILES['csv_file'] ?? [];
$filename = product_import_safe_filename($file['name'] ?? 'products.csv');
record_activity_log(get_current_user_id(), 'import_initiated', 'products', 'Product CSV import initiated: ' . $filename);

$result = process_product_csv_import($file, true);
cleanup_product_import_upload($file);

if ($result['fatal_error'] !== '') {
    record_activity_log(get_current_user_id(), 'import_failed', 'products', 'Product CSV import failed: ' . $filename . ' — ' . $result['fatal_error']);
} else {
    record_activity_log(
        get_current_user_id(),
        'import_completed',
        'products',
        'Product CSV import completed: ' . $filename . '; processed ' . $result['total_rows'] . ', imported ' . $result['imported'] . ', rejected ' . $result['failed']
    );
}

$_SESSION['product_import_result'] = $result;
redirect('/products/import.php');
