-- Phase 10: Advanced Products

USE pos_db;

ALTER TABLE products
    ADD COLUMN image VARCHAR(255) NULL AFTER description;
