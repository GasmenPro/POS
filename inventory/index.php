<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/inventory.php';

require_permission('inventory.view');

$search = trim($_GET['q'] ?? '');
$stock_filter = $_GET['stock'] ?? '';
$items = get_inventory_list($search, $stock_filter);
$page_title = APP_NAME . ' — Inventory';
$success = get_flash('success');
$error = get_flash('error');
$can_manage = user_has_permission('inventory.manage');

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">Inventory</h1>
            <p class="text-muted mb-0">Current stock levels and reorder settings.</p>
        </div>
        <?php if ($can_manage): ?>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?php echo e(BASE_URL); ?>/inventory/stock_in.php" class="btn btn-success">Stock In</a>
            <a href="<?php echo e(BASE_URL); ?>/inventory/stock_out.php" class="btn btn-warning">Stock Out</a>
            <a href="<?php echo e(BASE_URL); ?>/inventory/adjust.php" class="btn btn-secondary">Adjust</a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

    <form method="get" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="q" class="form-control" placeholder="Search code, barcode, name..." value="<?php echo e($search); ?>">
        </div>
        <div class="col-md-3">
            <select name="stock" class="form-select">
                <option value="">All stock status</option>
                <option value="In Stock" <?php echo $stock_filter === 'In Stock' ? 'selected' : ''; ?>>In Stock</option>
                <option value="Low Stock" <?php echo $stock_filter === 'Low Stock' ? 'selected' : ''; ?>>Low Stock</option>
                <option value="Out of Stock" <?php echo $stock_filter === 'Out of Stock' ? 'selected' : ''; ?>>Out of Stock</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary">Filter</button>
            <?php if ($search !== '' || $stock_filter !== ''): ?>
                <a href="<?php echo e(BASE_URL); ?>/inventory/index.php" class="btn btn-link">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Barcode</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Unit</th>
                        <th>Stock</th>
                        <th>Reorder</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="9" class="text-muted">No inventory records found.</td></tr>
                <?php else: foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo e($item['product_code']); ?></td>
                        <td><?php echo e($item['barcode'] ?? '—'); ?></td>
                        <td>
                            <?php echo e($item['product_name']); ?>
                            <?php if ($item['product_status'] === 'inactive'): ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($item['category_name']); ?></td>
                        <td><?php echo e($item['unit_name']); ?></td>
                        <td><?php echo e(format_qty($item['quantity'])); ?></td>
                        <td>
                            <?php if ($can_manage): ?>
                            <form method="post" action="<?php echo e(BASE_URL); ?>/inventory/process.php" class="d-flex gap-1">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="reorder">
                                <input type="hidden" name="product_id" value="<?php echo e($item['product_id']); ?>">
                                <input type="number" name="reorder_level" class="form-control form-control-sm" min="0" step="0.001" value="<?php echo e(format_qty($item['reorder_level'])); ?>" style="width:90px">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Save</button>
                            </form>
                            <?php else: ?>
                                <?php echo e(format_qty($item['reorder_level'])); ?>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-<?php echo e(get_stock_status_badge($item['stock_status'])); ?>"><?php echo e($item['stock_status']); ?></span></td>
                        <td>
                            <a href="<?php echo e(BASE_URL); ?>/inventory/history.php?product_id=<?php echo e($item['product_id']); ?>" class="btn btn-sm btn-outline-primary">History</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
