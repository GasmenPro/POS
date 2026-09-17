<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/suppliers.php';

require_permission('suppliers.manage');

$supplier_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$supplier = $supplier_id ? get_supplier_by_id((int) $supplier_id) : null;
if (!$supplier) {
    set_flash('error', 'Supplier not found.');
    redirect('/suppliers/index.php');
}

$page_title = APP_NAME . ' — Edit Supplier';
$error = get_flash('error');
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <h1 class="h3 mb-3">Edit Supplier</h1>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

    <div class="card"><div class="card-body">
        <form method="post" action="<?php echo e(BASE_URL); ?>/suppliers/process.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="supplier_id" value="<?php echo (int) $supplier['supplier_id']; ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="supplier_code">Supplier Code</label>
                    <input type="text" class="form-control" id="supplier_code" name="supplier_code" maxlength="50" value="<?php echo e($old['supplier_code'] ?? $supplier['supplier_code']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="supplier_name">Supplier Name</label>
                    <input type="text" class="form-control" id="supplier_name" name="supplier_name" value="<?php echo e($old['supplier_name'] ?? $supplier['supplier_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="contact_person">Contact Person</label>
                    <input type="text" class="form-control" id="contact_person" name="contact_person" value="<?php echo e($old['contact_person'] ?? $supplier['contact_person'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="phone">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo e($old['phone'] ?? $supplier['phone'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo e($old['email'] ?? $supplier['email'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status">
                        <?php foreach (['active', 'inactive'] as $status): ?>
                            <option value="<?php echo e($status); ?>" <?php echo ($old['status'] ?? $supplier['status']) === $status ? 'selected' : ''; ?>><?php echo e(ucfirst($status)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="address">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2"><?php echo e($old['address'] ?? $supplier['address'] ?? ''); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"><?php echo e($old['notes'] ?? $supplier['notes'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Update Supplier</button>
                <a href="<?php echo e(BASE_URL); ?>/suppliers/index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div></div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
