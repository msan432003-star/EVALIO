-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 20, 2026 at 02:11 PM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `onexam`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(2, 'system admin', 'admin@exam.com', '$2y$10$HjQd4JxVXWA6loeUV23pTuDmCTq7SOhE3jrB09lEgGoB1G3jWPZ5K', '2026-09-22 05:53:10');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int DEFAULT NULL,
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_table` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_id` int DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `admin_id`, `action`, `target_table`, `target_id`, `notes`, `created_at`) VALUES
(39, 2, 'approve_examiner', 'examiners', 20, 'hina (hina@gmail.com)', '2026-10-19 21:44:09'),
(40, 2, 'approve_exam', 'exams', 32, '', '2026-10-19 22:29:12'),
(41, 2, 'edit_course', 'courses', 33, 'Renamed course Chemical Thermodynamicsss to Chemical Thermodynamics', '2026-10-20 11:38:00'),
(42, 2, 'approve_examiner', 'examiners', 21, 'khan (khan@gmail.com)', '2026-10-20 12:51:09'),
(43, 2, 'add_course', 'courses', 42, 'Added course: MicroBiology', '2026-10-20 12:53:15'),
(44, 2, 'add_course', 'courses', 43, 'Added course: Zoology', '2026-10-20 12:53:40'),
(45, 2, 'add_course', 'courses', 44, 'Added course: Ecology & Evolutionary Biology', '2026-10-20 12:54:02'),
(46, 2, 'add_course', 'courses', 45, 'Added course: Molecular Biology', '2026-10-20 12:54:29'),
(47, 2, 'add_course', 'courses', 46, 'Added course: Biochemistry', '2026-10-20 12:55:18'),
(48, 2, 'add_course', 'courses', 47, 'Added course: Immunology', '2026-10-20 12:56:07'),
(49, 2, 'add_course', 'courses', 48, 'Added course: Pathology', '2026-10-20 12:56:25'),
(50, 2, 'add_course', 'courses', 49, 'Added course: Forensic Biology', '2026-10-20 12:56:42'),
(51, 2, 'add_course', 'courses', 50, 'Added course: Bioinformatics', '2026-10-20 12:56:59'),
(52, 2, 'approve_exam', 'exams', 33, '', '2026-10-20 16:12:28'),
(53, 2, 'approve_exam', 'exams', 34, '', '2026-10-20 16:16:34'),
(54, 2, 'approve_exam', 'exams', 35, '', '2026-10-20 16:26:22'),
(55, 2, 'approve_exam', 'exams', 36, '', '2026-10-20 16:51:46'),
(56, 2, 'approve_exam', 'exams', 37, '', '2026-10-20 16:55:02'),
(57, 2, 'approve_exam', 'exams', 38, '', '2026-10-20 16:58:21'),
(58, 2, 'approve_exam', 'exams', 39, '', '2026-10-20 17:01:39'),
(59, 2, 'approve_exam', 'exams', 44, '', '2026-10-20 17:31:04'),
(60, 2, 'approve_exam', 'exams', 43, '', '2026-10-20 17:31:07'),
(61, 2, 'approve_exam', 'exams', 42, '', '2026-10-20 17:31:10'),
(62, 2, 'approve_exam', 'exams', 41, '', '2026-10-20 17:31:12'),
(63, 2, 'approve_exam', 'exams', 40, '', '2026-10-20 17:31:15'),
(64, 2, 'approve_examiner', 'examiners', 26, 'Lee tae yoon (lee@gmail.com)', '2026-10-20 18:05:04'),
(65, 2, 'approve_exam', 'exams', 48, '', '2026-10-20 18:17:21'),
(66, 2, 'approve_exam', 'exams', 47, '', '2026-10-20 18:17:26'),
(67, 2, 'approve_exam', 'exams', 46, '', '2026-10-20 18:17:29'),
(68, 2, 'approve_exam', 'exams', 45, '', '2026-10-20 18:17:32');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(5, 'Biology & Life Science'),
(4, 'Chemistry'),
(7, 'Commerce & Management'),
(1, 'Computer Science & IT'),
(6, 'Engineering (Non-IT)'),
(8, 'Languages & Humanities'),
(2, 'Mathematics & Statistics'),
(3, 'Physics');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `reply` text,
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE IF NOT EXISTS `courses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `category_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_name` (`course_name`),
  KEY `fk_course_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_name`, `created_at`, `category_id`) VALUES
(1, 'cloud computing', '2026-09-28 02:59:08', 1),
(2, 'Machine Learning', '2026-09-28 02:59:12', 1),
(3, 'Cyber forensics', '2026-09-28 02:59:15', 1),
(4, 'Software Engineering', '2026-09-28 02:59:19', 1),
(5, 'Web Development', '2026-09-28 02:59:24', 1),
(6, 'Python', '2026-09-28 02:59:30', 1),
(7, 'stack', '2026-09-28 02:59:36', 1),
(8, 'Data Science & Analytics', '2026-09-28 02:59:43', 1),
(9, 'Data structure', '2026-09-28 02:59:49', 1),
(10, 'Artificial Intelligence and Machine Learning', '2026-09-29 06:15:45', 1),
(11, 'DevOps Engineering', '2026-09-29 06:16:03', 1),
(12, 'Database Management Systems', '2026-09-29 06:17:05', 1),
(13, 'Operating Systems (OS)', '2026-09-29 06:17:40', 1),
(14, 'Object-Oriented Programming (OOP)', '2026-09-29 06:18:06', 1),
(16, 'Information Security & Governance', '2026-09-29 06:19:24', 1),
(17, 'System Administration & Maintenance', '2026-09-29 06:19:36', 1),
(18, 'Mobile Application Development', '2026-09-29 06:19:50', 1),
(19, 'Distributed Systems', '2026-09-29 06:20:03', 1),
(20, 'E-Commerce & Digital Marketing Systems', '2026-09-29 06:20:15', 1),
(21, 'Management Information Systems (MIS)', '2026-09-29 06:20:32', 1),
(22, 'Human-Computer Interaction (HCI)', '2026-09-29 06:20:41', 1),
(23, 'Edge Computing', '2026-09-29 06:21:55', 1),
(24, 'Quantum Computing Foundations', '2026-09-29 06:22:04', 1),
(25, 'Parallel and High-Performance Computing (HPC)', '2026-09-29 06:22:17', 1),
(26, 'Blockchain & Smart Contracts', '2026-09-29 06:22:25', 1),
(27, 'Natural Language Processing (NLP)', '2026-09-29 06:22:34', 1),
(28, 'Computer Vision & Image Processing', '2026-09-29 06:22:43', 1),
(29, 'Data Warehousing and Data Mining', '2026-09-29 06:22:52', 1),
(30, 'Game Development & Computer Graphics', '2026-09-29 06:23:05', 1),
(31, 'Geographic Information Systems (GIS)', '2026-09-29 06:23:13', 1),
(32, 'Molecular Modeling & Mechanics', '2026-09-29 06:24:17', 4),
(33, 'Chemical Thermodynamics', '2026-09-29 06:24:27', 4),
(34, 'Chemical Kinetics & Dynamics', '2026-09-29 06:24:40', 4),
(35, 'Statistical Mechanics (Statistical Thermodynamics)', '2026-09-29 06:24:48', 4),
(36, 'Advanced Inorganic Chemistry Theory', '2026-09-29 06:25:02', 4),
(37, 'Organic Reaction Mechanisms', '2026-09-29 06:25:13', 4),
(38, 'Basic Physics', '2026-09-29 06:25:26', 3),
(39, 'chemistrys', '2026-10-12 09:44:23', NULL),
(41, 'Basic Inorganic Chemistry Theory11', '2026-10-13 06:39:11', NULL),
(42, 'MicroBiology', '2026-10-20 07:23:15', NULL),
(43, 'Zoology', '2026-10-20 07:23:40', NULL),
(44, 'Ecology & Evolutionary Biology', '2026-10-20 07:24:02', NULL),
(45, 'Molecular Biology', '2026-10-20 07:24:29', NULL),
(46, 'Biochemistry', '2026-10-20 07:25:18', NULL),
(47, 'Immunology', '2026-10-20 07:26:07', NULL),
(48, 'Pathology', '2026-10-20 07:26:25', NULL),
(49, 'Forensic Biology', '2026-10-20 07:26:42', NULL),
(50, 'Bioinformatics', '2026-10-20 07:26:59', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `course_categories`
--

DROP TABLE IF EXISTS `course_categories`;
CREATE TABLE IF NOT EXISTS `course_categories` (
  `course_id` int NOT NULL,
  `category_id` int NOT NULL,
  PRIMARY KEY (`course_id`,`category_id`),
  KEY `idx_cc_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_categories`
--

INSERT INTO `course_categories` (`course_id`, `category_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(12, 1),
(13, 1),
(14, 1),
(16, 1),
(17, 1),
(18, 1),
(19, 1),
(20, 1),
(21, 1),
(22, 1),
(23, 1),
(24, 1),
(25, 1),
(26, 1),
(27, 1),
(28, 1),
(29, 1),
(30, 1),
(31, 1),
(38, 3),
(32, 4),
(33, 4),
(34, 4),
(35, 4),
(36, 4),
(37, 4),
(39, 4),
(41, 4),
(46, 4),
(42, 5),
(43, 5),
(44, 5),
(45, 5),
(46, 5),
(47, 5),
(48, 5),
(49, 5),
(50, 5);

