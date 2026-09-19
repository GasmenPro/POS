<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/reports.php';

require_permission('sales.view');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$search = trim($_GET['q'] ?? '');
$range = validate_report_date_range($date_from, $date_to);
$report = $range['valid']
    ? get_sales_report($range, $search, report_page_number($_GET['page'] ?? 1))
    : ['rows' => [], 'summary' => ['sale_count' => 0, 'total_sales' => 0, 'total_payment' => 0, 'total_change' => 0], 'pagination' => report_pagination(0, 1, 50)];
$page_title = APP_NAME . ' — Sales Report';

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div><h1 class="h3 mb-1">Sales Report</h1><p class="text-muted mb-0">Recorded sale totals and cashier activity.</p></div>
        <div><a href="<?php echo e(report_export_link('sales')); ?>" class="btn btn-success">Export CSV</a> <a href="<?php echo e(BASE_URL); ?>/reports/index.php" class="btn btn-outline-secondary">Reports</a></div>
    </div>
    <?php if (!$range['valid']): ?><div class="alert alert-danger"><?php echo e($range['message']); ?></div><?php endif; ?>
    <form method="get" class="card card-body mb-3 filter-bar"><div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label">Date From</label><input type="date" name="date_from" class="form-control" value="<?php echo e($date_from); ?>"></div>
        <div class="col-md-3"><label class="form-label">Date To</label><input type="date" name="date_to" class="form-control" value="<?php echo e($date_to); ?>"></div>
        <div class="col-md-4"><label class="form-label">Search</label><input type="text" name="q" class="form-control" value="<?php echo e($search); ?>" placeholder="Sale no. or cashier"></div>
        <div class="col-auto"><button class="btn btn-primary">Apply</button> <a href="<?php echo e(BASE_URL); ?>/reports/sales.php" class="btn btn-link">Clear</a></div>
    </div></form>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Sales</div><div class="h4 mb-0"><?php echo (int) $report['summary']['sale_count']; ?></div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Total Sales</div><div class="h4 mb-0"><?php echo e(report_money($report['summary']['total_sales'])); ?></div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Cash Received</div><div class="h4 mb-0"><?php echo e(report_money($report['summary']['total_payment'])); ?></div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">Change</div><div class="h4 mb-0"><?php echo e(report_money($report['summary']['total_change'])); ?></div></div></div></div>
    </div>

    <div class="card"><div class="table-responsive"><table class="table table-striped mb-0 align-middle">
        <thead><tr><th>Sale No.</th><th>Date/Time</th><th>Cashier</th><th>Total</th><th>Cash Received</th><th>Change</th><th>Actions</th></tr></thead>
        <tbody><?php if (!$report['rows']): ?><tr><td colspan="7" class="text-muted">No sales match the selected filters.</td></tr><?php else: foreach ($report['rows'] as $sale): ?>
            <tr><td><?php echo e($sale['sale_no']); ?></td><td><?php echo e(date('M j, Y g:i A', strtotime($sale['sale_date']))); ?></td><td><?php echo e($sale['cashier_name']); ?></td><td><?php echo e(report_money($sale['total_amount'])); ?></td><td><?php echo e(report_money($sale['payment_amount'])); ?></td><td><?php echo e(report_money($sale['change_amount'])); ?></td><td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="<?php echo e(BASE_URL); ?>/reports/sale_details.php?id=<?php echo (int) $sale['sale_id']; ?>">Details</a> <a class="btn btn-sm btn-outline-secondary" href="<?php echo e(BASE_URL); ?>/receipts/view.php?sale_id=<?php echo (int) $sale['sale_id']; ?>">Receipt</a></td></tr>
        <?php endforeach; endif; ?></tbody>
    </table></div></div>
    <?php if ($report['pagination']['pages'] > 1): ?><nav class="mt-3"><ul class="pagination">
        <?php for ($p = 1; $p <= $report['pagination']['pages']; $p++): ?><li class="page-item <?php echo $p === $report['pagination']['page'] ? 'active' : ''; ?>"><a class="page-link" href="<?php echo e(report_url('/reports/sales.php', ['page' => $p])); ?>"><?php echo $p; ?></a></li><?php endfor; ?>
    </ul></nav><?php endif; ?>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
