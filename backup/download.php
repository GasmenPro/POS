<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/backup.php';

require_permission('backup.manage');

$filename = $_GET['file'] ?? '';
$path = resolve_managed_backup($filename);

if ($path === null || !validate_database_backup($path)) {
    http_response_code(404);
    exit('Backup not found.');
}

record_activity_log(get_current_user_id(), 'backup_downloaded', 'backup', 'Downloaded database backup ' . $filename);

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, no-cache, must-revalidate');

readfile($path);
exit;
