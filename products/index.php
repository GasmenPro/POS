<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/products.php';

require_permission('products.view');

$search = trim($_GET['q'] ?? '');
$status_filter = $_GET['status'] ?? '';
$items = get_all_products($search, $status_filter);
$page_title = APP_NAME . ' — Product Management';
$success = get_flash('success');
$error = get_flash('error');
$can_manage = user_has_permission('products.manage');

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Product Management</h1>
            <p class="text-muted mb-0">Manage product master records.</p>
        </div>
        <?php if ($can_manage): ?>
            <a href="<?php echo e(BASE_URL); ?>/products/add.php" class="btn btn-primary">Add Product</a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

    <form method="get" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="q" class="form-control" placeholder="Search code, barcode, or name..." value="<?php echo e($search); ?>">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary">Filter</button>
            <?php if ($search !== '' || $status_filter !== ''): ?>
                <a href="<?php echo e(BASE_URL); ?>/products/index.php" class="btn btn-link">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Barcode</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Unit</th>
                        <th>Price</th>
                        <th>Status</th>
                        <?php if ($can_manage): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="<?php echo $can_manage ? 9 : 8; ?>" class="text-muted">No products found.</td></tr>
                <?php else: foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo e($item['product_code']); ?></td>
                        <td><?php echo e($item['barcode'] ?? '—'); ?></td>
                        <td><?php echo e($item['product_name']); ?></td>
                        <td><?php echo e($item['category_name']); ?></td>
                        <td><?php echo e($item['brand_name'] ?? '—'); ?></td>
                        <td><?php echo e($item['unit_name']); ?></td>
                        <td><?php echo e(format_price($item['selling_price'])); ?></td>
                        <td><span class="badge bg-<?php echo $item['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo e(ucfirst($item['status'])); ?></span></td>
                        <?php if ($can_manage): ?>
                        <td>
                            <a href="<?php echo e(BASE_URL); ?>/products/edit.php?id=<?php echo e($item['product_id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="post" action="<?php echo e(BASE_URL); ?>/products/process.php" class="d-inline">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="<?php echo $item['status'] === 'active' ? 'deactivate' : 'activate'; ?>">
                                <input type="hidden" name="product_id" value="<?php echo e($item['product_id']); ?>">
                                <button type="submit" class="btn btn-sm btn-outline-<?php echo $item['status'] === 'active' ? 'warning' : 'success'; ?>"><?php echo $item['status'] === 'active' ? 'Deactivate' : 'Activate'; ?></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
