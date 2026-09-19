-- Sari-Sari Store POS — Complete schema through Phase 18 (release 1.0.0)
-- Database: pos_db

CREATE DATABASE IF NOT EXISTS pos_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pos_db;

-- --------------------------------------------------------
-- roles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- permissions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS permissions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(100) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_permissions_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- role_permissions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_role_permissions (role_id, permission_id),
    KEY idx_role_permissions_permission_id (permission_id),
    CONSTRAINT fk_role_permissions_role
        FOREIGN KEY (role_id) REFERENCES roles (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_role_permissions_permission
        FOREIGN KEY (permission_id) REFERENCES permissions (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- user_roles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_roles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_roles (user_id, role_id),
    KEY idx_user_roles_role_id (role_id),
    CONSTRAINT fk_user_roles_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_user_roles_role
        FOREIGN KEY (role_id) REFERENCES roles (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- activity_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_activity_logs_user_id (user_id),
    KEY idx_activity_logs_module (module),
    KEY idx_activity_logs_created_at (created_at),
    CONSTRAINT fk_activity_logs_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- login_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED DEFAULT NULL,
    login_status ENUM('success', 'failed') NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_logs_user_id (user_id),
    KEY idx_login_logs_status (login_status),
    KEY idx_login_logs_created_at (created_at),
    CONSTRAINT fk_login_logs_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- categories (Phase 4)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    category_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (category_id),
    UNIQUE KEY uq_categories_name (category_name),
    KEY idx_categories_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- brands (Phase 4)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS brands (
    brand_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    brand_name VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (brand_id),
    UNIQUE KEY uq_brands_name (brand_name),
    KEY idx_brands_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- units (Phase 4)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS units (
    unit_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    unit_name VARCHAR(100) NOT NULL,
    unit_code VARCHAR(20) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (unit_id),
    UNIQUE KEY uq_units_code (unit_code),
    KEY idx_units_name (unit_name),
    KEY idx_units_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- suppliers (Phase 9)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    supplier_code VARCHAR(50) NOT NULL,
    supplier_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (supplier_id),
    UNIQUE KEY uq_suppliers_code (supplier_code),
    KEY idx_suppliers_name (supplier_name),
    KEY idx_suppliers_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- products (Phase 5)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    product_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_code VARCHAR(100) NOT NULL,
    barcode VARCHAR(100) DEFAULT NULL,
    product_name VARCHAR(255) NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    brand_id INT UNSIGNED DEFAULT NULL,
    unit_id INT UNSIGNED NOT NULL,
    description TEXT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    selling_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (product_id),
    UNIQUE KEY uq_products_code (product_code),
    UNIQUE KEY uq_products_barcode (barcode),
    KEY idx_products_name (product_name),
    KEY idx_products_status (status),
    KEY idx_products_category_id (category_id),
    KEY idx_products_brand_id (brand_id),
    KEY idx_products_unit_id (unit_id),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories (category_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_products_brand
        FOREIGN KEY (brand_id) REFERENCES brands (brand_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_products_unit
        FOREIGN KEY (unit_id) REFERENCES units (unit_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- inventory (Phase 6)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS inventory (
    inventory_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    quantity DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    reorder_level DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (inventory_id),
    UNIQUE KEY uq_inventory_product (product_id),
    KEY idx_inventory_quantity (quantity),
    CONSTRAINT fk_inventory_product
        FOREIGN KEY (product_id) REFERENCES products (product_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- inventory_movements (Phase 6)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS inventory_movements (
    movement_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED DEFAULT NULL,
    movement_type ENUM('stock_in', 'stock_out', 'adjustment') NOT NULL,
    quantity DECIMAL(12,3) NOT NULL,
    previous_quantity DECIMAL(12,3) NOT NULL,
    new_quantity DECIMAL(12,3) NOT NULL,
    reference_no VARCHAR(100) DEFAULT NULL,
    remarks VARCHAR(255) DEFAULT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (movement_id),
    KEY idx_movements_product_id (product_id),
    KEY idx_movements_supplier_id (supplier_id),
    KEY idx_movements_type (movement_type),
    KEY idx_movements_created_by (created_by),
    KEY idx_movements_created_at (created_at),
    CONSTRAINT fk_movements_product
        FOREIGN KEY (product_id) REFERENCES products (product_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_movements_supplier
        FOREIGN KEY (supplier_id) REFERENCES suppliers (supplier_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_movements_user
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- sales (Phase 7)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS sales (
    sale_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sale_no VARCHAR(50) NOT NULL,
    offline_transaction_id VARCHAR(80) DEFAULT NULL,
    sale_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    change_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (sale_id),
    UNIQUE KEY uq_sales_sale_no (sale_no),
    UNIQUE KEY uq_sales_offline_transaction_id (offline_transaction_id),
    KEY idx_sales_sale_date (sale_date),
    KEY idx_sales_created_by (created_by),
    CONSTRAINT fk_sales_created_by
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- sale_items (Phase 7)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS sale_items (
    sale_item_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sale_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity DECIMAL(12,3) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (sale_item_id),
    KEY idx_sale_items_sale_id (sale_id),
    KEY idx_sale_items_product_id (product_id),
    CONSTRAINT fk_sale_items_sale
        FOREIGN KEY (sale_id) REFERENCES sales (sale_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sale_items_product
        FOREIGN KEY (product_id) REFERENCES products (product_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed data
-- --------------------------------------------------------
INSERT INTO roles (name, description) VALUES
    ('Administrator', 'Full system access'),
    ('Staff', 'Standard staff access')
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT INTO permissions (code, name, description) VALUES
    ('users.view', 'View Users', 'View user accounts'),
    ('users.manage', 'Manage Users', 'Create, update, and deactivate users'),
    ('roles.view', 'View Roles', 'View roles and permissions'),
    ('roles.manage', 'Manage Roles', 'Assign roles and permissions'),
    ('settings.manage', 'Manage Settings', 'Update system settings'),
    ('logs.view', 'View Logs', 'View activity and login logs'),
    ('categories.view', 'View Categories', 'View product categories'),
    ('categories.manage', 'Manage Categories', 'Create and update categories'),
    ('brands.view', 'View Brands', 'View product brands'),
    ('brands.manage', 'Manage Brands', 'Create and update brands'),
    ('units.view', 'View Units', 'View product units'),
    ('units.manage', 'Manage Units', 'Create and update units'),
    ('products.view', 'View Products', 'View product records'),
    ('products.manage', 'Manage Products', 'Create and update products'),
    ('inventory.view', 'View Inventory', 'View inventory and movement history'),
    ('inventory.manage', 'Manage Inventory', 'Stock in, stock out, and adjustments'),
    ('pos.view', 'View POS', 'Access the point-of-sale screen'),
    ('pos.manage', 'Manage POS', 'Add to cart and complete sales'),
    ('sales.view', 'View Sales', 'View completed sales history'),
    ('suppliers.view', 'View Suppliers', 'View supplier records'),
    ('suppliers.manage', 'Manage Suppliers', 'Create and update suppliers'),
    ('backup.manage', 'Manage Backups', 'Create, download, and restore database backups')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'Administrator'
ON DUPLICATE KEY UPDATE role_id = role_id;

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.code IN ('users.view', 'logs.view')
WHERE r.name = 'Staff'
ON DUPLICATE KEY UPDATE role_id = role_id;

INSERT INTO settings (setting_key, setting_value) VALUES
    ('app_name', 'Sari-Sari Store POS'),
    ('timezone', 'Asia/Manila'),
    ('currency', 'PHP')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
