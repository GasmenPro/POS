<?php
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/inventory.php';

function report_money($amount)
{
    return '₱' . number_format((float) $amount, 2, '.', ',');
}

function user_can_view_any_report()
{
    return user_has_permission('sales.view')
        || user_has_permission('inventory.view')
        || user_has_permission('suppliers.view');
}

function require_any_report_permission()
{
    require_auth();
    if (!user_can_view_any_report()) {
        set_flash('error', 'You do not have permission to access reports.');
        redirect('/account.php');
    }
}

function validate_report_date_range($date_from, $date_to)
{
    $date_from = trim((string) $date_from);
    $date_to = trim((string) $date_to);

    foreach ([$date_from, $date_to] as $date) {
        if ($date === '') {
            continue;
        }
        $parsed = DateTime::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            return ['valid' => false, 'message' => 'Enter valid report dates.', 'date_from' => $date_from, 'date_to' => $date_to];
        }
    }

    if ($date_from !== '' && $date_to !== '' && $date_from > $date_to) {
        return ['valid' => false, 'message' => 'Date From cannot be after Date To.', 'date_from' => $date_from, 'date_to' => $date_to];
    }

    return [
        'valid' => true,
        'message' => '',
        'date_from' => $date_from,
        'date_to' => $date_to,
        'from_boundary' => $date_from !== '' ? $date_from . ' 00:00:00' : null,
        'to_boundary' => $date_to !== '' ? date('Y-m-d H:i:s', strtotime($date_to . ' +1 day')) : null,
    ];
}

function report_page_number($value)
{
    $page = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $page === false ? 1 : (int) $page;
}

function report_positive_id($value)
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? 0 : (int) $id;
}

function report_fetch_rows($sql, $types = '', $params = [])
{
    $db = get_db_connection();
    if (!$db) {
        return [];
    }
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        return [];
    }
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function report_fetch_one($sql, $types = '', $params = [])
{
    $rows = report_fetch_rows($sql, $types, $params);
    return $rows[0] ?? null;
}

function report_pagination($total, $page, $per_page)
{
    $pages = max(1, (int) ceil($total / $per_page));
    $page = min(max(1, $page), $pages);
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $per_page, 'per_page' => $per_page];
}

function report_url($path, $overrides = [])
{
    $params = $_GET;
    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    $query = http_build_query($params);
    return BASE_URL . $path . ($query !== '' ? '?' . $query : '');
}

function report_sales_where($range, $search)
{
    $where = ' WHERE 1=1';
    $types = '';
    $params = [];
    if ($range['from_boundary'] !== null) {
        $where .= ' AND s.sale_date >= ?';
        $types .= 's';
        $params[] = $range['from_boundary'];
    }
    if ($range['to_boundary'] !== null) {
        $where .= ' AND s.sale_date < ?';
        $types .= 's';
        $params[] = $range['to_boundary'];
    }
    $search = trim((string) $search);
    if ($search !== '') {
        $where .= ' AND (s.sale_no LIKE ? OR u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)';
        $like = '%' . $search . '%';
        $types .= 'ssss';
        array_push($params, $like, $like, $like, $like);
    }
    return [$where, $types, $params];
}

function get_sales_report($range, $search = '', $page = 1, $per_page = 50, $all = false)
{
    list($where, $types, $params) = report_sales_where($range, $search);
    $from = ' FROM sales s INNER JOIN users u ON u.id = s.created_by';
    $summary = report_fetch_one(
        'SELECT COUNT(*) AS sale_count, COALESCE(SUM(s.total_amount), 0) AS total_sales,
                COALESCE(SUM(s.payment_amount), 0) AS total_payment,
                COALESCE(SUM(s.change_amount), 0) AS total_change' . $from . $where,
        $types,
        $params
    ) ?: ['sale_count' => 0, 'total_sales' => 0, 'total_payment' => 0, 'total_change' => 0];

    $sql = 'SELECT s.sale_id, s.sale_no, s.sale_date, s.total_amount, s.payment_amount, s.change_amount,
                   CONCAT(u.first_name, " ", u.last_name) AS cashier_name, u.username AS cashier_username'
        . $from . $where . ' ORDER BY s.sale_date DESC, s.sale_id DESC';
    $pagination = report_pagination((int) $summary['sale_count'], $page, $per_page);
    if (!$all) {
        $sql .= ' LIMIT ? OFFSET ?';
        $types .= 'ii';
        $params[] = $pagination['per_page'];
        $params[] = $pagination['offset'];
    }
    return ['rows' => report_fetch_rows($sql, $types, $params), 'summary' => $summary, 'pagination' => $pagination];
}

