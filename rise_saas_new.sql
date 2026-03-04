-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 27, 2026 at 04:16 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rise_saas_new`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin') NOT NULL DEFAULT 'admin',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `wallet_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `role`, `status`, `wallet_balance`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'superadmin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active', 0.00, '2026-02-27 15:27:16', '2026-02-27 20:46:19'),
(2, 'Najeeb', 'n@gmail.com', '$2y$10$rq6Yoawa45cGGn/KIyBI7.KCWdrKTuSE.CMrNbPrdJFT8W3CnvN9S', 'admin', 'active', 5000.00, '2026-02-27 15:56:45', '2026-02-27 20:06:04'),
(3, 'Haseeb', 'h@gmail.com', '$2y$10$quoQNu22lk49GnO43S5akOblZqyhE4SNj6W9GaDU5dOywhlNxlzPm', 'admin', 'active', 0.00, '2026-02-27 19:33:56', '2026-02-27 19:33:56');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `certificate_id` varchar(100) NOT NULL,
  `issue_date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `student_id`, `certificate_id`, `issue_date`, `created_at`) VALUES
(1, 1, 'RISE-2026-000001', '2026-02-27', '2026-02-27 20:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `program_id`, `course_name`, `created_at`) VALUES
(1, 1, 'Diploma in Computer Application', '2026-02-27 15:27:16'),
(2, 1, 'Diploma in Business Management', '2026-02-27 15:27:16'),
(3, 2, 'Advanced Diploma in IT', '2026-02-27 15:27:16'),
(4, 2, 'Advanced Diploma in Marketing', '2026-02-27 15:27:16'),
(5, 3, 'BCA', '2026-02-27 15:27:16'),
(6, 3, 'BBA', '2026-02-27 15:27:16'),
(7, 4, 'MCA', '2026-02-27 15:27:16'),
(8, 4, 'MBA', '2026-02-27 15:27:16'),
(9, 5, 'Certificate in Web Development', '2026-02-27 15:27:16'),
(10, 5, 'Certificate in Data Entry', '2026-02-27 15:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `marks_obtained` int(11) NOT NULL DEFAULT 0,
  `grade` varchar(10) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `marks`
--

INSERT INTO `marks` (`id`, `student_id`, `subject_id`, `marks_obtained`, `grade`, `created_at`) VALUES
(1, 1, 13, 78, 'A', '2026-02-27 16:05:12'),
(2, 1, 12, 100, 'A', '2026-02-27 16:05:12'),
(3, 1, 14, 67, 'B', '2026-02-27 16:05:12'),
(4, 1, 11, 23, 'Fail', '2026-02-27 16:05:12'),
(5, 1, 15, 56, 'C', '2026-02-27 16:05:12');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `duration` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `program_name`, `duration`, `created_at`) VALUES
(1, 'Diploma', '1 Year', '2026-02-27 15:27:16'),
(2, 'Advanced Diploma', '2 Years', '2026-02-27 15:27:16'),
(3, 'Bachelor Degree', '3 Years', '2026-02-27 15:27:16'),
(4, 'Master Degree', '2 Years', '2026-02-27 15:27:16'),
(5, 'Certificate Course', '6 Months', '2026-02-27 15:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_no` varchar(50) NOT NULL,
  `roll_no` varchar(50) NOT NULL,
  `session_name` varchar(100) NOT NULL,
  `batch` varchar(100) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `dob` date NOT NULL,
  `father_name` varchar(200) NOT NULL,
  `mother_name` varchar(200) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text NOT NULL,
  `aadhaar_number` varchar(12) NOT NULL,
  `aadhaar_upload` varchar(255) DEFAULT NULL,
  `tenth_board_name` varchar(255) NOT NULL,
  `tenth_passing_year` year(4) NOT NULL,
  `tenth_percentage` decimal(5,2) NOT NULL,
  `tenth_marksheet_upload` varchar(255) DEFAULT NULL,
  `twelfth_board_name` varchar(255) NOT NULL,
  `twelfth_passing_year` year(4) NOT NULL,
  `twelfth_percentage` decimal(5,2) NOT NULL,
  `twelfth_marksheet_upload` varchar(255) DEFAULT NULL,
  `ug_university_name` varchar(255) DEFAULT NULL,
  `ug_passing_year` year(4) DEFAULT NULL,
  `ug_percentage` decimal(5,2) DEFAULT NULL,
  `pg_university_name` varchar(255) DEFAULT NULL,
  `pg_passing_year` year(4) DEFAULT NULL,
  `pg_percentage` decimal(5,2) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved') NOT NULL DEFAULT 'Pending',
  `id_card_pdf` varchar(255) DEFAULT NULL,
  `marksheet_pdf` varchar(255) DEFAULT NULL,
  `certificate_pdf` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `admin_id`, `program_id`, `course_id`, `enrollment_no`, `roll_no`, `session_name`, `batch`, `full_name`, `gender`, `dob`, `father_name`, `mother_name`, `mobile`, `email`, `address`, `aadhaar_number`, `aadhaar_upload`, `tenth_board_name`, `tenth_passing_year`, `tenth_percentage`, `tenth_marksheet_upload`, `twelfth_board_name`, `twelfth_passing_year`, `twelfth_percentage`, `twelfth_marksheet_upload`, `ug_university_name`, `ug_passing_year`, `ug_percentage`, `pg_university_name`, `pg_passing_year`, `pg_percentage`, `photo`, `signature`, `status`, `id_card_pdf`, `marksheet_pdf`, `certificate_pdf`, `created_at`, `updated_at`) VALUES
