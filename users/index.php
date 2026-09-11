<?php
/**
 * User list
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/users.php';

require_permission('users.view');

$users = get_all_users();
$page_title = APP_NAME . ' — User Management';
$success = get_flash('success');
$error = get_flash('error');
$can_manage = user_has_permission('users.manage');

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">User Management</h1>
            <p class="text-muted mb-0">Manage system user accounts.</p>
        </div>
        <?php if ($can_manage): ?>
            <a href="<?php echo e(BASE_URL); ?>/users/add.php" class="btn btn-primary">Add User</a>
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
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Roles</th>
                        <th>Created</th>
                        <?php if ($can_manage): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="<?php echo $can_manage ? 7 : 6; ?>" class="text-muted">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo e($user['username']); ?></td>
                                <td><?php echo e($user['email']); ?></td>
                                <td>
                                    <?php
                                    $badge = $user['status'] === 'active' ? 'success' : ($user['status'] === 'suspended' ? 'danger' : 'secondary');
                                    ?>
                                    <span class="badge bg-<?php echo e($badge); ?>"><?php echo e(ucfirst($user['status'])); ?></span>
                                </td>
                                <td><?php echo e($user['roles'] ?? '—'); ?></td>
                                <td><?php echo e(date('M j, Y', strtotime($user['created_at']))); ?></td>
                                <?php if ($can_manage): ?>
                                <td>
                                    <a href="<?php echo e(BASE_URL); ?>/users/edit.php?id=<?php echo e($user['id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <?php if ((int) $user['id'] !== get_current_user_id()): ?>
                                        <?php if ($user['status'] === 'active'): ?>
                                            <form method="post" action="<?php echo e(BASE_URL); ?>/users/process.php" class="d-inline">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="deactivate">
                                                <input type="hidden" name="user_id" value="<?php echo e($user['id']); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning">Deactivate</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="<?php echo e(BASE_URL); ?>/users/process.php" class="d-inline">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="activate">
                                                <input type="hidden" name="user_id" value="<?php echo e($user['id']); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success">Activate</button>
                                            </form>
                                        <?php endif; ?>
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
