<?php
/**
 * Application entry point — Phase 0 foundation check
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = APP_NAME . ' — Foundation';

$db_status = db_is_connected()
    ? ['class' => 'success', 'message' => 'MySQLi connection successful.']
    : ['class' => 'warning', 'message' => 'MySQLi connection not available. Database may not exist yet (expected in Phase 0).'];

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <h1 class="h3 mb-3">Application Foundation</h1>
    <p class="text-muted">Phase 0 setup is loading successfully.</p>

    <div class="alert alert-<?php echo e($db_status['class']); ?>" role="alert">
        <?php echo e($db_status['message']); ?>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="h5 card-title">Foundation Status</h2>
            <ul class="mb-0">
                <li>PHP config loaded</li>
                <li>Constants defined</li>
                <li>Helper functions available</li>
                <li>Bootstrap 5 layout active</li>
                <li>MySQLi connection layer ready</li>
                <li>Authentication: <?php echo is_logged_in() ? 'Logged in' : 'Not logged in'; ?></li>
            </ul>
            <?php if (!is_logged_in()): ?>
                <a href="<?php echo e(BASE_URL); ?>/login.php" class="btn btn-primary btn-sm mt-3">Go to Login</a>
            <?php else: ?>
                <a href="<?php echo e(BASE_URL); ?>/account.php" class="btn btn-primary btn-sm mt-3">Go to Account</a>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
