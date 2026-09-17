<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/suppliers.php';

require_permission('suppliers.view');

$search = trim($_GET['q'] ?? '');
$status_filter = $_GET['status'] ?? '';
$suppliers = get_all_suppliers($search, $status_filter);
$can_manage = user_has_permission('suppliers.manage');
$page_title = APP_NAME . ' — Suppliers';
$success = get_flash('success');
$error = get_flash('error');

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">Suppliers</h1>
            <p class="text-muted mb-0">Manage supplier master data.</p>
        </div>
        <?php if ($can_manage): ?>
            <a href="<?php echo e(BASE_URL); ?>/suppliers/add.php" class="btn btn-primary">Add Supplier</a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

    <form method="get" class="row g-2 mb-3">
        <div class="col-md-5">
            <input type="text" name="q" class="form-control" placeholder="Search code, name, contact, phone, or email..." value="<?php echo e($search); ?>">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary">Filter</button>
            <?php if ($search !== '' || $status_filter !== ''): ?>
                <a href="<?php echo e(BASE_URL); ?>/suppliers/index.php" class="btn btn-link">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Supplier</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <?php if ($can_manage): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr><td colspan="<?php echo $can_manage ? 7 : 6; ?>" class="text-muted">No suppliers found.</td></tr>
                <?php else: foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td><?php echo e($supplier['supplier_code']); ?></td>
                        <td><?php echo e($supplier['supplier_name']); ?></td>
                        <td><?php echo e($supplier['contact_person'] ?: '—'); ?></td>
                        <td><?php echo e($supplier['phone'] ?: '—'); ?></td>
                        <td><?php echo e($supplier['email'] ?: '—'); ?></td>
                        <td><span class="badge bg-<?php echo $supplier['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo e(ucfirst($supplier['status'])); ?></span></td>
                        <?php if ($can_manage): ?>
                            <td class="text-nowrap">
                                <a href="<?php echo e(BASE_URL); ?>/suppliers/edit.php?id=<?php echo (int) $supplier['supplier_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="post" action="<?php echo e(BASE_URL); ?>/suppliers/process.php" class="d-inline">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="<?php echo $supplier['status'] === 'active' ? 'deactivate' : 'activate'; ?>">
                                    <input type="hidden" name="supplier_id" value="<?php echo (int) $supplier['supplier_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-<?php echo $supplier['status'] === 'active' ? 'warning' : 'success'; ?>">
                                        <?php echo $supplier['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
