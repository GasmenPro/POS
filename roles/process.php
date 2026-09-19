<?php
/**
 * Role management form processor
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/roles.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/roles/index.php');
}

if (!verify_csrf()) {
    set_flash('error', 'Invalid request. Please try again.');
    redirect('/roles/index.php');
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        require_permission('roles.manage');
        handle_create_role();
        break;
    case 'update':
        require_permission('roles.manage');
        handle_update_role();
        break;
    case 'delete':
        require_permission('roles.manage');
        handle_delete_role();
        break;
    default:
        set_flash('error', 'Invalid action.');
        redirect('/roles/index.php');
}

function save_old_input()
{
    $_SESSION['old_input'] = $_POST;
}

function clear_old_input()
{
    unset($_SESSION['old_input']);
}

function get_submitted_permissions()
{
    $permissions = $_POST['permissions'] ?? [];
    if (!is_array($permissions)) {
        return [];
    }
    $ids = [];
    foreach ($permissions as $permission_id) {
        if ((is_int($permission_id) || is_string($permission_id))
            && filter_var($permission_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false) {
            $ids[] = (int) $permission_id;
        }
    }
    return array_values(array_unique($ids));
}

function handle_create_role()
{
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $permission_ids = get_submitted_permissions();

    if ($name === '') {
        save_old_input();
        set_flash('error', 'Role name is required.');
        redirect('/roles/add.php');
    }

    if (role_name_exists($name)) {
        save_old_input();
        set_flash('error', 'Role name already exists.');
        redirect('/roles/add.php');
    }

    $role_id = create_role($name, $description !== '' ? $description : null);
    if (!$role_id) {
        save_old_input();
        set_flash('error', 'Unable to create role.');
        redirect('/roles/add.php');
    }

    if (!set_role_permissions($role_id, $permission_ids)) {
        set_flash('error', 'Role created but permission assignment failed.');
        redirect('/roles/edit.php?id=' . $role_id);
    }

    clear_old_input();
    $perm_names = get_permission_names_by_ids($permission_ids);
    $desc = 'Created role ' . $name;
    if ($perm_names) {
        $desc .= ' with permissions: ' . implode(', ', $perm_names);
    }
    record_activity_log(get_current_user_id(), 'create', 'roles', $desc);

    set_flash('success', 'Role created successfully.');
    redirect('/roles/index.php');
}

function handle_update_role()
{
    $role_id = (int) ($_POST['role_id'] ?? 0);
    $role = get_role_by_id($role_id);

    if (!$role) {
        set_flash('error', 'Role not found.');
        redirect('/roles/index.php');
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $permission_ids = get_submitted_permissions();
    $is_protected = is_protected_role_name($role['name']);

    if ($name === '') {
        save_old_input();
        set_flash('error', 'Role name is required.');
        redirect('/roles/edit.php?id=' . $role_id);
    }

    if ($is_protected && $name !== $role['name']) {
        save_old_input();
        set_flash('error', 'System role names cannot be changed.');
        redirect('/roles/edit.php?id=' . $role_id);
    }

    if (!$is_protected && role_name_exists($name, $role_id)) {
        save_old_input();
        set_flash('error', 'Role name already exists.');
        redirect('/roles/edit.php?id=' . $role_id);
    }

    if ($role['name'] === 'Administrator') {
        $roles_manage_id = null;
        foreach (get_all_permissions() as $permission) {
            if ($permission['code'] === 'roles.manage') {
                $roles_manage_id = (int) $permission['id'];
                break;
            }
        }
        if ($roles_manage_id === null || !in_array($roles_manage_id, $permission_ids, true)) {
            save_old_input();
            set_flash('error', 'The Administrator role must retain Role Management permission.');
            redirect('/roles/edit.php?id=' . $role_id);
        }
    }

    if (!update_role($role_id, $name, $description !== '' ? $description : null)) {
        save_old_input();
        set_flash('error', 'Unable to update role.');
        redirect('/roles/edit.php?id=' . $role_id);
    }

    $old_permission_ids = get_role_permission_ids($role_id);
    if (!set_role_permissions($role_id, $permission_ids)) {
        save_old_input();
        set_flash('error', 'Role updated but permission assignment failed.');
        redirect('/roles/edit.php?id=' . $role_id);
    }

    clear_old_input();

    $changes = [];
    if ($role['name'] !== $name || ($role['description'] ?? '') !== $description) {
        $changes[] = 'details updated';
    }

    $added = array_diff($permission_ids, $old_permission_ids);
    $removed = array_diff($old_permission_ids, $permission_ids);

    if ($added) {
        $changes[] = 'added: ' . implode(', ', get_permission_names_by_ids($added));
    }
    if ($removed) {
        $changes[] = 'removed: ' . implode(', ', get_permission_names_by_ids($removed));
    }

    $description_log = 'Updated role ' . $name;
    if ($changes) {
        $description_log .= ' (' . implode('; ', $changes) . ')';
    }

    record_activity_log(get_current_user_id(), 'update', 'roles', $description_log);

    if (get_current_user_id()) {
        $_SESSION['roles'] = get_user_roles(get_current_user_id());
    }

    set_flash('success', 'Role updated successfully.');
    redirect('/roles/index.php');
}

function handle_delete_role()
{
    $role_id = (int) ($_POST['role_id'] ?? 0);
    $role = get_role_by_id($role_id);

    if (!$role) {
        set_flash('error', 'Role not found.');
        redirect('/roles/index.php');
    }

    if (is_protected_role_name($role['name'])) {
        set_flash('error', 'System roles cannot be deleted.');
        redirect('/roles/index.php');
    }

    if (count_users_with_role($role_id) > 0) {
        set_flash('error', 'Cannot delete a role that is assigned to users.');
        redirect('/roles/index.php');
    }

    if (!delete_role($role_id)) {
        set_flash('error', 'Unable to delete role.');
        redirect('/roles/index.php');
    }

    record_activity_log(get_current_user_id(), 'delete', 'roles', 'Deleted role ' . $role['name']);

    set_flash('success', 'Role deleted successfully.');
    redirect('/roles/index.php');
}
