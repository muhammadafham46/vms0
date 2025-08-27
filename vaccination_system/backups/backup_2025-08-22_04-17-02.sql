--
-- Table structure for table `appointments`
--
DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `vaccine_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `child_id` (`child_id`),
  KEY `hospital_id` (`hospital_id`),
  KEY `vaccine_id` (`vaccine_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_4` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

--
-- Table structure for table `children`
--
DROP TABLE IF EXISTS `children`;
CREATE TABLE `children` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `birth_weight` decimal(5,2) DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `children_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `children`
--
INSERT INTO `children` VALUES('1', '3', 'Asad', '2023-01-01', 'male', '5.00', 'B+', '2025-08-22 06:33:05');

--
-- Table structure for table `hospital_vaccines`
--
DROP TABLE IF EXISTS `hospital_vaccines`;
CREATE TABLE `hospital_vaccines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hospital_id` int(11) NOT NULL,
  `vaccine_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_hospital_vaccine` (`hospital_id`,`vaccine_id`),
  KEY `vaccine_id` (`vaccine_id`),
  CONSTRAINT `hospital_vaccines_ibfk_1` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hospital_vaccines_ibfk_2` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospital_vaccines`
--

--
-- Table structure for table `hospitals`
--
DROP TABLE IF EXISTS `hospitals`;
CREATE TABLE `hospitals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospitals`
--
INSERT INTO `hospitals` VALUES('1', 'City General Hospital', '123 Main Street, City Center', '011-23456789', 'info@cityhospital.com', 'Dr. Sharma', NULL, NULL, '2025-08-22 04:58:10', 'active');
INSERT INTO `hospitals` VALUES('2', 'Children Health Center', '456 Park Avenue, West City', '011-34567890', 'contact@childrenhealth.com', 'Dr. Patel', NULL, NULL, '2025-08-22 04:58:10', 'active');
INSERT INTO `hospitals` VALUES('3', 'Community Medical Center', '789 Market Road, East City', '011-45678901', 'admin@communitymed.com', 'Dr. Gupta', NULL, NULL, '2025-08-22 04:58:10', 'active');
INSERT INTO `hospitals` VALUES('4', 'Public Health Clinic', '321 Health Street, North City', '011-56789012', 'clinic@publichealth.com', 'Dr. Singh', NULL, NULL, '2025-08-22 04:58:10', 'active');

--
-- Table structure for table `parent_requests`
--
DROP TABLE IF EXISTS `parent_requests`;
CREATE TABLE `parent_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `child_id` int(11) DEFAULT NULL,
  `request_type` enum('account_update','child_info','appointment_change','other') NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `contact_preference` enum('email','phone','any') DEFAULT 'email',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `child_id` (`child_id`),
  CONSTRAINT `parent_requests_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `parent_requests_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parent_requests`
--

