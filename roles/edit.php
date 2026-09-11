<?php
/**
 * Edit role form
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/roles.php';

require_permission('roles.manage');

$role_id = (int) ($_GET['id'] ?? 0);
$role = get_role_by_id($role_id);

if (!$role) {
    set_flash('error', 'Role not found.');
    redirect('/roles/index.php');
}

$permissions = get_all_permissions();
$assigned = get_role_permission_ids($role_id);
$is_protected = is_protected_role_name($role['name']);
$page_title = APP_NAME . ' — Edit Role';
$error = get_flash('error');
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <h1 class="h3 mb-3">Edit Role</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>

    <?php if ($is_protected): ?>
        <div class="alert alert-info py-2">This is a system role. Its name cannot be changed.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?php echo e(BASE_URL); ?>/roles/process.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="role_id" value="<?php echo e($role['id']); ?>">

                <div class="mb-3">
                    <label for="name" class="form-label">Role Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo e($old['name'] ?? $role['name']); ?>" <?php echo $is_protected ? 'readonly' : 'required'; ?>>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="2"><?php echo e($old['description'] ?? $role['description'] ?? ''); ?></textarea>
                </div>

                <h2 class="h6 mt-4">Permissions</h2>
                <div class="row">
                    <?php foreach ($permissions as $permission): ?>
                        <?php
                        $perm_id = (string) $permission['id'];
                        $checked = isset($old['permissions'])
                            ? in_array($perm_id, $old['permissions'], true)
                            : in_array((int) $permission['id'], $assigned, true);
                        ?>
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
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="<?php echo e(BASE_URL); ?>/roles/index.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
