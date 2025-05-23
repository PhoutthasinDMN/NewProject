-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 23, 2025 at 04:33 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clinic_onemeds`
--

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `specialization` varchar(100) NOT NULL,
  `education` varchar(200) DEFAULT NULL,
  `address` text,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `bio` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `first_name`, `last_name`, `email`, `phone`, `specialization`, `education`, `address`, `date_of_birth`, `gender`, `emergency_contact`, `bio`, `created_at`, `updated_at`) VALUES
(1, 'Docotor1', 'test', 'user1@example.com', '+85602028881324', 'General Medicine', 'test', 'nonkhor', '1994-03-30', 'Male', '+85602028881324', 'test', '2025-05-23 02:46:43', '2025-05-23 02:46:43'),
(2, 'Docotor2', 'test', 'user1@example1.com', '78888789', 'Psychiatry', 'test', 'nonkhor', '2024-01-25', 'Male', '+85602028881324', 'test', '2025-05-23 03:00:40', '2025-05-23 03:00:40');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` int NOT NULL,
  `patient_id` int NOT NULL,
  `visit_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `diagnosis` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `symptoms` text COLLATE utf8mb4_unicode_ci,
  `treatment` text COLLATE utf8mb4_unicode_ci,
  `prescription` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `doctor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL COMMENT 'น้ำหนัก (กิโลกรัม)',
  `height` decimal(5,2) DEFAULT NULL COMMENT 'ความสูง (เซนติเมตร)',
  `pulse_rate` int DEFAULT NULL COMMENT 'อัตราการเต้นของหัวใจ (ครั้งต่อนาที)',
  `blood_pressure_systolic` int DEFAULT NULL COMMENT 'ความดันโลหิต ค่าบน (mmHg)',
  `blood_pressure_diastolic` int DEFAULT NULL COMMENT 'ความดันโลหิต ค่าล่าง (mmHg)',
  `temperature` decimal(4,1) DEFAULT NULL COMMENT 'อุณหภูมิร่างกาย (องศาเซลเซียส)',
  `bmi` decimal(5,2) DEFAULT NULL COMMENT 'ค่า BMI (คำนวณอัตโนมัติ)',
  `next_appointment` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`id`, `patient_id`, `visit_date`, `diagnosis`, `symptoms`, `treatment`, `prescription`, `notes`, `doctor_name`, `weight`, `height`, `pulse_rate`, `blood_pressure_systolic`, `blood_pressure_diastolic`, `temperature`, `bmi`, `next_appointment`, `created_at`, `updated_at`) VALUES
(5, 1, '2025-05-22 12:19:00', 'fever 3day', 'fever', 'dengue fever', 'test', 'test', 'doctor 1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-22 13:20:00', '2025-05-22 12:21:10', '2025-05-22 12:21:10'),
(6, 3, '2025-05-22 12:44:00', 'fever', 'test', 'test', 'test', 'test', 'doctor 1', 90.00, 178.00, 80, 121, 80, 37.0, 28.41, '2025-05-24 12:44:00', '2025-05-22 12:45:00', '2025-05-22 12:55:12'),
(7, 3, '2025-05-22 12:53:00', 'fever1', 'test', 'test', 'test', 'test', 'doctor 1', 90.00, 178.00, 80, 138, 75, 36.0, 28.41, '2025-05-25 12:54:00', '2025-05-22 12:54:32', '2025-05-22 12:54:32'),
(8, 2, '2025-05-22 13:03:00', 'fever 3day', 'fever 3day', 'fever 3day', 'fever 3day', 'fever 3day', 'doctor 1', 90.00, 178.00, 80, 111, 90, 37.0, 28.41, '2025-05-27 13:05:00', '2025-05-22 13:04:32', '2025-05-22 20:22:40'),
(9, 4, '2025-05-22 22:06:00', 'fever 3day', 'test', 'test', 'test', 'test', 'doctor 1', 58.00, 158.00, 70, 120, 80, 36.0, 23.23, '2025-06-03 02:07:00', '2025-05-22 22:07:17', '2025-05-22 22:07:17');