(1, 2, 3, 6, 'RISE202620034', 'R26021934', '2024-25', 'january 2024', 'Najeeb', 'Male', '2026-02-27', 'Alam', 'Ank', '7992292086', 'najeebahmad845401@gmail.com', 'Motihari', '231001605216', 'rise_69a172aa9d9790.49505860.jpg', 'Bihar', '2022', 85.00, 'rise_69a172aa9e6630.36982669.jpg', 'BSEB', '2024', 90.00, 'rise_69a172aa9ec163.38518789.jpg', NULL, NULL, NULL, NULL, NULL, NULL, 'rise_69a172aa9cbc56.97823939.jpg', 'rise_69a172aa9d2af9.43954437.jpg', 'Approved', 'ID_RISE202620034_1772204089.pdf', 'MS_RISE202620034_1772204538.pdf', 'CERT_RISE202620034_1772204564.pdf', '2026-02-27 16:02:10', '2026-02-27 20:32:44');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `total_marks` int(11) NOT NULL DEFAULT 100,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `program_id`, `subject_name`, `total_marks`, `created_at`) VALUES
(1, 1, 'Computer Fundamentals', 100, '2026-02-27 15:27:16'),
(2, 1, 'Programming in C', 100, '2026-02-27 15:27:16'),
(3, 1, 'Database Management', 100, '2026-02-27 15:27:16'),
(4, 1, 'Web Technology', 100, '2026-02-27 15:27:16'),
(5, 1, 'Office Automation', 100, '2026-02-27 15:27:16'),
(6, 2, 'Advanced Programming', 100, '2026-02-27 15:27:16'),
(7, 2, 'Software Engineering', 100, '2026-02-27 15:27:16'),
(8, 2, 'Networking', 100, '2026-02-27 15:27:16'),
(9, 2, 'Operating Systems', 100, '2026-02-27 15:27:16'),
(10, 2, 'Project Management', 100, '2026-02-27 15:27:16'),
(11, 3, 'Mathematics', 100, '2026-02-27 15:27:16'),
(12, 3, 'Data Structures', 100, '2026-02-27 15:27:16'),
(13, 3, 'Computer Architecture', 100, '2026-02-27 15:27:16'),
(14, 3, 'Java Programming', 100, '2026-02-27 15:27:16'),
(15, 3, 'Software Testing', 100, '2026-02-27 15:27:16'),
(16, 4, 'Advanced Database', 100, '2026-02-27 15:27:16'),
(17, 4, 'Cloud Computing', 100, '2026-02-27 15:27:16'),
(18, 4, 'Machine Learning', 100, '2026-02-27 15:27:16'),
(19, 4, 'Research Methodology', 100, '2026-02-27 15:27:16'),
(20, 4, 'Dissertation', 100, '2026-02-27 15:27:16'),
(21, 5, 'HTML & CSS', 100, '2026-02-27 15:27:16'),
(22, 5, 'JavaScript', 100, '2026-02-27 15:27:16'),
(23, 5, 'PHP & MySQL', 100, '2026-02-27 15:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `transaction_type` enum('recharge','approval_fee') NOT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `status` enum('success','failed','pending') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `admin_id`, `amount`, `type`, `transaction_type`, `razorpay_payment_id`, `razorpay_order_id`, `description`, `status`, `created_at`) VALUES
(1, 2, 500.00, 'credit', 'recharge', 'pay_SL8eELDam7V39a', NULL, 'Wallet recharge of ₹500.00', 'success', '2026-02-27 16:04:02'),
(2, 2, 500.00, 'debit', 'approval_fee', NULL, NULL, 'Approval fee for student: Najeeb (RISE202620034)', 'success', '2026-02-27 16:04:22'),
(3, 2, 5000.00, 'credit', 'recharge', 'pay_SLClvNYTTHMNYF', NULL, 'Wallet recharge of ₹5,000.00', 'success', '2026-02-27 20:06:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_id` (`certificate_id`),
  ADD KEY `idx_certificate_id` (`certificate_id`),
  ADD KEY `idx_student_cert` (`student_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_program_id` (`program_id`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_subject` (`student_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `idx_student_marks` (`student_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_program_name` (`program_name`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `enrollment_no` (`enrollment_no`),
  ADD UNIQUE KEY `roll_no` (`roll_no`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_enrollment` (`enrollment_no`),
  ADD KEY `idx_roll` (`roll_no`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_program` (`program_id`),
  ADD KEY `idx_course` (`course_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subject_program` (`program_id`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_wallet` (`admin_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `marks`
--
ALTER TABLE `marks`
  ADD CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `marks_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
