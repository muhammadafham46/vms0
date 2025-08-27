CREATE TABLE IF NOT EXISTS hospital_notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT NOT NULL,
    new_appointment BOOLEAN DEFAULT TRUE,
    appointment_reminder BOOLEAN DEFAULT TRUE,
    appointment_cancellation BOOLEAN DEFAULT TRUE,
    vaccination_reminder BOOLEAN DEFAULT TRUE,
    stock_alert BOOLEAN DEFAULT TRUE,
    stock_threshold INT DEFAULT 10,
    email_notifications BOOLEAN DEFAULT TRUE,
    sms_notifications BOOLEAN DEFAULT FALSE,
    reminder_days_before INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
    UNIQUE KEY unique_hospital (hospital_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