function get_report_sale_detail($sale_id)
{
    $sale = report_fetch_one(
        'SELECT s.sale_id, s.sale_no, s.sale_date, s.subtotal, s.total_amount, s.payment_amount, s.change_amount,
                CONCAT(u.first_name, " ", u.last_name) AS cashier_name, u.username AS cashier_username
         FROM sales s INNER JOIN users u ON u.id = s.created_by WHERE s.sale_id = ? LIMIT 1',
        'i',
        [$sale_id]
    );
    if (!$sale) {
        return null;
    }
    $sale['items'] = report_fetch_rows(
        'SELECT si.quantity, si.unit_price, si.line_total,
                COALESCE(p.product_code, "Unavailable") AS product_code,
                COALESCE(p.product_name, "Unavailable product") AS product_name
         FROM sale_items si
         LEFT JOIN products p ON p.product_id = si.product_id
         WHERE si.sale_id = ? ORDER BY si.sale_item_id ASC',
        'i',
        [$sale_id]
    );
    return $sale;
}

function report_stock_condition($stock_status)
{
    $quantity = 'COALESCE(i.quantity, 0)';
    $reorder = 'COALESCE(i.reorder_level, 0)';
    if ($stock_status === 'Out of Stock') {
        return ' AND ' . $quantity . ' <= 0';
    }
    if ($stock_status === 'Low Stock') {
        return ' AND ' . $quantity . ' > 0 AND ' . $reorder . ' > 0 AND ' . $quantity . ' <= ' . $reorder;
    }
    if ($stock_status === 'In Stock') {
        return ' AND ' . $quantity . ' > 0 AND (' . $reorder . ' <= 0 OR ' . $quantity . ' > ' . $reorder . ')';
    }
    return '';
}

function report_inventory_where($filters)
{
    $where = ' WHERE 1=1';
    $types = '';
    $params = [];
    $search = trim((string) ($filters['search'] ?? ''));
    if ($search !== '') {
        $where .= ' AND (p.product_code LIKE ? OR p.barcode LIKE ? OR p.product_name LIKE ?)';
        $like = '%' . $search . '%';
        $types .= 'sss';
        array_push($params, $like, $like, $like);
    }
    $category_id = (int) ($filters['category_id'] ?? 0);
    if ($category_id > 0) {
        $where .= ' AND p.category_id = ?';
        $types .= 'i';
        $params[] = $category_id;
    }
    $product_status = $filters['product_status'] ?? '';
    if (in_array($product_status, ['active', 'inactive'], true)) {
        $where .= ' AND p.status = ?';
        $types .= 's';
        $params[] = $product_status;
    }
    $where .= report_stock_condition($filters['stock_status'] ?? '');
    return [$where, $types, $params];
}

