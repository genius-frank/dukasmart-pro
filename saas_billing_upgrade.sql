USE duka_smart;

ALTER TABLE settings
ADD COLUMN plan_code VARCHAR(20) DEFAULT 'starter';

UPDATE settings
SET
    trial_started = CURDATE ()
WHERE
    trial_started IS NULL;

CREATE TABLE
    IF NOT EXISTS subscription_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plan_code VARCHAR(20) NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        payment_reference VARCHAR(80) NOT NULL,
        status ENUM ('pending', 'approved', 'rejected') DEFAULT 'pending',
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_at TIMESTAMP NULL DEFAULT NULL
    );