# DukaSmart Pro Deployment

## Current local testing

- Apache: `http://127.0.0.1:8080/duka_smart/`
- Database: MySQL database `duka_smart`
- Sample data: keep cleared before recording or demonstrations

## Production requirements

Use PHP 8.1+ with MySQL/MariaDB and HTTPS. Create a separate production database and a least-privilege database user.

Set these environment variables in the hosting control panel or PHP-FPM/Apache configuration:

- `DUKA_DB_HOST`
- `DUKA_DB_USER`
- `DUKA_DB_PASS`
- `DUKA_DB_NAME`
- `DUKA_SUBSCRIPTION_KEY`

Do not put production secrets in PHP files, SQL files, screenshots, or chat messages.

## Deployment checklist

1. Create the production database and import `schema.sql` once.
2. Upload the PHP files and `icons/` directory.
3. Keep `.htaccess` enabled and confirm `schema.sql` is not downloadable.
4. Configure the environment variables above.
5. Enable HTTPS before creating customer accounts.
6. Change the initial owner password immediately.
7. Replace the payment and support details in `subscription.php`.
8. Configure real email delivery before promising email password recovery.
9. Create one owner account per shop and never share one account between businesses.
10. Back up the production database before launch.

## SaaS billing model

- Every shop receives one 30-day trial.
- Starter: KES 500/month.
- Growth: KES 900/month.
- Pro: KES 1,500/month.
- The shop owner chooses a plan in Billing & Plans.
- Payment activation is manual until a payment provider is connected.
- When ready, connect M-Pesa or another provider to update `settings.subscription_expires` after confirmed payment.

## Temporary public demonstrations

Cloudflare Quick Tunnels are suitable for short demonstrations only. The computer, Apache, MySQL, and tunnel must remain running, and the URL can change. Do not use a Quick Tunnel as the permanent production service.
