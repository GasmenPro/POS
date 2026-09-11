<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/products.php';

require_permission('products.manage');

$id = (int) ($_GET['id'] ?? 0);
$item = get_product_by_id($id);
if (!$item) {
    set_flash('error', 'Product not found.');
    redirect('/products/index.php');
}

$categories = get_active_categories_for_select();
$brands = get_active_brands_for_select();
$units = get_active_units_for_select();

if (!in_array($item['category_id'], array_column($categories, 'category_id'), true)) {
    $current_cat = get_category_by_id_raw($item['category_id']);
    if ($current_cat) {
        $categories[] = ['category_id' => $current_cat['category_id'], 'category_name' => $current_cat['category_name'] . ' (inactive)'];
    }
}
if ($item['brand_id'] && !in_array($item['brand_id'], array_column($brands, 'brand_id'), true)) {
    $current_brand = get_brand_by_id_raw($item['brand_id']);
    if ($current_brand) {
        $brands[] = ['brand_id' => $current_brand['brand_id'], 'brand_name' => $current_brand['brand_name'] . ' (inactive)'];
    }
}
if (!in_array($item['unit_id'], array_column($units, 'unit_id'), true)) {
    $current_unit = get_unit_by_id_raw($item['unit_id']);
    if ($current_unit) {
        $units[] = ['unit_id' => $current_unit['unit_id'], 'unit_name' => $current_unit['unit_name'], 'unit_code' => $current_unit['unit_code'] . '-inactive'];
    }
}

$page_title = APP_NAME . ' — Edit Product';
$error = get_flash('error');
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <h1 class="h3 mb-3">Edit Product</h1>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
    <div class="card"><div class="card-body">
        <form method="post" action="<?php echo e(BASE_URL); ?>/products/process.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="product_id" value="<?php echo e($item['product_id']); ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Product Code</label>
                    <input type="text" class="form-control" name="product_code" value="<?php echo e($old['product_code'] ?? $item['product_code']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Barcode</label>
                    <input type="text" class="form-control" name="barcode" value="<?php echo e($old['barcode'] ?? $item['barcode'] ?? ''); ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Product Name</label>
                    <input type="text" class="form-control" name="product_name" value="<?php echo e($old['product_name'] ?? $item['product_name']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo e($cat['category_id']); ?>" <?php echo (string) ($old['category_id'] ?? $item['category_id']) === (string) $cat['category_id'] ? 'selected' : ''; ?>><?php echo e($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Brand</label>
                    <select class="form-select" name="brand_id">
                        <option value="">None</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo e($brand['brand_id']); ?>" <?php echo (string) ($old['brand_id'] ?? $item['brand_id'] ?? '') === (string) $brand['brand_id'] ? 'selected' : ''; ?>><?php echo e($brand['brand_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Unit</label>
                    <select class="form-select" name="unit_id" required>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?php echo e($unit['unit_id']); ?>" <?php echo (string) ($old['unit_id'] ?? $item['unit_id']) === (string) $unit['unit_id'] ? 'selected' : ''; ?>><?php echo e($unit['unit_name'] . ' (' . $unit['unit_code'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2"><?php echo e($old['description'] ?? $item['description'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Selling Price</label>
                    <input type="number" class="form-control" name="selling_price" min="0" step="0.01" value="<?php echo e($old['selling_price'] ?? $item['selling_price']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <?php foreach (['active', 'inactive'] as $s): ?>
                            <option value="<?php echo e($s); ?>" <?php echo ($old['status'] ?? $item['status']) === $s ? 'selected' : ''; ?>><?php echo e(ucfirst($s)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?php echo e(BASE_URL); ?>/products/index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div></div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
