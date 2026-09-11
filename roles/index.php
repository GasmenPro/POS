<?php
/**
 * Role list
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/roles.php';

require_permission('roles.view');

$roles = get_all_roles_with_permissions();
$page_title = APP_NAME . ' — Role Management';
$success = get_flash('success');
$error = get_flash('error');
$can_manage = user_has_permission('roles.manage');

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Role Management</h1>
            <p class="text-muted mb-0">Manage roles and permission assignments.</p>
        </div>
        <?php if ($can_manage): ?>
            <a href="<?php echo e(BASE_URL); ?>/roles/add.php" class="btn btn-primary">Add Role</a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo e($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Description</th>
                        <th>Permissions</th>
                        <th>Created</th>
                        <?php if ($can_manage): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($roles)): ?>
                        <tr><td colspan="<?php echo $can_manage ? 5 : 4; ?>" class="text-muted">No roles found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($roles as $role): ?>
                            <tr>
                                <td><?php echo e($role['name']); ?></td>
                                <td><?php echo e($role['description'] ?? '—'); ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo e($role['permission_count']); ?></span>
                                    <?php if ($role['permissions']): ?>
                                        <small class="text-muted d-block"><?php echo e($role['permissions']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e(date('M j, Y', strtotime($role['created_at']))); ?></td>
                                <?php if ($can_manage): ?>
                                <td>
                                    <a href="<?php echo e(BASE_URL); ?>/roles/edit.php?id=<?php echo e($role['id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <?php if (!is_protected_role_name($role['name']) && count_users_with_role((int) $role['id']) === 0): ?>
                                        <form method="post" action="<?php echo e(BASE_URL); ?>/roles/process.php" class="d-inline">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="role_id" value="<?php echo e($role['id']); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this role?');">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
