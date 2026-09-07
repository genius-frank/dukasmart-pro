# DukaSmart Pro

DukaSmart Pro is a PHP and MySQL shop-management application for sales, inventory, customer credit, expenses, receipts, reports, staff accounts, and subscription plans.

## Current Features

- Point of sale for cash, M-Pesa, and credit sales
- Inventory, low-stock alerts, and bulk product import
- Credit book and payment tracking
- Expenses, daily/monthly/profit reports, and printable receipts
- Receipt sharing through the phone share menu or WhatsApp
- Staff accounts and admin password management
- 30-day trial with Starter, Growth, and Pro plans
- Payment-reference submission for manual subscription activation
- Installable PWA shell with connection status
- Support and privacy pages

## Local Setup

1. Install XAMPP with Apache, PHP, and MySQL.
2. Copy the project into `C:\xampp\htdocs\duka_smart`.
3. Start Apache and MySQL.
4. Import `schema.sql` into MySQL database `duka_smart`.
5. Open `http://127.0.0.1:8080/duka_smart/`.
6. Change the initial owner password immediately.

## Production Notes

Read `DEPLOYMENT.md` before hosting the application. Configure database credentials and the subscription secret through environment variables. Never commit database backups, passwords, API keys, or customer data.

The current release is a single-shop installation. Multi-shop data isolation is required before serving unrelated businesses from one shared deployment.
