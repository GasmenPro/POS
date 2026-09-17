<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/products.php';

require_permission('products.manage');

$categories = get_active_categories_for_select();
$brands = get_active_brands_for_select();
$units = get_active_units_for_select();
$page_title = APP_NAME . ' — Add Product';
$error = get_flash('error');
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <h1 class="h3 mb-3">Add Product</h1>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

    <?php if (empty($categories) || empty($units)): ?>
        <div class="alert alert-warning">Active categories and units are required before adding products.</div>
    <?php endif; ?>

    <div class="card"><div class="card-body">
        <form method="post" action="<?php echo e(BASE_URL); ?>/products/process.php" enctype="multipart/form-data">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="create">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Product Code</label>
                    <input type="text" class="form-control" name="product_code" maxlength="100" value="<?php echo e($old['product_code'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Barcode</label>
                    <input type="text" class="form-control" name="barcode" maxlength="100" value="<?php echo e($old['barcode'] ?? ''); ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Product Name</label>
                    <input type="text" class="form-control" name="product_name" maxlength="255" value="<?php echo e($old['product_name'] ?? ''); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category_id" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo e($cat['category_id']); ?>" <?php echo (string) ($old['category_id'] ?? '') === (string) $cat['category_id'] ? 'selected' : ''; ?>><?php echo e($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Brand</label>
                    <select class="form-select" name="brand_id">
                        <option value="">None</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo e($brand['brand_id']); ?>" <?php echo (string) ($old['brand_id'] ?? '') === (string) $brand['brand_id'] ? 'selected' : ''; ?>><?php echo e($brand['brand_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Unit</label>
                    <select class="form-select" name="unit_id" required>
                        <option value="">Select unit</option>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?php echo e($unit['unit_id']); ?>" <?php echo (string) ($old['unit_id'] ?? '') === (string) $unit['unit_id'] ? 'selected' : ''; ?>><?php echo e($unit['unit_name'] . ' (' . $unit['unit_code'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2"><?php echo e($old['description'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Product Image</label>
                    <input type="file" class="form-control" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <div class="form-text">Optional. JPG, PNG, or WEBP; maximum 5 MB.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Selling Price</label>
                    <input type="number" class="form-control" name="selling_price" min="0" step="0.01" value="<?php echo e($old['selling_price'] ?? '0.00'); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Create Product</button>
                <a href="<?php echo e(BASE_URL); ?>/products/index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div></div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