function get_inventory_report($filters, $page = 1, $per_page = 100, $all = false)
{
    list($where, $types, $params) = report_inventory_where($filters);
    $from = ' FROM products p
              INNER JOIN categories c ON c.category_id = p.category_id
              LEFT JOIN brands b ON b.brand_id = p.brand_id
              INNER JOIN units u ON u.unit_id = p.unit_id
              LEFT JOIN inventory i ON i.product_id = p.product_id';
    $summary = report_fetch_one(
        'SELECT COUNT(*) AS product_count, COALESCE(SUM(COALESCE(i.quantity, 0)), 0) AS total_quantity,
                COALESCE(SUM(COALESCE(i.quantity, 0) * p.selling_price), 0) AS potential_sales_value'
        . $from . $where,
        $types,
        $params
    ) ?: ['product_count' => 0, 'total_quantity' => 0, 'potential_sales_value' => 0];

    $sql = 'SELECT p.product_id, p.product_code, p.barcode, p.product_name, p.status AS product_status,
                   p.selling_price, c.category_name, b.brand_name, u.unit_name,
                   COALESCE(i.quantity, 0) AS quantity, COALESCE(i.reorder_level, 0) AS reorder_level,
                   COALESCE(i.quantity, 0) * p.selling_price AS potential_sales_value'
        . $from . $where . ' ORDER BY p.product_name ASC';
    $pagination = report_pagination((int) $summary['product_count'], $page, $per_page);
    if (!$all) {
        $sql .= ' LIMIT ? OFFSET ?';
        $types .= 'ii';
        $params[] = $pagination['per_page'];
        $params[] = $pagination['offset'];
    }
    $rows = report_fetch_rows($sql, $types, $params);
    foreach ($rows as &$row) {
        $row['stock_status'] = get_stock_status($row['quantity'], $row['reorder_level']);
    }
    unset($row);
    return ['rows' => $rows, 'summary' => $summary, 'pagination' => $pagination];
}

function report_movements_where($range, $filters)
{
    $where = ' WHERE 1=1';
    $types = '';
    $params = [];
    if ($range['from_boundary'] !== null) {
        $where .= ' AND m.created_at >= ?'; $types .= 's'; $params[] = $range['from_boundary'];
    }
    if ($range['to_boundary'] !== null) {
        $where .= ' AND m.created_at < ?'; $types .= 's'; $params[] = $range['to_boundary'];
    }
    $type = $filters['movement_type'] ?? '';
    if (in_array($type, ['stock_in', 'stock_out', 'adjustment'], true)) {
        $where .= ' AND m.movement_type = ?'; $types .= 's'; $params[] = $type;
    }
    $product_id = (int) ($filters['product_id'] ?? 0);
    if ($product_id > 0) {
        $where .= ' AND m.product_id = ?'; $types .= 'i'; $params[] = $product_id;
    }
    $supplier_id = (int) ($filters['supplier_id'] ?? 0);
    if ($supplier_id > 0) {
        $where .= ' AND m.supplier_id = ?'; $types .= 'i'; $params[] = $supplier_id;
    }
    return [$where, $types, $params];
}

function get_inventory_movement_report($range, $filters, $page = 1, $per_page = 100, $all = false)
{
    list($where, $types, $params) = report_movements_where($range, $filters);
    $from = ' FROM inventory_movements m
              INNER JOIN products p ON p.product_id = m.product_id
              LEFT JOIN suppliers s ON s.supplier_id = m.supplier_id
              INNER JOIN users u ON u.id = m.created_by';
    $summary = report_fetch_one('SELECT COUNT(*) AS movement_count' . $from . $where, $types, $params)
        ?: ['movement_count' => 0];
    $sql = 'SELECT m.movement_id, m.created_at, m.movement_type, m.quantity, m.previous_quantity,
                   m.new_quantity, m.reference_no, m.remarks, p.product_code, p.product_name,
                   s.supplier_code, s.supplier_name,
                   CONCAT(u.first_name, " ", u.last_name) AS user_name'
        . $from . $where . ' ORDER BY m.created_at DESC, m.movement_id DESC';
    $pagination = report_pagination((int) $summary['movement_count'], $page, $per_page);
    if (!$all) {
        $sql .= ' LIMIT ? OFFSET ?'; $types .= 'ii';
        $params[] = $pagination['per_page']; $params[] = $pagination['offset'];
    }
    return ['rows' => report_fetch_rows($sql, $types, $params), 'summary' => $summary, 'pagination' => $pagination];
}

