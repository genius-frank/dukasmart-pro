-- ================================================================
-- DukaSmart Pro — Complete Database Schema
-- HOW TO USE: Open phpMyAdmin → SQL tab → paste this → click Go
-- Safe to re-run anytime. Cleans up old tables first.
-- ================================================================
CREATE DATABASE IF NOT EXISTS duka_smart CHARACTER
SET
  utf8mb4 COLLATE utf8mb4_unicode_ci;

USE duka_smart;

-- Clean slate (safe, drops in correct order)
SET
  FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS sale_items;

DROP TABLE IF EXISTS credit_payments;

DROP TABLE IF EXISTS sales;

DROP TABLE IF EXISTS expenses;

DROP TABLE IF EXISTS products;

DROP TABLE IF EXISTS categories;

DROP TABLE IF EXISTS customers;

DROP TABLE IF EXISTS users;

DROP TABLE IF EXISTS settings;

SET
  FOREIGN_KEY_CHECKS = 1;

-- Settings
CREATE TABLE
  settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_name VARCHAR(100) DEFAULT 'My Duka',
    shop_address TEXT,
    shop_phone VARCHAR(30),
    currency VARCHAR(10) DEFAULT 'KES',
    tax_rate DECIMAL(5, 2) DEFAULT 0.00,
    receipt_footer TEXT DEFAULT 'Thank you for shopping with us!'
  );

INSERT INTO
  settings (shop_name, currency)
VALUES
  ('My Duka', 'KES');

-- Users
-- Default login: admin / admin123
-- Change password immediately after first login via Settings
CREATE TABLE
  users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM ('admin', 'cashier') DEFAULT 'cashier',
    is_active TINYINT (1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );

INSERT INTO
  users (username, password, full_name, role)
VALUES
  (
    'admin',
    '$2y$10$qaUMA0ZZgIWaZxOg2pmcb./4n4pu9ij3f37s/h5c3I.4qPQS7EJEm',
    'Shop Owner',
    'admin'
  );

-- Categories
CREATE TABLE
  categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );

INSERT INTO
  categories (name)
VALUES
  ('General'),
  ('Food & Drinks'),
  ('Electronics'),
  ('Clothing'),
  ('Stationery'),
  ('Household');

-- Customers (credit/deni tracking)
CREATE TABLE
  customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30),
    address VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );

-- Products
CREATE TABLE
  products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT 1,
    product_name VARCHAR(150) NOT NULL,
    unit_type ENUM (
      'piece',
      'kg',
      'g',
      'liter',
      'ml',
      'box',
      'dozen',
      'pack'
    ) DEFAULT 'piece',
    buying_price DECIMAL(10, 2) NOT NULL DEFAULT 0,
    selling_price DECIMAL(10, 2) NOT NULL DEFAULT 0,
    quantity DECIMAL(10, 2) NOT NULL DEFAULT 0,
    low_stock_alert INT DEFAULT 5,
    is_active TINYINT (1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
  );

-- Sales header
CREATE TABLE
  sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cashier_id INT,
    customer_id INT NULL,
    payment_method ENUM ('cash', 'mpesa', 'credit') DEFAULT 'cash',
    mpesa_ref VARCHAR(20),
    subtotal DECIMAL(10, 2) DEFAULT 0,
    tax DECIMAL(10, 2) DEFAULT 0,
    total DECIMAL(10, 2) DEFAULT 0,
    profit DECIMAL(10, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cashier_id) REFERENCES users (id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL
  );

-- Sale line items
CREATE TABLE
  sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    quantity_sold DECIMAL(10, 2) NOT NULL,
    buying_price DECIMAL(10, 2) NOT NULL,
    selling_price DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    profit DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales (id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT
  );

-- Credit repayments
CREATE TABLE
  credit_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM ('cash', 'mpesa') DEFAULT 'cash',
    note VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users (id) ON DELETE SET NULL
  );

-- Expenses
CREATE TABLE
  expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(200) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    category VARCHAR(100) DEFAULT 'General',
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recorded_by) REFERENCES users (id) ON DELETE SET NULL
  );

-- Indexes for performance
CREATE INDEX idx_sales_date ON sales (created_at);

CREATE INDEX idx_sales_customer ON sales (customer_id);

CREATE INDEX idx_sale_items ON sale_items (sale_id);

CREATE INDEX idx_products_active ON products (is_active);

CREATE INDEX idx_products_stock ON products (quantity);

CREATE INDEX idx_credit_customer ON credit_payments (customer_id);

ALTER TABLE settings
ADD COLUMN subscription_expires DATE DEFAULT NULL;

ALTER TABLE settings
ADD COLUMN trial_started DATE DEFAULT NULL;

ALTER TABLE settings
ADD COLUMN plan_code VARCHAR(20) DEFAULT 'starter';

CREATE TABLE
  subscription_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_code VARCHAR(20) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_reference VARCHAR(80) NOT NULL,
    status ENUM ('pending', 'approved', 'rejected') DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL
  );

UPDATE settings
SET
  trial_started = CURDATE ()
WHERE
  trial_started IS NULL;