-- Phase 14: Offline POS synchronization identifier

USE pos_db;

ALTER TABLE sales
    ADD COLUMN offline_transaction_id VARCHAR(80) NULL AFTER sale_no,
    ADD UNIQUE KEY uq_sales_offline_transaction_id (offline_transaction_id);
