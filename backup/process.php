<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/backup.php';

require_permission('backup.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('error', 'Invalid request.');
    redirect('/backup/index.php');
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $lock = acquire_backup_operation_lock();
    if (!$lock) {
        set_flash('error', 'Another backup or restore operation is already running.');
        redirect('/backup/index.php');
    }

    $result = create_database_backup_file();
    if ($result['ok']) {
        record_activity_log(
            get_current_user_id(),
            'backup_created',
            'backup',
            'Created database backup ' . $result['filename'] . ' (' . $result['size'] . ' bytes)'
        );
        set_flash('success', 'Database backup created and verified successfully.');
    } else {
        set_flash('error', 'Backup creation failed. No completed backup was added.');
    }

    release_backup_operation_lock($lock);
    redirect('/backup/index.php');
}

if ($action === 'restore') {
    $filename = $_POST['filename'] ?? '';
    $confirmation = trim($_POST['confirmation'] ?? '');
    $acknowledge = $_POST['acknowledge'] ?? '';

    if ($confirmation !== 'RESTORE' || $acknowledge !== 'yes') {
        set_flash('error', 'Restore confirmation was not completed.');
        redirect('/backup/index.php');
    }

    $selected_path = resolve_managed_backup($filename);
    if ($selected_path === null || !validate_database_backup($selected_path)) {
        record_activity_log(get_current_user_id(), 'restore_failed', 'backup', 'Rejected an invalid or unavailable managed backup selection');
        set_flash('error', 'The selected backup is invalid or unavailable.');
        redirect('/backup/index.php');
    }

    $lock = acquire_backup_operation_lock();
    if (!$lock) {
        set_flash('error', 'Another backup or restore operation is already running.');
        redirect('/backup/index.php');
    }

    $user_id = get_current_user_id();
    record_activity_log($user_id, 'restore_initiated', 'backup', 'Restore initiated for managed backup ' . $filename);

    $safety_backup = create_database_backup_file('pre_restore');
    if (!$safety_backup['ok']) {
        record_activity_log($user_id, 'restore_failed', 'backup', 'Restore stopped because the pre-restore safety backup failed');
        release_backup_operation_lock($lock);
        set_flash('error', 'Restore was stopped because the safety backup could not be created. The database was not restored.');
        redirect('/backup/index.php');
    }

    $restore = run_database_restore($selected_path);
    if (!$restore['ok']) {
        record_activity_log(null, 'restore_failed', 'backup', 'Database restore failed; safety backup preserved as ' . $safety_backup['filename']);
        release_backup_operation_lock($lock);
        set_flash('error', 'Database restoration failed. The pre-restore safety backup has been preserved.');
        redirect('/backup/index.php');
    }

    record_activity_log(null, 'restore_completed', 'backup', 'Restored managed backup ' . $filename . '; safety backup ' . $safety_backup['filename']);
    release_backup_operation_lock($lock);

    logout_user();
    set_flash('success', 'Database restored successfully. Please sign in using an account from the restored database.');
    redirect('/login.php');
}

set_flash('error', 'Invalid action.');
redirect('/backup/index.php');
