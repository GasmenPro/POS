<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/dashboard.php';

require_auth();

$user = get_logged_in_user();
$can_sales = user_has_permission('sales.view');
$can_inventory = user_has_permission('inventory.view');
$can_suppliers = user_has_permission('suppliers.view');

$sales_summary = $can_sales ? get_dashboard_sales_summary() : null;
$recent_sales = $can_sales ? get_dashboard_recent_sales(7) : [];
$top_products = $can_sales ? get_dashboard_top_products(30, 5) : [];
$sales_trend = $can_sales ? get_dashboard_sales_trend(7) : [];
$inventory_summary = $can_inventory ? get_dashboard_inventory_summary() : null;
$stock_alerts = $can_inventory ? get_dashboard_stock_alerts(8) : [];
$supplier_summary = $can_suppliers ? get_dashboard_supplier_summary() : null;
$supplier_stockins = $can_suppliers ? get_dashboard_recent_supplier_stockins(5) : [];

$page_title = APP_NAME . ' — Dashboard';
require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4 dashboard-page">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">Dashboard</h1>
            <p class="text-muted mb-0">Welcome, <?php echo e($user['first_name']); ?>. <?php echo e(date('l, F j, Y')); ?></p>
        </div>
        <a href="<?php echo e(BASE_URL); ?>/account.php" class="btn btn-outline-secondary"><i class="bi bi-person-circle" aria-hidden="true"></i> My Account</a>
    </div>

    <?php if ($can_sales || $can_inventory || $can_suppliers): ?>
    <div class="row g-3 mb-4">
        <?php if ($can_sales): ?>
        <div class="col-sm-6 col-xl-3"><div class="card h-100 metric-card metric-primary"><div class="card-body">
            <div class="metric-label">Today's Sales</div>
            <div class="metric-value money"><?php echo e(report_money($sales_summary['total_sales'])); ?></div>
            <div class="small"><?php echo (int) $sales_summary['sale_count']; ?> completed sale(s)</div>
        </div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card h-100 metric-card"><div class="card-body">
            <div class="metric-label">Today's Cash Received</div>
            <div class="metric-value money"><?php echo e(report_money($sales_summary['cash_received'])); ?></div>
            <div class="small">Change: <?php echo e(report_money($sales_summary['change_amount'])); ?></div>
        </div></div></div>
        <?php endif; ?>

        <?php if ($can_inventory): ?>
        <div class="col-sm-6 col-xl-3"><div class="card h-100 metric-card"><div class="card-body">
            <div class="metric-label">Active Products With Inventory</div>
            <div class="metric-value"><?php echo (int) $inventory_summary['active_product_count']; ?></div>
            <div class="small"><?php echo e(format_qty($inventory_summary['total_quantity'])); ?> total quantity on hand</div>
        </div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card h-100 metric-card metric-warning"><div class="card-body">
            <div class="metric-label">Low Stock</div>
            <div class="metric-value"><?php echo (int) $inventory_summary['low_stock_count']; ?></div>
            <div class="small">Active products at reorder level</div>
        </div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card h-100 metric-card metric-danger"><div class="card-body">
            <div class="metric-label">Out of Stock</div>
            <div class="metric-value"><?php echo (int) $inventory_summary['out_of_stock_count']; ?></div>
            <div class="small">Active products with zero stock</div>
        </div></div></div>
        <?php endif; ?>

        <?php if ($can_suppliers): ?>
        <div class="col-sm-6 col-xl-3"><div class="card h-100 metric-card"><div class="card-body">
            <div class="metric-label">Active Suppliers</div>
            <div class="metric-value"><?php echo (int) $supplier_summary['active_count']; ?></div>
            <div class="small"><?php echo (int) $supplier_summary['inactive_count']; ?> inactive</div>
        </div></div></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="card mb-4"><div class="card-body">
        <h2 class="h5 mb-3">Quick Actions</h2>
        <div class="d-flex flex-wrap gap-2">
            <?php if (user_has_permission('products.manage')): ?><a href="<?php echo e(BASE_URL); ?>/products/add.php" class="btn btn-outline-primary"><i class="bi bi-plus-circle" aria-hidden="true"></i> Add Product</a><?php endif; ?>
            <?php if (user_has_permission('inventory.manage')): ?>
                <a href="<?php echo e(BASE_URL); ?>/inventory/stock_in.php" class="btn btn-outline-success"><i class="bi bi-box-arrow-in-down" aria-hidden="true"></i> Stock In</a>
                <a href="<?php echo e(BASE_URL); ?>/inventory/stock_out.php" class="btn btn-outline-warning"><i class="bi bi-box-arrow-up" aria-hidden="true"></i> Stock Out</a>
                <a href="<?php echo e(BASE_URL); ?>/inventory/adjust.php" class="btn btn-outline-secondary">Adjust Inventory</a>
            <?php endif; ?>
            <?php if (user_has_permission('pos.view')): ?><a href="<?php echo e(BASE_URL); ?>/pos/index.php" class="btn btn-primary"><i class="bi bi-cart3" aria-hidden="true"></i> Open POS</a><?php endif; ?>
            <?php if ($can_sales): ?><a href="<?php echo e(BASE_URL); ?>/pos/sales.php" class="btn btn-outline-primary">View Sales</a><?php endif; ?>
            <?php if ($can_suppliers): ?><a href="<?php echo e(BASE_URL); ?>/suppliers/index.php" class="btn btn-outline-primary">Manage Suppliers</a><?php endif; ?>
            <?php if (user_can_view_any_report()): ?><a href="<?php echo e(BASE_URL); ?>/reports/index.php" class="btn btn-outline-dark">View Reports</a><?php endif; ?>
            <?php if (!user_has_permission('products.manage') && !user_has_permission('inventory.manage') && !user_has_permission('pos.view') && !$can_sales && !$can_suppliers): ?>
                <span class="text-muted">No module actions are available for your current permissions.</span>
            <?php endif; ?>
        </div>
    </div></div>

    <?php if ($can_sales): ?>
    <div class="card mb-4"><div class="card-header d-flex justify-content-between align-items-center"><span>Today's Sales Summary</span><a href="<?php echo e(BASE_URL); ?>/reports/sales.php?date_from=<?php echo e(date('Y-m-d')); ?>&amp;date_to=<?php echo e(date('Y-m-d')); ?>" class="btn btn-sm btn-outline-primary">Full Report</a></div><div class="card-body">
        <?php if ((int) $sales_summary['sale_count'] === 0): ?>
            <p class="text-muted mb-0">No completed sales today.</p>
        <?php else: ?>
        <div class="row g-3 text-center">
            <div class="col-6 col-lg-3"><div class="text-muted small">Sales Count</div><strong><?php echo (int) $sales_summary['sale_count']; ?></strong></div>
            <div class="col-6 col-lg-3"><div class="text-muted small">Sales Amount</div><strong><?php echo e(report_money($sales_summary['total_sales'])); ?></strong></div>
            <div class="col-6 col-lg-3"><div class="text-muted small">Cash Received</div><strong><?php echo e(report_money($sales_summary['cash_received'])); ?></strong></div>
            <div class="col-6 col-lg-3"><div class="text-muted small">Change</div><strong><?php echo e(report_money($sales_summary['change_amount'])); ?></strong></div>
        </div>
        <?php endif; ?>
    </div></div>

    <div class="card mb-4"><div class="card-header d-flex justify-content-between align-items-center"><span>Recent Sales</span><a href="<?php echo e(BASE_URL); ?>/pos/sales.php" class="btn btn-sm btn-outline-primary">View All</a></div><div class="table-responsive"><table class="table table-striped mb-0 align-middle">
        <thead><tr><th>Sale No.</th><th>Date/Time</th><th>Cashier</th><th>Total</th><th>Cash Received</th><th>Change</th><th></th></tr></thead>
        <tbody><?php if (!$recent_sales): ?><tr><td colspan="7" class="text-muted">No completed sales available.</td></tr><?php else: foreach ($recent_sales as $sale): ?>
            <tr><td><?php echo e($sale['sale_no']); ?></td><td><?php echo e(date('M j, Y g:i A', strtotime($sale['sale_date']))); ?></td><td><?php echo e($sale['cashier_name']); ?></td><td><?php echo e(report_money($sale['total_amount'])); ?></td><td><?php echo e(report_money($sale['payment_amount'])); ?></td><td><?php echo e(report_money($sale['change_amount'])); ?></td><td><a href="<?php echo e(BASE_URL); ?>/receipts/view.php?sale_id=<?php echo (int) $sale['sale_id']; ?>" class="btn btn-sm btn-outline-secondary">Receipt</a></td></tr>
        <?php endforeach; endif; ?></tbody>
    </table></div></div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6"><div class="card h-100"><div class="card-header">Top Products — Last 30 Days</div><div class="table-responsive"><table class="table table-sm table-striped mb-0"><thead><tr><th>Product</th><th>Qty Sold</th><th>Sales Amount</th></tr></thead><tbody>
            <?php if (!$top_products): ?><tr><td colspan="3" class="text-muted">No sales available for this period.</td></tr><?php else: foreach ($top_products as $product): ?><tr><td><?php echo e($product['product_code'] . ' — ' . $product['product_name']); ?></td><td><?php echo e(format_qty($product['quantity_sold'])); ?></td><td><?php echo e(report_money($product['sales_amount'])); ?></td></tr><?php endforeach; endif; ?>
        </tbody></table></div></div></div>
        <div class="col-lg-6"><div class="card h-100"><div class="card-header">Sales — Last 7 Days</div><div class="table-responsive"><table class="table table-sm table-striped mb-0"><thead><tr><th>Date</th><th>Sales Count</th><th>Total Sales</th></tr></thead><tbody>
            <?php foreach ($sales_trend as $day): ?><tr><td><?php echo e(date('M j, Y', strtotime($day['sale_day']))); ?></td><td><?php echo (int) $day['sale_count']; ?></td><td><?php echo e(report_money($day['total_sales'])); ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div></div>
    </div>
    <?php endif; ?>

    <?php if ($can_inventory): ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-8"><div class="card h-100"><div class="card-header d-flex justify-content-between align-items-center"><span>Stock Alerts</span><a href="<?php echo e(BASE_URL); ?>/inventory/index.php" class="btn btn-sm btn-outline-primary">Inventory</a></div><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Code</th><th>Product</th><th>Quantity</th><th>Reorder Level</th><th>Status</th></tr></thead><tbody>
            <?php if (!$stock_alerts): ?><tr><td colspan="5" class="text-muted">No low-stock or out-of-stock active products.</td></tr><?php else: foreach ($stock_alerts as $item): ?><tr><td><?php echo e($item['product_code']); ?></td><td><?php echo e($item['product_name']); ?></td><td><?php echo e(format_qty($item['quantity'])); ?></td><td><?php echo e(format_qty($item['reorder_level'])); ?></td><td><span class="badge bg-<?php echo e(get_stock_status_badge($item['stock_status'])); ?>"><?php echo e($item['stock_status']); ?></span></td></tr><?php endforeach; endif; ?>
        </tbody></table></div></div></div>
        <div class="col-lg-4"><div class="card h-100"><div class="card-header">Inventory Overview</div><div class="card-body">
            <div class="d-flex justify-content-between mb-3"><span>In Stock</span><strong><?php echo (int) $inventory_summary['in_stock_count']; ?></strong></div>
            <div class="d-flex justify-content-between mb-3"><span>Low Stock</span><strong><?php echo (int) $inventory_summary['low_stock_count']; ?></strong></div>
            <div class="d-flex justify-content-between"><span>Out of Stock</span><strong><?php echo (int) $inventory_summary['out_of_stock_count']; ?></strong></div>
        </div></div></div>
    </div>
    <?php endif; ?>

    <?php if ($can_suppliers): ?>
    <div class="card mb-4"><div class="card-header d-flex justify-content-between align-items-center"><span>Recent Supplier Stock-In Activity</span><a href="<?php echo e(BASE_URL); ?>/reports/suppliers.php" class="btn btn-sm btn-outline-primary">Supplier Report</a></div><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Date/Time</th><th>Supplier</th><th>Product</th><th>Quantity</th><th>Reference</th></tr></thead><tbody>
        <?php if (!$supplier_stockins): ?><tr><td colspan="5" class="text-muted">No supplier-linked stock-in activity available.</td></tr><?php else: foreach ($supplier_stockins as $movement): ?><tr><td><?php echo e(date('M j, Y g:i A', strtotime($movement['created_at']))); ?></td><td><?php echo e($movement['supplier_code'] . ' — ' . $movement['supplier_name']); ?></td><td><?php echo e($movement['product_code'] . ' — ' . $movement['product_name']); ?></td><td><?php echo e(format_qty($movement['quantity'])); ?></td><td><?php echo e($movement['reference_no'] ?: '—'); ?></td></tr><?php endforeach; endif; ?>
    </tbody></table></div></div>
    <?php endif; ?>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
