# Database Status

## Overview

| Item | Status |
|------|--------|
| Database name | `pos_db` |
| Engine / charset | InnoDB / utf8mb4 |
| Connection layer | Active (`config/database.php`) |
| Tables | 14 (8 foundation + 3 master data + 1 products + 2 inventory) |

## Tables

### Foundation (Phase 1)

| Table | Purpose |
|-------|---------|
| `roles` | System roles |
| `permissions` | Permission codes for RBAC |
| `role_permissions` | Maps permissions to roles |
| `users` | User accounts |
| `user_roles` | Maps users to roles |
| `activity_logs` | Activity audit trail |
| `login_logs` | Login audit trail |
| `settings` | System configuration |

### Master Data (Phase 4)

| Table | Purpose |
|-------|---------|
| `categories` | Product categories |
| `brands` | Product brands |
| `units` | Units of measure |

### Products (Phase 5)

| Table | Purpose |
|-------|---------|
| `products` | Product master records |

### Inventory (Phase 6)

| Table | Purpose |
|-------|---------|
| `inventory` | Stock quantity and reorder level per product |
| `inventory_movements` | Audit trail for stock in, stock out, adjustments |

## products Table

| Column | Notes |
|--------|-------|
| `product_id` | Primary key |
| `product_code` | Unique, required |
| `barcode` | Unique when set, optional |
| `product_name` | Required |
| `category_id` | FK → `categories.category_id`, RESTRICT |
| `brand_id` | FK → `brands.brand_id`, nullable, RESTRICT |
| `unit_id` | FK → `units.unit_id`, RESTRICT |
| `description` | Optional |
| `selling_price` | DECIMAL(12,2), default 0.00 |
| `status` | `active`, `inactive` |

## inventory Table

| Column | Notes |
|--------|-------|
| `inventory_id` | Primary key |
| `product_id` | FK → `products.product_id`, UNIQUE, RESTRICT |
| `quantity` | DECIMAL(12,2), default 0.00 |
| `reorder_level` | DECIMAL(12,2), default 0.00 |

## inventory_movements Table

| Column | Notes |
|--------|-------|
| `movement_id` | Primary key |
| `product_id` | FK → `products.product_id`, RESTRICT |
| `movement_type` | `stock_in`, `stock_out`, `adjustment` |
| `quantity` | Amount moved or adjusted to |
| `previous_quantity` | Quantity before change |
| `new_quantity` | Quantity after change |
| `reference_no` | Optional reference (e.g. PO number) |
| `remarks` | Optional notes |
| `created_by` | FK → `users.id`, RESTRICT |
| `created_at` | Timestamp |

## Relationships

```
roles ──< role_permissions >── permissions
users ──< user_roles >── roles
users ──< activity_logs
users ──< login_logs

categories ──< products
brands ──< products (optional)
units ──< products
products ── inventory (1:1)
products ──< inventory_movements
users ──< inventory_movements
```

Product and inventory foreign keys use `ON DELETE RESTRICT` to protect referenced data.

## Permissions (16 total)

| Code | Administrator | Staff |
|------|---------------|-------|
| users.view / users.manage | ✅ | view only |
| roles.view / roles.manage | ✅ | — |
| settings.manage | ✅ | — |
| logs.view | ✅ | ✅ |
| categories.view / categories.manage | ✅ | — |
| brands.view / brands.manage | ✅ | — |
| units.view / units.manage | ✅ | — |
| products.view / products.manage | ✅ | — |
| inventory.view / inventory.manage | ✅ | — |

## SQL Files

- `database/database.sql` — full schema including Phase 6
- `database/migrations/006_inventory.sql` — Phase 6 migration

## Notes

- Products use active/inactive status; no hard deletion.
- Each product has exactly one inventory row (auto-created on product add).
- Stock changes are recorded in `inventory_movements` for audit.
- Sales/POS tables not yet implemented (Phase 7+).
