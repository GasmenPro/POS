<?php
require_once BASE_PATH . '/includes/reports.php';

function dashboard_day_range($date = null)
{
    $date = $date ?: date('Y-m-d');
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date) {
        $parsed = new DateTimeImmutable('today');
    }
    return [
        'date' => $parsed->format('Y-m-d'),
        'start' => $parsed->format('Y-m-d 00:00:00'),
        'end' => $parsed->modify('+1 day')->format('Y-m-d 00:00:00'),
    ];
}

function get_dashboard_sales_summary($date = null)
{
    $range = dashboard_day_range($date);
    $row = report_fetch_one(
        'SELECT COUNT(*) AS sale_count,
                COALESCE(SUM(total_amount), 0) AS total_sales,
                COALESCE(SUM(payment_amount), 0) AS cash_received,
                COALESCE(SUM(change_amount), 0) AS change_amount
         FROM sales
         WHERE sale_date >= ? AND sale_date < ?',
        'ss',
        [$range['start'], $range['end']]
    );
    return $row ?: ['sale_count' => 0, 'total_sales' => 0, 'cash_received' => 0, 'change_amount' => 0];
}

function get_dashboard_recent_sales($limit = 7)
{
    $limit = min(max((int) $limit, 1), 20);
    return report_fetch_rows(
        'SELECT s.sale_id, s.sale_no, s.sale_date, s.total_amount, s.payment_amount, s.change_amount,
                CONCAT(u.first_name, " ", u.last_name) AS cashier_name
         FROM sales s
         INNER JOIN users u ON u.id = s.created_by
         ORDER BY s.sale_date DESC, s.sale_id DESC
         LIMIT ?',
        'i',
        [$limit]
    );
}

function get_dashboard_inventory_summary()
{
    $active = 'active';
    $row = report_fetch_one(
        'SELECT COUNT(*) AS active_product_count,
                COALESCE(SUM(i.quantity), 0) AS total_quantity,
                SUM(CASE WHEN i.quantity <= 0 THEN 1 ELSE 0 END) AS out_of_stock_count,
                SUM(CASE WHEN i.quantity > 0 AND i.reorder_level > 0 AND i.quantity <= i.reorder_level THEN 1 ELSE 0 END) AS low_stock_count,
                SUM(CASE WHEN i.quantity > 0 AND (i.reorder_level <= 0 OR i.quantity > i.reorder_level) THEN 1 ELSE 0 END) AS in_stock_count
         FROM products p
         INNER JOIN inventory i ON i.product_id = p.product_id
         WHERE p.status = ?',
        's',
        [$active]
    );
    return $row ?: [
        'active_product_count' => 0, 'total_quantity' => 0, 'out_of_stock_count' => 0,
        'low_stock_count' => 0, 'in_stock_count' => 0,
    ];
}

function get_dashboard_stock_alerts($limit = 8)
{
    $active = 'active';
    $limit = min(max((int) $limit, 1), 50);
    $rows = report_fetch_rows(
        'SELECT p.product_id, p.product_code, p.product_name, i.quantity, i.reorder_level
         FROM products p
         INNER JOIN inventory i ON i.product_id = p.product_id
         WHERE p.status = ?
           AND (i.quantity <= 0 OR (i.quantity > 0 AND i.reorder_level > 0 AND i.quantity <= i.reorder_level))
         ORDER BY CASE WHEN i.quantity <= 0 THEN 0 ELSE 1 END, i.quantity ASC, p.product_name ASC
         LIMIT ?',
        'si',
        [$active, $limit]
    );
    foreach ($rows as &$row) {
        $row['stock_status'] = get_stock_status($row['quantity'], $row['reorder_level']);
    }
    unset($row);
    return $rows;
}

function get_dashboard_supplier_summary()
{
    $row = report_fetch_one(
        'SELECT SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS inactive_count
         FROM suppliers',
        'ss',
        ['active', 'inactive']
    );
    return $row ?: ['active_count' => 0, 'inactive_count' => 0];
}

function get_dashboard_recent_supplier_stockins($limit = 5)
{
    $limit = min(max((int) $limit, 1), 20);
    return report_fetch_rows(
        'SELECT m.created_at, m.quantity, m.reference_no,
                p.product_code, p.product_name, s.supplier_code, s.supplier_name
         FROM inventory_movements m
         INNER JOIN products p ON p.product_id = m.product_id
         INNER JOIN suppliers s ON s.supplier_id = m.supplier_id
         WHERE m.movement_type = ?
         ORDER BY m.created_at DESC, m.movement_id DESC
         LIMIT ?',
        'si',
        ['stock_in', $limit]
    );
}

function dashboard_period_range($days, $end_date = null)
{
    $end = dashboard_day_range($end_date);
    $start = (new DateTimeImmutable($end['date']))->modify('-' . ((int) $days - 1) . ' days');
    return ['start' => $start->format('Y-m-d 00:00:00'), 'end' => $end['end'], 'end_date' => $end['date']];
}

function get_dashboard_top_products($days = 30, $limit = 5, $end_date = null)
{
    $days = min(max((int) $days, 1), 365);
    $limit = min(max((int) $limit, 1), 20);
    $range = dashboard_period_range($days, $end_date);
    return report_fetch_rows(
        'SELECT p.product_id, p.product_code, p.product_name,
                SUM(si.quantity) AS quantity_sold, SUM(si.line_total) AS sales_amount
         FROM sale_items si
         INNER JOIN sales s ON s.sale_id = si.sale_id
         INNER JOIN products p ON p.product_id = si.product_id
         WHERE s.sale_date >= ? AND s.sale_date < ?
         GROUP BY p.product_id, p.product_code, p.product_name
         ORDER BY quantity_sold DESC, sales_amount DESC, p.product_name ASC
         LIMIT ?',
        'ssi',
        [$range['start'], $range['end'], $limit]
    );
}

function get_dashboard_sales_trend($days = 7, $end_date = null)
{
    $days = min(max((int) $days, 1), 31);
    $range = dashboard_period_range($days, $end_date);
    $rows = report_fetch_rows(
        'SELECT DATE(sale_date) AS sale_day, COUNT(*) AS sale_count,
                COALESCE(SUM(total_amount), 0) AS total_sales
         FROM sales
         WHERE sale_date >= ? AND sale_date < ?
         GROUP BY DATE(sale_date)
         ORDER BY sale_day ASC',
        'ss',
        [$range['start'], $range['end']]
    );
    $by_date = [];
    foreach ($rows as $row) {
        $by_date[$row['sale_day']] = $row;
    }
    $start = new DateTimeImmutable(substr($range['start'], 0, 10));
    $trend = [];
    for ($offset = 0; $offset < $days; $offset++) {
        $date = $start->modify('+' . $offset . ' days')->format('Y-m-d');
        $trend[] = $by_date[$date] ?? ['sale_day' => $date, 'sale_count' => 0, 'total_sales' => 0];
    }
    return $trend;
}