--
-- Triggers `medical_records`
--
DELIMITER $$
CREATE TRIGGER `calculate_bmi_before_insert` BEFORE INSERT ON `medical_records` FOR EACH ROW BEGIN
    IF NEW.weight IS NOT NULL AND NEW.height IS NOT NULL AND NEW.height > 0 THEN
        SET NEW.bmi = ROUND((NEW.weight / POWER(NEW.height/100, 2)), 2);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `calculate_bmi_before_update` BEFORE UPDATE ON `medical_records` FOR EACH ROW BEGIN
    IF NEW.weight IS NOT NULL AND NEW.height IS NOT NULL AND NEW.height > 0 THEN
        SET NEW.bmi = ROUND((NEW.weight / POWER(NEW.height/100, 2)), 2);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `nurses`
--

CREATE TABLE `nurses` (
  `id` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT 'Staff Nurse',
  `education` varchar(200) DEFAULT NULL,
  `address` text,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `bio` text,
  `certifications` text,
  `salary` decimal(10,2) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','On Leave') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `nurses`
--

INSERT INTO `nurses` (`id`, `first_name`, `last_name`, `email`, `phone`, `license_number`, `department`, `position`, `education`, `address`, `date_of_birth`, `gender`, `emergency_contact`, `bio`, `certifications`, `salary`, `hire_date`, `status`, `created_at`, `updated_at`) VALUES
(6, 'Nurse 1', 'test', 'user1@example2.com', '+85602028881324', NULL, 'Emergency Department', 'Staff Nurse', 'test', 'nonkhor', '1994-01-30', 'Male', '+85602028881324', 'test', 'test', NULL, NULL, 'Active', '2025-05-23 04:22:06', '2025-05-23 04:22:06');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `age` int DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('M','F','O') DEFAULT NULL,
  `address` text,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `marital_status` varchar(20) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `first_name`, `last_name`, `age`, `dob`, `gender`, `address`, `phone`, `email`, `nationality`, `religion`, `marital_status`, `occupation`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_phone`, `created_at`, `updated_at`) VALUES
(1, 'mino', 'dmn', 31, '1994-03-30', 'M', 'nonkhorS', '78888789', 'mino@gmail.com', 'laos', 'lao', 'Married', 'test', '111', 'test', 'test', '2025-05-21 14:58:19', '2025-05-21 15:15:18'),
(2, 'deon', 'dmn', 1, '2024-01-25', 'M', 'nonkhor', '+85602028881324', 'user1@example.com', 'laos', 'lao', 'Single', 'test', 'test', 'test', 'test', '2025-05-21 15:19:08', '2025-05-21 15:19:08'),
(3, 'Phoutthasin', 'DOUANGMANY', 31, '1994-03-30', 'M', 'Nonkhor', '78888789', '', 'laos', 'lao', 'Married', 'test', 'test', 'test', 'test', '2025-05-22 05:42:20', '2025-05-22 05:42:20'),
(4, 'Tunee', 'KBM', 30, '1995-06-06', 'F', 'nonkhor', '77458583', 'user@example.com', 'laos', 'lao', 'Married', 'test', 'test', 'test', 'test', '2025-05-22 15:06:15', '2025-05-22 15:06:15');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `remember_token` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `reset_token`, `reset_token_expires`, `created_at`, `remember_token`, `role`) VALUES
(1, 'admin', 'mino@gmail.com', '$2y$10$k2rzPUtvANWWSuTL5.Ua2.6ENLbkee2dGLeACkphW.BqcrGjT93Bu', NULL, NULL, '2025-05-19 08:26:31', '85d72d833a22122ad3a213965a6935b6', 'admin'),
(2, 'Mino', 'user@example.com', '$2y$10$HTlRWyIuDPTO1NwMm44FReauNKS8LKqDkeB3apuxQw3XNYZ1EXr1e', NULL, NULL, '2025-05-19 15:00:10', '1b999bbbd6e35877ac0b8308d6b572a5', 'user'),
(3, 'user', 'user1@example.com', '$2y$10$DclcsXD92o9RO2g4lCtAuuyeC4ASadCti5lvciwoppRCjKn2.NUjO', NULL, NULL, '2025-05-19 15:41:06', NULL, 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `nurses`
--
ALTER TABLE `nurses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `license_number` (`license_number`),
  ADD KEY `idx_nurses_department` (`department`),
  ADD KEY `idx_nurses_position` (`position`),
  ADD KEY `idx_nurses_status` (`status`),
  ADD KEY `idx_nurses_name` (`first_name`,`last_name`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `nurses`
--
ALTER TABLE `nurses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
