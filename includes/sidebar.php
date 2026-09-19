<?php
$sidebar_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$sidebar_active = static function ($path, $prefix = false) use ($sidebar_path) {
    $target = rtrim(BASE_URL, '/') . $path;
    if ($path === '/') {
        return $sidebar_path === rtrim(BASE_URL, '/') . '/' || $sidebar_path === rtrim(BASE_URL, '/') . '/index.php';
    }
    return $prefix ? str_starts_with($sidebar_path, $target) : $sidebar_path === $target;
};
$sidebar_current = static function ($active) {
    return $active ? ' aria-current="page"' : '';
};
?>
<aside class="sidebar offcanvas-lg offcanvas-start col-lg-2 border-end" tabindex="-1" id="appSidebar" aria-label="Application navigation">
    <div class="offcanvas-header border-bottom d-lg-none">
        <div>
            <h2 class="offcanvas-title h6 mb-0">Navigation</h2>
            <small class="text-muted"><?php echo e(APP_NAME); ?></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Close navigation"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
        <nav class="sidebar-nav" aria-label="Sidebar">
            <div class="nav-section-label">Workspace</div>
            <ul class="nav flex-column mb-3">
                <?php $active = $sidebar_active('/'); ?>
                <li class="nav-item"><a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo e(BASE_URL); ?>/"<?php echo $sidebar_current($active); ?>><i class="bi bi-grid-1x2" aria-hidden="true"></i><span>Dashboard</span></a></li>
                <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
                <?php $active = $sidebar_active('/account.php'); ?>
                <li class="nav-item"><a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo e(BASE_URL); ?>/account.php"<?php echo $sidebar_current($active); ?>><i class="bi bi-person-circle" aria-hidden="true"></i><span>My Account</span></a></li>
                <?php endif; ?>
            </ul>

            <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
                <?php if (user_has_permission('pos.view') || user_has_permission('pos.manage') || user_has_permission('sales.view')): ?>
                <div class="nav-section-label">Sales</div>
                <ul class="nav flex-column mb-3">
                    <?php if (user_has_permission('pos.view')): $active = $sidebar_active('/pos/index.php'); ?>
                    <li class="nav-item"><a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo e(BASE_URL); ?>/pos/index.php"<?php echo $sidebar_current($active); ?>><i class="bi bi-cart3" aria-hidden="true"></i><span>Point of Sale</span></a></li>
                    <?php endif; ?>
                    <?php if (user_has_permission('pos.manage')): $active = $sidebar_active('/pos/offline.php'); ?>
                    <li class="nav-item"><a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo e(BASE_URL); ?>/pos/offline.php"<?php echo $sidebar_current($active); ?>><i class="bi bi-cloud-slash" aria-hidden="true"></i><span>Offline POS</span></a></li>
                    <?php endif; ?>
                    <?php if (user_has_permission('sales.view')): $active = $sidebar_active('/pos/sales.php'); ?>
                    <li class="nav-item"><a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo e(BASE_URL); ?>/pos/sales.php"<?php echo $sidebar_current($active); ?>><i class="bi bi-receipt" aria-hidden="true"></i><span>Sales History</span></a></li>
                    <?php endif; ?>
                </ul>
                <?php endif; ?>

                <?php if (user_has_permission('products.view') || user_has_permission('categories.view') || user_has_permission('brands.view') || user_has_permission('units.view')): ?>
                <div class="nav-section-label">Catalog</div>
                <ul class="nav flex-column mb-3">
                    <?php if (user_has_permission('products.view')): $active = $sidebar_active('/products/', true); ?>
                    <li class="nav-item"><a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo e(BASE_URL); ?>/products/index.php"<?php echo $sidebar_current($active); ?>><i class="bi bi-box-seam" aria-hidden="true"></i><span>Products</span></a></li>
                    <?php endif; ?>
                    <?php if (user_has_permission('categories.view')): $active = $sidebar_active('/categories/', true); ?>
                    <li class="nav-item"><a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo e(BASE_URL); ?>/categories/index.php"<?php echo $sidebar_current($active); ?>><i class="bi bi-tags" aria-hidden="true"></i><span>Categories</span></a></li>
                    <?php endif; ?>
                    <?php if (user_has_permission('brands.view')): $active = $sidebar_active('/brands/', true); ?>
                    <li class="nav-item"><a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo e(BASE_URL); ?>/brands/index.php"<?php echo $sidebar_current($active); ?>><i class="bi bi-award" aria-hidden="true"></i><span>Brands</span></a></li>
                    <?php endif; ?>
                    <?php if (user_has_permission('units.view')): $active = $sidebar_active('/units/', true); ?>
                    <li class="nav-item"><a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo e(BASE_URL); ?>/units/index.php"<?php echo $sidebar_current($active); ?>><i class="bi bi-rulers" aria-hidden="true"></i><span>Units</span></a></li>
                    <?php endif; ?>
                </ul>
                <?php endif; ?>

                <?php if (user_has_permission('inventory.view') || user_has_permission('suppliers.view')): ?>
                <div class="nav-section-label">Operations</div>
                <ul class="nav flex-column mb-3">
                    <?php if (user_has_permission('inventory.view')): $active = $sidebar_active('/inventory/', true); ?>
                    <li class="nav-item"><a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo e(BASE_URL); ?>/inventory/index.php"<?php echo $sidebar_current($active); ?>><i class="bi bi-boxes" aria-hidden="true"></i><span>Inventory</span></a></li>
                    <?php endif; ?>
                    <?php if (user_has_permission('suppliers.view')): $active = $sidebar_active('/suppliers/', true); ?>
                    <li class="nav-item"><a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo e(BASE_URL); ?>/suppliers/index.php"<?php echo $sidebar_current($active); ?>><i class="bi bi-truck" aria-hidden="true"></i><span>Suppliers</span></a></li>
                    <?php endif; ?>
                </ul>
                <?php endif; ?>

                <?php if (user_has_permission('sales.view') || user_has_permission('inventory.view') || user_has_permission('suppliers.view')): ?>
                <div class="nav-section-label">Insights</div>
                <ul class="nav flex-column mb-3">
                    <?php $active = $sidebar_active('/reports/', true); ?>
                    <li class="nav-item"><a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo e(BASE_URL); ?>/reports/index.php"<?php echo $sidebar_current($active); ?>><i class="bi bi-bar-chart-line" aria-hidden="true"></i><span>Reports</span></a></li>
                </ul>
                <?php endif; ?>

                <?php if (user_has_permission('users.view') || user_has_permission('roles.view') || user_has_permission('backup.manage')): ?>
                <div class="nav-section-label">Administration</div>
                <ul class="nav flex-column">
                    <?php if (user_has_permission('users.view')): $active = $sidebar_active('/users/', true); ?>
                    <li class="nav-item"><a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo e(BASE_URL); ?>/users/index.php"<?php echo $sidebar_current($active); ?>><i class="bi bi-people" aria-hidden="true"></i><span>Users</span></a></li>
                    <?php endif; ?>
                    <?php if (user_has_permission('roles.view')): $active = $sidebar_active('/roles/', true); ?>
                    <li class="nav-item"><a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo e(BASE_URL); ?>/roles/index.php"<?php echo $sidebar_current($active); ?>><i class="bi bi-shield-check" aria-hidden="true"></i><span>Roles &amp; Permissions</span></a></li>
                    <?php endif; ?>
                    <?php if (user_has_permission('backup.manage')): $active = $sidebar_active('/backup/', true); ?>
                    <li class="nav-item"><a class="nav-link<?php echo $active ? ' active' : ''; ?>" href="<?php echo e(BASE_URL); ?>/backup/index.php"<?php echo $sidebar_current($active); ?>><i class="bi bi-database-lock" aria-hidden="true"></i><span>Backup &amp; Restore</span></a></li>
                    <?php endif; ?>
                </ul>
                <?php endif; ?>
            <?php else: ?>
                <ul class="nav flex-column"><li class="nav-item"><a class="nav-link" href="<?php echo e(BASE_URL); ?>/login.php"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i><span>Login</span></a></li></ul>
            <?php endif; ?>
        </nav>
        <div class="sidebar-meta mt-auto pt-4 small text-muted">
            <span><?php echo e(APP_NAME); ?></span><span>v<?php echo e(APP_VERSION); ?></span>
        </div>
    </div>
</aside>
