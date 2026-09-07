-- ================================================================
-- DukaSmart Pro — Upgrade Script: Credit Ledger Feature
-- ================================================================
-- Use this ONLY if you already have a working duka_smart database
-- with real products/sales you want to KEEP.
-- This adds the new credit ledger tables WITHOUT deleting anything.
--
-- If you don't mind starting fresh instead, just re-run schema.sql
-- and skip this file entirely.
-- ================================================================

USE duka_smart;

-- 1. New table: customers
CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(30),
  address VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Add customer_id to existing sales table
ALTER TABLE sales ADD COLUMN customer_id INT NULL AFTER cashier_id;
ALTER TABLE sales ADD CONSTRAINT fk_sales_customer
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL;

-- 3. New table: credit_payments
CREATE TABLE credit_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_method ENUM('cash','mpesa') DEFAULT 'cash',
  note VARCHAR(255),
  recorded_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 4. New indexes
CREATE INDEX idx_sales_customer ON sales(customer_id);
CREATE INDEX idx_credit_payments_customer ON credit_payments(customer_id);

-- Done! Your existing products, sales, and users are untouched.
