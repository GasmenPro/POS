<?php
/**
 * Edit user form
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/users.php';

require_permission('users.manage');

$user_id = (int) ($_GET['id'] ?? 0);
$user = get_user_by_id($user_id);

if (!$user) {
    set_flash('error', 'User not found.');
    redirect('/users/index.php');
}

$roles = get_all_roles();
$current_role_id = get_user_role_id($user_id);
$page_title = APP_NAME . ' — Edit User';
$error = get_flash('error');
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <h1 class="h3 mb-3">Edit User</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?php echo e(BASE_URL); ?>/users/process.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" value="<?php echo e($user['id']); ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo e($old['first_name'] ?? $user['first_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo e($old['last_name'] ?? $user['last_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo e($old['username'] ?? $user['username']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo e($old['email'] ?? $user['email']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <div class="form-text">Leave blank to keep the current password.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirm" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm">
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <?php foreach (['active', 'inactive', 'suspended'] as $status): ?>
                                <?php $selected = ($old['status'] ?? $user['status']) === $status; ?>
                                <option value="<?php echo e($status); ?>" <?php echo $selected ? 'selected' : ''; ?>>
                                    <?php echo e(ucfirst($status)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="role_id" class="form-label">Role</label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                                <?php $selected = (string) ($old['role_id'] ?? $current_role_id) === (string) $role['id']; ?>
                                <option value="<?php echo e($role['id']); ?>" <?php echo $selected ? 'selected' : ''; ?>>
                                    <?php echo e($role['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="<?php echo e(BASE_URL); ?>/users/index.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
