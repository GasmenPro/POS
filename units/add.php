<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/units.php';
require_permission('units.manage');
$page_title = APP_NAME . ' — Add Unit';
$error = get_flash('error');
$old = $_SESSION['old_input'] ?? []; unset($_SESSION['old_input']);
require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>
<main class="col-md-9 col-lg-10 p-4">
    <h1 class="h3 mb-3">Add Unit</h1>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
    <div class="card"><div class="card-body"><form method="post" action="<?php echo e(BASE_URL); ?>/units/process.php">
        <?php csrf_field(); ?><input type="hidden" name="action" value="create">
        <div class="mb-3"><label class="form-label">Unit Name</label><input type="text" class="form-control" name="unit_name" value="<?php echo e($old['unit_name'] ?? ''); ?>" required></div>
        <div class="mb-3"><label class="form-label">Unit Code</label><input type="text" class="form-control" name="unit_code" value="<?php echo e($old['unit_code'] ?? ''); ?>" required maxlength="20"></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"><?php echo e($old['description'] ?? ''); ?></textarea></div>
        <div class="mb-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
        <button type="submit" class="btn btn-primary">Create Unit</button>
        <a href="<?php echo e(BASE_URL); ?>/units/index.php" class="btn btn-outline-secondary">Cancel</a>
    </form></div></div>
</main>
<?php require_once BASE_PATH . '/includes/footer.php'; ?>
