<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/categories.php';

require_permission('categories.manage');

$page_title = APP_NAME . ' — Add Category';
$error = get_flash('error');
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <h1 class="h3 mb-3">Add Category</h1>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
    <div class="card"><div class="card-body">
        <form method="post" action="<?php echo e(BASE_URL); ?>/categories/process.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="create">
            <div class="mb-3">
                <label class="form-label" for="category_name">Category Name</label>
                <input type="text" class="form-control" id="category_name" name="category_name" value="<?php echo e($old['category_name'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="2"><?php echo e($old['description'] ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="active" <?php echo ($old['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($old['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Create Category</button>
            <a href="<?php echo e(BASE_URL); ?>/categories/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div></div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
