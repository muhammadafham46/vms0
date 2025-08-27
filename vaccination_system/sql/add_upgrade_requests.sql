-- Upgrade Requests System
-- Create upgrade_requests table
CREATE TABLE IF NOT EXISTS upgrade_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hospital_id INT NOT NULL,
    current_plan_id INT NOT NULL,
    requested_plan_id INT NOT NULL,
    contact_person VARCHAR(100) NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    email VARCHAR(100) NOT NULL,
    payment_method VARCHAR(50),
    account_title VARCHAR(100),
    account_number VARCHAR(50),
    receipt_filename VARCHAR(255),
    additional_notes TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
    FOREIGN KEY (current_plan_id) REFERENCES hospital_plans(id),
    FOREIGN KEY (requested_plan_id) REFERENCES hospital_plans(id)
);

-- Add email settings for notifications
INSERT INTO settings (setting_key, setting_value, description) VALUES
('upgrade_request_email', 'afham2406f@aptechsite.net', 'Email address to receive upgrade request notifications'),
('upgrade_request_subject', 'New Hospital Upgrade Request', 'Subject for upgrade request email notifications');

-- Create directory for receipt uploads
-- This will be handled by PHP code
