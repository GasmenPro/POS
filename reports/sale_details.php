<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/reports.php';

require_permission('sales.view');
$sale_id = report_positive_id($_GET['id'] ?? null);
$sale = $sale_id ? get_report_sale_detail($sale_id) : null;
if (!$sale) {
    set_flash('error', 'Sale not found.');
    redirect('/reports/sales.php');
}
$page_title = APP_NAME . ' — Sale Report Details';

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div><h1 class="h3 mb-1">Sale Details</h1><p class="text-muted mb-0"><?php echo e($sale['sale_no']); ?></p></div>
        <div><a href="<?php echo e(BASE_URL); ?>/receipts/view.php?sale_id=<?php echo (int) $sale['sale_id']; ?>" class="btn btn-outline-primary">Receipt</a> <a href="<?php echo e(BASE_URL); ?>/reports/sales.php" class="btn btn-outline-secondary">Back</a></div>
    </div>
    <div class="card mb-3"><div class="card-body"><div class="row">
        <div class="col-md-6"><strong>Date/Time:</strong> <?php echo e(date('M j, Y g:i A', strtotime($sale['sale_date']))); ?><br><strong>Cashier:</strong> <?php echo e($sale['cashier_name'] . ' (' . $sale['cashier_username'] . ')'); ?></div>
        <div class="col-md-6"><strong>Total:</strong> <?php echo e(report_money($sale['total_amount'])); ?><br><strong>Cash Received:</strong> <?php echo e(report_money($sale['payment_amount'])); ?><br><strong>Change:</strong> <?php echo e(report_money($sale['change_amount'])); ?></div>
    </div></div></div>
    <div class="card"><div class="table-responsive"><table class="table table-striped mb-0">
        <thead><tr><th>Product Code</th><th>Product</th><th>Quantity</th><th>Historical Unit Price</th><th>Line Total</th></tr></thead>
        <tbody><?php if (!$sale['items']): ?><tr><td colspan="5" class="text-muted">No sale items found.</td></tr><?php else: foreach ($sale['items'] as $item): ?>
            <tr><td><?php echo e($item['product_code']); ?></td><td><?php echo e($item['product_name']); ?></td><td><?php echo e(format_qty($item['quantity'])); ?></td><td><?php echo e(report_money($item['unit_price'])); ?></td><td><?php echo e(report_money($item['line_total'])); ?></td></tr>
        <?php endforeach; endif; ?></tbody>
    </table></div></div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
