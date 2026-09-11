# Database Status

## Overview

| Item | Status |
|------|--------|
| Database name | `pos_db` |
| Engine / charset | InnoDB / utf8mb4 |
| Connection layer | Active (`config/database.php`) |
| Tables | 16 (8 foundation + 3 master data + 1 products + 2 inventory + 2 sales) |

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

### Sales / POS (Phase 7)

| Table | Purpose |
|-------|---------|
| `sales` | Completed sale records |
| `sale_items` | Sale line items with historical unit price |

## sales Table

| Column | Notes |
|--------|-------|
| `sale_id` | Primary key |
| `sale_no` | Unique sale number (e.g. SALE-20260911-0001) |
| `sale_date` | Datetime of sale |
| `subtotal` | Sum of line totals |
| `total_amount` | Total due (same as subtotal in Phase 7) |
| `payment_amount` | Cash tendered |
| `change_amount` | Change given |
| `created_by` | FK → `users.id`, RESTRICT |

## sale_items Table

| Column | Notes |
|--------|-------|
| `sale_item_id` | Primary key |
| `sale_id` | FK → `sales.sale_id`, RESTRICT |
| `product_id` | FK → `products.product_id`, RESTRICT |
| `quantity` | Quantity sold |
| `unit_price` | Price at time of sale (historical) |
| `line_total` | quantity × unit_price |

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
users ──< sales
sales ──< sale_items >── products
```

## Permissions (19 total)

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
| pos.view / pos.manage | ✅ | — |
| sales.view | ✅ | — |

## SQL Files

- `database/database.sql` — full schema including Phase 7
- `database/migrations/007_basic_pos.sql` — Phase 7 migration

## Notes

- Checkout uses a single DB transaction: sale + items + inventory + movements.
- POS stock-outs use `movement_type = stock_out` with `reference_no = sale_no`.
- Receipt printing not yet implemented (Phase 8).
