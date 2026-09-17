-- Phase 13: Backup and restore permission

USE pos_db;

INSERT INTO permissions (code, name, description) VALUES
    ('backup.manage', 'Manage Backups', 'Create, download, and restore database backups')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p ON p.code = 'backup.manage'
WHERE r.name = 'Administrator'
ON DUPLICATE KEY UPDATE role_id = role_id;
