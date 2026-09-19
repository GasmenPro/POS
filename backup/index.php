<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/backup.php';

require_permission('backup.manage');

$backups = list_database_backups();
$environment = get_backup_environment_status();
$environment_ready = !in_array(false, $environment, true);
$success = get_flash('success');
$error = get_flash('error');
$page_title = APP_NAME . ' — Backup & Restore';

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/includes/sidebar.php';
?>

<main class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">Backup &amp; Restore</h1>
            <p class="text-muted mb-0">Create, download, and restore protected database backups.</p>
        </div>
        <form method="post" action="<?php echo e(BASE_URL); ?>/backup/process.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="create">
            <button type="submit" class="btn btn-primary" <?php echo $environment_ready ? '' : 'disabled'; ?>><i class="bi bi-database-add" aria-hidden="true"></i> Create Backup</button>
        </form>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

    <?php if (!$environment_ready): ?>
        <div class="alert alert-danger">Backup or restore is unavailable. Verify that the XAMPP MySQL utilities are installed and the protected storage directory is writable.</div>
    <?php else: ?>
        <div class="alert alert-success py-2 mb-3">Backup storage and XAMPP database utilities are ready.</div>
    <?php endif; ?>

    <div class="alert alert-warning danger-zone">
        <strong>Restore warning:</strong> Restoring a backup will replace the current database state. A fresh safety backup is created before restoration, and you will be signed out after a successful restore.
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Available Backups</span>
            <span class="badge text-bg-secondary"><?php echo count($backups); ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Created</th>
                        <th>Size</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($backups)): ?>
                    <tr><td colspan="5" class="text-muted">No verified backups are available.</td></tr>
                <?php else: foreach ($backups as $backup): ?>
                    <tr>
                        <td><code><?php echo e($backup['filename']); ?></code></td>
                        <td><?php echo e(date('M j, Y g:i:s A', $backup['created_at'])); ?></td>
                        <td><?php echo e(format_file_size($backup['size'])); ?></td>
                        <td>
                            <span class="badge text-bg-<?php echo $backup['is_safety_backup'] ? 'warning' : 'primary'; ?>">
                                <?php echo $backup['is_safety_backup'] ? 'Pre-restore safety' : 'Manual'; ?>
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo e(BASE_URL); ?>/backup/download.php?file=<?php echo rawurlencode($backup['filename']); ?>"><i class="bi bi-download" aria-hidden="true"></i> Download</a>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#restore-<?php echo e(md5($backup['filename'])); ?>" aria-expanded="false" aria-controls="restore-<?php echo e(md5($backup['filename'])); ?>"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Restore</button>
                        </td>
                    </tr>
                    <tr class="collapse" id="restore-<?php echo e(md5($backup['filename'])); ?>">
                        <td colspan="5" class="bg-light">
                            <form method="post" action="<?php echo e(BASE_URL); ?>/backup/process.php" class="row g-2 align-items-end">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="restore">
                                <input type="hidden" name="filename" value="<?php echo e($backup['filename']); ?>">
                                <div class="col-lg-7">
                                    <label class="form-label" for="confirm-<?php echo e(md5($backup['filename'])); ?>">Type <strong>RESTORE</strong> to confirm restoring this backup.</label>
                                    <input class="form-control" id="confirm-<?php echo e(md5($backup['filename'])); ?>" name="confirmation" required autocomplete="off">
                                </div>
                                <div class="col-lg-auto">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" value="yes" name="acknowledge" id="ack-<?php echo e(md5($backup['filename'])); ?>" required>
                                        <label class="form-check-label" for="ack-<?php echo e(md5($backup['filename'])); ?>">I understand current data will be replaced.</label>
                                    </div>
                                    <button type="submit" class="btn btn-danger">Create Safety Backup &amp; Restore</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
