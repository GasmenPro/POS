<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/inventory.php';
require_once dirname(__DIR__) . '/includes/products.php';

require_permission('inventory.view');

$product_id = (int) ($_GET['product_id'] ?? 0);
$product = get_product_by_id($product_id);

if (!$product) {
    set_flash('error', 'Product not found.');
    redirect('/inventory/index.php');
}

$movements = get_product_movements($product_id);
$page_title = APP_NAME . ' — Inventory History';

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Inventory History</h1>
            <p class="text-muted mb-0"><?php echo e($product['product_code'] . ' — ' . $product['product_name']); ?></p>
        </div>
        <a href="<?php echo e(BASE_URL); ?>/inventory/index.php" class="btn btn-outline-secondary">Back to Inventory</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Type</th>
                        <th>Qty/Diff</th>
                        <th>Previous</th>
                        <th>New</th>
                        <th>Supplier</th>
                        <th>Reference</th>
                        <th>Remarks</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($movements)): ?>
                    <tr><td colspan="9" class="text-muted">No movement history for this product.</td></tr>
                <?php else: foreach ($movements as $m): ?>
                    <tr>
                        <td><?php echo e(date('M j, Y g:i A', strtotime($m['created_at']))); ?></td>
                        <td><?php echo e(str_replace('_', ' ', ucfirst($m['movement_type']))); ?></td>
                        <td><?php echo e(format_qty($m['quantity'])); ?></td>
                        <td><?php echo e(format_qty($m['previous_quantity'])); ?></td>
                        <td><?php echo e(format_qty($m['new_quantity'])); ?></td>
                        <td><?php echo e($m['supplier_name'] ? $m['supplier_code'] . ' — ' . $m['supplier_name'] : '—'); ?></td>
                        <td><?php echo e($m['reference_no'] ?? '—'); ?></td>
                        <td><?php echo e($m['remarks'] ?? '—'); ?></td>
                        <td><?php echo e($m['user_name']); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
