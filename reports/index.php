<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/reports.php';

require_any_report_permission();
$page_title = APP_NAME . ' — Reports';

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <h1 class="h3 mb-1">Reports</h1>
    <p class="text-muted mb-4">Review recorded sales, inventory, movements, and supplier stock-in activity.</p>

    <div class="row g-3">
        <?php if (user_has_permission('sales.view')): ?>
        <div class="col-md-6 col-xl-4"><div class="card h-100"><div class="card-body">
            <h2 class="h5">Sales Report</h2>
            <p class="text-muted">Recorded sale totals, cash received, change, cashier, and historical line items.</p>
            <a href="<?php echo e(BASE_URL); ?>/reports/sales.php" class="btn btn-outline-primary">Open Report</a>
        </div></div></div>
        <?php endif; ?>

        <?php if (user_has_permission('inventory.view')): ?>
        <div class="col-md-6 col-xl-4"><div class="card h-100"><div class="card-body">
            <h2 class="h5">Inventory Report</h2>
            <p class="text-muted">Current quantities, reorder levels, product status, and potential sales value.</p>
            <a href="<?php echo e(BASE_URL); ?>/reports/inventory.php" class="btn btn-outline-primary">Open Report</a>
        </div></div></div>
        <div class="col-md-6 col-xl-4"><div class="card h-100"><div class="card-body">
            <h2 class="h5">Inventory Movements</h2>
            <p class="text-muted">Stock-in, stock-out, adjustment, supplier, reference, and user audit data.</p>
            <a href="<?php echo e(BASE_URL); ?>/reports/movements.php" class="btn btn-outline-primary">Open Report</a>
        </div></div></div>
        <div class="col-md-6 col-xl-4"><div class="card h-100"><div class="card-body">
            <h2 class="h5">Stock Status</h2>
            <p class="text-muted">Out of Stock, Low Stock, and In Stock products using current inventory rules.</p>
            <a href="<?php echo e(BASE_URL); ?>/reports/stock_status.php" class="btn btn-outline-primary">Open Report</a>
        </div></div></div>
        <?php endif; ?>

        <?php if (user_has_permission('suppliers.view')): ?>
        <div class="col-md-6 col-xl-4"><div class="card h-100"><div class="card-body">
            <h2 class="h5">Supplier Stock-In Activity</h2>
            <p class="text-muted">Actual supplier-linked stock-in movement counts and quantities.</p>
            <a href="<?php echo e(BASE_URL); ?>/reports/suppliers.php" class="btn btn-outline-primary">Open Report</a>
        </div></div></div>
        <?php endif; ?>
    </div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