-- --------------------------------------------------------

--
-- Table structure for table `degrees`
--

DROP TABLE IF EXISTS `degrees`;
CREATE TABLE IF NOT EXISTS `degrees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_degree_cat` (`category_id`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `degrees`
--

INSERT INTO `degrees` (`id`, `name`, `category_id`) VALUES
(3, 'B.Sc Computer Science', 1),
(7, 'B.Sc Information Technology', 1),
(5, 'B.Tech Computer Science', 1),
(1, 'BCA', 1),
(4, 'M.Sc Computer Science', 1),
(8, 'M.Sc Information Technology', 1),
(6, 'M.Tech Computer Science', 1),
(2, 'MCA', 1),
(9, 'B.Sc Mathematics', 2),
(11, 'B.Sc Statistics', 2),
(10, 'M.Sc Mathematics', 2),
(12, 'M.Sc Statistics', 2),
(13, 'B.Sc Physics', 3),
(14, 'M.Sc Physics', 3),
(15, 'B.Sc Chemistry', 4),
(16, 'M.Sc Chemistry', 4),
(19, 'B.Sc Biotechnology', 5),
(17, 'B.Sc Botany', 5),
(18, 'B.Sc Zoology', 5),
(20, 'M.Sc Biotechnology', 5),
(21, 'M.Sc Microbiology', 5),
(23, 'B.Tech Civil', 6),
(24, 'B.Tech Electrical', 6),
(25, 'B.Tech Electronics', 6),
(22, 'B.Tech Mechanical', 6),
(26, 'B.Com', 7),
(28, 'BBA', 7),
(27, 'M.Com', 7),
(29, 'MBA', 7),
(30, 'BA English', 8),
(32, 'BA Malayalam', 8),
(31, 'MA English', 8),
(33, 'MA Malayalam', 8);

-- --------------------------------------------------------

--
-- Table structure for table `examinees`
--

DROP TABLE IF EXISTS `examinees`;
CREATE TABLE IF NOT EXISTS `examinees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','disabled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `uq_examinee_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `examinees`
--

INSERT INTO `examinees` (`id`, `name`, `email`, `password`, `status`, `created_at`, `phone`, `profile_photo`) VALUES
(2, 'Alex Solis', 'alex@gmail.com', '$2y$10$UwvLqZrFuQK0DzrcBwsk0.TOFnlww7HD48exY4VJ8ITFFqZSWIX6i', 'active', '2026-10-20 09:14:22', '9808765431', NULL),
(3, 'Kahoru Makino', 'kahoru@gmail.com', '$2y$10$bQES5esGy8CwuHaBxAWp5.pFwZQoyMGYuKZkixFZQIKN71NZ1im.a', 'active', '2026-10-20 09:15:32', '7800446721', NULL),
(4, 'Hong Mo', 'hong@gmail.com', '$2y$10$wAmW3YwLBE7G2D.sAAvlEurbM7bJ8u8bkqQy03T04UU7yzXSVun4i', 'active', '2026-10-20 09:17:00', '8890667532', NULL),
(5, 'Elena Bosco', 'elena@gmail.com', '$2y$10$FdJwEMe1xy4HRdFbl2R2tefY9ZJNMx8nZd//JU2BdaA1a67D3Kr9C', 'active', '2026-10-20 09:18:02', '9033217890', NULL),
(6, 'Molly Salas', 'molly@gmail.com', '$2y$10$qmsPubM9H.Q5b4AdPAD4EORZqToPwf6Lb02wiPA1ANQDack2QhIqK', 'active', '2026-10-20 09:19:15', '6799330002', NULL),
(7, 'Jed Wheeler', 'jed@gmail.com', '$2y$10$w0FY6zreIe1PM0.S0yollu6Tt7/cR43Jsvf0bHyFE9uQJ0Ysi9kX.', 'active', '2026-10-20 09:20:22', '4509221098', NULL),
(8, 'Ally Thomson', 'ally@gmail.com', '$2y$10$HNVXVJ4/tv6o8BCyljcauuT53k5FGzmk/OkiPfevDx.jbGyI6PFtq', 'active', '2026-10-20 09:21:20', '8192305678', '20c9032d71d117442affb58d2de43f3b.png');

-- --------------------------------------------------------

--
-- Table structure for table `examiners`
--

DROP TABLE IF EXISTS `examiners`;
CREATE TABLE IF NOT EXISTS `examiners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `account_status` enum('active','disabled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `category_id` int DEFAULT NULL,
  `phone` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_examiner_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `examiners`
--

INSERT INTO `examiners` (`id`, `name`, `email`, `password`, `status`, `created_at`, `account_status`, `category_id`, `phone`, `profile_photo`) VALUES
(20, 'hina', 'hina@gmail.com', '$2y$10$YROV4BnzWm61lTszmKe3k.45Y7lJRjpOB9h.YF9cH0nyCxT7GDqkG', 'approved', '2026-10-19 16:13:13', 'active', 1, '9900876541', 'examiner_20_1792426548.jpg'),
(21, 'khan', 'khan@gmail.com', '$2y$10$Dl0DEhJXwnvcJmJ00oUkEe0emQLANzGiYQSDVp/vrVU0OlRNRh3ly', 'approved', '2026-10-20 07:20:25', 'active', 4, '7809654321', 'examiner_21_1792482937.png'),
(22, 'jacob', 'jacob@gmail.com', '$2y$10$DEfEzv/QtLI0AUy4cpXPl.LWEAbDUKE63aN7XeWk0ti2QsCgKhNK2', 'pending', '2026-10-20 07:41:04', 'active', 5, '8879654321', NULL),
(23, 'Akira', 'akira@gmail.com', '$2y$10$z4hrOkp3Wj5jDshPt6UXB.EEdWciySn2fweetAN7vUNMCsixWiZiy', 'pending', '2026-10-20 07:44:20', 'active', 4, '9987654321', NULL),
(24, 'Marty', 'marty@gmail.com', '$2y$10$cEaYvo6W9Uv587CedTKIpuqOyRaWDI3zXfKX./9K9IPQNZ0KhMZSG', 'pending', '2026-10-20 07:50:25', 'active', 1, '9900876543', NULL),
(25, 'Viola', 'viola@gmail.com', '$2y$10$sl1e7aAsV1OWNxwj1tZmieGg5D1b.g8koH0ar.6aGlfC20zUBtxp6', 'pending', '2026-10-20 07:53:21', 'active', 7, '9809876542', NULL),
(26, 'Lee tae yoon', 'lee@gmail.com', '$2y$10$E6wV0NkuLwItprUPSk22TekRSSACa2GbtnjdxVVT4c3sUSvRFlND6', 'approved', '2026-10-20 12:34:32', 'active', 1, '7778225209', 'examiner_26_1792504843.png');

-- --------------------------------------------------------

--
-- Table structure for table `examiner_certificates`
--

DROP TABLE IF EXISTS `examiner_certificates`;
CREATE TABLE IF NOT EXISTS `examiner_certificates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `examiner_id` int NOT NULL,
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `issuer` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cert_examiner` (`examiner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `examiner_certificates`
--

INSERT INTO `examiner_certificates` (`id`, `examiner_id`, `title`, `issuer`, `file_path`, `uploaded_at`) VALUES
(12, 20, 'Degree', 'UN University', 'cert_20_1792426393_6ad6419991875.png', '2026-10-19 16:13:13'),
(13, 21, 'Degree', 'CHEM University', 'cert_21_1792480825_6ad716393265b.pdf', '2026-10-20 07:20:25'),
(14, 22, 'Degree', 'BIO University', 'cert_22_1792482064_6ad71b10e19ee.png', '2026-10-20 07:41:04'),
(15, 23, 'Degree', 'CHEM University', 'cert_23_1792482260_6ad71bd416282.png', '2026-10-20 07:44:20'),
(16, 24, 'DEGREE', 'COM University', 'cert_24_1792482625_6ad71d41df399.png', '2026-10-20 07:50:25'),
(17, 25, 'DEGREE', 'COM University', 'cert_25_1792482801_6ad71df14ec2c.png', '2026-10-20 07:53:21'),
(18, 25, 'COURSE', 'COM University', 'cert_25_1792482801_6ad71df150b42.pdf', '2026-10-20 07:53:21'),
(19, 26, 'Degree', 'COM University', 'cert_26_1792499672_6ad75fd8288d8.jpg', '2026-10-20 12:34:32');

-- --------------------------------------------------------

--
-- Table structure for table `examiner_profiles`
--

DROP TABLE IF EXISTS `examiner_profiles`;
CREATE TABLE IF NOT EXISTS `examiner_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `examiner_id` int NOT NULL,
  `degree_id` int DEFAULT NULL,
  `degree_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialization` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `institution` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `year_passed` smallint NOT NULL,
  `experience_years` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `about` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `designation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_profile_examiner` (`examiner_id`),
  KEY `fk_profile_degree` (`degree_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `examiner_profiles`
--

INSERT INTO `examiner_profiles` (`id`, `examiner_id`, `degree_id`, `degree_name`, `specialization`, `institution`, `year_passed`, `experience_years`, `about`, `created_at`, `designation`, `city`, `linkedin_url`) VALUES
(11, 20, 2, 'MCA', 'CyberSecurity', 'UN University', 2021, 2, 'An enthusiastic and tech-savvy Educator with an MCA degree and 2 years of experience teaching computer science and information technology. Proficient in simplifying complex coding concepts, software development principles, and database management for students. Adept at bridging the gap between theoretical computing and practical, real-world applications to foster a future-ready learning environment.', '2026-10-19 16:13:13', NULL, 'Kochi', NULL),
(12, 21, 16, 'M.Sc Chemistry', '', 'CHEM University', 2018, 6, 'Dynamic chemistry teacher with over 8 years of experience in high school education, specializing in inquiry-based learning and laboratory safety protocols. Improved student test scores by 20% through innovative curriculum development and personalized tutoring strategies. Proficient in using technology for virtual labs and fostering a collaborative classroom environment to improve student engagement', '2026-10-20 07:20:25', NULL, 'NEW YORK', NULL),
(13, 22, 19, 'B.Sc Biotechnology', '', 'Bio University', 2020, 3, 'Dedicated Biology Teacher with over 3  years of experience in high school classrooms. Skilled in developing interactive lab curricula, integrating STEM technology, and boosting student engagement and test scores by 20%', '2026-10-20 07:41:04', NULL, NULL, NULL),
(14, 23, 15, 'B.Sc Chemistry', '', 'CHEM University', 2020, 3, 'Dedicated and passionate Chemistry Teacher with [X] years of experience teaching IGCSE, CBSE, and AP Chemistry. Proven track record of simplifying complex concepts like organic chemistry mechanisms, thermodynamics, and stoichiometry for diverse student groups. Adept at designing safe, engaging laboratory experiments that bridge theoretical knowledge with practical application. Committed to boosting student confidence, examination scores, and analytical skills.', '2026-10-20 07:44:20', NULL, NULL, NULL),
(15, 24, 6, 'M.Tech Computer Science', '', 'COM University', 2020, 2, 'Passionate Computer Science Educator dedicated to fostering computational thinking and digital literacy. Proven track record of teaching core concepts, including object-oriented programming, data structures, and software development methodologies. Adept at breaking down complex syntax and logic into accessible, engaging lessons for diverse learners. Committed to preparing students for the digital economy by cultivating problem-solving skills and project-based teamwork.', '2026-10-20 07:50:25', NULL, NULL, NULL),
(16, 25, 28, 'BBA', '', 'COM University', 2020, 1, 'Dedicated Commerce Educator with a strong background in business studies, accounting, and economics. Proven ability to simplify complex financial and corporate concepts into engaging, practical lessons for students. Adept at fostering critical thinking and analytical skills, preparing students for future academic pursuits and careers in the business world.', '2026-10-20 07:53:21', NULL, NULL, NULL),
(17, 26, 2, 'MCA', '', 'COM University', 2020, 0, 'I am a passionate computer science educator', '2026-10-20 12:34:32', NULL, 'Gwacheon-si, South Korea', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
CREATE TABLE IF NOT EXISTS `exams` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_id` int NOT NULL,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Certification',
  `exam_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hybrid',
  `duration_minutes` int NOT NULL,
  `price` decimal(10,2) DEFAULT '0.00',
  `total_marks` decimal(8,2) NOT NULL DEFAULT '0.00',
  `passing_percentage` decimal(5,2) NOT NULL DEFAULT '50.00',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registration_deadline` datetime DEFAULT NULL,
  `exam_start_datetime` datetime DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `title`, `description`, `image`, `course_id`, `category`, `exam_type`, `duration_minutes`, `price`, `total_marks`, `passing_percentage`, `created_by`, `created_at`, `registration_deadline`, `exam_start_datetime`, `status`, `submitted_at`, `reviewed_at`, `rejection_reason`) VALUES
(31, 'Basic Python', NULL, NULL, 6, 'Mock', 'mcq', 30, 0.00, 10.00, 50.00, 20, '2026-10-19 16:33:02', NULL, NULL, 'approved', '2026-10-19 22:14:44', NULL, NULL),
(32, 'Python with Data Science', 'Python is the dominant programming language for Data Science, chosen by professionals worldwide for its simplicity, readability, and powerful ecosystem of libraries. Instead of building complex mathematical models from scratch, data scientists use Python extensive tools to clean data, perform statistical analysis, build machine learning models, and create interactive visualizations.', 'exam_1792428942_6ad64b8e97747.jpg', 6, 'Certification', 'hybrid', 30, 1000.00, 9.00, 50.00, 20, '2026-10-19 16:55:42', '2026-10-19 22:28:00', '2026-10-19 22:30:00', 'approved', '2026-10-19 22:28:22', '2026-10-19 22:29:12', NULL),
(33, 'Chemistry Thermodynamics Basics', 'In chemistry, thermodynamics (specifically called chemical thermodynamics) is the study of how heat, work, and energy are transferred and transformed during chemical reactions and physical changes of state', 'exam_1792492835_6ad74523c634c.png', 33, 'Certification', 'hybrid', 30, 500.00, 13.00, 50.00, 21, '2026-10-20 10:40:35', '2026-10-20 16:12:00', '2026-10-20 16:13:00', 'approved', '2026-10-20 16:12:13', '2026-10-20 16:12:28', NULL),
(34, 'Organic Chemistry', 'Organic chemistry is the branch of chemistry that studies the structure, properties, composition, reactions, and preparation of carbon-containing compounds', 'exam_1792493088_6ad74620ab805.png', 37, 'Certification', 'hybrid', 30, 3000.00, 18.00, 50.00, 21, '2026-10-20 10:44:48', '2026-10-20 16:17:00', '2026-10-20 16:18:00', 'approved', '2026-10-20 16:16:27', '2026-10-20 16:16:34', NULL),
(35, 'Basic Inorganic Chemistry', 'Inorganic chemistry is the branch of chemistry that focuses on the behavior, structure, properties, and composition of non-carbon-based elements and compounds. It essentially covers the chemistry of everything else in the periodic table outside the domain of organic chemistry.', '', 41, 'Certification', 'essay', 30, 4000.00, 30.00, 50.00, 21, '2026-10-20 10:54:55', '2026-10-20 16:28:00', '2026-10-20 16:29:00', 'approved', '2026-10-20 16:26:06', '2026-10-20 16:26:22', NULL),
(36, 'Machine Learning ', 'Machine learning (ML) is a subset of artificial intelligence (AI) focused on building systems and statistical algorithms that learn from data to recognize patterns and make decisions without explicit, hard-coded instructions. Pioneered by Arthur Samuel in 1959, it essentially shifts programming from writing manual step-by-step rules to letting a computer optimize its own mathematical model through experience and data exposure', 'exam_1792495202_6ad74e6226f98.png', 2, 'Certification', 'mcq', 50, 6000.00, 50.00, 50.00, 20, '2026-10-20 11:20:02', '2027-01-10 16:53:00', '2027-01-10 16:55:00', 'approved', '2026-10-20 16:51:23', '2026-10-20 16:51:46', NULL),
(37, 'Cyber Forensics', 'Cyber forensics (also referred to as computer forensics or digital forensics) is the branch of forensic science that involves the identification, preservation, extraction, analysis, and presentation of digital evidence in a manner that is legally admissible in a court of law.', 'exam_1792495387_6ad74f1b55116.png', 3, 'Certification', 'true_false', 60, 6789.00, 50.00, 50.00, 20, '2026-10-20 11:23:07', '2026-12-20 16:52:00', '2026-12-20 16:58:00', 'approved', '2026-10-20 16:54:56', '2026-10-20 16:55:02', NULL),
(38, 'Data Structure ', 'Data structures are specialized formats for organizing, processing, managing, and storing data in a computer so that it can be accessed and modified efficiently. They provide a structural blueprint for how individual pieces of data relate to one another mathematically and logically.', 'exam_1792495639_6ad75017be908.png', 9, 'Certification', 'mcq', 55, 2000.00, 50.00, 50.00, 20, '2026-10-20 11:27:19', '2027-01-20 16:56:00', '2027-01-20 20:27:00', 'approved', '2026-10-20 16:58:10', '2026-10-20 16:58:21', NULL),
(39, 'Operating System', 'An operating system (OS) is a foundational layer of system software that manages a computer\'s hardware and software resources and provides common services for computer programs. ', 'exam_1792495853_6ad750edafc35.jpg', 13, 'Certification', 'mcq', 50, 7900.00, 50.00, 50.00, 20, '2026-10-20 11:30:53', '2027-01-24 21:03:00', '2027-01-24 22:04:00', 'approved', '2026-10-20 17:01:30', '2026-10-20 17:01:39', NULL),
(40, 'Organic Chemistry', 'Organic chemistry is the branch of chemistry focused on the scientific study of the structure, properties, composition, reactions, and synthesis of carbon-containing compounds and materials. Originally restricted to compounds produced by living organisms, the modern definition encompasses both natural biological molecules and human-made substances like plastics.', 'exam_1792497075_6ad755b3e2387.png', 37, 'Certification', 'mcq', 60, 2500.00, 50.00, 50.00, 21, '2026-10-20 11:51:15', '2027-01-20 17:20:00', '2027-01-20 17:26:00', 'approved', '2026-10-20 17:22:28', '2026-10-20 17:31:15', NULL),
(41, 'Chemistry', 'Organic chemistry is the branch of chemistry focused on the scientific study of the structure, properties, composition, reactions, and synthesis of carbon-containing compounds and materials. Originally restricted to compounds produced by living organisms, the modern definition encompasses both natural biological molecules and human-made substances like plastics.', 'exam_1792497239_6ad7565767b3a.png', 34, 'Certification', 'true_false', 40, 1000.00, 50.00, 50.00, 21, '2026-10-20 11:53:59', '2027-01-30 17:23:00', '2027-01-30 17:29:00', 'approved', '2026-10-20 17:24:34', '2026-10-20 17:31:12', NULL),
(42, 'Chemistry Inorganics', 'Organic chemistry is the branch of chemistry focused on the scientific study of the structure, properties, composition, reactions, and synthesis of carbon-containing compounds and materials. Originally restricted to compounds produced by living organisms, the modern definition encompasses both natural biological molecules and human-made substances like plastics.', 'exam_1792497336_6ad756b89c8e6.png', 33, 'Certification', 'mcq', 30, 4000.00, 100.00, 50.00, 21, '2026-10-20 11:55:36', '2027-02-27 17:25:00', '2027-02-27 17:31:00', 'approved', '2026-10-20 17:26:27', '2026-10-20 17:31:10', NULL),
(43, 'Chemistry Kinetics', 'Chemical kinetics (also known as reaction kinetics) is the branch of physical chemistry that studies the rates of chemical reactions, the factors that influence these speeds, and the sequence of molecular steps (mechanisms) by which reactions occur. ', 'exam_1792497465_6ad757396fca5.png', 34, 'Certification', 'mcq', 30, 6000.00, 50.00, 50.00, 21, '2026-10-20 11:57:45', '2027-03-12 17:27:00', '2027-03-12 17:33:00', 'approved', '2026-10-20 17:28:23', '2026-10-20 17:31:07', NULL),
(44, 'Basic Inorganic Chemistry', 'Inorganic chemistry is the branch of chemistry focused on the structure, properties, composition, and reactions of compounds that lack carbon-hydrogen (C-H) bonds. It essentially covers the behavior of non-living, mineral-based elements spanning the entire periodic table outside the domain of organic chemistry.', 'exam_1792497589_6ad757b54feab.png', 41, 'Certification', 'true_false', 56, 789.00, 100.00, 50.00, 21, '2026-10-20 11:59:49', '2027-02-18 19:29:00', '2027-02-18 22:35:00', 'approved', '2026-10-20 17:30:23', '2026-10-20 17:31:04', NULL),
(45, 'Web Development', 'Web development is the process of building, creating, and maintaining websites and web applications that run over the internet via a web browser. It encompasses everything from designing a simple static page of plain text to engineering complex, interactive web applications like social networks or e-commerce platforms.', 'exam_1792499858_6ad76092ba626.png', 5, 'Certification', 'mcq', 60, 8000.00, 50.00, 50.00, 26, '2026-10-20 12:37:38', '2027-03-20 18:07:00', '2027-03-20 18:12:00', 'approved', '2026-10-20 18:08:13', '2026-10-20 18:17:32', NULL),
(46, 'Data Science and Analytics', 'Data science and data analytics are closely related, highly collaborative disciplines focused on extracting value from data, yet they differ fundamentally in their scope and objectives. Data science is an overarching, multidisciplinary field that builds complex algorithms and machine learning models to forecast future trends. Conversely, data analytics is a specialized discipline within that framework focused on querying, examining, and visualizing existing datasets to solve immediate business problems', 'exam_1792500041_6ad76149a1cfa.png', 8, 'Certification', 'true_false', 30, 5000.00, 50.00, 50.00, 26, '2026-10-20 12:40:41', '2027-02-20 18:10:00', '2027-02-20 18:16:00', 'approved', '2026-10-20 18:11:29', '2026-10-20 18:17:29', NULL),
(47, 'Web Development ', 'Web development is the process of building, creating, and maintaining websites and web applications that run over the internet via a web browser. It encompasses everything from designing a single static page of plain text to engineering massive, highly interactive web applications like social networks, cloud platforms, or e-commerce storefronts.', 'exam_1792500195_6ad761e3b66e3.png', 5, 'Certification', 'true_false', 60, 2000.00, 50.00, 50.00, 26, '2026-10-20 12:43:15', '2027-01-31 18:12:00', '2027-01-31 18:19:00', 'approved', '2026-10-20 18:13:42', '2026-10-20 18:17:26', NULL),
(48, 'Cyber Forensics', 'Cyber forensics (also called computer forensics or digital forensics) is the practice of identifying, preserving, extracting, analyzing, and presenting digital evidence from electronic devices in a manner that ensures it remains legally admissible in a court of law. It blends computer science with legal principles to reconstruct system events, uncover data breaches, track cybercriminals, and recover compromised or deleted information.', 'exam_1792500349_6ad7627d1881c.png', 3, 'Certification', 'true_false', 120, 4567.00, 50.00, 50.00, 26, '2026-10-20 12:45:49', '2027-03-20 18:15:00', '2027-03-20 18:21:00', 'approved', '2026-10-20 18:16:29', '2026-10-20 18:17:21', NULL),
(49, 'Stack', NULL, NULL, 7, 'Mock', 'mcq', 30, 0.00, 1.00, 50.00, 26, '2026-10-20 12:56:09', NULL, NULL, 'approved', '2026-10-20 18:26:52', NULL, NULL),
(51, 'Operating  Systems', NULL, NULL, 13, 'Mock', 'true_false', 30, 0.00, 1.00, 50.00, 26, '2026-10-20 13:00:48', NULL, NULL, 'approved', '2026-10-20 18:32:05', NULL, NULL),
(52, 'DBMS  For Beginner', NULL, NULL, 12, 'Mock', 'true_false', 30, 0.00, 1.00, 50.00, 26, '2026-10-20 13:04:19', NULL, NULL, 'approved', '2026-10-20 18:34:50', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `exam_references`
--

DROP TABLE IF EXISTS `exam_references`;
CREATE TABLE IF NOT EXISTS `exam_references` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `exam_id` int NOT NULL,
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ref_type` enum('PDF','URL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

DROP TABLE IF EXISTS `exam_results`;
CREATE TABLE IF NOT EXISTS `exam_results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `exam_id` int NOT NULL,
  `score` int NOT NULL,
  `total_marks` decimal(6,2) NOT NULL DEFAULT '0.00',
  `marks_obtained` decimal(6,2) NOT NULL DEFAULT '0.00',
  `grading_status` enum('completed','pending_review') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `evaluated_at` datetime DEFAULT NULL,
  `evaluated_by` int DEFAULT NULL,
  `total_questions` int NOT NULL,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_results`
--

INSERT INTO `exam_results` (`id`, `student_id`, `exam_id`, `score`, `total_marks`, `marks_obtained`, `grading_status`, `evaluated_at`, `evaluated_by`, `total_questions`, `submitted_at`) VALUES
(17, 8, 31, 0, 10.00, 0.00, 'completed', NULL, NULL, 1, '2026-10-20 16:02:44'),
(18, 8, 34, 18, 18.00, 18.00, 'completed', '2026-10-20 16:21:19', 21, 7, '2026-10-20 16:20:13'),
(19, 8, 35, 30, 30.00, 30.00, 'completed', '2026-10-20 16:44:32', 21, 6, '2026-10-20 16:29:33'),
(20, 6, 35, 29, 30.00, 29.00, 'completed', '2026-10-20 16:43:48', 21, 6, '2026-10-20 16:37:16'),
(21, 8, 49, 1, 1.00, 1.00, 'completed', NULL, NULL, 1, '2026-10-20 18:36:01'),
(22, 5, 49, 1, 1.00, 1.00, 'completed', NULL, NULL, 1, '2026-10-20 18:38:59'),
(23, 5, 51, 1, 1.00, 1.00, 'completed', NULL, NULL, 1, '2026-10-20 18:39:33'),
(24, 5, 52, 1, 1.00, 1.00, 'completed', NULL, NULL, 1, '2026-10-20 18:40:07');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
CREATE TABLE IF NOT EXISTS `feedback` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `exam_id` int NOT NULL,
  `rating` tinyint NOT NULL,
  `comments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_feedback_student_exam` (`student_id`,`exam_id`),
  KEY `idx_feedback_exam` (`exam_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `student_id`, `exam_id`, `rating`, `comments`, `created_at`, `is_deleted`) VALUES
(2, 8, 31, 4, 'Good Exam', '2026-10-20 09:28:12', 0),
(3, 8, 34, 5, 'Really Good Exam was hard but learned more', '2026-10-20 10:50:36', 0),
(4, 8, 35, 5, 'Really nice exam', '2026-10-20 10:59:49', 0),
(5, 6, 35, 5, 'A very good exam though the questions was kind of hard', '2026-10-20 11:07:54', 0),
(6, 8, 49, 5, 'very nice exam.', '2026-10-20 13:07:01', 0),
(7, 5, 49, 1, 'Bad exam', '2026-10-20 13:45:17', 0),
(8, 5, 51, 1, 'Bad exam.', '2026-10-20 13:09:44', 0),
(9, 5, 52, 3, 'Bad Exam.', '2026-10-20 13:10:22', 0);

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
CREATE TABLE IF NOT EXISTS `questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `question_type` enum('mcq','essay','short_answer','true_false','fill_blank','matching') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mcq',
  `marks` decimal(5,2) NOT NULL DEFAULT '1.00',
  `question_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_a` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `option_b` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `option_c` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `option_d` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `correct_option` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `correct_answer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `options_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `exam_id` (`exam_id`)
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `exam_id`, `question_type`, `marks`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`, `correct_answer`, `options_data`) VALUES
(64, 31, 'mcq', 10.00, 'What is the correct file extension for a Python file', 'python', '.pl', '.py', '.pgg', 'C', 'C', ''),
(65, 32, 'mcq', 1.00, 'gfhjdkls;', 'vcfds', 'vd', 'vcdfvb', 'ytre', 'A', 'A', ''),
(66, 32, 'essay', 5.00, 'dfghjkl', '', '', '', '', '', '', ''),
(67, 32, 'short_answer', 1.00, 'defghj', '', '', '', '', '', 'a', ''),
(68, 32, 'true_false', 1.00, 'sdfghj', '', '', '', '', 'True', 'True', ''),
(69, 32, 'fill_blank', 1.00, 'sdfgh ____ fghjkilo', '', '', '', '', '', 'a', ''),
(70, 33, 'mcq', 1.00, 'gfdgshajk', 'hgf', 'hgtr', 'nbvc', 'ytre', 'A', 'A', ''),
(71, 33, 'essay', 5.00, 'jhgfds', '', '', '', '', '', '', ''),
(72, 33, 'short_answer', 1.00, 'sdfghj', '', '', '', '', '', 'a', ''),
(73, 33, 'true_false', 1.00, 'asdfghj', '', '', '', '', 'True', 'True', ''),
(74, 33, 'fill_blank', 1.00, 'dfg _____ fghjk', '', '', '', '', '', 'a', ''),
(75, 33, 'matching', 4.00, 'hgfd', '', '', '', '', '', '{\"pairs\":[{\"left\":\"a\",\"right\":\"b\"},{\"left\":\"c\",\"right\":\"d\"}]}', '{\"pairs\":[{\"left\":\"a\",\"right\":\"b\"},{\"left\":\"c\",\"right\":\"d\"}]}'),
(76, 34, 'mcq', 1.00, 'nbhgfds', 'gf', 'hgf', 'bvc', 'gfre', 'A', 'A', ''),
(77, 34, 'essay', 5.00, 'nbvcxzs', '', '', '', '', '', '', ''),
(78, 34, 'short_answer', 1.00, 'nbgfdsjhgf', '', '', '', '', '', 'a', ''),
(79, 34, 'true_false', 1.00, 'jhgfdsa', '', '', '', '', 'True', 'True', ''),
(80, 34, 'fill_blank', 1.00, 'gfds_____fghj', '', '', '', '', '', 'a', ''),
(81, 34, 'matching', 4.00, 'bvcxz', '', '', '', '', '', '{\"pairs\":[{\"left\":\"a\",\"right\":\"b\"},{\"left\":\"c\",\"right\":\"d\"}]}', '{\"pairs\":[{\"left\":\"a\",\"right\":\"b\"},{\"left\":\"c\",\"right\":\"d\"}]}'),
(82, 34, 'essay', 5.00, 'sdfghj', '', '', '', '', '', '', ''),
(83, 35, 'essay', 5.00, 'mnbvcfxds', '', '', '', '', '', '', ''),
(84, 35, 'essay', 5.00, 'nbvfds', '', '', '', '', '', '', ''),
(85, 35, 'essay', 5.00, 'hgfds', '', '', '', '', '', '', ''),
(86, 35, 'essay', 5.00, 'kjhgytfrdeswa', '', '', '', '', '', '', ''),
(87, 35, 'essay', 5.00, 'nbvcfxdsz', '', '', '', '', '', '', ''),
(88, 35, 'essay', 5.00, 'nhbgvfdsa', '', '', '', '', '', '', ''),
(89, 36, 'mcq', 1.00, 'cvbnm', 'fghj', 'gi', 'cvbnm', 'dfghyuj', 'A', 'A', ''),
(90, 36, 'mcq', 9.00, 'rtgyhuji', 'cvbn', 'fghj', 'dcfghj', 'vbn', 'A', 'A', ''),
(91, 36, 'mcq', 40.00, 'dfghjkl', 'cvbnm', 'yuio', 'qwer', 'hjk', 'B', 'B', ''),
(92, 37, 'true_false', 1.00, 'hgfdesw', '', '', '', '', 'True', 'True', ''),
(93, 37, 'true_false', 1.00, 'iuytrew', '', '', '', '', 'True', 'True', ''),
(94, 37, 'true_false', 1.00, 'nbvcx', '', '', '', '', 'True', 'True', ''),
(95, 37, 'true_false', 1.00, 'iuytrew', '', '', '', '', 'True', 'True', ''),
(96, 37, 'true_false', 1.00, 'uytrewq', '', '', '', '', 'True', 'True', ''),
(97, 37, 'true_false', 45.00, 'wertyuijk', '', '', '', '', 'True', 'True', ''),
(98, 38, 'mcq', 50.00, 'jhgfdsa', 'trd', 'nbvc', 'dw', 'jhgf', 'A', 'A', ''),
(99, 39, 'mcq', 50.00, 'ertghjk', 'fghj', 'yuio', 'cvbn', 'wer', 'A', 'A', ''),
(100, 40, 'mcq', 50.00, 'vbhjkl', 'ertyuio', 'vbnm,', 'fghjk', 'tyuio', 'A', 'A', ''),
(101, 41, 'true_false', 50.00, 'ertyuiovbnm', '', '', '', '', 'True', 'True', ''),
(102, 42, 'mcq', 100.00, 'dfghjuiop', 'fghjk', 'rtyui', 'bnm,', 'dfghjk', 'A', 'A', ''),
(103, 43, 'mcq', 50.00, 'fghjkl;', 'bnm,', 'tyhujki', 'ertyu', 'fghjk', 'A', 'A', ''),
(104, 44, 'true_false', 100.00, 'dfghjkl', '', '', '', '', 'True', 'True', ''),
(105, 45, 'mcq', 50.00, 'dfghjkl', 'bnm', 'fgh', 'cvbh', 'hjk', 'A', 'A', ''),
(106, 46, 'true_false', 50.00, 'cvbnm,', '', '', '', '', 'True', 'True', ''),
(107, 47, 'true_false', 50.00, 'dfghjkl', '', '', '', '', 'True', 'True', ''),
(108, 48, 'true_false', 50.00, 'xcvbnm,vbnm', '', '', '', '', 'True', 'True', ''),
(109, 49, 'mcq', 1.00, 'bcnxdmsa,l', 'HGFD', 'JHGFDS', 'MNBVC', 'YTREW', 'A', 'A', ''),
(111, 51, 'true_false', 1.00, 'fgtyhuiobn', '', '', '', '', 'True', 'True', ''),
(112, 52, 'true_false', 1.00, 'fghjktyuio', '', '', '', '', 'True', 'True', '');

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
CREATE TABLE IF NOT EXISTS `registrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `exam_id` int NOT NULL,
  `registration_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `payment_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Paid',
  `registration_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `transaction_id` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_exam` (`student_id`,`exam_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `student_id`, `exam_id`, `registration_date`, `payment_status`, `registration_no`, `category`, `payment_method`, `amount_paid`, `transaction_id`) VALUES
(8, 8, 34, '2026-10-20 16:16:51', 'Paid', 'REG2026-0008-0034', 'OBC', 'Net Banking', 3000.00, 'TXN202610201616516416'),
(9, 8, 35, '2026-10-20 16:26:56', 'Paid', 'REG2026-0008-0035', 'General', 'Net Banking', 4000.00, 'TXN202610201626569626'),
(10, 6, 35, '2026-10-20 16:27:15', 'Paid', 'REG2026-0006-0035', 'General', 'Net Banking', 4000.00, 'TXN202610201627155935');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

DROP TABLE IF EXISTS `results`;
CREATE TABLE IF NOT EXISTS `results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `exam_id` int NOT NULL,
  `score` int NOT NULL,
  `total_marks` int NOT NULL,
  `exam_status` enum('Pass','Fail') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `exam_id` (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_answers`
--

DROP TABLE IF EXISTS `student_answers`;
CREATE TABLE IF NOT EXISTS `student_answers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `student_id` int NOT NULL,
  `question_id` int NOT NULL,
  `answer_given` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `is_correct` tinyint(1) DEFAULT NULL,
  `marks_awarded` decimal(5,2) DEFAULT NULL,
  `max_marks` decimal(5,2) NOT NULL DEFAULT '1.00',
  `status` enum('auto_graded','pending_review','manually_graded') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'auto_graded',
  `feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `graded_by` int DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_exam_student` (`exam_id`,`student_id`),
  KEY `idx_question` (`question_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_answers`
--

INSERT INTO `student_answers` (`id`, `exam_id`, `student_id`, `question_id`, `answer_given`, `is_correct`, `marks_awarded`, `max_marks`, `status`, `feedback`, `graded_by`, `graded_at`, `submitted_at`) VALUES
(36, 31, 8, 64, '', 0, 0.00, 10.00, 'auto_graded', '', NULL, NULL, '2026-10-20 16:02:44'),
(37, 34, 8, 76, 'A', 1, 1.00, 1.00, 'manually_graded', '', 21, '2026-10-20 16:21:19', '2026-10-20 16:20:13'),
(38, 34, 8, 77, 'hcnjxmkz,lvchjxkz,', 1, 5.00, 5.00, 'manually_graded', '', 21, '2026-10-20 16:21:19', '2026-10-20 16:20:13'),
(39, 34, 8, 78, 'a', 1, 1.00, 1.00, 'manually_graded', '', 21, '2026-10-20 16:21:19', '2026-10-20 16:20:13'),
(40, 34, 8, 79, 'True', 1, 1.00, 1.00, 'manually_graded', '', 21, '2026-10-20 16:21:19', '2026-10-20 16:20:13'),
(41, 34, 8, 80, 'a', 1, 1.00, 1.00, 'manually_graded', '', 21, '2026-10-20 16:21:19', '2026-10-20 16:20:13'),
(42, 34, 8, 81, '[\"b\",\"d\"]', 1, 4.00, 4.00, 'manually_graded', '', 21, '2026-10-20 16:21:19', '2026-10-20 16:20:13'),
(43, 34, 8, 82, 'jhgfdsahgfds', 1, 5.00, 5.00, 'manually_graded', '', 21, '2026-10-20 16:21:19', '2026-10-20 16:20:13'),
(44, 35, 8, 83, 'gfhdjsk', 1, 5.00, 5.00, 'manually_graded', '', 21, '2026-10-20 16:44:32', '2026-10-20 16:29:32'),
(45, 35, 8, 84, 'hdjskl', 1, 5.00, 5.00, 'manually_graded', '', 21, '2026-10-20 16:44:32', '2026-10-20 16:29:32'),
(46, 35, 8, 85, 'fhdjkslp', 1, 5.00, 5.00, 'manually_graded', '', 21, '2026-10-20 16:44:32', '2026-10-20 16:29:32'),
(47, 35, 8, 86, 'gdhjskl', 1, 5.00, 5.00, 'manually_graded', '', 21, '2026-10-20 16:44:32', '2026-10-20 16:29:32'),
(48, 35, 8, 87, 'fduisop', 1, 5.00, 5.00, 'manually_graded', '', 21, '2026-10-20 16:44:32', '2026-10-20 16:29:33'),
(49, 35, 8, 88, 'cfhnxdmk,sl', 1, 5.00, 5.00, 'manually_graded', '', 21, '2026-10-20 16:44:32', '2026-10-20 16:29:33'),
(50, 35, 6, 83, 'nhbgvfcdxsz', 1, 5.00, 5.00, 'manually_graded', '', 21, '2026-10-20 16:43:47', '2026-10-20 16:37:16'),
(51, 35, 6, 84, 'jhbgvfcdx', 1, 5.00, 5.00, 'manually_graded', '', 21, '2026-10-20 16:43:47', '2026-10-20 16:37:16'),
(52, 35, 6, 85, 'jhbgvfcdxsz', 1, 5.00, 5.00, 'manually_graded', '', 21, '2026-10-20 16:43:47', '2026-10-20 16:37:16'),
(53, 35, 6, 86, 'njhbgvfcdxs', 1, 4.00, 5.00, 'manually_graded', '', 21, '2026-10-20 16:43:48', '2026-10-20 16:37:16'),
(54, 35, 6, 87, 'jnhbgvfcd', 1, 5.00, 5.00, 'manually_graded', '', 21, '2026-10-20 16:43:48', '2026-10-20 16:37:16'),
(55, 35, 6, 88, 'iuygtfrdes', 1, 5.00, 5.00, 'manually_graded', '', 21, '2026-10-20 16:43:48', '2026-10-20 16:37:16'),
(56, 49, 8, 109, 'A', 1, 1.00, 1.00, 'auto_graded', '', NULL, NULL, '2026-10-20 18:36:01'),
(57, 49, 5, 109, 'A', 1, 1.00, 1.00, 'auto_graded', '', NULL, NULL, '2026-10-20 18:38:59'),
(58, 51, 5, 111, 'True', 1, 1.00, 1.00, 'auto_graded', '', NULL, NULL, '2026-10-20 18:39:33'),
(59, 52, 5, 112, 'True', 1, 1.00, 1.00, 'auto_graded', '', NULL, NULL, '2026-10-20 18:40:07');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_course_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_categories`
--
ALTER TABLE `course_categories`
  ADD CONSTRAINT `fk_cc_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cc_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `degrees`
--
ALTER TABLE `degrees`
  ADD CONSTRAINT `fk_degree_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `examiners`
--
ALTER TABLE `examiners`
  ADD CONSTRAINT `fk_examiner_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `examiner_certificates`
--
ALTER TABLE `examiner_certificates`
  ADD CONSTRAINT `fk_cert_examiner` FOREIGN KEY (`examiner_id`) REFERENCES `examiners` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `examiner_profiles`
--
ALTER TABLE `examiner_profiles`
  ADD CONSTRAINT `fk_profile_degree` FOREIGN KEY (`degree_id`) REFERENCES `degrees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_profile_examiner` FOREIGN KEY (`examiner_id`) REFERENCES `examiners` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
