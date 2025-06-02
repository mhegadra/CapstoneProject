-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 30, 2024 at 04:56 PM
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
-- Database: `immuni_track`
--

-- --------------------------------------------------------

--
-- Table structure for table `barangay`
--

CREATE TABLE `barangay` (
  `barangay_id` int(11) NOT NULL,
  `barangay_name` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangay`
--

INSERT INTO `barangay` (`barangay_id`, `barangay_name`, `user_id`) VALUES
(1, 'Sagpon', 1),
(2, 'Market Site', 2),
(3, 'Binitayan', 3),
(4, 'Tagas', 4),
(5, 'Bañag', 5),
(6, 'Cullat', 7);

-- --------------------------------------------------------

--
-- Table structure for table `children`
--

CREATE TABLE `children` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `barangay_id` int(11) DEFAULT NULL,
  `registration_date` datetime DEFAULT NULL,
  `age_of_registration` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `children`
--

INSERT INTO `children` (`id`, `first_name`, `last_name`, `date_of_birth`, `gender`, `parent_id`, `barangay_id`, `registration_date`, `age_of_registration`) VALUES
(1, 'John', 'Doe', '2024-07-07', 'Male', 1, 1, '2024-10-21 05:12:03', 3),
(2, 'Robert', 'Tan', '2024-03-28', 'Male', 2, 2, '2024-10-26 03:15:53', 7),
(3, 'Laura', 'Gonzales', '2024-06-20', 'Female', 3, 3, '2024-10-26 03:19:30', 4),
(4, 'Andres ', 'Reyes', '2024-02-25', 'Male', 4, 4, '2024-10-26 03:22:48', 8),
(5, 'Miguel ', 'Cruz', '2024-04-20', 'Male', 5, 5, '2024-10-26 03:27:20', 6),
(6, 'Sofia', 'Delos Santos', '2024-05-24', 'Female', 6, 6, '2024-10-30 02:37:25', 5);

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `vaccine_id` int(11) NOT NULL,
  `barangay_id` int(11) NOT NULL,
  `vaccine_name` varchar(255) NOT NULL,
  `stock` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `vaccine_id`, `barangay_id`, `vaccine_name`, `stock`) VALUES
(1, 1, 1, 'Bacille Calmette-Guérin vaccine (BCG)', 0),
(2, 1, 2, 'Bacille Calmette-Guérin vaccine (BCG)', 0),
(3, 1, 3, 'Bacille Calmette-Guérin vaccine (BCG)', 0),
(4, 1, 4, 'Bacille Calmette-Guérin vaccine (BCG)', 0),
(5, 1, 5, 'Bacille Calmette-Guérin vaccine (BCG)', 0),
(6, 2, 3, 'Hepatitis B vaccine (HBV)', 0),
(7, 2, 4, 'Hepatitis B vaccine (HBV)', 0),
(8, 2, 5, 'Hepatitis B vaccine (HBV)', 0),
(9, 3, 1, 'DTwP-Hib-Hep B vaccine', 0),
(10, 3, 2, 'DTwP-Hib-Hep B vaccine', 0),
(11, 3, 3, 'DTwP-Hib-Hep B vaccine', 0),
(12, 3, 4, 'DTwP-Hib-Hep B vaccine', 0),
(13, 3, 5, 'DTwP-Hib-Hep B vaccine', 0),
(14, 4, 1, 'Polio vaccine', 0),
(15, 4, 2, 'Polio vaccine', 0),
(16, 4, 3, 'Polio vaccine', 0),
(17, 4, 4, 'Polio vaccine', 0),
(18, 4, 5, 'Polio vaccine', 0),
(19, 5, 1, 'Pneumococcal conjugate vaccine (PCV)', 0),
(20, 5, 2, 'Pneumococcal conjugate vaccine (PCV)', 0),
(21, 5, 3, 'Pneumococcal conjugate vaccine (PCV)', 0),
(22, 5, 4, 'Pneumococcal conjugate vaccine (PCV)', 0),
(23, 5, 5, 'Pneumococcal conjugate vaccine (PCV)', 0),
(24, 6, 1, 'Measles-Mumps-Rubella vaccine (MMR)', 0),
(25, 6, 2, 'Measles-Mumps-Rubella vaccine (MMR)', 0),
(26, 6, 3, 'Measles-Mumps-Rubella vaccine (MMR)', 0),
(27, 6, 4, 'Measles-Mumps-Rubella vaccine (MMR)', 0),
(28, 6, 5, 'Measles-Mumps-Rubella vaccine (MMR)', 0),
(29, 7, 1, 'Tetanus-Diptheria vaccine (Td)', 0),
(30, 7, 2, 'Tetanus-Diptheria vaccine (Td)', 0),
(31, 7, 3, 'Tetanus-Diptheria vaccine (Td)', 0),
(32, 7, 4, 'Tetanus-Diptheria vaccine (Td)', 0),
(33, 7, 5, 'Tetanus-Diptheria vaccine (Td)', 0),
(34, 8, 1, 'Human Papillomavirus vaccine (HPV)', 0),
(35, 8, 2, 'Human Papillomavirus vaccine (HPV)', 0),
(36, 8, 3, 'Human Papillomavirus vaccine (HPV)', 0),
(37, 8, 4, 'Human Papillomavirus vaccine (HPV)', 0),
(38, 8, 5, 'Human Papillomavirus vaccine (HPV)', 0),
(39, 2, 1, 'Hepatitis B vaccine (HBV)', 0),
(40, 2, 2, 'Hepatitis B vaccine (HBV)', 0),
(41, 1, 6, 'Bacille Calmette-Guérin vaccine (BCG)', 0),
(42, 2, 6, 'Hepatitis B vaccine (HBV)', 0),
(43, 3, 6, 'DTwP-Hib-Hep B vaccine', 0),
(44, 4, 6, 'Polio vaccine', 0),
(45, 5, 6, 'Pneumococcal conjugate vaccine (PCV)', 0),
(46, 6, 6, 'Measles-Mumps-Rubella vaccine (MMR)', 0),
(47, 7, 6, 'Tetanus-Diptheria vaccine (Td)', 0),
(48, 8, 6, 'Human Papillomavirus vaccine (HPV)', 0);

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `id` int(11) NOT NULL,
  `parent_name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `parent_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parents`
--

INSERT INTO `parents` (`id`, `parent_name`, `address`, `phone_number`, `created_at`, `updated_at`, `username`, `password`, `parent_id`) VALUES
(1, 'Joe Doe', 'P4 Sagpon Daraga, Albay', '+639207695353', '2024-10-20 08:22:22', '2024-10-20 08:22:22', NULL, NULL, 0),
(2, 'Isabella Tan', 'Market Site, Daraga Albay', '+639480913510', '2024-10-25 19:15:53', '2024-10-25 19:15:53', NULL, NULL, 0),
(3, 'Lucia Gonzales', 'P4 Binitayan Daraga Albay', '+639207695353', '2024-10-25 19:19:30', '2024-10-25 19:19:30', NULL, NULL, 0),
(4, 'Maria Reyes', 'P7 Tagas, Daraga Albay', '+639515485578', '2024-10-25 19:22:48', '2024-10-25 19:22:48', NULL, NULL, 0),
(5, 'Sofia Cruz', 'Bañag Daraga, Albay', '+639075141488', '2024-10-25 19:27:20', '2024-10-25 19:27:20', NULL, NULL, 0),
(6, 'Shiela Delos Santos ', 'P1 Cullat, Daraga Albay', '+639207695353', '2024-10-29 18:37:25', '2024-10-29 18:37:25', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `usertable`
--

CREATE TABLE `usertable` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `code` mediumint(8) NOT NULL,
  `status` text NOT NULL,
  `initials` varchar(10) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `barangay_id` int(11) DEFAULT NULL,
  `barangay_name` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `last_active` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usertable`
--

INSERT INTO `usertable` (`id`, `email`, `password`, `code`, `status`, `initials`, `role`, `barangay_id`, `barangay_name`, `user_id`, `first_name`, `last_name`, `last_active`) VALUES
(1, 'adramheg@gmail.com', '$2y$10$6W.4axx6FK5WslcDU4jV3.iVut6vpUmLzeidDU/T9nGZf5gzRGQOe', 0, 'active', 'AS', 'user', 1, 'Sagpon', 1, 'Mheg', 'Adra', NULL),
(2, 'rodriguezajhanelamae@gmail.com', '$2y$10$1GEHhYPTjFOjcTzm3bZnoe40WH8FYCVw2rQ.WzSDe.ZcCf1XwODZO', 0, 'active', 'RJ', 'user', 2, 'Market Site', 2, 'Jhanela', 'Rodrigueza', NULL),
(3, 'glennmaurenpayanay@gmail.com', '$2y$10$EWoQwglqhmf0FvG8XNLOSeZr3tfENO8c8pSDgJDUlBQP2/dj8Vqq.', 0, 'active', 'GP', 'user', 3, 'Binitayan', 3, 'Glenn', 'Payanay', NULL),
(4, 'mhegadra5@gmail.com', '$2y$10$ePRM3mncoOSKb8wLmS4cHeGkpO15rdDmS9u8oeCjBuirY2snzbzHm', 0, 'active', 'MH', 'user', 4, 'Tagas', 4, 'Mhegan', 'Dela Cruz', NULL),
(5, 'millaremheg@gmail.com', '$2y$10$VTxfGMBrN1soCrTzCXrGdOHIg29qk07vvk8aYeAsesm441mra7WU.', 0, 'active', 'MP', 'user', 5, 'Bañag', 5, 'Maria', 'Paulete', NULL),
(6, 'admin@gmail.com', '$2y$10$rOjiFMkfabNlNJTLRb2Y8uq3PnZYg7JfIVhpfVStCM0JW2CUdqpYq', 0, 'active', 'AD', 'admin', 6, 'N/A', 6, 'Admin', 'User', NULL),
(7, 'glennmauren@gmail.com', '$2y$10$xBCb4oPEO2gUa8xbpt.0QOFSmIgduKra5ulp6x2/HKYAtlJbYRinC', 0, 'active', 'GM', 'user', 6, '', NULL, 'Glenn', 'Mauren Payanay', '2024-10-30 02:34:35');

-- --------------------------------------------------------

--
-- Table structure for table `user_barangay`
--

CREATE TABLE `user_barangay` (
  `user_id` int(11) NOT NULL,
  `barangay_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_children`
--

CREATE TABLE `user_children` (
  `user_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vaccinations`
--

CREATE TABLE `vaccinations` (
  `id` int(11) NOT NULL,
  `vaccine_name` varchar(255) NOT NULL,
  `vaccination_date` date NOT NULL,
  `barangay_id` int(11) NOT NULL,
  `status` enum('upcoming','missed') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vaccination_records`
--

CREATE TABLE `vaccination_records` (
  `id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `vaccine_name` varchar(255) NOT NULL,
  `vaccination_date` date NOT NULL,
  `administered_by` varchar(255) NOT NULL,
  `age_in_months` int(11) DEFAULT NULL,
  `next_vaccine_name` varchar(255) DEFAULT NULL,
  `next_vaccination_date` date DEFAULT NULL,
  `status` enum('upcoming','missed') DEFAULT 'upcoming'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccination_records`
--

INSERT INTO `vaccination_records` (`id`, `child_id`, `vaccine_name`, `vaccination_date`, `administered_by`, `age_in_months`, `next_vaccine_name`, `next_vaccination_date`, `status`) VALUES
(1, 6, 'Polio vaccine', '2024-10-20', 'Shane Smith', 6, 'Pneumococcal conjugate vaccine (PCV)', '2024-11-30', 'upcoming');

-- --------------------------------------------------------

--
-- Table structure for table `vaccines`
--

CREATE TABLE `vaccines` (
  `id` int(11) NOT NULL,
  `vaccine_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccines`
--

INSERT INTO `vaccines` (`id`, `vaccine_name`) VALUES
(1, 'Bacille Calmette-Guérin vaccine (BCG)'),
(2, 'Hepatitis B vaccine (HBV)'),
(3, 'DTwP-Hib-Hep B vaccine'),
(4, 'Polio vaccine'),
(5, 'Pneumococcal conjugate vaccine (PCV)'),
(6, 'Measles-Mumps-Rubella vaccine (MMR)'),
(7, 'Tetanus-Diptheria vaccine (Td)'),
(8, 'Human Papillomavirus vaccine (HPV)');

-- --------------------------------------------------------

--
-- Table structure for table `vaccine_history`
--

CREATE TABLE `vaccine_history` (
  `id` int(11) NOT NULL,
  `barangay_id` int(11) NOT NULL,
  `vaccine_name` varchar(255) NOT NULL,
  `previous_stock` int(11) NOT NULL,
  `added_stock` int(11) NOT NULL,
  `change_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccine_history`
--

INSERT INTO `vaccine_history` (`id`, `barangay_id`, `vaccine_name`, `previous_stock`, `added_stock`, `change_date`) VALUES
(33, 1, 'Bacille Calmette-Guérin vaccine (BCG)', 0, 50, '2024-10-20 21:13:10'),
(34, 1, 'Hepatitis B vaccine (HBV)', 0, 30, '2024-10-20 21:13:10'),
(35, 1, 'DTwP-Hib-Hep B vaccine', 0, 20, '2024-10-20 21:13:10'),
(36, 1, 'Polio vaccine', 0, 40, '2024-10-20 21:13:10'),
(37, 1, 'Pneumococcal conjugate vaccine (PCV)', 0, 35, '2024-10-20 21:13:10'),
(38, 1, 'Measles-Mumps-Rubella vaccine (MMR)', 0, 45, '2024-10-20 21:13:10'),
(39, 1, 'Tetanus-Diptheria vaccine (Td)', 0, 25, '2024-10-20 21:13:10'),
(40, 1, 'Human Papillomavirus vaccine (HPV)', 0, 15, '2024-10-20 21:13:10'),
(41, 5, 'Bacille Calmette-Guérin vaccine (BCG)', 0, 0, '2024-10-29 18:16:44'),
(42, 5, 'Hepatitis B vaccine (HBV)', 0, 0, '2024-10-29 18:16:44'),
(43, 5, 'DTwP-Hib-Hep B vaccine', 0, 0, '2024-10-29 18:16:44'),
(44, 5, 'Polio vaccine', -1, 10, '2024-10-29 18:16:44'),
(45, 5, 'Pneumococcal conjugate vaccine (PCV)', 0, 0, '2024-10-29 18:16:44'),
(46, 5, 'Measles-Mumps-Rubella vaccine (MMR)', 0, 0, '2024-10-29 18:16:44'),
(47, 5, 'Tetanus-Diptheria vaccine (Td)', 0, 0, '2024-10-29 18:16:44'),
(48, 5, 'Human Papillomavirus vaccine (HPV)', 0, 0, '2024-10-29 18:16:44');

-- --------------------------------------------------------

--
-- Table structure for table `verification_codes`
--

CREATE TABLE `verification_codes` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `code` varchar(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verification_codes`
--

INSERT INTO `verification_codes` (`id`, `email`, `code`, `created_at`, `expires_at`) VALUES
(1, 'adramheg@gmail.com', '571599', '2024-10-21 07:36:42', '2024-10-21 01:46:42'),
(2, 'adramheg@gmail.com', '147045', '2024-10-21 07:40:17', '2024-10-21 01:50:17'),
(3, 'adramheg@gmail.com', '766810', '2024-10-21 07:43:14', '2024-10-21 01:53:14'),
(4, 'adramheg@gmail.com', '384771', '2024-10-21 07:44:30', '2024-10-21 01:54:30'),
(5, 'adramheg@gmail.com', '568605', '2024-10-21 07:54:27', '2024-10-21 02:04:27'),
(6, 'millaremheg@gmail.com', '530231', '2024-10-21 07:57:00', '2024-10-21 02:07:00'),
(7, 'millaremheg@gmail.com', '503836', '2024-10-21 07:58:15', '2024-10-21 08:08:15'),
(8, 'adramheg@gmail.com', '909573', '2024-10-21 08:04:09', '2024-10-21 02:14:09'),
(9, 'adramheg@gmail.com', '738376', '2024-10-21 08:06:37', '2024-10-21 02:16:37'),
(10, 'adramheg@gmail.com', '711935', '2024-10-21 08:08:14', '2024-10-21 02:18:14'),
(11, 'adramheg@gmail.com', '522924', '2024-10-21 08:08:38', '2024-10-21 08:18:38'),
(12, 'adramheg@gmail.com', '634550', '2024-10-21 08:12:51', '2024-10-21 02:17:51'),
(13, 'adramheg@gmail.com', '811409', '2024-10-21 08:14:32', '2024-10-21 02:19:32'),
(14, 'adramheg@gmail.com', '109516', '2024-10-21 08:20:36', '2024-10-21 08:30:36'),
(15, 'glennmaurenpayanay@gmail.com', '116028', '2024-10-21 08:22:38', '2024-10-21 02:27:38'),
(16, 'glennmaurenpayanay@gmail.com', '659133', '2024-10-21 08:25:58', '2024-10-21 08:35:58'),
(17, 'adramheg@gmail.com', '342488', '2024-10-21 08:27:00', '2024-10-21 02:32:00'),
(18, 'adramheg@gmail.com', '718637', '2024-10-21 08:30:08', '2024-10-21 02:35:08'),
(19, 'adramheg@gmail.com', '831603', '2024-10-21 08:30:52', '2024-10-21 08:40:52'),
(20, 'adramheg@gmail.com', '478266', '2024-10-21 08:31:48', '2024-10-21 02:36:48'),
(21, 'adramheg@gmail.com', '748992', '2024-10-21 08:41:40', '2024-10-21 02:46:40'),
(22, 'adramheg@gmail.com', '858591', '2024-10-21 08:44:58', '2024-10-21 02:49:58'),
(23, 'adramheg@gmail.com', '439325', '2024-10-21 08:48:48', '2024-10-21 08:58:48'),
(24, 'adramheg@gmail.com', '942635', '2024-10-21 08:50:12', '2024-10-21 02:55:12'),
(25, 'adramheg@gmail.com', '731924', '2024-10-21 09:01:03', '2024-10-21 03:06:03'),
(26, 'adramheg@gmail.com', '315818', '2024-10-21 09:06:17', '2024-10-21 09:16:17'),
(27, 'adramheg@gmail.com', '691533', '2024-10-21 09:09:37', '2024-10-21 03:14:37'),
(28, 'adramheg@gmail.com', '980864', '2024-10-21 09:10:24', '2024-10-21 09:20:24'),
(29, 'adramheg@gmail.com', '814175', '2024-10-21 09:21:29', '2024-10-21 03:26:29'),
(30, 'adramheg@gmail.com', '615879', '2024-10-21 09:22:07', '2024-10-21 09:32:07'),
(31, 'adramheg@gmail.com', '640686', '2024-10-21 09:28:18', '2024-10-21 03:33:18'),
(32, 'adramheg@gmail.com', '951007', '2024-10-21 09:44:55', '2024-10-21 03:49:55'),
(33, 'adramheg@gmail.com', '130895', '2024-10-21 09:45:14', '2024-10-21 09:55:14'),
(34, 'glennmauren@gmail.com', '706178', '2024-10-21 14:54:02', '2024-10-21 08:59:02'),
(35, 'glennmauren@gmail.com', '354451', '2024-10-21 14:55:30', '2024-10-21 15:05:30'),
(36, 'glennmauren@gmail.com', '350376', '2024-10-21 15:15:54', '2024-10-21 09:20:54'),
(37, 'glennmauren@gmail.com', '584602', '2024-10-21 15:16:05', '2024-10-21 15:26:05'),
(38, 'mhegadra5@gmail.com', '313544', '2024-10-22 06:26:09', '2024-10-22 00:31:09'),
(39, 'mhegadra5@gmail.com', '587094', '2024-10-22 06:26:48', '2024-10-22 06:36:48'),
(40, 'mhegadra5@gmail.com', '349590', '2024-10-22 06:34:04', '2024-10-22 00:39:04'),
(41, 'millaremheg@gmail.com', '360286', '2024-10-22 06:36:00', '2024-10-22 00:41:00'),
(42, 'millaremheg@gmail.com', '693236', '2024-10-22 06:36:20', '2024-10-22 06:46:20'),
(43, 'adramheg@gmail.com', '205632', '2024-10-22 06:47:32', '2024-10-22 00:52:32'),
(44, 'adramheg@gmail.com', '567387', '2024-10-22 06:48:00', '2024-10-22 06:58:00'),
(45, 'adramheg@gmail.com', '104088', '2024-10-22 06:48:29', '2024-10-22 06:58:29'),
(46, 'mhegadra5@gmail.com', '849053', '2024-10-22 08:32:24', '2024-10-22 02:37:24'),
(47, 'mhegadra5@gmail.com', '633366', '2024-10-22 08:33:27', '2024-10-22 08:43:27'),
(48, 'mhegadra5@gmail.com', '522473', '2024-10-23 21:06:47', '2024-10-23 15:11:47'),
(49, 'mhegadra5@gmail.com', '776166', '2024-10-23 21:06:56', '2024-10-23 21:16:56'),
(50, 'adramheg@gmail.com', '710321', '2024-10-23 23:02:43', '2024-10-23 17:07:43'),
(51, 'adramheg@gmail.com', '405108', '2024-10-23 23:03:04', '2024-10-23 23:13:04'),
(52, 'adramheg@gmail.com', '326953', '2024-10-25 06:29:44', '2024-10-25 00:34:44'),
(53, 'adramheg@gmail.com', '263420', '2024-10-25 06:30:20', '2024-10-25 06:40:20'),
(54, 'adramheg@gmail.com', '448603', '2024-10-25 06:30:45', '2024-10-25 06:40:45'),
(55, 'adramheg@gmail.com', '421085', '2024-10-25 07:57:02', '2024-10-25 02:02:02'),
(56, 'adramheg@gmail.com', '115644', '2024-10-25 07:57:08', '2024-10-25 08:07:08'),
(57, 'rodriguezajhanelamae@gmail.com', '994965', '2024-10-25 19:12:12', '2024-10-25 13:17:12'),
(58, 'rodriguezajhanelamae@gmail.com', '490556', '2024-10-25 19:12:15', '2024-10-25 19:22:15'),
(59, 'glennmaurenpayanay@gmail.com', '153508', '2024-10-25 19:17:17', '2024-10-25 13:22:17'),
(60, 'glennmaurenpayanay@gmail.com', '817079', '2024-10-25 19:17:30', '2024-10-25 19:27:30'),
(61, 'mhegadra5@gmail.com', '836396', '2024-10-25 19:20:59', '2024-10-25 13:25:59'),
(62, 'mhegadra5@gmail.com', '793153', '2024-10-25 19:21:02', '2024-10-25 19:31:02'),
(63, 'millaremheg@gmail.com', '167379', '2024-10-25 19:24:51', '2024-10-25 13:29:51'),
(64, 'millaremheg@gmail.com', '314560', '2024-10-25 19:24:53', '2024-10-25 19:34:53'),
(65, 'glennmauren@gmail.com', '970286', '2024-10-29 12:01:43', '2024-10-29 05:06:43'),
(66, 'glennmauren@gmail.com', '309058', '2024-10-29 12:01:51', '2024-10-29 12:11:51'),
(67, 'glennmauren@gmail.com', '404084', '2024-10-29 12:03:53', '2024-10-29 05:08:53'),
(68, 'glennmauren@gmail.com', '799492', '2024-10-29 12:04:12', '2024-10-29 05:09:12'),
(69, 'glennmauren@gmail.com', '291081', '2024-10-29 12:04:18', '2024-10-29 12:14:18'),
(70, 'millaremheg@gmail.com', '252861', '2024-10-29 12:23:40', '2024-10-29 05:28:40'),
(71, 'millaremheg@gmail.com', '211286', '2024-10-29 12:24:08', '2024-10-29 12:34:08'),
(72, 'millaremheg@gmail.com', '188307', '2024-10-29 12:27:46', '2024-10-29 05:32:46'),
(73, 'millaremheg@gmail.com', '119999', '2024-10-29 12:29:31', '2024-10-29 05:34:31'),
(74, 'millaremheg@gmail.com', '370964', '2024-10-29 12:31:56', '2024-10-29 12:41:56'),
(75, 'millaremheg@gmail.com', '139179', '2024-10-29 12:33:58', '2024-10-29 05:43:58'),
(76, 'millaremheg@gmail.com', '718099', '2024-10-29 12:36:35', '2024-10-29 12:46:35'),
(77, 'millaremheg@gmail.com', '859081', '2024-10-29 12:39:11', '2024-10-29 05:49:11'),
(78, 'millaremheg@gmail.com', '629468', '2024-10-29 12:40:05', '2024-10-29 12:50:05'),
(79, 'millaremheg@gmail.com', '978802', '2024-10-29 12:41:29', '2024-10-29 12:51:29'),
(80, 'millaremheg@gmail.com', '238577', '2024-10-29 16:18:26', '2024-10-29 09:28:26'),
(81, 'millaremheg@gmail.com', '966292', '2024-10-29 16:19:15', '2024-10-29 16:29:15'),
(82, 'millaremheg@gmail.com', '314778', '2024-10-29 16:21:41', '2024-10-29 16:31:41'),
(83, 'millaremheg@gmail.com', '164456', '2024-10-29 16:23:50', '2024-10-29 09:33:50'),
(84, 'millaremheg@gmail.com', '124741', '2024-10-29 16:24:12', '2024-10-29 16:34:12'),
(85, 'millaremheg@gmail.com', '427983', '2024-10-29 16:27:48', '2024-10-29 09:37:48'),
(86, 'millaremheg@gmail.com', '643573', '2024-10-29 16:28:48', '2024-10-29 16:38:48'),
(87, 'millaremheg@gmail.com', '147682', '2024-10-29 16:29:45', '2024-10-29 09:39:45'),
(88, 'millaremheg@gmail.com', '523114', '2024-10-29 16:32:27', '2024-10-29 09:42:27'),
(89, 'millaremheg@gmail.com', '918591', '2024-10-29 16:40:40', '2024-10-29 16:50:40'),
(90, 'millaremheg@gmail.com', '194014', '2024-10-29 16:41:33', '2024-10-29 09:51:33'),
(91, 'millaremheg@gmail.com', '936828', '2024-10-29 16:48:19', '2024-10-29 09:58:19'),
(92, 'millaremheg@gmail.com', '570585', '2024-10-29 16:48:55', '2024-10-29 09:58:55'),
(93, 'millaremheg@gmail.com', '700341', '2024-10-29 16:49:05', '2024-10-29 16:59:05'),
(94, 'glennmauren@gmail.com', '800972', '2024-10-29 17:04:13', '2024-10-29 10:14:13'),
(95, 'glennmauren@gmail.com', '732887', '2024-10-29 17:32:58', '2024-10-29 10:42:58'),
(96, 'glennmauren@gmail.com', '360325', '2024-10-29 17:41:15', '2024-10-29 10:51:15'),
(97, 'glennmauren@gmail.com', '151450', '2024-10-29 18:35:23', '2024-10-29 11:45:23'),
(98, 'mhegadra5@gmail.com', '863034', '2024-10-30 14:06:17', '2024-10-30 07:16:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barangay`
--
ALTER TABLE `barangay`
  ADD PRIMARY KEY (`barangay_id`);

--
-- Indexes for table `children`
--
ALTER TABLE `children`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `usertable`
--
ALTER TABLE `usertable`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_barangay`
--
ALTER TABLE `user_barangay`
  ADD PRIMARY KEY (`user_id`,`barangay_id`),
  ADD KEY `barangay_id` (`barangay_id`);

--
-- Indexes for table `user_children`
--
ALTER TABLE `user_children`
  ADD PRIMARY KEY (`user_id`,`child_id`),
  ADD KEY `child_id` (`child_id`);

--
-- Indexes for table `vaccinations`
--
ALTER TABLE `vaccinations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barangay_id` (`barangay_id`);

--
-- Indexes for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_id` (`child_id`);

--
-- Indexes for table `vaccines`
--
ALTER TABLE `vaccines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vaccine_history`
--
ALTER TABLE `vaccine_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barangay_id` (`barangay_id`);

--
-- Indexes for table `verification_codes`
--
ALTER TABLE `verification_codes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barangay`
--
ALTER TABLE `barangay`
  MODIFY `barangay_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `children`
--
ALTER TABLE `children`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `usertable`
--
ALTER TABLE `usertable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `vaccinations`
--
ALTER TABLE `vaccinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vaccines`
--
ALTER TABLE `vaccines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vaccine_history`
--
ALTER TABLE `vaccine_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `verification_codes`
--
ALTER TABLE `verification_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_barangay`
--
ALTER TABLE `user_barangay`
  ADD CONSTRAINT `user_barangay_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usertable` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_barangay_ibfk_2` FOREIGN KEY (`barangay_id`) REFERENCES `barangay` (`barangay_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_children`
--
ALTER TABLE `user_children`
  ADD CONSTRAINT `user_children_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usertable` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_children_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vaccinations`
--
ALTER TABLE `vaccinations`
  ADD CONSTRAINT `vaccinations_ibfk_1` FOREIGN KEY (`barangay_id`) REFERENCES `barangay` (`barangay_id`);

--
-- Constraints for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  ADD CONSTRAINT `vaccination_records_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`);

--
-- Constraints for table `vaccine_history`
--
ALTER TABLE `vaccine_history`
  ADD CONSTRAINT `vaccine_history_ibfk_1` FOREIGN KEY (`barangay_id`) REFERENCES `barangay` (`barangay_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
