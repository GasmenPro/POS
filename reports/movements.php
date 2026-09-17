<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/reports.php';

require_permission('inventory.view');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$range = validate_report_date_range($date_from, $date_to);
$filters = [
    'movement_type' => in_array($_GET['movement_type'] ?? '', ['stock_in', 'stock_out', 'adjustment'], true) ? $_GET['movement_type'] : '',
    'product_id' => report_positive_id($_GET['product_id'] ?? null),
    'supplier_id' => report_positive_id($_GET['supplier_id'] ?? null),
];
$report = $range['valid']
    ? get_inventory_movement_report($range, $filters, report_page_number($_GET['page'] ?? 1))
    : ['rows' => [], 'summary' => ['movement_count' => 0], 'pagination' => report_pagination(0, 1, 100)];
$products = get_report_products();
$suppliers = get_report_suppliers();
$page_title = APP_NAME . ' — Inventory Movement Report';

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2"><div><h1 class="h3 mb-1">Inventory Movement Report</h1><p class="text-muted mb-0">Recorded stock-in, stock-out, and adjustment activity.</p></div><div><a href="<?php echo e(report_export_link('movements')); ?>" class="btn btn-success">Export CSV</a> <a href="<?php echo e(BASE_URL); ?>/reports/index.php" class="btn btn-outline-secondary">Reports</a></div></div>
    <?php if (!$range['valid']): ?><div class="alert alert-danger"><?php echo e($range['message']); ?></div><?php endif; ?>
    <form method="get" class="card card-body mb-3"><div class="row g-2 align-items-end">
        <div class="col-md-2"><label class="form-label">Date From</label><input type="date" name="date_from" class="form-control" value="<?php echo e($date_from); ?>"></div>
        <div class="col-md-2"><label class="form-label">Date To</label><input type="date" name="date_to" class="form-control" value="<?php echo e($date_to); ?>"></div>
        <div class="col-md-2"><label class="form-label">Movement</label><select name="movement_type" class="form-select"><option value="">All</option><option value="stock_in" <?php echo $filters['movement_type'] === 'stock_in' ? 'selected' : ''; ?>>Stock In</option><option value="stock_out" <?php echo $filters['movement_type'] === 'stock_out' ? 'selected' : ''; ?>>Stock Out</option><option value="adjustment" <?php echo $filters['movement_type'] === 'adjustment' ? 'selected' : ''; ?>>Adjustment</option></select></div>
        <div class="col-md-3"><label class="form-label">Product</label><select name="product_id" class="form-select"><option value="">All products</option><?php foreach ($products as $product): ?><option value="<?php echo (int) $product['product_id']; ?>" <?php echo $filters['product_id'] === (int) $product['product_id'] ? 'selected' : ''; ?>><?php echo e($product['product_code'] . ' — ' . $product['product_name']); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label">Supplier</label><select name="supplier_id" class="form-select"><option value="">All suppliers</option><?php foreach ($suppliers as $supplier): ?><option value="<?php echo (int) $supplier['supplier_id']; ?>" <?php echo $filters['supplier_id'] === (int) $supplier['supplier_id'] ? 'selected' : ''; ?>><?php echo e($supplier['supplier_code'] . ' — ' . $supplier['supplier_name']); ?></option><?php endforeach; ?></select></div>
        <div class="col-auto"><button class="btn btn-primary">Apply</button> <a href="<?php echo e(BASE_URL); ?>/reports/movements.php" class="btn btn-link">Clear</a></div>
    </div></form>
    <div class="card mb-3"><div class="card-body"><span class="text-muted">Matching Movements:</span> <strong><?php echo (int) $report['summary']['movement_count']; ?></strong></div></div>
    <div class="card"><div class="table-responsive"><table class="table table-striped mb-0 align-middle"><thead><tr><th>Date/Time</th><th>Product</th><th>Type</th><th>Quantity</th><th>Previous</th><th>New</th><th>Reference</th><th>Supplier</th><th>User</th><th>Remarks</th></tr></thead><tbody>
        <?php if (!$report['rows']): ?><tr><td colspan="10" class="text-muted">No inventory movements match the selected filters.</td></tr><?php else: foreach ($report['rows'] as $movement): ?><tr><td><?php echo e(date('M j, Y g:i A', strtotime($movement['created_at']))); ?></td><td><?php echo e($movement['product_code'] . ' — ' . $movement['product_name']); ?></td><td><?php echo e(ucwords(str_replace('_', ' ', $movement['movement_type']))); ?></td><td><?php echo e(format_qty($movement['quantity'])); ?></td><td><?php echo e(format_qty($movement['previous_quantity'])); ?></td><td><?php echo e(format_qty($movement['new_quantity'])); ?></td><td><?php echo e($movement['reference_no'] ?: '—'); ?></td><td><?php echo e($movement['supplier_name'] ? $movement['supplier_code'] . ' — ' . $movement['supplier_name'] : '—'); ?></td><td><?php echo e($movement['user_name']); ?></td><td><?php echo e($movement['remarks'] ?: '—'); ?></td></tr><?php endforeach; endif; ?>
    </tbody></table></div></div>
    <?php if ($report['pagination']['pages'] > 1): ?><nav class="mt-3"><ul class="pagination"><?php for ($p = 1; $p <= $report['pagination']['pages']; $p++): ?><li class="page-item <?php echo $p === $report['pagination']['page'] ? 'active' : ''; ?>"><a class="page-link" href="<?php echo e(report_url('/reports/movements.php', ['page' => $p])); ?>"><?php echo $p; ?></a></li><?php endfor; ?></ul></nav><?php endif; ?>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
