<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/units.php';
require_permission('units.manage');
$id = (int) ($_GET['id'] ?? 0);
$item = get_unit_by_id($id);
if (!$item) { set_flash('error', 'Unit not found.'); redirect('/units/index.php'); }
$page_title = APP_NAME . ' — Edit Unit';
$error = get_flash('error');
$old = $_SESSION['old_input'] ?? []; unset($_SESSION['old_input']);
require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>
<main class="col-md-9 col-lg-10 p-4">
    <h1 class="h3 mb-3">Edit Unit</h1>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
    <div class="card"><div class="card-body"><form method="post" action="<?php echo e(BASE_URL); ?>/units/process.php">
        <?php csrf_field(); ?><input type="hidden" name="action" value="update"><input type="hidden" name="unit_id" value="<?php echo e($item['unit_id']); ?>">
        <div class="mb-3"><label class="form-label">Unit Name</label><input type="text" class="form-control" name="unit_name" value="<?php echo e($old['unit_name'] ?? $item['unit_name']); ?>" required></div>
        <div class="mb-3"><label class="form-label">Unit Code</label><input type="text" class="form-control" name="unit_code" value="<?php echo e($old['unit_code'] ?? $item['unit_code']); ?>" required maxlength="20"></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"><?php echo e($old['description'] ?? $item['description'] ?? ''); ?></textarea></div>
        <div class="mb-3"><label class="form-label">Status</label><select class="form-select" name="status">
            <?php foreach (['active','inactive'] as $s): ?><option value="<?php echo e($s); ?>" <?php echo ($old['status'] ?? $item['status']) === $s ? 'selected' : ''; ?>><?php echo e(ucfirst($s)); ?></option><?php endforeach; ?>
        </select></div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="<?php echo e(BASE_URL); ?>/units/index.php" class="btn btn-outline-secondary">Cancel</a>
    </form></div></div>
</main>
<?php require_once BASE_PATH . '/includes/footer.php'; ?>
