<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/reports.php';

require_permission('suppliers.view');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$search = trim($_GET['q'] ?? '');
$status = in_array($_GET['status'] ?? '', ['active', 'inactive'], true) ? $_GET['status'] : '';
$range = validate_report_date_range($date_from, $date_to);
$report = $range['valid']
    ? get_supplier_activity_report($range, $search, $status, report_page_number($_GET['page'] ?? 1))
    : ['rows' => [], 'summary' => ['supplier_count' => 0, 'movement_count' => 0, 'total_quantity' => 0], 'pagination' => report_pagination(0, 1, 100)];
$page_title = APP_NAME . ' — Supplier Stock-In Activity';

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2"><div><h1 class="h3 mb-1">Supplier Stock-In Activity</h1><p class="text-muted mb-0">Actual supplier-linked stock-in movements only.</p></div><div><a href="<?php echo e(report_export_link('suppliers')); ?>" class="btn btn-success">Export CSV</a> <a href="<?php echo e(BASE_URL); ?>/reports/index.php" class="btn btn-outline-secondary">Reports</a></div></div>
    <?php if (!$range['valid']): ?><div class="alert alert-danger"><?php echo e($range['message']); ?></div><?php endif; ?>
    <form method="get" class="card card-body mb-3 filter-bar"><div class="row g-2 align-items-end">
        <div class="col-md-2"><label class="form-label">Date From</label><input type="date" name="date_from" class="form-control" value="<?php echo e($date_from); ?>"></div>
        <div class="col-md-2"><label class="form-label">Date To</label><input type="date" name="date_to" class="form-control" value="<?php echo e($date_to); ?>"></div>
        <div class="col-md-4"><label class="form-label">Search</label><input type="text" name="q" class="form-control" value="<?php echo e($search); ?>" placeholder="Supplier code, name, or contact"></div>
        <div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All</option><option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option></select></div>
        <div class="col-auto"><button class="btn btn-primary">Apply</button> <a href="<?php echo e(BASE_URL); ?>/reports/suppliers.php" class="btn btn-link">Clear</a></div>
    </div></form>
    <div class="row g-3 mb-3"><div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">Suppliers</div><div class="h4 mb-0"><?php echo (int) $report['summary']['supplier_count']; ?></div></div></div></div><div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">Stock-In Movements</div><div class="h4 mb-0"><?php echo (int) $report['summary']['movement_count']; ?></div></div></div></div><div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">Total Quantity Supplied</div><div class="h4 mb-0"><?php echo e(format_qty($report['summary']['total_quantity'])); ?></div></div></div></div></div>
    <div class="card"><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Code</th><th>Supplier</th><th>Contact Person</th><th>Phone</th><th>Email</th><th>Status</th><th>Stock-In Movements</th><th>Total Quantity</th></tr></thead><tbody>
        <?php if (!$report['rows']): ?><tr><td colspan="8" class="text-muted">No suppliers match the selected filters.</td></tr><?php else: foreach ($report['rows'] as $supplier): ?><tr><td><?php echo e($supplier['supplier_code']); ?></td><td><?php echo e($supplier['supplier_name']); ?></td><td><?php echo e($supplier['contact_person'] ?: '—'); ?></td><td><?php echo e($supplier['phone'] ?: '—'); ?></td><td><?php echo e($supplier['email'] ?: '—'); ?></td><td><?php echo e(ucfirst($supplier['status'])); ?></td><td><?php echo (int) $supplier['movement_count']; ?></td><td><?php echo e(format_qty($supplier['total_quantity'])); ?></td></tr><?php endforeach; endif; ?>
    </tbody></table></div></div>
    <?php if ($report['pagination']['pages'] > 1): ?><nav class="mt-3"><ul class="pagination"><?php for ($p = 1; $p <= $report['pagination']['pages']; $p++): ?><li class="page-item <?php echo $p === $report['pagination']['page'] ? 'active' : ''; ?>"><a class="page-link" href="<?php echo e(report_url('/reports/suppliers.php', ['page' => $p])); ?>"><?php echo $p; ?></a></li><?php endfor; ?></ul></nav><?php endif; ?>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
