-- Hospital Subscription Pricing System
-- Create hospital_plans table
CREATE TABLE IF NOT EXISTS hospital_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    bookings_limit VARCHAR(50),
    branches_limit INT,
    staff_accounts_limit INT,
    sms_reminders INT,
    features TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create hospital_subscriptions table
CREATE TABLE IF NOT EXISTS hospital_subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hospital_id INT NOT NULL,
    plan_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES hospital_plans(id) ON DELETE CASCADE
);

-- Create subscription_payments table
CREATE TABLE IF NOT EXISTS subscription_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subscription_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    payment_date DATE,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES hospital_subscriptions(id) ON DELETE CASCADE
);

-- Insert the four pricing plans
INSERT INTO hospital_plans (name, price, duration, bookings_limit, branches_limit, staff_accounts_limit, sms_reminders, features) VALUES
(
    'Basic Plan (Free/Trial)',
    0.00,
    'monthly',
    '10/month',
    1,
    1,
    0,
    'Basic appointment count reports, Limited features for trial period'
),
(
    'Standard Plan',
    5000.00,
    'monthly',
    'Unlimited',
    1,
    3,
    200,
    'Pricing control, 200 SMS reminders/month, Appointment & vaccine reports, Full booking management'
),
(
    'Advanced Plan',
    25000.00,
    '6-months',
    'Unlimited',
    2,
    5,
    1000,
    'Analytics dashboard, 1,000 SMS reminders (6 months), Vaccine usage reports, Revenue insights, Priority support'
),
(
    'Premium Plan',
    50000.00,
    'yearly',
    'Unlimited',
    NULL, -- Unlimited branches
    NULL, -- Unlimited staff
    NULL, -- Unlimited SMS
    'Custom branding (logo & theme), Priority listing in search, Promotions & discount campaigns, Advanced analytics + growth tracking, 24/7 premium support, Unlimited everything'
);

-- Add subscription fields to hospitals table
ALTER TABLE hospitals 
ADD COLUMN current_plan_id INT DEFAULT NULL AFTER status,
ADD COLUMN subscription_status ENUM('active', 'trial', 'expired', 'cancelled') DEFAULT 'trial' AFTER current_plan_id,
ADD COLUMN trial_end_date DATE DEFAULT NULL AFTER subscription_status,
ADD COLUMN max_staff_accounts INT DEFAULT 1 AFTER trial_end_date,
ADD COLUMN max_branches INT DEFAULT 1 AFTER max_staff_accounts,
ADD COLUMN monthly_sms_limit INT DEFAULT 0 AFTER max_branches,
ADD FOREIGN KEY (current_plan_id) REFERENCES hospital_plans(id);

-- Update existing hospitals to have trial status
UPDATE hospitals SET subscription_status = 'trial', trial_end_date = DATE_ADD(CURDATE(), INTERVAL 30 DAY);

-- Create table for tracking hospital usage
CREATE TABLE IF NOT EXISTS hospital_usage_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hospital_id INT NOT NULL,
    month_year VARCHAR(7) NOT NULL, -- Format: YYYY-MM
    total_bookings INT DEFAULT 0,
    total_sms_sent INT DEFAULT 0,
    active_staff_accounts INT DEFAULT 0,
    active_branches INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
    UNIQUE KEY unique_hospital_month (hospital_id, month_year)
);

-- Create table for hospital branches
CREATE TABLE IF NOT EXISTS hospital_branches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hospital_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(15),
    email VARCHAR(100),
    contact_person VARCHAR(100),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
);

-- Add hospital_id foreign key to users table for staff accounts
ALTER TABLE users 
ADD COLUMN hospital_id INT DEFAULT NULL AFTER user_type,
ADD FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL;

-- Update existing hospital users to link them to hospitals
-- This assumes hospital users have user_type = 'hospital' and their username/email matches hospital names
UPDATE users u
JOIN hospitals h ON u.email LIKE CONCAT('%', h.email, '%') OR u.username LIKE CONCAT('%', LOWER(REPLACE(h.name, ' ', '')), '%')
SET u.hospital_id = h.id
WHERE u.user_type = 'hospital';

-- Create index for better performance
CREATE INDEX idx_hospital_subscriptions ON hospital_subscriptions(hospital_id, status);
CREATE INDEX idx_hospital_usage ON hospital_usage_stats(hospital_id, month_year);
CREATE INDEX idx_hospital_branches ON hospital_branches(hospital_id);
CREATE INDEX idx_users_hospital ON users(hospital_id);