function report_supplier_activity_parts($range, $search, $status)
{
    $join = ' LEFT JOIN inventory_movements m ON m.supplier_id = s.supplier_id AND m.movement_type = ?';
    $types = 's';
    $params = ['stock_in'];
    if ($range['from_boundary'] !== null) {
        $join .= ' AND m.created_at >= ?'; $types .= 's'; $params[] = $range['from_boundary'];
    }
    if ($range['to_boundary'] !== null) {
        $join .= ' AND m.created_at < ?'; $types .= 's'; $params[] = $range['to_boundary'];
    }
    $where = ' WHERE 1=1';
    $search = trim((string) $search);
    if ($search !== '') {
        $where .= ' AND (s.supplier_code LIKE ? OR s.supplier_name LIKE ? OR s.contact_person LIKE ?)';
        $like = '%' . $search . '%'; $types .= 'sss'; array_push($params, $like, $like, $like);
    }
    if (in_array($status, ['active', 'inactive'], true)) {
        $where .= ' AND s.status = ?'; $types .= 's'; $params[] = $status;
    }
    return [$join, $where, $types, $params];
}

function get_supplier_activity_report($range, $search = '', $status = '', $page = 1, $per_page = 100, $all = false)
{
    list($join, $where, $types, $params) = report_supplier_activity_parts($range, $search, $status);
    $summary = report_fetch_one(
        'SELECT COUNT(DISTINCT s.supplier_id) AS supplier_count, COUNT(m.movement_id) AS movement_count,
                COALESCE(SUM(m.quantity), 0) AS total_quantity
         FROM suppliers s' . $join . $where,
        $types,
        $params
    ) ?: ['supplier_count' => 0, 'movement_count' => 0, 'total_quantity' => 0];
    $sql = 'SELECT s.supplier_id, s.supplier_code, s.supplier_name, s.contact_person, s.phone, s.email, s.status,
                   COUNT(m.movement_id) AS movement_count, COALESCE(SUM(m.quantity), 0) AS total_quantity
            FROM suppliers s' . $join . $where . '
            GROUP BY s.supplier_id, s.supplier_code, s.supplier_name, s.contact_person, s.phone, s.email, s.status
            ORDER BY s.supplier_name ASC';
    $pagination = report_pagination((int) $summary['supplier_count'], $page, $per_page);
    if (!$all) {
        $sql .= ' LIMIT ? OFFSET ?'; $types .= 'ii';
        $params[] = $pagination['per_page']; $params[] = $pagination['offset'];
    }
    return ['rows' => report_fetch_rows($sql, $types, $params), 'summary' => $summary, 'pagination' => $pagination];
}

function get_report_categories()
{
    return report_fetch_rows('SELECT category_id, category_name FROM categories ORDER BY category_name ASC');
}

function get_report_products()
{
    return report_fetch_rows('SELECT product_id, product_code, product_name FROM products ORDER BY product_name ASC');
}

function get_report_suppliers()
{
    return report_fetch_rows('SELECT supplier_id, supplier_code, supplier_name FROM suppliers ORDER BY supplier_name ASC');
}

function report_export_link($type)
{
    $params = $_GET;
    unset($params['page']);
    $params['type'] = $type;
    return BASE_URL . '/reports/export.php?' . http_build_query($params);
}

function report_csv_safe($value)
{
    if (!is_string($value) || $value === '') {
        return $value;
    }
    return in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'" . $value : $value;
}

function output_report_csv($filename, $headers, $rows)
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');
    $stream = fopen('php://output', 'w');
    fputcsv($stream, $headers);
    foreach ($rows as $row) {
        $safe = [];
        foreach ($row as $value) {
            $safe[] = report_csv_safe($value);
        }
        fputcsv($stream, $safe);
    }
    fclose($stream);
    exit;
}
