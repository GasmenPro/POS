<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/reports.php';

require_permission('inventory.view');
$filters = [
    'search' => '',
    'category_id' => report_positive_id($_GET['category_id'] ?? null),
    'stock_status' => in_array($_GET['stock_status'] ?? '', ['Out of Stock', 'Low Stock', 'In Stock'], true) ? $_GET['stock_status'] : '',
    'product_status' => in_array($_GET['product_status'] ?? '', ['active', 'inactive'], true) ? $_GET['product_status'] : '',
];
$report = get_inventory_report($filters, report_page_number($_GET['page'] ?? 1));
$categories = get_report_categories();
$page_title = APP_NAME . ' — Stock Status Report';

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-1">Stock Status Report</h1><p class="text-muted mb-0">Uses the same status rules as Inventory.</p></div><a href="<?php echo e(BASE_URL); ?>/reports/index.php" class="btn btn-outline-secondary">Reports</a></div>
    <form method="get" class="card card-body mb-3 filter-bar"><div class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label">Category</label><select name="category_id" class="form-select"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?php echo (int) $category['category_id']; ?>" <?php echo $filters['category_id'] === (int) $category['category_id'] ? 'selected' : ''; ?>><?php echo e($category['category_name']); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label">Stock Status</label><select name="stock_status" class="form-select"><option value="">All</option><?php foreach (['Out of Stock', 'Low Stock', 'In Stock'] as $status): ?><option value="<?php echo e($status); ?>" <?php echo $filters['stock_status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label">Product Status</label><select name="product_status" class="form-select"><option value="">All</option><option value="active" <?php echo $filters['product_status'] === 'active' ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo $filters['product_status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option></select></div>
        <div class="col-auto"><button class="btn btn-primary">Apply</button> <a href="<?php echo e(BASE_URL); ?>/reports/stock_status.php" class="btn btn-link">Clear</a></div>
    </div></form>
    <div class="card"><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Product</th><th>Category</th><th>Current Quantity</th><th>Reorder Level</th><th>Stock Status</th><th>Product Status</th></tr></thead><tbody>
        <?php if (!$report['rows']): ?><tr><td colspan="6" class="text-muted">No products match the selected stock status.</td></tr><?php else: foreach ($report['rows'] as $item): ?><tr><td><?php echo e($item['product_code'] . ' — ' . $item['product_name']); ?></td><td><?php echo e($item['category_name']); ?></td><td><?php echo e(format_qty($item['quantity'])); ?></td><td><?php echo e(format_qty($item['reorder_level'])); ?></td><td><span class="badge bg-<?php echo e(get_stock_status_badge($item['stock_status'])); ?>"><?php echo e($item['stock_status']); ?></span></td><td><?php echo e(ucfirst($item['product_status'])); ?></td></tr><?php endforeach; endif; ?>
    </tbody></table></div></div>
    <?php if ($report['pagination']['pages'] > 1): ?><nav class="mt-3"><ul class="pagination"><?php for ($p = 1; $p <= $report['pagination']['pages']; $p++): ?><li class="page-item <?php echo $p === $report['pagination']['page'] ? 'active' : ''; ?>"><a class="page-link" href="<?php echo e(report_url('/reports/stock_status.php', ['page' => $p])); ?>"><?php echo $p; ?></a></li><?php endfor; ?></ul></nav><?php endif; ?>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
