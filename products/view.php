<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/products.php';

require_permission('products.view');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$item = $id ? get_product_details((int) $id) : null;
if (!$item) {
    set_flash('error', 'Product not found.');
    redirect('/products/index.php');
}

$page_title = APP_NAME . ' — Product Details';
$can_manage = user_has_permission('products.manage');
$image_url = product_image_url($item['image']);

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Product Details</h1>
        <div>
            <a href="<?php echo e(BASE_URL); ?>/products/index.php" class="btn btn-outline-secondary">Back</a>
            <?php if ($can_manage): ?>
                <a href="<?php echo e(BASE_URL); ?>/products/edit.php?id=<?php echo e($item['product_id']); ?>" class="btn btn-primary">Edit Product</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card"><div class="card-body">
        <div class="row g-4">
            <div class="col-md-3 text-center">
                <?php if ($image_url): ?>
                    <img src="<?php echo e($image_url); ?>" alt="<?php echo e($item['product_name']); ?>" class="product-image-preview">
                <?php else: ?>
                    <div class="product-image-placeholder mx-auto">No image</div>
                <?php endif; ?>
            </div>
            <div class="col-md-9">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Product Code</dt><dd class="col-sm-8"><?php echo e($item['product_code']); ?></dd>
                    <dt class="col-sm-4">Barcode</dt><dd class="col-sm-8"><?php echo e($item['barcode'] ?? '—'); ?></dd>
                    <dt class="col-sm-4">Product Name</dt><dd class="col-sm-8"><?php echo e($item['product_name']); ?></dd>
                    <dt class="col-sm-4">Category</dt><dd class="col-sm-8"><?php echo e($item['category_name']); ?></dd>
                    <dt class="col-sm-4">Brand</dt><dd class="col-sm-8"><?php echo e($item['brand_name'] ?? '—'); ?></dd>
                    <dt class="col-sm-4">Unit</dt><dd class="col-sm-8"><?php echo e($item['unit_name'] . ' (' . $item['unit_code'] . ')'); ?></dd>
                    <dt class="col-sm-4">Selling Price</dt><dd class="col-sm-8">₱<?php echo e(format_price($item['selling_price'])); ?></dd>
                    <dt class="col-sm-4">Current Stock</dt><dd class="col-sm-8"><?php echo e(format_qty($item['quantity'])); ?></dd>
                    <dt class="col-sm-4">Reorder Level</dt><dd class="col-sm-8"><?php echo e(format_qty($item['reorder_level'])); ?></dd>
                    <dt class="col-sm-4">Stock Status</dt><dd class="col-sm-8"><?php echo e($item['stock_status']); ?></dd>
                    <dt class="col-sm-4">Product Status</dt><dd class="col-sm-8"><?php echo e(ucfirst($item['status'])); ?></dd>
                    <dt class="col-sm-4">Description</dt><dd class="col-sm-8"><?php echo nl2br(e($item['description'] ?? '—')); ?></dd>
                    <dt class="col-sm-4">Created</dt><dd class="col-sm-8"><?php echo e($item['created_at']); ?></dd>
                    <dt class="col-sm-4">Updated</dt><dd class="col-sm-8"><?php echo e($item['updated_at']); ?></dd>
                </dl>
            </div>
        </div>
    </div></div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
