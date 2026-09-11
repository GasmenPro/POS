<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/inventory.php';

require_permission('inventory.manage');

$products = get_active_products_for_inventory_select();
$page_title = APP_NAME . ' — Stock Adjustment';
$error = get_flash('error');

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <h1 class="h3 mb-3">Stock Adjustment</h1>
    <p class="text-muted">Set the actual counted stock quantity.</p>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

    <div class="card"><div class="card-body">
        <form method="post" action="<?php echo e(BASE_URL); ?>/inventory/process.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="adjust">
            <div class="mb-3">
                <label class="form-label">Product</label>
                <select name="product_id" class="form-select" required>
                    <option value="">Select product</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo e($p['product_id']); ?>"><?php echo e($p['product_code'] . ' — ' . $p['product_name'] . ' (Current: ' . format_qty($p['quantity']) . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">New Quantity</label>
                <input type="number" name="new_quantity" class="form-control" min="0" step="0.001" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Adjustment</button>
            <a href="<?php echo e(BASE_URL); ?>/inventory/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div></div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
