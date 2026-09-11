<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo e(BASE_URL); ?>/"><?php echo e(APP_NAME); ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto">
                <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
                    <?php $nav_user = get_logged_in_user(); ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo e(BASE_URL); ?>/account.php">Account</a>
                    </li>
                    <li class="nav-item">
                        <form method="post" action="<?php echo e(BASE_URL); ?>/logout.php" class="d-inline">
                            <?php csrf_field(); ?>
                            <button type="submit" class="nav-link btn btn-link text-white">Logout</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo e(BASE_URL); ?>/login.php">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
