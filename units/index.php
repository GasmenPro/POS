<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/units.php';

require_permission('units.view');
$search = trim($_GET['q'] ?? '');
$items = get_all_units($search);
$page_title = APP_NAME . ' — Units';
$success = get_flash('success');
$error = get_flash('error');
$can_manage = user_has_permission('units.manage');

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h1 class="h3 mb-1">Units</h1><p class="text-muted mb-0">Manage product units of measure.</p></div>
        <?php if ($can_manage): ?><a href="<?php echo e(BASE_URL); ?>/units/add.php" class="btn btn-primary">Add Unit</a><?php endif; ?>
    </div>
    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-4"><input type="text" name="q" class="form-control" placeholder="Search units..." value="<?php echo e($search); ?>"></div>
        <div class="col-auto"><button type="submit" class="btn btn-outline-secondary">Search</button><?php if ($search !== ''): ?><a href="<?php echo e(BASE_URL); ?>/units/index.php" class="btn btn-link">Clear</a><?php endif; ?></div>
    </form>
    <div class="card"><div class="table-responsive"><table class="table table-striped mb-0">
        <thead><tr><th>Name</th><th>Code</th><th>Description</th><th>Status</th><th>Created</th><?php if ($can_manage): ?><th>Actions</th><?php endif; ?></tr></thead>
        <tbody>
        <?php if (empty($items)): ?><tr><td colspan="<?php echo $can_manage ? 6 : 5; ?>" class="text-muted">No units found.</td></tr>
        <?php else: foreach ($items as $item): ?>
            <tr>
                <td><?php echo e($item['unit_name']); ?></td>
                <td><?php echo e($item['unit_code']); ?></td>
                <td><?php echo e($item['description'] ?? '—'); ?></td>
                <td><span class="badge bg-<?php echo $item['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo e(ucfirst($item['status'])); ?></span></td>
                <td><?php echo e(date('M j, Y', strtotime($item['created_at']))); ?></td>
                <?php if ($can_manage): ?><td>
                    <a href="<?php echo e(BASE_URL); ?>/units/edit.php?id=<?php echo e($item['unit_id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <form method="post" action="<?php echo e(BASE_URL); ?>/units/process.php" class="d-inline"><?php csrf_field(); ?>
                        <input type="hidden" name="action" value="<?php echo $item['status'] === 'active' ? 'deactivate' : 'activate'; ?>">
                        <input type="hidden" name="unit_id" value="<?php echo e($item['unit_id']); ?>">
                        <button type="submit" class="btn btn-sm btn-outline-<?php echo $item['status'] === 'active' ? 'warning' : 'success'; ?>"><?php echo $item['status'] === 'active' ? 'Deactivate' : 'Activate'; ?></button>
                    </form>
                </td><?php endif; ?>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table></div></div>
</main>
<?php require_once BASE_PATH . '/includes/footer.php'; ?>
