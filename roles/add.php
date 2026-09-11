<?php
/**
 * Add role form
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/roles.php';

require_permission('roles.manage');

$permissions = get_all_permissions();
$page_title = APP_NAME . ' — Add Role';
$error = get_flash('error');
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <h1 class="h3 mb-3">Add Role</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?php echo e(BASE_URL); ?>/roles/process.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="create">

                <div class="mb-3">
                    <label for="name" class="form-label">Role Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo e($old['name'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="2"><?php echo e($old['description'] ?? ''); ?></textarea>
                </div>

                <h2 class="h6 mt-4">Permissions</h2>
                <div class="row">
                    <?php foreach ($permissions as $permission): ?>
                        <?php $checked = in_array((string) $permission['id'], $old['permissions'] ?? [], true); ?>
                        <div class="col-md-6 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo e($permission['id']); ?>" id="perm_<?php echo e($permission['id']); ?>" <?php echo $checked ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="perm_<?php echo e($permission['id']); ?>">
                                    <?php echo e($permission['name']); ?>
                                    <small class="text-muted">(<?php echo e($permission['code']); ?>)</small>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Create Role</button>
                    <a href="<?php echo e(BASE_URL); ?>/roles/index.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