--
-- Table structure for table `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_type` enum('admin','parent','hospital') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--
INSERT INTO `users` VALUES('1', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@vaccination.com', 'admin', 'System Administrator', '1234567890', NULL, 'user_1_1755827254.png', '2025-08-22 04:58:10', 'active');
INSERT INTO `users` VALUES('2', 'ziauddin', '$2y$10$hIfIDd8Nfw1URxAZRH6ese0pmebJRoOWGYykRQLN6K/ABnAg60lIS', 'ziauddin@gmail.com', 'hospital', 'Doctor Ziauddin', '3123456789', 'Aptech Site Center', NULL, '2025-08-22 05:22:12', 'active');
INSERT INTO `users` VALUES('3', 'afham', '$2y$10$qBFlRdh5q5N1u49oxT.fWeN8ieIqSNhcjgbtRxxQ6OxQo.TOMR9mq', 'afham2406f@aptechsite.net', 'parent', 'Muhammad Afham', '3123456789', 'SITE', NULL, '2025-08-22 06:12:07', 'active');

--
-- Table structure for table `vaccination_schedule`
--
DROP TABLE IF EXISTS `vaccination_schedule`;
CREATE TABLE `vaccination_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` int(11) NOT NULL,
  `vaccine_id` int(11) NOT NULL,
  `scheduled_date` date NOT NULL,
  `status` enum('pending','completed','missed') DEFAULT 'pending',
  `hospital_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `child_id` (`child_id`),
  KEY `vaccine_id` (`vaccine_id`),
  KEY `hospital_id` (`hospital_id`),
  CONSTRAINT `vaccination_schedule_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vaccination_schedule_ibfk_2` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vaccination_schedule_ibfk_3` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccination_schedule`
--
INSERT INTO `vaccination_schedule` VALUES('1', '1', '1', '2023-01-01', 'pending', NULL, NULL, '2025-08-22 06:33:05');
INSERT INTO `vaccination_schedule` VALUES('2', '1', '2', '2023-01-01', 'pending', NULL, NULL, '2025-08-22 06:33:06');
INSERT INTO `vaccination_schedule` VALUES('3', '1', '3', '2023-01-01', 'pending', NULL, NULL, '2025-08-22 06:33:06');
INSERT INTO `vaccination_schedule` VALUES('4', '1', '4', '2023-07-01', 'pending', NULL, NULL, '2025-08-22 06:33:06');
INSERT INTO `vaccination_schedule` VALUES('5', '1', '7', '2023-10-01', 'pending', NULL, NULL, '2025-08-22 06:33:06');
INSERT INTO `vaccination_schedule` VALUES('6', '1', '8', '2023-10-01', 'pending', NULL, NULL, '2025-08-22 06:33:06');
INSERT INTO `vaccination_schedule` VALUES('7', '1', '5', '2023-11-01', 'pending', NULL, NULL, '2025-08-22 06:33:06');
INSERT INTO `vaccination_schedule` VALUES('8', '1', '6', '2024-03-01', 'pending', NULL, NULL, '2025-08-22 06:33:06');
INSERT INTO `vaccination_schedule` VALUES('9', '1', '9', '2024-05-01', 'pending', NULL, NULL, '2025-08-22 06:33:06');
INSERT INTO `vaccination_schedule` VALUES('10', '1', '10', '2024-05-01', 'pending', NULL, NULL, '2025-08-22 06:33:06');
INSERT INTO `vaccination_schedule` VALUES('11', '1', '11', '2024-05-01', 'pending', NULL, NULL, '2025-08-22 06:33:06');
INSERT INTO `vaccination_schedule` VALUES('12', '1', '12', '2024-05-01', 'pending', NULL, NULL, '2025-08-22 06:33:06');
INSERT INTO `vaccination_schedule` VALUES('13', '1', '13', '2025-01-01', 'pending', '2', '', '2025-08-22 06:33:06');

--
-- Table structure for table `vaccines`
--
DROP TABLE IF EXISTS `vaccines`;
CREATE TABLE `vaccines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `recommended_age_months` int(11) NOT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `status` enum('available','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccines`
--
INSERT INTO `vaccines` VALUES('1', 'BCG', 'Bacillus Calmette-Guérin vaccine for tuberculosis', '0', '0.05ml', 'Serum Institute', 'available', '2025-08-22 04:58:10');
INSERT INTO `vaccines` VALUES('2', 'OPV-0', 'Oral Polio Vaccine at birth', '0', '2 drops', 'Bharat Biotech', 'available', '2025-08-22 04:58:10');
INSERT INTO `vaccines` VALUES('3', 'Hepatitis B-1', 'First dose of Hepatitis B vaccine', '0', '0.5ml', 'Serum Institute', 'available', '2025-08-22 04:58:10');
INSERT INTO `vaccines` VALUES('4', 'OPV-1 & Pentavalent-1', 'First dose of Polio and Pentavalent vaccine', '6', '0.5ml + 2 drops', 'Various', 'available', '2025-08-22 04:58:10');
INSERT INTO `vaccines` VALUES('5', 'OPV-2 & Pentavalent-2', 'Second dose of Polio and Pentavalent vaccine', '10', '0.5ml + 2 drops', 'Various', 'available', '2025-08-22 04:58:10');
INSERT INTO `vaccines` VALUES('6', 'OPV-3 & Pentavalent-3', 'Third dose of Polio and Pentavalent vaccine', '14', '0.5ml + 2 drops', 'Various', 'available', '2025-08-22 04:58:10');
INSERT INTO `vaccines` VALUES('7', 'Measles-1', 'First dose of Measles vaccine', '9', '0.5ml', 'Serum Institute', 'available', '2025-08-22 04:58:10');
INSERT INTO `vaccines` VALUES('8', 'Vitamin A-1', 'First dose of Vitamin A', '9', '1ml', 'Government Supply', 'available', '2025-08-22 04:58:10');
INSERT INTO `vaccines` VALUES('9', 'DPT Booster-1', 'First booster of DPT vaccine', '16', '0.5ml', 'Various', 'available', '2025-08-22 04:58:10');
INSERT INTO `vaccines` VALUES('10', 'Measles-2', 'Second dose of Measles vaccine', '16', '0.5ml', 'Serum Institute', 'available', '2025-08-22 04:58:10');
INSERT INTO `vaccines` VALUES('11', 'OPV Booster', 'Booster dose of Polio vaccine', '16', '2 drops', 'Bharat Biotech', 'available', '2025-08-22 04:58:10');
INSERT INTO `vaccines` VALUES('12', 'Vitamin A-2', 'Second dose of Vitamin A', '16', '1ml', 'Government Supply', 'available', '2025-08-22 04:58:10');
INSERT INTO `vaccines` VALUES('13', 'DPT Booster-2', 'Second booster of DPT vaccine', '24', '0.5ml', 'Various', 'available', '2025-08-22 04:58:10');

