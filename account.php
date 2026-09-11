<?php
/**
 * Protected page — authentication test
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

require_auth();

$user = get_logged_in_user();
$page_title = APP_NAME . ' — My Account';

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <h1 class="h3 mb-3">My Account</h1>
    <p class="text-muted">Authentication is working. This is a protected test page.</p>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 card-title">Logged-in User</h2>
            <ul class="mb-0">
                <li><strong>Name:</strong> <?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></li>
                <li><strong>Username:</strong> <?php echo e($user['username']); ?></li>
                <li><strong>Email:</strong> <?php echo e($user['email']); ?></li>
                <li><strong>Roles:</strong> <?php echo e(implode(', ', $user['roles'])); ?></li>
            </ul>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 card-title">Access Control Check</h2>
            <ul class="mb-0">
                <li>Has Administrator role: <?php echo user_has_role('Administrator') ? 'Yes' : 'No'; ?></li>
                <li>Has settings.manage permission: <?php echo user_has_permission('settings.manage') ? 'Yes' : 'No'; ?></li>
            </ul>
        </div>
    </div>

    <form method="post" action="<?php echo e(BASE_URL); ?>/logout.php">
        <?php csrf_field(); ?>
        <button type="submit" class="btn btn-outline-danger">Logout</button>
    </form>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
