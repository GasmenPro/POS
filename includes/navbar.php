<nav class="navbar navbar-dark app-navbar sticky-top" aria-label="Primary navigation">
    <div class="container-fluid gap-2">
        <button class="btn btn-outline-light d-lg-none app-menu-button" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Open navigation">
            <i class="bi bi-list" aria-hidden="true"></i><span>Menu</span>
        </button>
        <a class="navbar-brand d-flex align-items-center gap-2 me-auto" href="<?php echo e(BASE_URL); ?>/">
            <span class="brand-mark" aria-hidden="true"><i class="bi bi-shop"></i></span>
            <span class="brand-copy"><strong><?php echo e(APP_NAME); ?></strong><small>Store Operations</small></span>
        </a>
        <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
            <?php $nav_user = get_logged_in_user(); ?>
            <a class="user-chip text-decoration-none" href="<?php echo e(BASE_URL); ?>/account.php" aria-label="Open account for <?php echo e($nav_user['first_name']); ?>">
                <span class="user-avatar" aria-hidden="true"><?php echo e(strtoupper(substr($nav_user['first_name'] ?: $nav_user['username'], 0, 1))); ?></span>
                <span class="user-copy d-none d-sm-flex">
                    <strong><?php echo e($nav_user['first_name'] . ' ' . $nav_user['last_name']); ?></strong>
                    <small><?php echo e(!empty($nav_user['roles']) ? implode(', ', $nav_user['roles']) : 'Account'); ?></small>
                </span>
            </a>
            <form method="post" action="<?php echo e(BASE_URL); ?>/logout.php" class="d-inline">
                <?php csrf_field(); ?>
                <button type="submit" class="btn btn-sm btn-outline-light" title="Log out securely">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span class="d-none d-sm-inline ms-1">Logout</span>
                </button>
            </form>
        <?php else: ?>
            <a class="btn btn-sm btn-outline-light" href="<?php echo e(BASE_URL); ?>/login.php">Login</a>
        <?php endif; ?>
    </div>
</nav>
