-- Child Vaccination Management System Database
CREATE DATABASE IF NOT EXISTS vaccination_system;
USE vaccination_system;

-- Users table (for admin, parents, hospitals)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    user_type ENUM('admin', 'parent', 'hospital') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Children table
CREATE TABLE children (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    birth_weight DECIMAL(5,2),
    blood_group VARCHAR(5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Vaccines table
CREATE TABLE vaccines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    recommended_age_months INT NOT NULL,
    dosage VARCHAR(50),
    manufacturer VARCHAR(100),
    status ENUM('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hospitals table
CREATE TABLE hospitals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(15),
    email VARCHAR(100),
    contact_person VARCHAR(100),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Vaccination schedule table
CREATE TABLE vaccination_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    child_id INT NOT NULL,
    vaccine_id INT NOT NULL,
    scheduled_date DATE NOT NULL,
    status ENUM('pending', 'completed', 'missed') DEFAULT 'pending',
    hospital_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
    FOREIGN KEY (vaccine_id) REFERENCES vaccines(id) ON DELETE CASCADE,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL
);

-- Appointments/Bookings table
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_id INT NOT NULL,
    child_id INT NOT NULL,
    hospital_id INT NOT NULL,
    vaccine_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
    FOREIGN KEY (vaccine_id) REFERENCES vaccines(id) ON DELETE CASCADE
);

-- Vaccine inventory at hospitals
CREATE TABLE hospital_vaccines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hospital_id INT NOT NULL,
    vaccine_id INT NOT NULL,
    quantity INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
    FOREIGN KEY (vaccine_id) REFERENCES vaccines(id) ON DELETE CASCADE,
    UNIQUE KEY unique_hospital_vaccine (hospital_id, vaccine_id)
);

-- Parent requests table
CREATE TABLE parent_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_id INT NOT NULL,
    child_id INT,
    request_type ENUM('account_update', 'child_info', 'appointment_change', 'other') NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    contact_preference ENUM('email', 'phone', 'any') DEFAULT 'email',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE SET NULL
);

-- Reminder logs table
CREATE TABLE reminder_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    child_id INT NOT NULL,
    parent_email VARCHAR(100) NOT NULL,
    parent_phone VARCHAR(15),
    vaccinations_count INT DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (username, password, email, user_type, full_name, phone) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@vaccination.com', 'admin', 'System Administrator', '1234567890');

-- Insert sample vaccines
INSERT INTO vaccines (name, description, recommended_age_months, dosage, manufacturer) VALUES
('BCG', 'Bacillus Calmette-Guérin vaccine for tuberculosis', 0, '0.05ml', 'Serum Institute'),
('OPV-0', 'Oral Polio Vaccine at birth', 0, '2 drops', 'Bharat Biotech'),
('Hepatitis B-1', 'First dose of Hepatitis B vaccine', 0, '0.5ml', 'Serum Institute'),
('OPV-1 & Pentavalent-1', 'First dose of Polio and Pentavalent vaccine', 6, '0.5ml + 2 drops', 'Various'),
('OPV-2 & Pentavalent-2', 'Second dose of Polio and Pentavalent vaccine', 10, '0.5ml + 2 drops', 'Various'),
('OPV-3 & Pentavalent-3', 'Third dose of Polio and Pentavalent vaccine', 14, '0.5ml + 2 drops', 'Various'),
('Measles-1', 'First dose of Measles vaccine', 9, '0.5ml', 'Serum Institute'),
('Vitamin A-1', 'First dose of Vitamin A', 9, '1ml', 'Government Supply'),
('DPT Booster-1', 'First booster of DPT vaccine', 16, '0.5ml', 'Various'),
('Measles-2', 'Second dose of Measles vaccine', 16, '0.5ml', 'Serum Institute'),
('OPV Booster', 'Booster dose of Polio vaccine', 16, '2 drops', 'Bharat Biotech'),
('Vitamin A-2', 'Second dose of Vitamin A', 16, '1ml', 'Government Supply'),
('DPT Booster-2', 'Second booster of DPT vaccine', 24, '0.5ml', 'Various');

-- Insert sample hospitals
INSERT INTO hospitals (name, address, phone, email, contact_person) VALUES
('City General Hospital', '123 Main Street, City Center', '011-23456789', 'info@cityhospital.com', 'Dr. Sharma'),
('Children Health Center', '456 Park Avenue, West City', '011-34567890', 'contact@childrenhealth.com', 'Dr. Patel'),
('Community Medical Center', '789 Market Road, East City', '011-45678901', 'admin@communitymed.com', 'Dr. Gupta'),
('Public Health Clinic', '321 Health Street, North City', '011-56789012', 'clinic@publichealth.com', 'Dr. Singh');
