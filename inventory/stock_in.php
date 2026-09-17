<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/inventory.php';

require_permission('inventory.manage');

$products = get_active_products_for_inventory_select();
$suppliers = get_active_suppliers();
$page_title = APP_NAME . ' — Stock In';
$error = get_flash('error');

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <h1 class="h3 mb-3">Stock In</h1>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

    <?php if (empty($products)): ?>
        <div class="alert alert-warning">No active products available. Add products first.</div>
    <?php endif; ?>

    <div class="card"><div class="card-body">
        <form method="post" action="<?php echo e(BASE_URL); ?>/inventory/process.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="stock_in">
            <div class="mb-3">
                <label class="form-label">Product</label>
                <select name="product_id" class="form-select" required>
                    <option value="">Select product</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo e($p['product_id']); ?>"><?php echo e($p['product_code'] . ' — ' . $p['product_name'] . ' (Stock: ' . format_qty($p['quantity']) . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-select">
                    <option value="">No supplier</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo (int) $supplier['supplier_id']; ?>"><?php echo e($supplier['supplier_code'] . ' — ' . $supplier['supplier_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Optional. Only active suppliers are listed.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Quantity</label>
                <input type="number" name="quantity" class="form-control" min="0.001" step="0.001" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Reference Number</label>
                <input type="text" name="reference_no" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-success">Record Stock In</button>
            <a href="<?php echo e(BASE_URL); ?>/inventory/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div></div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
