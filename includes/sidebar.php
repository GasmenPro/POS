<aside class="col-md-3 col-lg-2 sidebar bg-light border-end min-vh-100 p-3">
    <h6 class="text-muted text-uppercase small mb-3">Menu</h6>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link" href="<?php echo e(BASE_URL); ?>/">Home</a>
        </li>
        <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo e(BASE_URL); ?>/account.php">My Account</a>
        </li>
        <?php if (function_exists('user_has_permission') && user_has_permission('users.view')): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo e(BASE_URL); ?>/users/index.php">User Management</a>
        </li>
        <?php endif; ?>
        <?php if (function_exists('user_has_permission') && user_has_permission('roles.view')): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo e(BASE_URL); ?>/roles/index.php">Role Management</a>
        </li>
        <?php endif; ?>
        <?php if (function_exists('user_has_permission') && user_has_permission('categories.view')): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo e(BASE_URL); ?>/categories/index.php">Categories</a>
        </li>
        <?php endif; ?>
        <?php if (function_exists('user_has_permission') && user_has_permission('brands.view')): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo e(BASE_URL); ?>/brands/index.php">Brands</a>
        </li>
        <?php endif; ?>
        <?php if (function_exists('user_has_permission') && user_has_permission('units.view')): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo e(BASE_URL); ?>/units/index.php">Units</a>
        </li>
        <?php endif; ?>
        <?php if (function_exists('user_has_permission') && user_has_permission('products.view')): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo e(BASE_URL); ?>/products/index.php">Product Management</a>
        </li>
        <?php endif; ?>
        <?php if (function_exists('user_has_permission') && user_has_permission('inventory.view')): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo e(BASE_URL); ?>/inventory/index.php">Inventory</a>
        </li>
        <?php endif; ?>
        <?php else: ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo e(BASE_URL); ?>/login.php">Login</a>
        </li>
        <?php endif; ?>
    </ul>
    <p class="text-muted small mt-4 mb-0">Modules will be added in later phases.</p>
</aside>
