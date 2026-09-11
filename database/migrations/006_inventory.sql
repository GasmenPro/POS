-- Phase 6: Inventory

USE pos_db;

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

CREATE TABLE IF NOT EXISTS inventory_movements (
    movement_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
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
    KEY idx_movements_type (movement_type),
    KEY idx_movements_created_by (created_by),
    KEY idx_movements_created_at (created_at),
    CONSTRAINT fk_movements_product
        FOREIGN KEY (product_id) REFERENCES products (product_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_movements_user
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (code, name, description) VALUES
    ('inventory.view', 'View Inventory', 'View inventory and movement history'),
    ('inventory.manage', 'Manage Inventory', 'Stock in, stock out, and adjustments')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'Administrator'
  AND p.code IN ('inventory.view', 'inventory.manage')
ON DUPLICATE KEY UPDATE role_id = role_id;
