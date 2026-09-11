-- Phase 5: Products

USE pos_db;

CREATE TABLE IF NOT EXISTS products (
    product_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_code VARCHAR(100) NOT NULL,
    barcode VARCHAR(100) DEFAULT NULL,
    product_name VARCHAR(255) NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    brand_id INT UNSIGNED DEFAULT NULL,
    unit_id INT UNSIGNED NOT NULL,
    description TEXT DEFAULT NULL,
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

INSERT INTO permissions (code, name, description) VALUES
    ('products.view', 'View Products', 'View product records'),
    ('products.manage', 'Manage Products', 'Create and update products')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'Administrator'
  AND p.code IN ('products.view', 'products.manage')
ON DUPLICATE KEY UPDATE role_id = role_id;
