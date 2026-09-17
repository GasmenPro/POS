<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/reports.php';

$type = $_GET['type'] ?? '';

if ($type === 'sales') {
    require_permission('sales.view');
    $range = validate_report_date_range($_GET['date_from'] ?? '', $_GET['date_to'] ?? '');
    if (!$range['valid']) {
        http_response_code(400);
        exit('Invalid report date range.');
    }
    $report = get_sales_report($range, trim($_GET['q'] ?? ''), 1, 50, true);
    $rows = [];
    foreach ($report['rows'] as $sale) {
        $rows[] = [
            $sale['sale_no'], $sale['sale_date'], $sale['cashier_name'], $sale['cashier_username'],
            (float) $sale['total_amount'], (float) $sale['payment_amount'], (float) $sale['change_amount'],
        ];
    }
    output_report_csv('sales-report-' . date('Ymd-His') . '.csv',
        ['Sale No.', 'Sale Date', 'Cashier', 'Username', 'Total Sales', 'Cash Received', 'Change'], $rows);
}

if ($type === 'inventory') {
    require_permission('inventory.view');
    $filters = [
        'search' => trim($_GET['q'] ?? ''),
        'category_id' => report_positive_id($_GET['category_id'] ?? null),
        'stock_status' => in_array($_GET['stock_status'] ?? '', ['Out of Stock', 'Low Stock', 'In Stock'], true) ? $_GET['stock_status'] : '',
        'product_status' => in_array($_GET['product_status'] ?? '', ['active', 'inactive'], true) ? $_GET['product_status'] : '',
    ];
    $report = get_inventory_report($filters, 1, 100, true);
    $rows = [];
    foreach ($report['rows'] as $item) {
        $rows[] = [
            $item['product_code'], $item['barcode'] ?: '', $item['product_name'], $item['category_name'],
            $item['brand_name'] ?: '', $item['unit_name'], (float) $item['quantity'], (float) $item['reorder_level'],
            $item['stock_status'], (float) $item['selling_price'], (float) $item['potential_sales_value'], $item['product_status'],
        ];
    }
    output_report_csv('inventory-report-' . date('Ymd-His') . '.csv',
        ['Product Code', 'Barcode', 'Product', 'Category', 'Brand', 'Unit', 'Quantity', 'Reorder Level', 'Stock Status', 'Selling Price', 'Potential Sales Value', 'Product Status'], $rows);
}

if ($type === 'movements') {
    require_permission('inventory.view');
    $range = validate_report_date_range($_GET['date_from'] ?? '', $_GET['date_to'] ?? '');
    if (!$range['valid']) {
        http_response_code(400);
        exit('Invalid report date range.');
    }
    $filters = [
        'movement_type' => in_array($_GET['movement_type'] ?? '', ['stock_in', 'stock_out', 'adjustment'], true) ? $_GET['movement_type'] : '',
        'product_id' => report_positive_id($_GET['product_id'] ?? null),
        'supplier_id' => report_positive_id($_GET['supplier_id'] ?? null),
    ];
    $report = get_inventory_movement_report($range, $filters, 1, 100, true);
    $rows = [];
    foreach ($report['rows'] as $movement) {
        $rows[] = [
            $movement['created_at'], $movement['product_code'], $movement['product_name'], $movement['movement_type'],
            (float) $movement['quantity'], (float) $movement['previous_quantity'], (float) $movement['new_quantity'],
            $movement['reference_no'] ?: '', $movement['supplier_code'] ?: '', $movement['supplier_name'] ?: '',
            $movement['user_name'], $movement['remarks'] ?: '',
        ];
    }
    output_report_csv('inventory-movements-' . date('Ymd-His') . '.csv',
        ['Date/Time', 'Product Code', 'Product', 'Movement Type', 'Quantity', 'Previous Quantity', 'New Quantity', 'Reference No.', 'Supplier Code', 'Supplier', 'User', 'Remarks'], $rows);
}

if ($type === 'suppliers') {
    require_permission('suppliers.view');
    $range = validate_report_date_range($_GET['date_from'] ?? '', $_GET['date_to'] ?? '');
    if (!$range['valid']) {
        http_response_code(400);
        exit('Invalid report date range.');
    }
    $status = in_array($_GET['status'] ?? '', ['active', 'inactive'], true) ? $_GET['status'] : '';
    $report = get_supplier_activity_report($range, trim($_GET['q'] ?? ''), $status, 1, 100, true);
    $rows = [];
    foreach ($report['rows'] as $supplier) {
        $rows[] = [
            $supplier['supplier_code'], $supplier['supplier_name'], $supplier['contact_person'] ?: '',
            $supplier['phone'] ?: '', $supplier['email'] ?: '', $supplier['status'],
            (int) $supplier['movement_count'], (float) $supplier['total_quantity'],
        ];
    }
    output_report_csv('supplier-stock-in-' . date('Ymd-His') . '.csv',
        ['Supplier Code', 'Supplier', 'Contact Person', 'Phone', 'Email', 'Status', 'Stock-In Movements', 'Total Quantity Supplied'], $rows);
}

http_response_code(404);
exit('Report export not found.');
