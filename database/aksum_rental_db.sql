-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: May 21, 2026 at 07:15 PM
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
-- Database: `aksum_rental_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`log_id`, `user_id`, `action`, `table_name`, `record_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 20:44:36'),
(2, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 20:45:43'),
(3, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 20:50:39'),
(4, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 20:51:27'),
(5, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 20:55:05'),
(6, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 20:55:27'),
(7, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 20:58:37'),
(8, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 20:58:44'),
(9, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 21:24:44'),
(12, 3, 'login', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 21:31:33'),
(13, 3, 'logout', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 21:40:45'),
(14, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 21:41:06'),
(15, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 22:20:51'),
(16, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 23:06:34'),
(17, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 23:20:59'),
(18, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 23:21:17'),
(19, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 23:25:34'),
(20, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 23:25:52'),
(21, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 23:31:33'),
(22, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 23:32:00'),
(23, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-26 23:32:07'),
(24, 6, 'register', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 02:48:55'),
(25, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 02:49:16'),
(26, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 03:17:56'),
(27, 7, 'register', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 03:19:44'),
(28, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 03:19:52'),
(29, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 03:20:01'),
(30, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 03:20:29'),
(31, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 03:29:10'),
(32, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 03:29:33'),
(33, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 03:29:57'),
(34, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 03:35:41'),
(35, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 03:35:51'),
(36, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 03:36:02'),
(37, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 03:36:21'),
(38, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 03:36:38'),
(39, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 03:36:51'),
(40, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 20:09:52'),
(41, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 20:52:37'),
(42, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 20:53:04'),
(43, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-27 21:08:28'),
(44, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-28 02:00:29'),
(45, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-28 03:23:30'),
(46, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-28 21:07:58'),
(47, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-28 21:22:11'),
(48, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-28 21:22:17'),
(49, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-28 21:41:12'),
(50, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-28 21:41:17'),
(51, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-28 22:19:43'),
(52, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-28 22:21:46'),
(53, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-28 23:44:33'),
(54, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-28 23:44:40'),
(55, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-28 23:53:34'),
(56, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-28 23:53:39'),
(57, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-29 00:34:38'),
(58, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-29 21:12:10'),
(59, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-29 22:55:52'),
(60, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-29 22:56:21'),
(61, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-29 23:03:38'),
(62, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-30 20:38:27'),
(63, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-30 21:25:29'),
(64, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-30 21:25:37'),
(65, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-30 23:07:15'),
(66, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-30 23:07:40'),
(67, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-30 23:24:50'),
(68, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-30 23:25:34'),
(69, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-31 06:20:49'),
(70, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-31 06:40:42'),
(71, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-31 06:40:47'),
(72, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-31 07:32:12'),
(73, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-31 07:32:26'),
(74, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-01-31 07:55:53'),
(75, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-01 20:40:04'),
(76, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-01 20:45:54'),
(77, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-01 20:46:04'),
(78, 6, 'submit_request', 'rental_requests', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-01 20:56:06'),
(79, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-01 21:15:56'),
(80, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-01 21:16:04'),
(81, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-01 21:16:47'),
(82, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-01 21:16:53'),
(83, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-01 21:21:03'),
(84, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-01 21:21:22'),
(85, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-01 21:25:33'),
(90, 3, 'login', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-01 22:21:51'),
(91, 3, 'submit_payment', 'payments', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-01 23:41:12'),
(92, 3, 'logout', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-01 23:58:41'),
(95, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 06:32:24'),
(96, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 06:34:04'),
(97, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 06:34:46'),
(98, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 06:35:01'),
(99, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 06:35:08'),
(100, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 06:42:22'),
(101, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 06:42:27'),
(102, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 06:42:44'),
(103, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 06:46:37'),
(104, 6, 'submit_request', 'rental_requests', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 07:46:41'),
(105, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 07:47:19'),
(108, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 07:47:58'),
(109, 6, 'submit_payment', 'payments', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 07:48:45'),
(110, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 09:45:52'),
(111, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 09:45:58'),
(112, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 09:58:45'),
(113, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 09:58:56'),
(114, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 10:01:44'),
(117, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-02 10:10:27'),
(120, 3, 'login', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 01:34:52'),
(121, 3, 'logout', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 01:34:54'),
(122, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 01:35:08'),
(123, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 01:52:24'),
(124, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 01:52:33'),
(125, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 02:21:28'),
(126, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 02:21:33'),
(127, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 02:32:15'),
(128, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 02:32:21'),
(129, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 02:32:41'),
(130, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 02:33:25'),
(131, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 02:44:25'),
(132, 3, 'login', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.4.27 Chrome/142.0.7444.235 Electron/39.2.7 Safari/537.36', '2026-02-03 02:46:11'),
(133, 3, 'logout', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.4.27 Chrome/142.0.7444.235 Electron/39.2.7 Safari/537.36', '2026-02-03 02:46:34'),
(134, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 02:47:27'),
(135, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 03:15:29'),
(136, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 03:15:37'),
(137, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 03:18:48'),
(138, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 03:18:55'),
(139, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 06:35:21'),
(140, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 06:35:23'),
(141, 3, 'login', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 06:35:28'),
(142, 3, 'logout', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 07:14:10'),
(145, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 07:25:44'),
(146, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 08:09:29'),
(147, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 08:09:43'),
(148, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-03 08:10:51'),
(149, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 07:09:06'),
(150, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 07:23:40'),
(153, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 08:33:38'),
(154, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 08:39:46'),
(155, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 08:39:51'),
(156, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 09:11:06'),
(157, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 09:11:12'),
(158, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 09:11:30'),
(159, 3, 'login', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 09:11:35'),
(160, 3, 'logout', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 09:11:40'),
(163, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 09:21:00'),
(164, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 09:42:15'),
(165, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 09:43:29'),
(166, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 09:48:40'),
(167, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 09:49:06'),
(168, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 09:54:59'),
(169, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 09:55:05'),
(170, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 10:14:45'),
(171, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 10:14:54'),
(172, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 10:16:07'),
(173, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 10:19:02'),
(174, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 11:01:55'),
(175, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-04 23:14:35'),
(176, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-05 00:24:29'),
(177, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-05 00:24:34'),
(178, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 06:31:32'),
(179, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 07:18:07'),
(180, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 07:18:16'),
(181, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 07:26:34'),
(182, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 07:27:04'),
(183, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 07:33:28'),
(184, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 07:33:39'),
(185, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 07:44:29'),
(186, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 07:44:35'),
(187, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 07:49:06'),
(188, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 07:49:12'),
(189, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 08:27:29'),
(190, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 08:27:34'),
(191, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 08:33:21'),
(192, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 08:33:26'),
(193, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 08:33:43'),
(194, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 08:34:04'),
(195, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 08:34:19'),
(196, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 08:34:24'),
(197, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 08:39:21'),
(198, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 08:43:16'),
(199, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 08:44:11'),
(200, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 08:49:39'),
(201, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 08:49:55'),
(202, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 08:50:01'),
(203, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 08:53:12'),
(204, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 08:53:44'),
(205, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 09:03:40'),
(206, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 09:03:48'),
(207, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 09:09:11'),
(208, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-06 09:09:17'),
(209, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 07:22:01'),
(210, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 08:06:32'),
(211, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 08:06:39'),
(212, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 08:08:49'),
(213, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 08:08:56'),
(214, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 09:30:05'),
(217, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 09:54:28'),
(218, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 10:20:31'),
(219, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 10:20:42'),
(220, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 10:20:58'),
(221, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 10:21:03'),
(222, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 10:21:24'),
(223, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 10:21:34'),
(224, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 10:40:18'),
(225, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 10:40:25'),
(226, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 10:40:40'),
(229, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 10:53:33'),
(230, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 11:07:31'),
(231, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 11:07:35'),
(232, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 11:07:45'),
(235, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 11:09:00'),
(236, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 11:09:18'),
(237, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 11:09:31'),
(238, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 11:50:32'),
(239, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 11:50:36'),
(240, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 11:56:16'),
(241, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 11:56:20'),
(242, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 11:57:28'),
(243, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 11:57:34'),
(244, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 11:58:25'),
(245, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 11:58:45'),
(246, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:01:47'),
(247, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:02:00'),
(248, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:02:07'),
(249, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:02:13'),
(250, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:07:46'),
(251, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:07:52'),
(252, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:08:03'),
(253, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:08:09'),
(254, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:08:16'),
(255, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:12:17'),
(256, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:12:27'),
(257, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:12:33'),
(258, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:12:45'),
(259, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:15:08'),
(260, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:15:22'),
(261, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:15:27'),
(262, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 13:15:39'),
(263, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 22:11:38'),
(264, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 22:14:26'),
(265, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 22:14:33'),
(266, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 22:15:08'),
(267, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 22:28:55'),
(268, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 22:31:12'),
(269, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 22:31:22'),
(270, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 22:39:35'),
(271, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 22:39:41'),
(272, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 23:27:06'),
(273, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 23:27:11'),
(274, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 23:27:20'),
(275, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 23:27:25'),
(276, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 23:49:59'),
(277, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-07 23:50:04'),
(278, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-09 17:47:32'),
(279, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-09 17:49:11'),
(280, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-09 17:49:26'),
(281, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-09 18:22:39'),
(282, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-09 18:22:45'),
(283, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-09 18:23:05'),
(284, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-09 18:24:48'),
(285, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-09 18:30:08'),
(286, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-09 18:30:15'),
(287, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-09 18:31:01'),
(288, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-09 18:31:08'),
(289, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-09 18:44:26'),
(290, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-09 18:44:31'),
(291, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-09 19:10:22'),
(292, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-10 09:20:33'),
(293, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-11 21:10:04'),
(294, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-11 21:10:25'),
(295, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-12 09:04:10'),
(296, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-12 09:10:03'),
(297, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-12 09:10:40'),
(298, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-12 09:12:07'),
(299, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-12 09:12:19'),
(300, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-12 09:16:16'),
(301, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-12 09:16:24'),
(302, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-12 10:16:50'),
(303, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-12 18:33:32'),
(304, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-12 19:14:35'),
(305, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-12 19:14:41'),
(306, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-12 20:14:46'),
(307, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-12 20:14:56'),
(308, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-12 21:03:45'),
(309, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-13 01:42:14'),
(310, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-13 02:29:29'),
(311, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-13 02:29:55'),
(312, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-13 02:37:46'),
(313, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-13 21:07:17'),
(314, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-14 02:01:58'),
(315, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-14 02:50:29'),
(316, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-14 02:50:37'),
(317, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-14 03:18:05'),
(318, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-14 03:18:45'),
(319, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-14 03:20:39'),
(320, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-14 03:20:48'),
(321, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-14 09:33:10'),
(322, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-14 09:39:10'),
(323, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-14 10:22:24'),
(324, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-14 10:22:43'),
(325, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-14 10:25:07'),
(326, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-14 10:25:36'),
(327, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-14 10:59:05'),
(330, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-14 11:01:22'),
(331, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 01:26:50'),
(332, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 01:27:21'),
(333, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 02:25:52'),
(334, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 02:26:01'),
(335, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 02:26:18'),
(336, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 02:26:23');
INSERT INTO `audit_log` (`log_id`, `user_id`, `action`, `table_name`, `record_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(337, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 02:48:11'),
(338, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 02:48:18'),
(339, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 02:48:36'),
(340, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 02:48:45'),
(341, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 19:32:04'),
(342, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 19:32:20'),
(343, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 19:38:36'),
(344, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 19:38:41'),
(345, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 19:39:22'),
(346, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 19:39:30'),
(347, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 19:40:30'),
(348, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 19:40:43'),
(349, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 19:43:22'),
(350, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 19:43:28'),
(351, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 20:12:20'),
(352, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 20:12:28'),
(353, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 20:22:27'),
(354, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-15 20:27:10'),
(355, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-17 00:12:03'),
(356, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-17 09:19:53'),
(357, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-17 10:08:44'),
(358, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-17 10:08:52'),
(359, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-18 03:17:13'),
(360, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-18 03:17:34'),
(361, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-19 02:20:52'),
(362, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-19 02:21:45'),
(363, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-19 02:42:16'),
(364, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-19 02:42:22'),
(365, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-19 02:49:29'),
(366, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-19 02:49:35'),
(367, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-19 04:21:00'),
(372, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-19 09:06:04'),
(373, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-19 09:07:31'),
(374, 3, 'login', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-19 09:07:36'),
(375, 3, 'logout', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-19 09:16:07'),
(380, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-19 09:25:58'),
(381, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-28 02:35:52'),
(382, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-28 03:29:37'),
(383, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-28 03:29:55'),
(384, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-28 03:31:47'),
(385, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-28 03:32:10'),
(386, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-28 04:06:22'),
(387, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-28 04:06:27'),
(388, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-28 04:33:01'),
(389, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-28 04:33:45'),
(390, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-28 04:35:01'),
(391, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-28 04:35:06'),
(392, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-28 04:35:53'),
(393, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-28 04:36:13'),
(394, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '2026-02-28 04:42:37'),
(395, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-01 20:55:54'),
(396, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-01 20:56:33'),
(397, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-01 20:56:54'),
(398, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-01 21:04:30'),
(399, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-01 21:04:39'),
(400, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-01 21:04:45'),
(401, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 22:03:17'),
(402, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 22:12:44'),
(403, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 22:12:46'),
(404, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-11 22:42:57'),
(406, 6, 'login', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 00:09:02'),
(407, 6, 'logout', 'users', 6, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 00:16:27'),
(408, NULL, 'register', 'users', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-13 00:20:11'),
(409, 7, 'login', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 06:48:35'),
(410, 7, 'logout', 'users', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 06:49:44'),
(411, 3, 'login', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 06:51:06'),
(412, 3, 'logout', 'users', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 06:51:44'),
(413, NULL, 'register', 'users', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 06:56:21'),
(414, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 06:58:43'),
(415, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 06:59:03'),
(420, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 06:59:37'),
(421, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-13 07:01:34'),
(423, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-14 21:12:15'),
(424, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-14 21:12:30'),
(425, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-14 21:13:44'),
(426, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-14 21:49:40'),
(427, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-14 21:57:27'),
(428, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-14 21:57:29'),
(429, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-14 21:58:08'),
(430, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-14 21:58:28'),
(432, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-14 22:01:26'),
(433, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-14 22:01:49'),
(434, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 22:30:52'),
(435, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-14 22:32:57'),
(436, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 07:02:14'),
(437, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 07:04:51'),
(439, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 07:23:04'),
(440, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 07:23:33'),
(443, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 07:44:15'),
(446, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 07:46:48'),
(449, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 07:58:24'),
(450, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 08:00:17'),
(452, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 08:32:27'),
(453, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 08:32:42'),
(454, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 08:41:45'),
(455, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 08:42:24'),
(460, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 08:47:28'),
(461, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 08:49:14'),
(462, 20, 'register', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 08:49:58'),
(463, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 08:51:04'),
(464, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 08:51:20'),
(465, 20, 'email_verified', 'users', 20, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-17 08:52:31'),
(466, 20, 'login', 'users', 20, NULL, NULL, '10.189.231.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-17 09:08:41'),
(467, 20, 'logout', 'users', 20, NULL, NULL, '10.189.231.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-17 09:12:28'),
(468, 20, 'login', 'users', 20, NULL, NULL, '10.189.231.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-17 09:13:13'),
(469, 20, 'login', 'users', 20, NULL, NULL, '10.189.231.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-17 09:13:40'),
(470, 20, 'submit_request', 'rental_requests', 4, NULL, NULL, '10.189.231.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36', '2026-03-17 09:14:31'),
(471, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 11:33:28'),
(472, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 12:04:12'),
(473, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 12:10:23'),
(474, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 12:15:46'),
(475, 2, 'login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '2026-03-17 12:16:30'),
(476, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 07:57:50'),
(477, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 07:58:06'),
(478, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 07:58:11'),
(479, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 07:59:38'),
(480, 2, 'login', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 07:59:52'),
(481, 2, 'logout', 'users', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 08:00:05'),
(482, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 08:00:09'),
(483, 20, 'submit_request', 'rental_requests', 5, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 08:02:22'),
(484, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 08:02:43'),
(485, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 08:02:47'),
(486, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 08:03:37'),
(487, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 08:03:42'),
(488, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 08:04:49'),
(489, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 08:05:08'),
(490, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 08:06:36'),
(491, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 08:08:26'),
(492, 21, 'register', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.7632.159 Mobile Safari/537.36', '2026-03-26 09:27:39'),
(493, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.7632.159 Mobile Safari/537.36', '2026-03-26 09:30:24'),
(494, 21, 'email_verified', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-26 09:47:03'),
(495, 21, 'login', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-26 09:49:21'),
(496, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.7632.159 Mobile Safari/537.36', '2026-03-26 10:04:36'),
(497, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.7632.159 Mobile Safari/537.36', '2026-03-26 10:04:55'),
(498, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 11:31:33'),
(499, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 11:31:39'),
(500, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 12:19:30'),
(501, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 12:19:40'),
(502, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 12:21:50'),
(503, 21, 'login', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 12:25:48'),
(504, 21, 'logout', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 12:46:20'),
(505, 21, 'login', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 12:46:26'),
(506, 21, 'login', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 12:48:29'),
(507, 21, 'logout', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 12:49:32'),
(508, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 12:49:39'),
(509, 1, 'login', 'users', 1, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 12:56:46'),
(510, 1, 'logout', 'users', 1, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 12:58:59'),
(511, 1, 'login', 'users', 1, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 13:00:35'),
(512, 1, 'logout', 'users', 1, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 13:04:20'),
(513, 23, 'register', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 13:05:46'),
(514, 23, 'email_verified', 'users', 23, NULL, NULL, '10.189.239.72', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-26 13:06:25'),
(515, 1, 'login', 'users', 1, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 13:07:16'),
(516, 1, 'logout', 'users', 1, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 13:07:32'),
(517, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 13:07:47'),
(518, 21, 'logout', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 13:09:08'),
(519, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 13:40:40'),
(520, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 13:40:57'),
(521, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 13:49:12'),
(522, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 13:50:24'),
(523, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-03-26 13:54:17'),
(524, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 13:54:34'),
(525, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 13:55:47'),
(526, 1, 'login', 'users', 1, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 13:56:22'),
(527, 1, 'logout', 'users', 1, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 13:58:05'),
(528, 20, 'login', 'users', 20, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 14:00:09'),
(529, 20, 'submit_request', 'rental_requests', 6, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 14:02:31'),
(530, 20, 'logout', 'users', 20, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 14:03:13'),
(531, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 14:03:18'),
(532, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 14:04:48'),
(533, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 14:04:58'),
(534, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 14:05:01'),
(535, 20, 'login', 'users', 20, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 14:05:07'),
(536, 20, 'logout', 'users', 20, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 14:06:40'),
(537, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 14:06:45'),
(538, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 14:31:07'),
(539, 20, 'login', 'users', 20, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 14:31:18'),
(540, 20, 'logout', 'users', 20, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 19:06:41'),
(541, 20, 'login', 'users', 20, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 19:23:15'),
(542, 20, 'login', 'users', 20, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 19:23:15'),
(543, 20, 'logout', 'users', 20, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 20:17:10'),
(544, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 20:17:20'),
(545, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 20:18:00'),
(546, 20, 'login', 'users', 20, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 20:18:05'),
(547, 20, 'logout', 'users', 20, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 20:33:54'),
(548, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 20:34:01'),
(549, 23, 'reject_payment', 'payments', 27, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 20:34:23'),
(550, 23, 'reject_payment', 'payments', 26, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 20:34:29'),
(551, 23, 'reject_payment', 'payments', 25, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 20:34:34'),
(552, 23, 'reject_payment', 'payments', 24, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 20:34:38'),
(553, 23, 'reject_payment', 'payments', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 20:34:42'),
(554, 23, 'reject_payment', 'payments', 22, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 20:35:07'),
(555, 23, 'reject_payment', 'payments', 21, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 20:35:07'),
(556, 23, 'reject_payment', 'payments', 20, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 20:35:07'),
(557, 23, 'verify_payment', 'payments', 19, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 20:35:08'),
(558, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 20:36:36'),
(559, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-07 08:02:24'),
(560, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-08 19:55:37'),
(561, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-08 20:01:00'),
(562, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-08 20:04:35'),
(563, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-08 20:22:57'),
(564, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-08 20:24:56'),
(565, 23, 'verify_payment', 'payments', 29, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-08 20:25:49'),
(566, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-08 20:28:56'),
(567, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-08 20:31:13'),
(568, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-09 19:32:55'),
(569, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-09 19:33:51'),
(570, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-09 19:35:14'),
(571, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-09 19:36:38'),
(572, 24, 'register', 'users', 24, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 14:26:53'),
(573, 24, 'email_verified', 'users', 24, NULL, NULL, '10.189.236.71', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-04-15 14:27:28'),
(574, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 14:31:53'),
(575, 24, 'login', 'users', 24, NULL, NULL, '10.189.236.71', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-04-15 14:33:46'),
(576, 24, 'login', 'users', 24, NULL, NULL, '10.189.236.71', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-04-15 14:37:47'),
(577, 24, 'logout', 'users', 24, NULL, NULL, '10.189.236.71', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-04-15 14:39:26'),
(578, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 18:08:37'),
(579, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 19:04:21'),
(580, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 19:37:12'),
(581, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 19:37:39'),
(582, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 19:38:30'),
(583, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 19:42:30'),
(584, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 19:51:57'),
(585, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 19:52:45'),
(586, 23, 'verify_payment', 'payments', 30, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 19:53:05'),
(587, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 19:53:48'),
(588, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 20:03:05'),
(589, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 20:19:02'),
(590, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 20:21:04'),
(591, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 20:23:56'),
(592, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 20:24:45'),
(593, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 20:41:57'),
(594, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 20:43:24'),
(595, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 20:59:37'),
(596, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 21:10:09'),
(597, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 21:16:23'),
(598, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-15 21:16:29'),
(599, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 18:30:21'),
(600, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 18:31:01'),
(601, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 18:46:23'),
(602, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 18:55:12'),
(603, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 18:55:18'),
(604, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 19:14:30'),
(605, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 19:14:34'),
(606, 21, 'login', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 19:42:59'),
(607, 21, 'logout', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 19:43:02'),
(608, 21, 'login', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 19:51:15'),
(609, 21, 'logout', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 19:51:23'),
(610, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 20:45:55'),
(611, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 20:46:28'),
(612, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 20:46:32'),
(613, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 20:49:14'),
(614, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 20:49:20'),
(615, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 20:51:00'),
(616, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 20:51:12'),
(617, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 20:56:35'),
(618, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.7680.177 Mobile Safari/537.36', '2026-04-16 20:57:28'),
(619, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 20:58:19'),
(620, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 21:26:40'),
(621, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 21:26:49'),
(622, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 21:29:29'),
(623, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 21:29:34'),
(624, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 21:36:40'),
(625, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 21:36:50'),
(626, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 21:37:06'),
(627, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 21:37:12'),
(628, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 21:38:09'),
(629, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 21:38:15'),
(630, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 21:39:58'),
(631, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-16 21:40:02'),
(632, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 17:56:33'),
(633, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 17:56:56'),
(634, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 17:58:03'),
(635, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 17:58:11'),
(636, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 17:58:41'),
(637, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 17:59:37'),
(638, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 17:59:43'),
(639, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 18:01:59'),
(640, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 18:02:16'),
(641, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 18:02:43'),
(642, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 18:02:55'),
(643, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 18:26:24'),
(644, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 18:37:03'),
(645, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 19:00:50'),
(646, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:13:17'),
(647, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:13:47'),
(648, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:16:50'),
(649, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:16:59'),
(650, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:18:42'),
(651, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 19:18:59'),
(652, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 19:19:06');
INSERT INTO `audit_log` (`log_id`, `user_id`, `action`, `table_name`, `record_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(653, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:19:22'),
(654, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:19:59'),
(655, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:23:27'),
(656, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:23:41'),
(657, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:24:57'),
(658, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:26:35'),
(659, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:31:43'),
(660, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:32:29'),
(661, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 19:39:20'),
(662, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:39:44'),
(663, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:39:51'),
(664, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:41:41'),
(665, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 19:41:47'),
(666, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.7680.178 Mobile Safari/537.36', '2026-04-19 19:50:44'),
(667, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 20:06:56'),
(668, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', '2026-04-19 20:17:00'),
(669, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', '2026-04-19 20:18:21'),
(670, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 20:18:55'),
(671, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.7680.178 Mobile Safari/537.36', '2026-04-19 20:28:13'),
(672, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-19 20:31:36'),
(673, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 21:02:42'),
(674, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 21:07:09'),
(675, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 21:09:32'),
(676, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 21:11:44'),
(677, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 21:12:26'),
(678, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 22:33:01'),
(679, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 22:33:07'),
(680, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 22:33:12'),
(681, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-19 22:34:04'),
(682, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', '2026-04-19 22:46:14'),
(683, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', '2026-04-20 10:52:43'),
(684, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-20 13:20:45'),
(685, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-20 13:21:45'),
(686, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-20 13:21:50'),
(687, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-20 13:25:15'),
(688, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-20 13:28:39'),
(689, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:149.0) Gecko/20100101 Firefox/149.0', '2026-04-20 13:29:04'),
(690, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 13:35:15'),
(691, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 13:35:41'),
(692, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 13:35:46'),
(693, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-20 13:35:49'),
(694, 25, 'register', 'users', 25, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 08:31:45'),
(695, 26, 'register', 'users', 26, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 08:34:54'),
(696, 26, 'email_verified', 'users', 26, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-04-21 08:35:33'),
(697, 26, 'login', 'users', 26, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-04-21 08:36:21'),
(698, 26, 'login', 'users', 26, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 08:36:26'),
(699, 26, 'submit_request', 'rental_requests', 7, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 08:43:26'),
(700, 26, 'logout', 'users', 26, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 08:44:31'),
(701, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 08:48:11'),
(702, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 08:57:02'),
(703, 26, 'login', 'users', 26, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 09:02:48'),
(704, 26, 'logout', 'users', 26, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 09:05:49'),
(705, 26, 'login', 'users', 26, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 09:06:43'),
(706, 26, 'logout', 'users', 26, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 09:08:21'),
(707, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 09:10:11'),
(708, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.7680.178 Mobile Safari/537.36', '2026-04-21 09:15:57'),
(709, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.7680.178 Mobile Safari/537.36', '2026-04-21 09:18:12'),
(710, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.7680.178 Mobile Safari/537.36', '2026-04-21 09:18:48'),
(711, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 09:22:13'),
(712, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 09:22:49'),
(713, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 09:29:27'),
(714, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 09:29:52'),
(715, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-21 09:30:25'),
(716, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 14; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.7680.177 Mobile Safari/537.36', '2026-04-21 14:30:35'),
(717, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 11:40:05'),
(718, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 11:40:19'),
(719, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 11:40:28'),
(720, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 11:43:23'),
(721, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 11:45:36'),
(722, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 11:47:50'),
(723, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 11:47:57'),
(724, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 11:52:39'),
(725, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 12:03:21'),
(726, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-22 20:14:57'),
(727, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 20:38:29'),
(728, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 20:55:42'),
(729, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 20:55:47'),
(730, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 21:08:53'),
(731, 21, 'login', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 21:08:58'),
(732, 21, 'logout', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 21:23:38'),
(733, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 21:24:08'),
(734, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 21:28:30'),
(735, 21, 'login', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 21:28:36'),
(736, 21, 'logout', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 22:43:00'),
(737, 21, 'login', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 22:43:09'),
(738, 21, 'logout', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 22:44:26'),
(739, 21, 'login', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 22:44:30'),
(740, 21, 'logout', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 22:50:30'),
(741, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 22:50:38'),
(742, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 22:55:39'),
(743, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 22:55:43'),
(744, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 22:57:46'),
(745, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 22:57:51'),
(746, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 22:58:55'),
(747, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 22:59:27'),
(748, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 23:00:54'),
(749, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 23:01:19'),
(750, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 23:18:19'),
(751, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 23:18:24'),
(752, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 23:18:53'),
(753, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 23:18:59'),
(754, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 23:19:41'),
(755, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 23:19:47'),
(756, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 23:19:59'),
(757, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-23 23:20:25'),
(758, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-24 10:24:01'),
(759, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-24 10:25:23'),
(760, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-24 10:56:11'),
(761, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-24 11:43:52'),
(762, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.7680.178 Mobile Safari/537.36', '2026-04-24 12:00:38'),
(763, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.7680.178 Mobile Safari/537.36', '2026-04-24 12:29:10'),
(764, 27, 'register', 'users', 27, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 14; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.55 Mobile Safari/537.36', '2026-04-24 12:35:33'),
(765, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.7680.178 Mobile Safari/537.36', '2026-04-24 12:36:38'),
(766, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.7680.178 Mobile Safari/537.36', '2026-04-24 12:36:54'),
(767, 27, 'email_verified', 'users', 27, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', '2026-04-24 12:39:50'),
(768, 27, 'login', 'users', 27, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', '2026-04-24 12:40:02'),
(769, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.7680.178 Mobile Safari/537.36', '2026-04-24 12:46:34'),
(770, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.7680.178 Mobile Safari/537.36', '2026-04-24 12:46:52'),
(771, 27, 'logout', 'users', 27, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', '2026-04-24 12:50:29'),
(772, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', '2026-04-24 12:51:15'),
(773, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 12:19:22'),
(774, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 13:19:55'),
(775, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 13:20:00'),
(776, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 17:12:54'),
(777, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 17:26:41'),
(778, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 17:37:29'),
(779, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 17:43:29'),
(780, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 17:43:33'),
(781, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 17:44:44'),
(782, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 17:44:49'),
(783, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 19:43:11'),
(784, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 19:43:17'),
(785, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 19:44:00'),
(786, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 19:44:04'),
(787, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 19:46:05'),
(788, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 19:46:19'),
(789, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 19:46:39'),
(790, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 19:47:19'),
(791, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-25 20:42:29'),
(792, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 18:33:37'),
(793, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 18:34:02'),
(794, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 18:34:09'),
(795, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 19:32:22'),
(796, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 19:32:28'),
(797, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 20:28:42'),
(798, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 20:28:47'),
(799, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 21:05:15'),
(800, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 21:05:20'),
(801, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 21:06:21'),
(802, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 21:07:05'),
(803, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 21:08:58'),
(804, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 21:09:03'),
(805, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 21:10:40'),
(806, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 21:10:45'),
(807, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 21:45:01'),
(808, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 21:45:05'),
(809, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 21:45:28'),
(810, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 21:45:33'),
(811, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 22:27:14'),
(812, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 22:27:24'),
(813, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 22:31:07'),
(814, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 22:31:11'),
(815, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.55 Mobile Safari/537.36', '2026-04-27 22:36:09'),
(816, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 23:30:26'),
(817, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 23:30:32'),
(818, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 23:57:56'),
(819, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 23:58:00'),
(820, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-27 23:59:59'),
(821, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 00:00:19'),
(822, 23, 'verify_payment', 'payments', 43, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 00:05:45'),
(823, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 00:13:02'),
(824, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 00:13:09'),
(825, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 00:21:46'),
(826, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 00:21:55'),
(827, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 01:10:19'),
(828, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 01:10:25'),
(829, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 01:11:35'),
(830, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 17:44:25'),
(831, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 19:08:27'),
(832, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 19:09:00'),
(833, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 21:01:27'),
(834, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 21:01:34'),
(835, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 21:52:51'),
(836, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 21:52:57'),
(837, 20, 'login', 'users', 20, NULL, NULL, '10.189.32.186', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', '2026-04-28 21:55:21'),
(838, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 21:58:05'),
(839, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 21:58:10'),
(840, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 22:02:59'),
(841, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 22:03:17'),
(842, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 22:06:15'),
(843, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 22:06:19'),
(844, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-28 22:09:30'),
(845, 20, 'logout', 'users', 20, NULL, NULL, '10.189.32.186', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', '2026-04-28 22:15:03'),
(846, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 11:56:49'),
(847, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:01:16'),
(848, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:01:53'),
(849, 23, 'verify_payment', 'payments', 53, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:02:21'),
(850, 23, 'verify_payment', 'payments', 53, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:03:04'),
(851, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:05:13'),
(852, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:05:42'),
(853, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-04-29 12:26:28'),
(854, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-02 18:45:03'),
(855, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-02 18:45:37'),
(856, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-02 19:42:09'),
(857, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-02 19:42:33'),
(858, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-02 19:42:42'),
(859, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-02 19:44:36'),
(860, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-02 22:32:44'),
(861, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-02 22:41:49'),
(862, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-02 23:46:25'),
(863, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-02 23:46:36'),
(864, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.55 Mobile Safari/537.36', '2026-05-02 23:54:37'),
(865, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.55 Mobile Safari/537.36', '2026-05-02 23:59:11'),
(866, 20, 'login', 'users', 20, NULL, NULL, '10.189.224.96', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', '2026-05-03 07:39:02'),
(867, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 08:54:55'),
(868, 1, 'login', 'users', 1, NULL, NULL, '10.189.240.108', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 10:24:01'),
(869, 1, 'logout', 'users', 1, NULL, NULL, '10.189.240.108', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 11:04:35'),
(870, 23, 'login', 'users', 23, NULL, NULL, '10.189.240.108', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 11:17:00'),
(871, 23, 'logout', 'users', 23, NULL, NULL, '10.189.240.108', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 11:17:41'),
(872, 23, 'login', 'users', 23, NULL, NULL, '10.189.240.108', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 11:17:56'),
(873, 23, 'logout', 'users', 23, NULL, NULL, '10.189.240.108', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 11:24:49'),
(874, 1, 'login', 'users', 1, NULL, NULL, '10.189.32.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 11:56:19'),
(875, 1, 'logout', 'users', 1, NULL, NULL, '10.189.32.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:02:11'),
(876, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:07:54'),
(877, 1, 'login', 'users', 1, NULL, NULL, '10.189.32.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:10:43'),
(878, 1, 'logout', 'users', 1, NULL, NULL, '10.189.32.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:11:59'),
(879, 1, 'login', 'users', 1, NULL, NULL, '10.189.32.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:12:50'),
(880, 1, 'logout', 'users', 1, NULL, NULL, '10.189.32.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:15:44'),
(881, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:26:08'),
(882, 20, 'logout', 'users', 20, NULL, NULL, '10.189.224.96', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', '2026-05-03 12:33:51'),
(883, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:35:17'),
(884, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:35:20'),
(885, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:35:36'),
(886, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:36:21'),
(887, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:38:51'),
(888, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:38:56'),
(889, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:42:06'),
(890, 23, 'login', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:42:54'),
(891, 23, 'verify_payment', 'payments', 53, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:43:32'),
(892, 23, 'reject_payment', 'payments', 54, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:44:28'),
(893, 23, 'reject_payment', 'payments', 54, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:47:13'),
(894, 23, 'verify_payment', 'payments', 53, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:47:22'),
(895, 23, 'verify_payment', 'payments', 53, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:47:31'),
(896, 23, 'logout', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:48:02'),
(897, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:48:06'),
(898, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:51:43'),
(899, 23, 'login', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 12:51:47'),
(900, 23, 'reject_payment', 'payments', 53, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 13:17:57'),
(901, 23, 'verify_payment', 'payments', 54, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 13:18:09'),
(902, 23, 'logout', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 16:08:24'),
(903, 23, 'login', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 16:08:31'),
(904, 23, 'verify_payment', 'payments', 54, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 16:24:47'),
(905, 23, 'verify_payment', 'payments', 53, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 16:24:54'),
(906, 23, 'logout', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 16:41:32'),
(907, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 16:41:36'),
(908, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 16:45:54'),
(909, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 16:45:59'),
(910, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 19:08:24'),
(911, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 19:33:34'),
(912, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 19:33:55'),
(913, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 19:46:01'),
(914, 23, 'login', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 19:46:07'),
(915, 23, 'logout', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 19:46:49'),
(916, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 19:46:52'),
(917, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 19:58:53'),
(918, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 19:58:58'),
(919, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 20:02:34'),
(920, 23, 'login', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 20:02:39'),
(921, 23, 'logout', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 20:24:09'),
(922, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 20:24:16'),
(923, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 20:26:17'),
(924, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 20:26:32'),
(925, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 20:27:27'),
(926, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-03 20:27:33'),
(927, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 07:00:16'),
(928, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 07:00:26'),
(929, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 07:11:04'),
(930, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 07:11:08'),
(931, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 07:14:46'),
(932, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 07:15:09'),
(933, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 07:23:22'),
(934, 23, 'login', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 07:23:27'),
(935, 23, 'logout', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 07:30:12'),
(936, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 07:30:22'),
(937, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 07:37:49'),
(938, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 07:37:52'),
(939, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 07:45:34'),
(940, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 07:45:40'),
(941, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 08:22:23'),
(942, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 08:22:34');
INSERT INTO `audit_log` (`log_id`, `user_id`, `action`, `table_name`, `record_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(943, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 08:28:29'),
(944, 23, 'login', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 08:28:34'),
(945, 23, 'logout', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 09:29:00'),
(946, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 09:29:04'),
(947, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-04 10:28:06'),
(948, 20, 'login', 'users', 20, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 16:44:21'),
(949, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 16:49:26'),
(950, 4, 'login', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 17:29:52'),
(951, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 17:36:51'),
(952, 4, 'logout', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 17:39:06'),
(953, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 17:39:12'),
(954, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 17:52:02'),
(955, 4, 'login', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 17:52:20'),
(956, 4, 'logout', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 18:01:01'),
(957, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 18:01:10'),
(958, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 18:04:02'),
(959, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 18:04:13'),
(960, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 18:06:05'),
(961, 4, 'login', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 18:06:10'),
(962, 4, 'logout', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 18:06:15'),
(963, 4, 'login', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 18:06:25'),
(964, 4, 'logout', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 18:40:53'),
(965, 23, 'login', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 18:40:57'),
(966, 23, 'logout', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:09:23'),
(967, 4, 'login', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:09:27'),
(968, 4, 'logout', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:12:53'),
(969, 20, 'logout', 'users', 20, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:12:54'),
(970, 21, 'login', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:13:18'),
(971, 20, 'login', 'users', 20, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:13:42'),
(972, 21, 'submit_request', 'rental_requests', 8, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:14:55'),
(973, 20, 'submit_request', 'rental_requests', 9, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:15:50'),
(974, 21, 'logout', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:24:55'),
(975, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:25:02'),
(976, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:31:59'),
(977, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:32:00'),
(978, 1, 'login', 'users', 1, NULL, NULL, '10.189.35.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:32:39'),
(979, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:33:00'),
(980, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:33:19'),
(981, 4, 'login', 'users', 4, NULL, NULL, '10.189.226.96', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-08 19:39:57'),
(982, 1, 'logout', 'users', 1, NULL, NULL, '10.189.35.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:40:11'),
(983, 4, 'login', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:40:46'),
(984, 4, 'logout', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:41:08'),
(985, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:41:14'),
(986, 1, 'login', 'users', 1, NULL, NULL, '10.189.35.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:43:33'),
(987, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:44:14'),
(988, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:44:55'),
(989, 1, 'logout', 'users', 1, NULL, NULL, '10.189.35.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:46:19'),
(990, 4, 'login', 'users', 4, NULL, NULL, '10.189.35.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:46:57'),
(991, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:51:45'),
(992, 21, 'login', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:51:56'),
(993, 21, 'logout', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:52:18'),
(994, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:53:14'),
(995, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:53:36'),
(996, 21, 'login', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 19:53:40'),
(997, 21, 'logout', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:03:28'),
(998, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:03:32'),
(999, 4, 'logout', 'users', 4, NULL, NULL, '10.189.35.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:10:47'),
(1000, 1, 'login', 'users', 1, NULL, NULL, '10.189.35.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:10:50'),
(1001, 1, 'logout', 'users', 1, NULL, NULL, '10.189.35.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:12:37'),
(1002, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:12:54'),
(1003, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:18:10'),
(1004, 1, 'login', 'users', 1, NULL, NULL, '10.189.35.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:18:15'),
(1005, 1, 'logout', 'users', 1, NULL, NULL, '10.189.35.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:18:43'),
(1006, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:29:33'),
(1007, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:29:37'),
(1008, 4, 'logout', 'users', 4, NULL, NULL, '10.189.226.96', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-08 20:33:33'),
(1009, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:53:32'),
(1010, 23, 'login', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:53:41'),
(1011, 23, 'logout', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:54:34'),
(1012, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:54:39'),
(1013, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:55:22'),
(1014, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:55:28'),
(1015, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:57:46'),
(1016, 23, 'login', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 20:57:54'),
(1017, 23, 'logout', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 21:00:21'),
(1018, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 21:00:25'),
(1019, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 21:01:05'),
(1020, 23, 'login', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 21:01:11'),
(1021, 23, 'logout', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 21:05:34'),
(1022, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-08 21:05:42'),
(1023, 21, 'login', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 11:38:57'),
(1024, 21, 'logout', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 12:40:01'),
(1025, 21, 'login', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 12:40:06'),
(1026, 21, 'logout', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 14:26:42'),
(1027, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 14:26:58'),
(1028, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 16:53:19'),
(1029, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 16:53:26'),
(1030, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 16:56:00'),
(1031, 4, 'login', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 16:56:09'),
(1032, 4, 'logout', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 17:01:24'),
(1033, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 17:02:47'),
(1034, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 18:29:37'),
(1035, 21, 'login', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 18:29:47'),
(1036, 20, 'login', 'users', 20, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 19:50:51'),
(1037, 20, 'logout', 'users', 20, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 19:51:30'),
(1038, 21, 'logout', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 20:06:26'),
(1039, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 20:08:00'),
(1040, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 20:08:53'),
(1041, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 20:09:00'),
(1042, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 20:09:06'),
(1043, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 20:09:17'),
(1044, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 20:09:57'),
(1045, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-16 20:10:01'),
(1046, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 06:44:28'),
(1047, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 06:46:34'),
(1048, 4, 'login', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 06:46:40'),
(1049, 4, 'logout', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 06:47:22'),
(1050, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 06:47:27'),
(1051, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 06:48:18'),
(1052, 4, 'login', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 06:48:22'),
(1053, 4, 'logout', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 06:49:00'),
(1054, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 06:49:09'),
(1055, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 06:54:42'),
(1056, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 06:54:47'),
(1057, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 07:31:11'),
(1058, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 07:55:46'),
(1059, 4, 'login', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 07:55:53'),
(1060, 4, 'logout', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 08:06:50'),
(1061, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 08:07:00'),
(1062, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 08:07:57'),
(1063, 21, 'login', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 08:08:03'),
(1064, 21, 'logout', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 08:09:16'),
(1065, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 08:09:21'),
(1066, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 08:10:52'),
(1067, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 10:00:19'),
(1068, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 10:25:07'),
(1069, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 10:30:18'),
(1070, 20, 'login', 'users', 20, NULL, NULL, '10.189.232.188', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.138 Mobile Safari/537.36', '2026-05-17 11:55:19'),
(1071, 21, 'login', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 16:49:27'),
(1072, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 16:49:41'),
(1073, 21, 'login', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 16:49:57'),
(1074, 21, 'logout', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 17:10:50'),
(1075, 21, 'login', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 17:29:55'),
(1076, 21, 'logout', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 17:58:53'),
(1077, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 17:58:58'),
(1078, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 18:01:11'),
(1079, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 18:06:02'),
(1080, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 18:43:30'),
(1081, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 18:43:36'),
(1082, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:07:38'),
(1083, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:07:51'),
(1084, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:07:55'),
(1085, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:09:04'),
(1086, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:09:09'),
(1087, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:09:37'),
(1088, 23, 'login', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:09:52'),
(1089, 23, 'logout', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:16:14'),
(1090, 21, 'login', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:16:32'),
(1091, 21, 'logout', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:21:39'),
(1092, 23, 'login', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:21:43'),
(1093, 23, 'logout', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:21:55'),
(1094, 20, 'login', 'users', 20, NULL, NULL, '10.189.232.188', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-17 21:25:59'),
(1095, 21, 'login', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:27:59'),
(1096, 21, 'logout', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:28:12'),
(1097, 4, 'login', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:28:17'),
(1098, 4, 'logout', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:34:19'),
(1099, 23, 'login', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:34:22'),
(1100, 23, 'logout', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:38:57'),
(1101, 21, 'login', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:39:03'),
(1102, 21, 'logout', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:43:56'),
(1103, 23, 'login', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:44:06'),
(1104, 23, 'logout', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:44:29'),
(1105, 1, 'login', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:44:36'),
(1106, 1, 'logout', 'users', 1, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:45:07'),
(1107, 21, 'login', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:45:11'),
(1108, 21, 'logout', 'users', 21, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:45:28'),
(1109, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-17 21:45:32'),
(1110, 20, 'logout', 'users', 20, NULL, NULL, '10.189.232.188', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-18 05:41:34'),
(1111, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-18 12:01:51'),
(1112, 20, 'login', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-18 12:02:40'),
(1113, 20, 'logout', 'users', 20, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-18 12:03:57'),
(1114, 4, 'login', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-18 13:05:47'),
(1115, 4, 'logout', 'users', 4, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-18 13:06:30'),
(1116, 23, 'login', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-18 13:06:47'),
(1117, 23, 'logout', 'users', 23, NULL, NULL, '10.189.33.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-18 13:09:27'),
(1118, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 05:29:47'),
(1119, 20, 'logout', 'users', 20, NULL, NULL, '10.189.232.188', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.138 Mobile Safari/537.36', '2026-05-19 05:55:58'),
(1120, 1, 'login', 'users', 1, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 05:56:15'),
(1121, 1, 'logout', 'users', 1, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 06:04:57'),
(1122, 23, 'login', 'users', 23, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 06:05:08'),
(1123, 23, 'logout', 'users', 23, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 06:06:31'),
(1124, 1, 'login', 'users', 1, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 06:06:40'),
(1125, 1, 'logout', 'users', 1, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 06:08:10'),
(1126, 20, 'login', 'users', 20, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 06:10:00'),
(1127, 20, 'logout', 'users', 20, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 06:11:27'),
(1128, 23, 'login', 'users', 23, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 06:11:32'),
(1129, 23, 'logout', 'users', 23, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 06:16:41'),
(1130, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 10:43:08'),
(1131, 20, 'login', 'users', 20, NULL, NULL, '10.189.232.188', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.138 Mobile Safari/537.36', '2026-05-19 12:28:49'),
(1132, 20, 'logout', 'users', 20, NULL, NULL, '10.189.232.188', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.138 Mobile Safari/537.36', '2026-05-19 12:41:07'),
(1133, 1, 'login', 'users', 1, NULL, NULL, '10.189.232.188', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.138 Mobile Safari/537.36', '2026-05-19 12:41:47'),
(1134, 1, 'logout', 'users', 1, NULL, NULL, '10.189.232.188', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.138 Mobile Safari/537.36', '2026-05-19 12:44:54'),
(1135, 1, 'login', 'users', 1, NULL, NULL, '10.189.232.188', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.138 Mobile Safari/537.36', '2026-05-19 12:45:23'),
(1136, 1, 'logout', 'users', 1, NULL, NULL, '10.189.232.188', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.138 Mobile Safari/537.36', '2026-05-19 12:46:21'),
(1137, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 21:13:10'),
(1138, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 23:13:12'),
(1139, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 23:14:15'),
(1140, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 23:15:19'),
(1141, 21, 'login', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 23:15:36'),
(1142, 21, 'logout', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-19 23:41:35'),
(1143, 20, 'login', 'users', 20, NULL, NULL, '10.189.232.188', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-20 00:33:37'),
(1144, 20, 'login', 'users', 20, NULL, NULL, '10.189.232.188', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-20 00:33:38'),
(1145, 20, 'logout', 'users', 20, NULL, NULL, '10.189.232.188', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-20 00:45:05'),
(1146, 21, 'login', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 01:04:18'),
(1147, 1, 'login', 'users', 1, NULL, NULL, '10.189.232.188', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-20 01:04:58'),
(1148, 21, 'submit_request', 'rental_requests', 10, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 01:11:18'),
(1149, 21, 'logout', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 01:11:32'),
(1150, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 01:11:43'),
(1151, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 01:12:30'),
(1152, 21, 'login', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 01:12:45'),
(1153, 21, 'logout', 'users', 21, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 01:14:20'),
(1154, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 01:14:24'),
(1155, 1, 'login', 'users', 1, NULL, NULL, '10.189.238.220', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-20 04:55:15'),
(1156, 1, 'logout', 'users', 1, NULL, NULL, '10.189.238.220', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-20 04:58:02'),
(1157, 21, 'login', 'users', 21, NULL, NULL, '10.189.238.135', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-20 06:36:52'),
(1158, 1, 'login', 'users', 1, NULL, NULL, '10.189.232.188', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.138 Mobile Safari/537.36', '2026-05-20 06:40:40'),
(1159, 28, 'register', 'users', 28, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 08:04:23'),
(1160, 28, 'email_verified', 'users', 28, NULL, NULL, '10.189.238.220', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-20 08:05:43'),
(1161, 20, 'login', 'users', 20, NULL, NULL, '10.189.238.220', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-20 08:07:20'),
(1162, 29, 'register', 'users', 29, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 08:15:50'),
(1163, 29, 'email_verified', 'users', 29, NULL, NULL, '10.189.238.220', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-20 08:16:15'),
(1164, 29, 'login', 'users', 29, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 08:16:22'),
(1165, 29, 'logout', 'users', 29, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 08:16:26'),
(1166, 29, 'login', 'users', 29, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 08:22:03'),
(1167, 29, 'logout', 'users', 29, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 08:26:43'),
(1168, 20, 'login', 'users', 20, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 08:32:19'),
(1169, 20, 'logout', 'users', 20, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 08:32:25'),
(1170, 23, 'login', 'users', 23, NULL, NULL, '10.189.254.196', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 08:32:31'),
(1171, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 13:22:12'),
(1172, 29, 'login', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 13:23:12'),
(1173, 29, 'logout', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 13:23:20'),
(1174, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 13:23:26'),
(1175, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 13:50:12'),
(1176, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 13:57:56'),
(1177, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 13:58:33'),
(1178, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:00:13'),
(1179, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:00:25'),
(1180, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:02:29'),
(1181, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:02:41'),
(1182, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:16:29'),
(1183, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:16:47'),
(1184, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:18:04'),
(1185, 29, 'login', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:18:12'),
(1186, 29, 'submit_request', 'rental_requests', 11, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:27:57'),
(1187, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:31:19'),
(1188, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:31:29'),
(1189, 29, 'logout', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:34:59'),
(1190, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:35:49'),
(1191, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:41:26'),
(1192, 29, 'login', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:41:30'),
(1193, 29, 'submit_request', 'rental_requests', 12, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:47:24'),
(1194, 29, 'logout', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:48:15'),
(1195, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:48:18'),
(1196, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:49:40'),
(1197, 29, 'login', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:49:47'),
(1198, 29, 'logout', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:55:02'),
(1199, 1, 'login', 'users', 1, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 14:55:07'),
(1200, 1, 'logout', 'users', 1, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 15:01:04'),
(1201, 4, 'login', 'users', 4, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 15:01:08'),
(1202, 1, 'login', 'users', 1, NULL, NULL, '10.189.235.31', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-20 16:50:55'),
(1203, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:12:29'),
(1204, 1, 'logout', 'users', 1, NULL, NULL, '10.189.235.31', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-20 17:12:59'),
(1205, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:13:40'),
(1206, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:22:13'),
(1207, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:22:27'),
(1208, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:23:11'),
(1209, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:23:23'),
(1210, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:23:33'),
(1211, 4, 'logout', 'users', 4, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:34:05'),
(1212, 29, 'login', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:34:42'),
(1213, 29, 'logout', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:35:01'),
(1214, 29, 'login', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:35:08'),
(1215, 29, 'logout', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:35:16'),
(1216, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:35:22'),
(1217, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:47:40'),
(1218, 29, 'login', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:47:46'),
(1219, 29, 'logout', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:48:01'),
(1220, 4, 'login', 'users', 4, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:48:06'),
(1221, 4, 'logout', 'users', 4, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:49:11'),
(1222, 29, 'login', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:49:16'),
(1223, 29, 'submit_request', 'rental_requests', 13, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:51:28'),
(1224, 29, 'logout', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:51:54'),
(1225, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:52:01'),
(1226, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:53:15'),
(1227, 29, 'login', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:53:21'),
(1228, 29, 'submit_request', 'rental_requests', 14, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:55:14'),
(1229, 29, 'logout', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:55:18'),
(1230, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:55:22'),
(1231, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:56:32'),
(1232, 29, 'login', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:56:36'),
(1233, 29, 'logout', 'users', 29, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 17:58:41'),
(1234, 30, 'register', 'users', 30, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:00:47');
INSERT INTO `audit_log` (`log_id`, `user_id`, `action`, `table_name`, `record_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(1235, 30, 'email_verified', 'users', 30, NULL, NULL, '10.189.230.8', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-20 18:01:32'),
(1236, 30, 'login', 'users', 30, NULL, NULL, '10.189.230.8', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-20 18:02:02'),
(1237, 30, 'login', 'users', 30, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:03:15'),
(1238, 30, 'submit_request', 'rental_requests', 15, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:04:18'),
(1239, 30, 'logout', 'users', 30, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:04:30'),
(1240, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:04:36'),
(1241, 30, 'login', 'users', 30, NULL, NULL, '10.189.230.8', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-20 18:06:13'),
(1242, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:09:36'),
(1243, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:16:16'),
(1244, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:24:18'),
(1245, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:24:26'),
(1246, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:24:33'),
(1247, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:27:06'),
(1248, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:41:50'),
(1249, 30, 'login', 'users', 30, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:42:20'),
(1250, 30, 'logout', 'users', 30, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:43:32'),
(1251, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:45:51'),
(1252, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:46:33'),
(1253, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:49:05'),
(1254, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:49:17'),
(1255, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:49:37'),
(1256, 4, 'login', 'users', 4, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:49:43'),
(1257, 4, 'logout', 'users', 4, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:49:48'),
(1258, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:49:55'),
(1259, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:49:59'),
(1260, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:53:16'),
(1261, 31, 'register', 'users', 31, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:58:09'),
(1262, 31, 'email_verified', 'users', 31, NULL, NULL, '10.189.230.8', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-20 18:58:36'),
(1263, 31, 'login', 'users', 31, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:58:56'),
(1264, 31, 'logout', 'users', 31, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:59:30'),
(1265, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 18:59:34'),
(1266, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 19:03:21'),
(1267, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 19:03:28'),
(1268, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 19:04:45'),
(1269, 31, 'login', 'users', 31, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 19:04:51'),
(1270, 31, 'logout', 'users', 31, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 19:05:15'),
(1271, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 19:12:42'),
(1272, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 19:13:34'),
(1273, 24, 'login', 'users', 24, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 19:13:39'),
(1274, 1, 'login', 'users', 1, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 19:50:48'),
(1275, 24, 'logout', 'users', 24, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 20:18:29'),
(1276, 24, 'login', 'users', 24, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 20:18:42'),
(1277, 24, 'logout', 'users', 24, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 20:33:21'),
(1278, 24, 'login', 'users', 24, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 20:33:37'),
(1279, 24, 'logout', 'users', 24, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 20:33:41'),
(1280, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 20:33:52'),
(1281, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 20:34:25'),
(1282, 31, 'login', 'users', 31, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 20:34:59'),
(1283, 31, 'submit_request', 'rental_requests', 16, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 20:37:30'),
(1284, 31, 'logout', 'users', 31, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 20:38:08'),
(1285, 24, 'login', 'users', 24, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 20:38:23'),
(1286, 24, 'logout', 'users', 24, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 20:41:06'),
(1287, 31, 'login', 'users', 31, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 20:41:15'),
(1288, 31, 'logout', 'users', 31, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 21:02:24'),
(1289, 1, 'login', 'users', 1, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-20 22:53:11'),
(1290, 1, 'logout', 'users', 1, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 01:25:27'),
(1291, 1, 'login', 'users', 1, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 01:25:34'),
(1292, 1, 'logout', 'users', 1, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 01:26:15'),
(1293, 4, 'login', 'users', 4, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 02:35:12'),
(1294, 4, 'logout', 'users', 4, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 02:35:53'),
(1295, 29, 'login', 'users', 29, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 02:36:23'),
(1296, 29, 'logout', 'users', 29, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 02:36:53'),
(1297, 23, 'login', 'users', 23, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 02:37:02'),
(1298, 23, 'logout', 'users', 23, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 02:37:10'),
(1299, 1, 'login', 'users', 1, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 02:37:15'),
(1300, 1, 'logout', 'users', 1, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 02:38:34'),
(1301, 4, 'login', 'users', 4, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 05:19:30'),
(1302, 4, 'logout', 'users', 4, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 05:22:04'),
(1303, 23, 'login', 'users', 23, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 05:22:13'),
(1304, 23, 'logout', 'users', 23, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 05:27:57'),
(1305, 1, 'login', 'users', 1, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 05:28:03'),
(1306, 1, 'logout', 'users', 1, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 05:42:35'),
(1307, 29, 'login', 'users', 29, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 05:46:03'),
(1308, 29, 'logout', 'users', 29, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 05:51:01'),
(1309, 1, 'login', 'users', 1, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 05:51:07'),
(1310, 1, 'logout', 'users', 1, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 05:52:06'),
(1311, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 06:20:53'),
(1312, 1, 'login', 'users', 1, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 06:36:44'),
(1313, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 06:40:25'),
(1314, 1, 'logout', 'users', 1, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 06:50:44'),
(1315, 4, 'login', 'users', 4, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 06:50:48'),
(1316, 4, 'logout', 'users', 4, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 07:05:12'),
(1317, 23, 'login', 'users', 23, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 07:05:18'),
(1318, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 07:11:59'),
(1319, 23, 'logout', 'users', 23, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 07:21:32'),
(1320, 29, 'login', 'users', 29, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 07:21:48'),
(1321, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 07:47:03'),
(1322, 1, 'login', 'users', 1, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 07:55:38'),
(1323, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 08:10:12'),
(1324, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 08:11:48'),
(1325, 29, 'logout', 'users', 29, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 08:15:31'),
(1326, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 08:37:24'),
(1327, 1, 'login', 'users', 1, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 09:47:44'),
(1328, 1, 'logout', 'users', 1, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 09:48:31'),
(1329, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 09:59:12'),
(1330, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 10:24:24'),
(1331, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 10:27:47'),
(1332, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 10:30:26'),
(1333, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 10:39:39'),
(1334, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 11:08:44'),
(1335, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 11:12:34'),
(1336, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 11:14:19'),
(1337, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 11:36:03'),
(1338, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 11:49:17'),
(1339, 1, 'login', 'users', 1, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 11:50:53'),
(1340, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 11:51:49'),
(1341, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 11:51:57'),
(1342, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 11:58:25'),
(1343, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 11:58:29'),
(1344, 1, 'logout', 'users', 1, NULL, NULL, '10.189.253.240', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:01:57'),
(1345, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:33:46'),
(1346, 1, 'logout', 'users', 1, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:45:59'),
(1347, 36, 'register', 'users', 36, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:46:44'),
(1348, 36, 'email_verified', 'users', 36, NULL, NULL, '10.189.237.91', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-21 12:47:10'),
(1349, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:50:45'),
(1350, 36, 'login', 'users', 36, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:51:17'),
(1351, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:51:31'),
(1352, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:51:38'),
(1353, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:51:50'),
(1354, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:52:11'),
(1355, 36, 'logout', 'users', 36, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:53:31'),
(1356, 1, 'login', 'users', 1, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:53:47'),
(1357, 1, 'logout', 'users', 1, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:54:28'),
(1358, 1, 'login', 'users', 1, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:54:37'),
(1359, 1, 'logout', 'users', 1, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:54:42'),
(1360, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:55:26'),
(1361, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:55:42'),
(1362, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 12:57:29'),
(1363, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 13:01:27'),
(1364, 31, 'login', 'users', 31, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 13:02:12'),
(1365, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 13:05:03'),
(1366, 23, 'login', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 13:05:19'),
(1367, 23, 'logout', 'users', 23, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 13:16:45'),
(1368, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 13:40:25'),
(1369, 31, 'login', 'users', 31, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 13:55:34'),
(1370, 1, 'login', 'users', 1, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:00:17'),
(1371, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:04:10'),
(1372, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:04:45'),
(1373, 1, 'logout', 'users', 1, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:07:08'),
(1374, 31, 'logout', 'users', 31, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:07:54'),
(1375, 23, 'login', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:07:57'),
(1376, 36, 'login', 'users', 36, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:08:17'),
(1377, 23, 'logout', 'users', 23, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:12:27'),
(1378, 20, 'login', 'users', 20, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:13:11'),
(1379, 20, 'logout', 'users', 20, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:14:21'),
(1380, 20, 'login', 'users', 20, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:14:31'),
(1381, 20, 'logout', 'users', 20, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:14:45'),
(1382, 4, 'login', 'users', 4, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:15:28'),
(1383, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:15:29'),
(1384, 4, 'logout', 'users', 4, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:16:15'),
(1385, 4, 'login', 'users', 4, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:16:28'),
(1386, 1, 'login', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:16:35'),
(1387, 4, 'logout', 'users', 4, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:17:32'),
(1388, 1, 'logout', 'users', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:18:01'),
(1389, 36, 'login', 'users', 36, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:18:04'),
(1390, 36, 'logout', 'users', 36, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:18:29'),
(1391, 20, 'login', 'users', 20, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:25:46'),
(1392, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:32:09'),
(1393, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:32:16'),
(1394, 20, 'logout', 'users', 20, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:34:33'),
(1395, 36, 'login', 'users', 36, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:34:39'),
(1396, 36, 'logout', 'users', 36, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:34:43'),
(1397, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:36:04'),
(1398, 36, 'logout', 'users', 36, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:43:55'),
(1399, 36, 'login', 'users', 36, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:44:07'),
(1400, 36, 'logout', 'users', 36, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:44:48'),
(1401, 4, 'login', 'users', 4, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:46:08'),
(1402, 4, 'logout', 'users', 4, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:47:30'),
(1403, 36, 'login', 'users', 36, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:47:35'),
(1404, 36, 'logout', 'users', 36, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:51:31'),
(1405, 4, 'login', 'users', 4, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:51:35'),
(1406, 4, 'logout', 'users', 4, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:52:20'),
(1407, 36, 'login', 'users', 36, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:52:26'),
(1408, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:53:10'),
(1409, 20, 'login', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:57:17'),
(1410, 20, 'logout', 'users', 20, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:57:59'),
(1411, 31, 'login', 'users', 31, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:58:03'),
(1412, 31, 'logout', 'users', 31, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 14:58:25'),
(1413, 36, 'logout', 'users', 36, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 15:06:15'),
(1414, 4, 'login', 'users', 4, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 15:06:19'),
(1415, 4, 'logout', 'users', 4, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 15:06:36'),
(1416, 36, 'login', 'users', 36, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 15:06:41'),
(1417, 36, 'logout', 'users', 36, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 16:46:08'),
(1418, 36, 'login', 'users', 36, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 16:46:17'),
(1419, 4, 'login', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 16:52:54'),
(1420, 4, 'logout', 'users', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 16:54:48'),
(1421, 1, 'login', 'users', 1, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 17:05:33'),
(1422, 1, 'logout', 'users', 1, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 17:05:38'),
(1423, 40, 'register', 'users', 40, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 17:06:55'),
(1424, 36, 'logout', 'users', 36, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 17:06:57'),
(1425, 4, 'login', 'users', 4, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 17:07:08'),
(1426, 4, 'logout', 'users', 4, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 17:07:25'),
(1427, 40, 'email_verified', 'users', 40, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Mobile Safari/537.36', '2026-05-21 17:07:29'),
(1428, 36, 'login', 'users', 36, NULL, NULL, '10.189.32.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 17:07:29'),
(1429, 40, 'login', 'users', 40, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 17:07:41'),
(1430, 40, 'logout', 'users', 40, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 17:07:45'),
(1431, 40, 'login', 'users', 40, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 17:08:52'),
(1432, 40, 'logout', 'users', 40, NULL, NULL, '10.189.35.111', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', '2026-05-21 17:12:19');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text NOT NULL,
  `type` enum('complaint','suggestion','review','general') DEFAULT 'general',
  `status` enum('pending','reviewed','resolved') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `user_id`, `property_id`, `rating`, `comment`, `type`, `status`, `reviewed_by`, `reviewed_at`, `created_at`) VALUES
(2, 3, 1, 1, 'Very Satisfied with Service\n\nit is good', 'general', 'resolved', NULL, NULL, '2026-02-01 23:51:04'),
(4, 20, 20, 5, 'sth\n\ni really satisfied by your service', 'general', 'resolved', NULL, NULL, '2026-05-08 17:33:13');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `location_id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `subcity` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`location_id`, `location_name`, `subcity`, `latitude`, `longitude`, `description`) VALUES
(4, 'Aksum University Area', 'Educational', NULL, NULL, NULL),
(10, 'Aksum K\'Idist Maryam Hospital', 'K\'Idist Maryam Hospital Area', NULL, NULL, NULL),
(11, 'Aksum Zion', 'Aksum Zion Area', NULL, NULL, NULL),
(12, 'Ezana Park', 'Ezana Park Area', NULL, NULL, NULL),
(13, 'Aksum Market', 'Aksum Market Area', NULL, NULL, NULL),
(14, 'Referral Hospital', 'Referral Hospital Area', NULL, NULL, NULL),
(15, 'Airport Street', 'Airport Street Area', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `maintenance_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `issue_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `owner_reply` text DEFAULT NULL,
  `owner_reply_at` datetime DEFAULT NULL,
  `priority` enum('low','medium','high','emergency') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`maintenance_id`, `property_id`, `tenant_id`, `issue_type`, `description`, `owner_reply`, `owner_reply_at`, `priority`, `status`, `assigned_to`, `completion_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 6, 'electrical', '', NULL, NULL, '', 'pending', NULL, NULL, NULL, '2026-02-04 09:07:13', '2026-02-04 09:07:13'),
(2, 3, 6, 'electrical', '', 'okay we will fix it with in 12 hour', NULL, '', 'in_progress', NULL, NULL, 'make it fast \n\nAccess Instructions: no', '2026-02-04 09:10:36', '2026-02-04 09:18:03'),
(3, 20, 20, 'electrical', '', 'accepted ', NULL, 'high', 'in_progress', NULL, NULL, 'Electric failure happened ......................................\n\nAccess Instructions: call before arriving', '2026-05-08 17:16:16', '2026-05-08 18:16:18');

-- --------------------------------------------------------

--
-- Table structure for table `news_attachments`
--

CREATE TABLE `news_attachments` (
  `attachment_id` int(11) NOT NULL,
  `news_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_categories`
--

CREATE TABLE `news_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#007bff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news_categories`
--

INSERT INTO `news_categories` (`category_id`, `category_name`, `description`, `color`, `created_at`) VALUES
(1, 'System Updates', 'Important system updates and maintenance notices', '#28a745', '2026-02-07 12:28:19'),
(2, 'Policy Changes', 'Policy updates and rule changes', '#dc3545', '2026-02-07 12:28:19'),
(3, 'Service Notices', 'Service interruptions and maintenance', '#ffc107', '2026-02-07 12:28:19'),
(4, 'Announcements', 'General announcements and news', '#17a2b8', '2026-02-07 12:28:19'),
(5, 'Security Alerts', 'Security-related notifications', '#6f42c1', '2026-02-07 12:28:19');

-- --------------------------------------------------------

--
-- Table structure for table `news_category_relations`
--

CREATE TABLE `news_category_relations` (
  `relation_id` int(11) NOT NULL,
  `news_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_comments`
--

CREATE TABLE `news_comments` (
  `comment_id` int(11) NOT NULL,
  `news_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news_comments`
--

INSERT INTO `news_comments` (`comment_id`, `news_id`, `parent_id`, `user_id`, `comment`, `status`, `created_at`) VALUES
(2, 20, NULL, 20, 'that is good', 'approved', '2026-05-17 10:38:38');

-- --------------------------------------------------------

--
-- Table structure for table `news_likes`
--

CREATE TABLE `news_likes` (
  `like_id` int(11) NOT NULL,
  `news_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news_likes`
--

INSERT INTO `news_likes` (`like_id`, `news_id`, `user_id`, `created_at`) VALUES
(2, 20, 20, '2026-05-17 11:56:19');

-- --------------------------------------------------------

--
-- Table structure for table `news_views`
--

CREATE TABLE `news_views` (
  `view_id` int(11) NOT NULL,
  `news_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news_views`
--

INSERT INTO `news_views` (`view_id`, `news_id`, `user_id`, `viewed_at`) VALUES
(27, 12, 6, '2026-02-12 09:48:27'),
(41, 14, 6, '2026-02-15 19:37:54'),
(42, 15, 6, '2026-02-15 19:38:04'),
(52, 15, 7, '2026-02-15 20:13:13'),
(64, 12, 21, '2026-03-26 12:47:22'),
(68, 12, 20, '2026-04-15 21:18:18'),
(77, 15, 20, '2026-05-08 17:38:21'),
(79, 14, 20, '2026-05-08 17:39:26'),
(90, 15, 23, '2026-05-08 18:20:05'),
(113, 20, 20, '2026-05-16 17:03:19'),
(143, 20, 21, '2026-05-16 18:30:01'),
(144, 15, 21, '2026-05-16 18:30:09'),
(183, 14, 21, '2026-05-17 08:08:44');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error','alert') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `type`, `is_read`, `link`, `created_at`, `expires_at`) VALUES
(1, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-01-26 20:45:43', NULL),
(2, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-01-26 20:51:27', NULL),
(3, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-01-26 20:55:27', NULL),
(4, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-01-26 20:58:44', NULL),
(6, 3, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-01-26 21:31:33', NULL),
(12, 6, 'Welcome to Aksum Rental System!', 'Your account has been created successfully. Please complete your profile.', 'success', 1, NULL, '2026-01-27 02:48:55', NULL),
(13, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-01-27 02:49:16', NULL),
(14, 7, 'Welcome to Aksum Rental System!', 'Your account has been created successfully. Please complete your profile.', 'success', 1, NULL, '2026-01-27 03:19:44', NULL),
(15, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-01-27 03:19:52', NULL),
(16, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-01-27 03:20:29', NULL),
(18, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-01-27 03:35:41', NULL),
(19, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-01-27 03:36:02', NULL),
(20, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-01-27 03:36:38', NULL),
(21, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-01-27 20:09:52', NULL),
(22, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-01-27 20:53:04', NULL),
(23, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-01-28 02:00:29', NULL),
(24, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-01-28 21:07:58', NULL),
(25, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-01-28 21:22:17', NULL),
(26, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-01-28 21:41:17', NULL),
(27, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-01-28 22:21:46', NULL),
(28, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-01-28 23:44:40', NULL),
(32, 3, 'Account Status Update', 'Your account has been rejected. Please contact support for more information.', 'warning', 0, NULL, '2026-01-29 22:09:38', NULL),
(34, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-01-30 20:38:27', NULL),
(35, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-01-30 21:25:37', NULL),
(36, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-01-30 23:07:40', NULL),
(37, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-01-30 23:25:34', NULL),
(38, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-01-31 06:20:49', NULL),
(39, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-01-31 06:40:47', NULL),
(40, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-01-31 07:32:26', NULL),
(41, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-01 20:40:04', NULL),
(42, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-01 20:46:04', NULL),
(43, 7, 'New Rental Request', 'A tenant has requested to rent your property.', 'alert', 1, '../owner/requests.php', '2026-02-01 20:56:06', NULL),
(44, 6, 'Request Submitted', 'Your rental request has been submitted successfully.', 'success', 1, NULL, '2026-02-01 20:56:06', NULL),
(45, 7, 'Rental Request Cancelled', 'A tenant has cancelled their rental request for your property. Please check your requests panel.', 'warning', 1, NULL, '2026-02-01 21:07:14', NULL),
(46, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-01 21:16:04', NULL),
(47, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-01 21:16:53', NULL),
(50, 3, 'Account Status Update', 'Your account has been approved. You can now log in and use the system.', 'success', 0, NULL, '2026-02-01 21:25:27', NULL),
(52, 3, 'Rental Request Approved', 'Your rental request for \'Modern 3 Bedroom House\' has been approved.', 'success', 0, '../tenant/agreements.php', '2026-02-01 21:40:02', NULL),
(54, 3, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-01 22:21:51', NULL),
(56, 3, 'Payment Submitted', 'Your payment of ETB 5,000 has been submitted and is pending verification.', 'success', 0, '../tenant/payments.php', '2026-02-01 23:41:12', NULL),
(57, 1, 'New Feedback Submitted', 'A tenant has submitted new feedback that requires your attention.', 'info', 0, NULL, '2026-02-01 23:51:04', NULL),
(58, 1, 'New Feedback Submitted', 'A tenant has submitted new feedback that requires your attention.', 'info', 0, NULL, '2026-02-01 23:51:53', NULL),
(61, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-02 06:34:46', NULL),
(62, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-02 06:35:08', NULL),
(63, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-02 06:42:27', NULL),
(64, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-02 06:46:37', NULL),
(66, 6, 'Request Submitted', 'Your rental request has been submitted successfully.', 'success', 1, NULL, '2026-02-02 07:46:41', NULL),
(68, 6, 'Rental Request Approved', 'Your rental request for \'Luxury 4 Bedroom Villa\' has been approved.', 'success', 1, '../tenant/agreements.php', '2026-02-02 07:47:35', NULL),
(69, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-02 07:47:58', NULL),
(71, 6, 'Payment Submitted', 'Your payment of ETB 8,000 has been submitted and is pending verification.', 'success', 1, '../tenant/payments.php', '2026-02-02 07:48:45', NULL),
(72, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-02 09:45:58', NULL),
(73, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-02 09:58:56', NULL),
(77, 3, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-03 01:34:52', NULL),
(79, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-03 01:52:33', NULL),
(80, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-03 02:21:33', NULL),
(81, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-03 02:32:21', NULL),
(82, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-03 02:33:25', NULL),
(83, 3, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-03 02:46:11', NULL),
(84, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-03 02:47:27', NULL),
(85, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-03 03:15:37', NULL),
(86, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-03 03:18:55', NULL),
(87, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-03 06:35:21', NULL),
(88, 3, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-03 06:35:28', NULL),
(90, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-03 07:25:44', NULL),
(94, 3, 'Feedback Status Updated', 'Your feedback status has been updated to: Reviewed.', 'info', 0, NULL, '2026-02-04 08:21:31', NULL),
(95, 3, 'Feedback Status Updated', 'Your feedback status has been updated to: Reviewed.', 'info', 0, NULL, '2026-02-04 08:21:37', NULL),
(96, 3, 'Feedback Status Updated', 'Your feedback status has been updated to: Resolved.', 'info', 0, NULL, '2026-02-04 08:21:47', NULL),
(97, 3, 'Feedback Status Updated', 'Your feedback status has been updated to: Resolved.', 'info', 0, NULL, '2026-02-04 08:21:54', NULL),
(98, 6, 'Payment Verified', 'Your payment has been verified for \'Luxury 4 Bedroom Villa\'.', 'success', 1, NULL, '2026-02-04 08:32:31', NULL),
(99, 3, 'Payment Verified', 'Your payment has been verified for \'Modern 3 Bedroom House\'.', 'success', 0, NULL, '2026-02-04 08:32:34', NULL),
(100, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-04 08:33:38', NULL),
(101, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-04 08:39:51', NULL),
(104, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-04 09:11:12', NULL),
(105, 3, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-04 09:11:35', NULL),
(107, 6, 'Maintenance Request Update', 'Your maintenance request is now being worked on. Owner: okay we will fix it with in 12 hour', 'info', 1, NULL, '2026-02-04 09:18:03', NULL),
(108, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-04 09:21:00', NULL),
(109, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-04 09:43:29', NULL),
(110, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-04 09:49:06', NULL),
(112, 6, 'Support Response', 'Your support ticket #2 has been updated with a response.', 'success', 1, NULL, '2026-02-04 10:14:18', NULL),
(113, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-04 10:14:54', NULL),
(118, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-06 07:18:16', NULL),
(120, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-06 07:33:39', NULL),
(122, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-06 07:49:12', NULL),
(124, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-06 08:33:26', NULL),
(126, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-06 08:34:24', NULL),
(127, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-06 08:43:16', NULL),
(128, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-06 08:49:39', NULL),
(134, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-07 08:06:39', NULL),
(140, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-07 10:20:42', NULL),
(141, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-07 10:21:03', NULL),
(152, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-07 10:40:25', NULL),
(160, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-07 11:09:31', NULL),
(164, 7, 'Property Approved', 'Your property \'Executive Top-Floor Penthouse – Panoramic Views\' has been approved and is now visible to tenants.', 'success', 1, '../owner/edit-property.php?id=19', '2026-02-07 11:55:59', NULL),
(165, 7, 'Property Approved', 'Your property \'Heritage Guest House - 5 Bedrooms with Courtyard\' has been approved and is now visible to tenants.', 'success', 1, '../owner/edit-property.php?id=18', '2026-02-07 11:56:06', NULL),
(166, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-07 11:56:20', NULL),
(167, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-07 11:57:34', NULL),
(169, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-07 13:02:00', NULL),
(171, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-07 13:07:52', NULL),
(172, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-07 13:08:09', NULL),
(173, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-07 13:12:17', NULL),
(174, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-07 13:12:33', NULL),
(175, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-07 13:15:08', NULL),
(176, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-07 13:15:27', NULL),
(178, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-07 22:14:33', NULL),
(210, 3, 'New System Announcement', 'New Luxury Listings Now Available in Central Aksum...', 'info', 0, 'public/news-details.php?id=1', '2026-02-07 22:26:28', NULL),
(212, 6, 'New System Announcement', 'New Luxury Listings Now Available in Central Aksum...', 'info', 1, 'public/news-details.php?id=1', '2026-02-07 22:26:28', NULL),
(213, 7, 'New System Announcement', 'New Luxury Listings Now Available in Central Aksum...', 'info', 0, 'public/news-details.php?id=1', '2026-02-07 22:26:28', NULL),
(214, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-07 22:28:55', NULL),
(215, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-07 22:31:22', NULL),
(216, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-07 22:39:41', NULL),
(218, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-07 23:27:25', NULL),
(220, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-09 17:47:32', NULL),
(222, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-09 18:22:45', NULL),
(225, 3, 'New System Announcement', 'Important: Compliance with New Rent Control Regulations...', 'info', 0, 'public/news-details.php?id=2', '2026-02-09 18:30:00', NULL),
(227, 6, 'New System Announcement', 'Important: Compliance with New Rent Control Regulations...', 'info', 1, 'public/news-details.php?id=2', '2026-02-09 18:30:00', NULL),
(228, 7, 'New System Announcement', 'Important: Compliance with New Rent Control Regulations...', 'info', 0, 'public/news-details.php?id=2', '2026-02-09 18:30:00', NULL),
(229, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-09 18:30:15', NULL),
(230, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-09 18:31:08', NULL),
(231, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-09 18:44:31', NULL),
(232, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-10 09:20:33', NULL),
(234, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-12 09:04:10', NULL),
(236, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-12 09:12:19', NULL),
(237, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-12 09:16:24', NULL),
(238, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-12 18:33:32', NULL),
(239, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-12 19:14:41', NULL),
(240, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-12 20:14:56', NULL),
(241, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-13 01:42:14', NULL),
(242, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-13 02:29:55', NULL),
(243, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-13 21:07:17', NULL),
(244, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-14 02:01:58', NULL),
(245, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-14 02:50:37', NULL),
(246, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-14 03:18:45', NULL),
(247, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-14 03:20:48', NULL),
(248, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-14 09:39:10', NULL),
(249, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-14 10:22:43', NULL),
(250, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-14 10:25:36', NULL),
(252, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-14 11:01:22', NULL),
(253, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-15 01:27:21', NULL),
(254, 3, 'password updating', 'make strong the password', 'warning', 0, '', '2026-02-15 02:23:59', NULL),
(255, 6, 'password updating', 'make strong the password', 'warning', 1, '', '2026-02-15 02:23:59', NULL),
(257, 7, 'password updating', 'make strong the password', 'warning', 0, '', '2026-02-15 02:23:59', NULL),
(260, 1, 'password updating', 'make strong the password', 'warning', 0, '', '2026-02-15 02:23:59', NULL),
(261, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-15 02:26:01', NULL),
(262, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-15 02:26:23', NULL),
(263, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-15 02:48:18', NULL),
(264, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-15 02:48:45', NULL),
(265, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-15 19:32:20', NULL),
(268, 3, 'New System Announcement', 'New Luxury Listings Now Available in Central Aksum...', 'info', 0, 'public/news-details.php?id=1', '2026-02-15 19:39:15', NULL),
(270, 6, 'New System Announcement', 'New Luxury Listings Now Available in Central Aksum...', 'info', 1, 'public/news-details.php?id=1', '2026-02-15 19:39:15', NULL),
(271, 7, 'New System Announcement', 'New Luxury Listings Now Available in Central Aksum...', 'info', 0, 'public/news-details.php?id=1', '2026-02-15 19:39:15', NULL),
(273, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-15 19:39:30', NULL),
(276, 3, 'New System Announcement', 'about aaa...', 'info', 0, 'public/news-details.php?id=16', '2026-02-15 19:43:16', NULL),
(278, 6, 'New System Announcement', 'about aaa...', 'info', 1, 'public/news-details.php?id=16', '2026-02-15 19:43:16', NULL),
(279, 7, 'New System Announcement', 'about aaa...', 'info', 0, 'public/news-details.php?id=16', '2026-02-15 19:43:16', NULL),
(281, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-15 19:43:28', NULL),
(282, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-15 20:12:28', NULL),
(283, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-15 20:27:10', NULL),
(284, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-17 09:19:53', NULL),
(285, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-17 10:08:52', NULL),
(286, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-18 03:17:34', NULL),
(287, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-19 02:21:45', NULL),
(288, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-19 02:42:22', NULL),
(289, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-19 02:49:35', NULL),
(293, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-19 09:06:04', NULL),
(294, 3, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-19 09:07:36', NULL),
(299, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-28 02:35:52', NULL),
(300, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-02-28 03:29:55', NULL),
(301, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-28 03:32:10', NULL),
(302, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-28 04:06:27', NULL),
(303, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-28 04:33:45', NULL),
(304, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-28 04:35:06', NULL),
(305, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-02-28 04:36:13', NULL),
(309, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-03-11 22:03:17', NULL),
(310, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-03-11 22:12:46', NULL),
(312, 6, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-03-13 00:09:02', NULL),
(313, 7, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-13 06:48:35', NULL),
(314, 3, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-13 06:51:06', NULL),
(315, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-13 06:58:43', NULL),
(318, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-13 06:59:37', NULL),
(320, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-14 21:12:15', NULL),
(321, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-14 21:13:44', NULL),
(322, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-14 21:57:27', NULL),
(323, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-14 21:58:08', NULL),
(325, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-14 22:01:26', NULL),
(326, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-14 22:30:52', NULL),
(327, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-17 07:02:14', NULL),
(329, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-17 07:23:04', NULL),
(331, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-17 07:44:15', NULL),
(334, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-17 07:58:24', NULL),
(336, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-17 08:32:27', NULL),
(337, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-17 08:41:45', NULL),
(342, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-17 08:47:28', NULL),
(344, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-17 08:51:04', NULL),
(350, 7, 'New Rental Request', 'A tenant has requested to rent your property.', 'alert', 0, '../owner/requests.php', '2026-03-17 09:14:31', NULL),
(352, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-17 11:33:28', NULL),
(353, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-17 12:10:23', NULL),
(354, 2, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-17 12:16:30', NULL),
(355, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-26 07:57:50', NULL),
(357, 2, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-26 07:59:52', NULL),
(359, 2, 'New Rental Request', 'A tenant has requested to rent your property.', 'alert', 0, '../owner/requests.php', '2026-03-26 08:02:22', NULL),
(361, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-26 08:02:47', NULL),
(363, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-26 08:05:08', NULL),
(364, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-26 08:08:26', NULL),
(366, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-26 09:30:24', NULL),
(370, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-26 10:04:55', NULL),
(371, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-26 11:31:40', NULL),
(372, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-26 12:19:40', NULL),
(376, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-26 12:49:39', NULL),
(377, 22, 'Password Changed', 'Your password has been successfully changed.', 'success', 0, NULL, '2026-03-26 12:53:42', NULL),
(378, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-26 12:56:46', NULL),
(379, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-26 13:00:35', NULL),
(383, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-26 13:07:16', NULL),
(390, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-03-26 13:56:22', NULL),
(393, 2, 'Rental Request Cancelled', 'A tenant has cancelled their rental request for your property. Please check your requests panel.', 'warning', 0, NULL, '2026-03-26 14:00:27', NULL),
(424, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-07 08:02:24', NULL),
(425, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-08 19:55:38', NULL),
(434, 24, 'Welcome to Aksum House Rental System!', 'Your account has been created successfully. Please check your email to verify your account.', 'success', 0, NULL, '2026-04-15 14:26:53', NULL),
(435, 24, 'Email Verified!', 'Your email has been successfully verified. You can now log in to your account.', 'success', 0, NULL, '2026-04-15 14:27:29', NULL),
(436, 24, 'ኢሜል ተረጋግጧል!', 'ኢሜልዎ በትክክል ተረጋግጧል። አሁን ወደ መለያዎ መግባት ይችላሉ።', 'success', 0, NULL, '2026-04-15 14:27:29', NULL),
(437, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-15 14:31:54', NULL),
(438, 24, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-15 14:33:46', NULL),
(439, 24, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-15 14:37:47', NULL),
(450, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-15 20:43:24', NULL),
(452, 2, 'New System Announcement', 'Happy Easter to you and your families ...', 'info', 0, 'public/news-details.php?id=17', '2026-04-15 21:16:00', NULL),
(453, 3, 'New System Announcement', 'Happy Easter to you and your families ...', 'info', 0, 'public/news-details.php?id=17', '2026-04-15 21:16:01', NULL),
(456, 21, 'New System Announcement', 'Happy Easter to you and your families ...', 'info', 0, 'public/news-details.php?id=17', '2026-04-15 21:16:01', NULL),
(458, 24, 'New System Announcement', 'Happy Easter to you and your families ...', 'info', 0, 'public/news-details.php?id=17', '2026-04-15 21:16:01', NULL),
(463, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-16 19:42:59', NULL),
(464, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-16 19:51:16', NULL),
(482, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-19 17:56:33', NULL),
(485, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-04-19 17:59:43', NULL),
(487, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-04-19 18:02:55', NULL),
(488, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-04-19 18:37:03', NULL),
(489, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-19 19:00:50', NULL),
(490, 3, 'Greeting', 'how was every thing', 'info', 0, '', '2026-04-19 19:11:04', NULL),
(492, 21, 'Greeting', 'how was every thing', 'info', 0, '', '2026-04-19 19:11:05', NULL),
(493, 24, 'Greeting', 'how was every thing', 'info', 0, '', '2026-04-19 19:11:05', NULL),
(500, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-19 19:23:41', NULL),
(506, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-19 20:06:56', NULL),
(507, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-19 20:17:00', NULL),
(508, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-19 21:02:43', NULL),
(509, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-19 21:11:44', NULL),
(510, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-19 22:33:01', NULL),
(515, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-20 13:28:39', NULL),
(518, 25, 'Welcome to Aksum House Rental System!', 'Your account has been created successfully. Please check your email to verify your account.', 'success', 0, NULL, '2026-04-21 08:31:45', NULL),
(519, 26, 'Welcome to Aksum House Rental System!', 'Your account has been created successfully. Please check your email to verify your account.', 'success', 0, NULL, '2026-04-21 08:34:54', NULL),
(520, 26, 'Email Verified!', 'Your email has been successfully verified. You can now log in to your account.', 'success', 0, NULL, '2026-04-21 08:35:33', NULL),
(521, 26, 'ኢሜል ተረጋግጧል!', 'ኢሜልዎ በትክክል ተረጋግጧል። አሁን ወደ መለያዎ መግባት ይችላሉ።', 'success', 0, NULL, '2026-04-21 08:35:33', NULL),
(522, 26, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-21 08:36:21', NULL),
(523, 26, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-21 08:36:26', NULL),
(525, 26, 'Request Submitted', 'Your rental request has been submitted successfully.', 'success', 0, NULL, '2026-04-21 08:43:26', NULL),
(526, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-21 08:48:11', NULL),
(527, 26, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-21 09:02:48', NULL),
(528, 26, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-21 09:06:43', NULL),
(533, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-21 09:29:52', NULL),
(538, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-22 11:47:58', NULL),
(539, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-22 12:03:21', NULL),
(541, 26, 'Rental Request Rejected', 'Your rental request for \'Modern Student Apartment Near Aksum University\' was rejected.', 'warning', 0, NULL, '2026-04-23 20:55:23', NULL),
(543, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-23 21:08:58', NULL),
(545, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-23 21:28:36', NULL),
(546, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-23 22:43:09', NULL),
(547, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-23 22:44:30', NULL),
(555, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-23 23:19:47', NULL),
(560, 27, 'Welcome to Aksum House Rental System!', 'Your account has been created successfully. Please check your email to verify your account.', 'success', 0, NULL, '2026-04-24 12:35:34', NULL),
(561, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-24 12:36:54', NULL),
(562, 27, 'Email Verified!', 'Your email has been successfully verified. You can now log in to your account.', 'success', 0, NULL, '2026-04-24 12:39:50', NULL),
(563, 27, 'ኢሜል ተረጋግጧል!', 'ኢሜልዎ በትክክል ተረጋግጧል። አሁን ወደ መለያዎ መግባት ይችላሉ።', 'success', 0, NULL, '2026-04-24 12:39:50', NULL),
(564, 27, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-24 12:40:02', NULL),
(568, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-04-25 13:20:00', NULL),
(571, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-25 17:43:33', NULL),
(577, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-27 18:33:37', NULL),
(581, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-04-27 21:05:20', NULL),
(584, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-27 21:09:03', NULL),
(585, 3, 'Withdrawal Cancelled', 'Your withdrawal request for 50.00 ETB has been cancelled. The amount has been refunded to your wallet.', 'warning', 0, '../tenant/payments.php', '2026-04-27 21:09:18', NULL),
(586, 3, 'Withdrawal Cancelled', 'Your withdrawal request for 50.00 ETB has been cancelled. The amount has been refunded to your wallet.', 'warning', 0, '../tenant/payments.php', '2026-04-27 21:09:23', NULL),
(589, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-27 21:45:05', NULL),
(593, 1, 'New Withdrawal Request', 'User yeshu requested a withdrawal of 3,000.00 ETB', '', 0, NULL, '2026-04-27 22:29:17', NULL),
(594, 23, 'New payment received', 'A new payment of ETB 3,000.00 was submitted for agreement #3.', 'info', 1, '../owner/payments.php', '2026-04-27 22:30:18', NULL),
(595, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-04-27 22:31:11', NULL),
(597, 23, 'New payment received', 'A new payment of ETB 3,000.00 was submitted for agreement #3.', 'info', 1, '../owner/payments.php', '2026-04-27 22:49:00', NULL),
(599, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-27 23:58:00', NULL),
(601, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-04-28 00:00:19', NULL),
(603, 23, 'Payment Received', 'You have received ETB 3,000 for Modern Student Apartment Near Aksum University. The amount has been credited to your wallet.', 'success', 1, '../owner/payments.php', '2026-04-28 00:05:45', NULL),
(604, 1, 'New Withdrawal Request', 'User tewe requested a withdrawal of 200.00 ETB', '', 0, NULL, '2026-04-28 00:11:59', NULL),
(605, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-28 00:13:09', NULL),
(606, 23, 'Withdrawal Approved', 'Your withdrawal request for 200.00 ETB has been approved and completed.', 'success', 1, '../owner/withdrawals.php', '2026-04-28 00:15:16', NULL),
(616, 3, 'Deposit Successful', 'Your deposit of ETB 500.00 has been completed successfully.', 'success', 0, '../tenant/payments.php', '2026-04-28 21:30:50', NULL),
(619, 1, 'New Withdrawal Request', 'User yeshu requested a withdrawal of 4,000.00 ETB', '', 0, NULL, '2026-04-28 21:51:49', NULL),
(620, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-28 21:52:57', NULL),
(625, 1, 'New Withdrawal Request', 'User tewe requested a withdrawal of 800.00 ETB', '', 1, NULL, '2026-04-28 22:04:58', NULL),
(626, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-04-28 22:06:19', NULL),
(627, 23, 'Withdrawal Approved', 'Your withdrawal request for 800.00 ETB has been approved and completed.', 'success', 1, '../owner/withdrawals.php', '2026-04-28 22:06:35', NULL),
(630, 23, 'New payment received (Paid)', 'A new payment of ETB 3,000.00 has been paid via wallet for agreement #3.', 'info', 1, '../owner/payments.php', '2026-04-29 12:00:49', NULL),
(631, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-04-29 12:01:53', NULL),
(633, 23, 'Payment Received', 'You have received ETB 3,000 for Modern Student Apartment Near Aksum University. The amount has been credited to your wallet.', 'success', 1, '../owner/payments.php', '2026-04-29 12:02:21', NULL),
(635, 23, 'Payment Received', 'You have received ETB 3,000 for Modern Student Apartment Near Aksum University. The amount has been credited to your wallet.', 'success', 1, '../owner/payments.php', '2026-04-29 12:03:04', NULL),
(636, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-04-29 12:05:43', NULL),
(637, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-02 18:45:03', NULL),
(638, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-02 19:42:09', NULL),
(645, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-03 10:24:01', NULL),
(646, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-05-03 11:17:00', NULL),
(647, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-05-03 11:17:56', NULL),
(648, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-03 11:56:19', NULL),
(649, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-03 12:10:43', NULL),
(650, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-03 12:12:50', NULL),
(652, 23, 'New payment received (Paid)', 'A new payment of ETB 3,090.00 has been paid via wallet for agreement #3 (Base rent: ETB 3,000.00, you will receive ETB 2,850.00 after 5% commission).', 'info', 1, '../owner/payments.php', '2026-05-03 12:26:20', NULL),
(654, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-03 12:36:21', NULL),
(657, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-05-03 12:42:54', NULL),
(659, 23, 'Payment Received', 'You have received ETB 2,766.99 for Modern Student Apartment Near Aksum University (after 5% commission deduction). The amount has been credited to your wallet.', 'success', 1, '../owner/payments.php', '2026-05-03 12:43:27', NULL),
(660, 1, 'Commission Earned', 'You have earned ETB 233.01 commission for payment ID 53.', 'success', 0, '../admin/payments.php', '2026-05-03 12:43:27', NULL),
(664, 23, 'Payment Received', 'You have received ETB 2,766.99 for Modern Student Apartment Near Aksum University (after 5% commission deduction). The amount has been credited to your wallet.', 'success', 1, '../owner/payments.php', '2026-05-03 12:47:18', NULL),
(665, 1, 'Commission Earned', 'You have earned ETB 233.01 commission for payment ID 53.', 'success', 0, '../admin/payments.php', '2026-05-03 12:47:18', NULL),
(667, 23, 'Payment Received', 'You have received ETB 2,766.99 for Modern Student Apartment Near Aksum University (after 5% commission deduction). The amount has been credited to your wallet.', 'success', 1, '../owner/payments.php', '2026-05-03 12:47:26', NULL),
(668, 1, 'Commission Earned', 'You have earned ETB 233.01 commission for payment ID 53.', 'success', 0, '../admin/payments.php', '2026-05-03 12:47:27', NULL),
(669, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-03 12:48:06', NULL),
(670, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-05-03 12:51:47', NULL),
(673, 23, 'Payment Received', 'You have received ETB 2,850.00 for Modern Student Apartment Near Aksum University (after 5% commission deduction). The amount has been credited to your wallet.', 'success', 1, '../owner/payments.php', '2026-05-03 13:18:05', NULL),
(674, 1, 'Commission Earned', 'You have earned ETB 240.00 commission for payment ID 54.', 'success', 0, '../admin/payments.php', '2026-05-03 13:18:05', NULL),
(675, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-05-03 16:08:32', NULL),
(677, 23, 'Payment Received', 'You have received ETB 2,850.00 for Modern Student Apartment Near Aksum University (after 5% commission deduction). The amount has been credited to your wallet.', 'success', 1, '../owner/payments.php', '2026-05-03 16:24:42', NULL),
(678, 1, 'Commission Earned', 'You have earned ETB 240.00 commission for payment ID 54.', 'success', 0, '../admin/payments.php', '2026-05-03 16:24:43', NULL),
(680, 23, 'Payment Received', 'You have received ETB 2,766.99 for Modern Student Apartment Near Aksum University (after 5% commission deduction). The amount has been credited to your wallet.', 'success', 1, '../owner/payments.php', '2026-05-03 16:24:48', NULL),
(681, 1, 'Commission Earned', 'You have earned ETB 233.01 commission for payment ID 53.', 'success', 0, '../admin/payments.php', '2026-05-03 16:24:48', NULL),
(682, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-03 16:41:36', NULL),
(685, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-03 19:08:24', NULL),
(687, 23, 'New payment received (Paid)', 'A new payment of ETB 3,090.00 has been paid via wallet for agreement #3 (Base rent: ETB 3,000.00, you will receive ETB 2,850.00 after 5% commission).', 'info', 1, '../owner/payments.php', '2026-05-03 19:35:10', NULL),
(688, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-05-03 19:46:07', NULL),
(689, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-03 19:46:52', NULL),
(691, 23, 'New payment received (Paid)', 'A new payment of ETB 3,090.00 has been paid via wallet for agreement #3 (Base rent: ETB 3,000.00, you will receive ETB 2,850.00 after 5% commission).', 'info', 1, '../owner/payments.php', '2026-05-03 19:59:39', NULL),
(692, 23, 'New payment received (Paid)', 'A new payment of ETB 3,090.00 has been paid via wallet for agreement #3 (Base rent: ETB 3,000.00, you will receive ETB 2,850.00 after 5% commission).', 'info', 1, '../owner/payments.php', '2026-05-03 20:02:03', NULL),
(693, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-05-03 20:02:39', NULL),
(695, 23, 'New payment received (Paid)', 'A new payment of ETB 618.00 has been paid via wallet for agreement #3 (Base rent: ETB 600.00, you will receive ETB 570.00 after 5% commission).', 'info', 1, '../owner/payments.php', '2026-05-03 20:25:00', NULL),
(697, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-03 20:27:34', NULL),
(699, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-04 07:11:08', NULL),
(700, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-04 07:15:09', NULL),
(701, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-05-04 07:23:27', NULL),
(703, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-04 07:37:52', NULL),
(707, 23, 'New payment received (Paid)', 'A new payment of ETB 3,150.00 has been paid via wallet for agreement #3 (Base rent: ETB 3,000.00, you will receive ETB 2,790.00 after 7% commission).', 'info', 1, '../owner/payments.php', '2026-05-04 08:27:25', NULL),
(708, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-05-04 08:28:34', NULL),
(714, 23, 'New payment received (Paid)', 'A new payment of ETB 3,150.00 has been paid via wallet for agreement #3 (Base rent: ETB 3,000.00, you will receive ETB 2,790.00 after 7% commission).', 'info', 1, '../owner/payments.php', '2026-05-08 17:05:31', NULL),
(716, 21, 'New System Announcement', 'good morning ...', 'info', 0, 'public/news-details.php?id=18', '2026-05-08 17:07:14', NULL),
(717, 24, 'New System Announcement', 'good morning ...', 'info', 0, 'public/news-details.php?id=18', '2026-05-08 17:07:14', NULL),
(718, 25, 'New System Announcement', 'good morning ...', 'info', 0, 'public/news-details.php?id=18', '2026-05-08 17:07:14', NULL),
(719, 26, 'New System Announcement', 'good morning ...', 'info', 0, 'public/news-details.php?id=18', '2026-05-08 17:07:14', NULL),
(720, 27, 'New System Announcement', 'good morning ...', 'info', 0, 'public/news-details.php?id=18', '2026-05-08 17:07:14', NULL),
(721, 1, 'New Withdrawal Request', 'User yeshu requested a withdrawal of 500.00 ETB', '', 0, NULL, '2026-05-08 17:11:24', NULL),
(722, 27, 'Support Response', 'Your support ticket #8 has been updated with a response.', 'success', 0, NULL, '2026-05-08 17:13:58', NULL),
(723, 23, 'New Maintenance Request', 'A tenant has submitted a new maintenance request. Please check your maintenance panel.', 'alert', 1, NULL, '2026-05-08 17:16:16', NULL),
(724, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 17:29:52', NULL),
(725, 1, 'New Feedback Submitted', 'A tenant has submitted new feedback that requires your attention.', 'info', 0, NULL, '2026-05-08 17:33:13', NULL),
(726, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-05-08 17:36:51', NULL),
(727, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 17:39:12', NULL),
(728, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 17:52:20', NULL),
(729, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 18:01:10', NULL),
(730, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 18:04:13', NULL),
(731, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 18:06:10', NULL),
(732, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 18:06:25', NULL),
(738, 1, 'New Withdrawal Request', 'User Desta  requested a withdrawal of 50.00 ETB', '', 0, NULL, '2026-05-08 18:29:18', NULL),
(739, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-05-08 18:40:57', NULL),
(740, 4, 'New Property Added', 'New property \'Newly Built House Royalty\' requires review', 'info', 0, NULL, '2026-05-08 18:42:19', NULL),
(741, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 19:09:27', NULL),
(742, 23, 'Property Approved', 'Your property \'Newly Built House Royalty\' has been approved and is now visible to tenants.', 'success', 1, '../owner/edit-property.php?id=21', '2026-05-08 19:10:47', NULL),
(743, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 19:13:18', NULL),
(745, 23, 'New Rental Request', 'A tenant has requested to rent your property.', 'alert', 1, '../owner/requests.php', '2026-05-08 19:14:55', NULL),
(746, 21, 'Request Submitted', 'Your rental request has been submitted successfully.', 'success', 0, NULL, '2026-05-08 19:14:55', NULL),
(747, 21, 'Rental Request Rejected', 'Your rental request for \'Newly Built House Royalty\' was rejected.', 'warning', 0, NULL, '2026-05-08 19:15:37', NULL),
(748, 23, 'New Rental Request', 'A tenant has requested to rent your property.', 'alert', 1, '../owner/requests.php', '2026-05-08 19:15:50', NULL),
(751, 23, 'New payment received (Paid)', 'A new payment of ETB 630.00 has been paid via wallet for agreement #3 (Base rent: ETB 600.00, you will receive ETB 558.00 after 7% commission).', 'info', 0, '../owner/payments.php', '2026-05-08 19:17:12', NULL),
(752, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 19:25:02', NULL),
(754, 23, 'New payment received (Paid)', 'A new payment of ETB 8,400.00 has been paid via wallet for agreement #4 (Base rent: ETB 8,000.00, you will receive ETB 7,440.00 after 7% commission).', 'info', 0, '../owner/payments.php', '2026-05-08 19:28:19', NULL),
(755, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 19:32:39', NULL),
(756, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 19:33:00', NULL),
(757, 4, 'Password Changed', 'Your password has been successfully changed.', 'success', 0, NULL, '2026-05-08 19:38:53', NULL),
(758, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 19:39:57', NULL),
(759, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 19:40:46', NULL),
(760, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 19:41:14', NULL),
(761, 1, 'Password Changed', 'Your password has been successfully changed.', 'success', 0, NULL, '2026-05-08 19:43:05', NULL),
(762, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 19:43:33', NULL),
(763, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 19:44:55', NULL),
(764, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 1, NULL, '2026-05-08 19:46:57', NULL),
(765, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 19:51:56', NULL),
(767, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 19:53:40', NULL),
(769, 21, 'New System Announcement', 'weweeretwty...', 'info', 0, 'public/news-details.php?id=19', '2026-05-08 19:54:17', NULL),
(770, 24, 'New System Announcement', 'weweeretwty...', 'info', 0, 'public/news-details.php?id=19', '2026-05-08 19:54:17', NULL),
(772, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 20:10:51', NULL),
(773, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 20:12:54', NULL),
(774, 1, 'New Withdrawal Request', 'User tewe requested a withdrawal of 500.00 ETB', '', 0, NULL, '2026-05-08 20:14:00', NULL),
(775, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 20:18:15', NULL),
(777, 1, 'New Withdrawal Request', 'User Desta  requested a withdrawal of 500.00 ETB', '', 0, NULL, '2026-05-08 20:28:29', NULL),
(778, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 20:29:37', NULL),
(779, 1, 'New Withdrawal Request', 'User Desta  requested a withdrawal of 500.00 ETB', '', 0, NULL, '2026-05-08 20:31:16', NULL),
(780, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 20:53:42', NULL);
INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `type`, `is_read`, `link`, `created_at`, `expires_at`) VALUES
(781, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 20:54:39', NULL),
(783, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 20:57:54', NULL),
(784, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 21:00:25', NULL),
(785, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 21:01:11', NULL),
(787, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-08 21:05:42', NULL),
(788, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-16 11:38:57', NULL),
(789, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-16 12:40:06', NULL),
(792, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-16 16:56:09', NULL),
(793, 4, 'New System Announcement', 'Important Update on Rental Payment System...', 'info', 0, 'public/news-details.php?id=20', '2026-05-16 17:01:13', NULL),
(795, 21, 'New System Announcement', 'Important Update on Rental Payment System...', 'info', 0, 'public/news-details.php?id=20', '2026-05-16 17:01:13', NULL),
(796, 23, 'New System Announcement', 'Important Update on Rental Payment System...', 'info', 0, 'public/news-details.php?id=20', '2026-05-16 17:01:13', NULL),
(797, 24, 'New System Announcement', 'Important Update on Rental Payment System...', 'info', 0, 'public/news-details.php?id=20', '2026-05-16 17:01:13', NULL),
(799, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-16 18:29:47', NULL),
(802, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-16 20:09:00', NULL),
(803, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-16 20:09:18', NULL),
(806, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 06:46:40', NULL),
(808, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 06:48:22', NULL),
(810, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 06:54:47', NULL),
(812, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 07:55:53', NULL),
(813, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 08:07:00', NULL),
(814, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 08:08:03', NULL),
(815, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 08:09:21', NULL),
(817, 23, 'New payment received (Paid)', 'A new payment of ETB 630.00 has been paid via wallet for agreement #3 (Base rent: ETB 600.00, you will receive ETB 558.00 after 7% commission).', 'info', 0, '../owner/payments.php', '2026-05-17 10:18:24', NULL),
(820, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 16:49:28', NULL),
(821, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 16:49:57', NULL),
(822, 21, 'Deposit Successful', 'Your deposit of ETB 10,000.00 has been completed successfully.', 'success', 0, '../tenant/payments.php', '2026-05-17 17:07:44', NULL),
(823, 21, 'Withdrawal Successful', 'Your withdrawal of ETB 500.00 via CBE has been processed successfully.', '', 0, NULL, '2026-05-17 17:10:21', NULL),
(824, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 17:29:56', NULL),
(827, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 18:43:36', NULL),
(829, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 21:07:56', NULL),
(831, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 21:09:52', NULL),
(832, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 21:16:32', NULL),
(833, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 21:21:44', NULL),
(835, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 21:27:59', NULL),
(836, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 21:28:17', NULL),
(837, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 21:34:22', NULL),
(838, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 21:39:03', NULL),
(840, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 21:44:06', NULL),
(841, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 21:44:36', NULL),
(842, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-17 21:45:11', NULL),
(845, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-18 13:05:48', NULL),
(846, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-18 13:06:47', NULL),
(847, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-19 05:29:47', NULL),
(848, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-19 05:56:15', NULL),
(849, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-19 06:05:08', NULL),
(850, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-19 06:06:40', NULL),
(852, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-19 06:11:32', NULL),
(854, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-19 12:41:48', NULL),
(855, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-19 12:45:23', NULL),
(856, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-19 21:13:10', NULL),
(857, 4, 'New Property Added', 'New property \'Smart Student Rental Home\' requires review', 'info', 0, NULL, '2026-05-19 22:45:23', NULL),
(858, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-19 23:14:15', NULL),
(859, 23, 'Property Approved', 'Your property \'Smart Student Rental Home\' has been approved and is now visible to tenants.', 'success', 0, '../owner/edit-property.php?id=22', '2026-05-19 23:15:13', NULL),
(860, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-19 23:15:36', NULL),
(863, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 01:04:18', NULL),
(864, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 01:04:58', NULL),
(865, 21, 'Deposit Successful', 'Your deposit of ETB 10,000.00 has been completed successfully.', 'success', 0, '../tenant/payments.php', '2026-05-20 01:06:37', NULL),
(866, 21, 'Withdrawal Successful', 'Your withdrawal of ETB 2,000.00 via CBE has been processed. Total deducted: ETB 2,060.00 (includes ETB 60.00 fee).', '', 0, NULL, '2026-05-20 01:08:18', NULL),
(867, 23, 'New Rental Request', 'A tenant has requested to rent your property.', 'alert', 0, '../owner/requests.php', '2026-05-20 01:11:18', NULL),
(868, 21, 'Request Submitted', 'Your rental request has been submitted successfully.', 'success', 0, NULL, '2026-05-20 01:11:18', NULL),
(869, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 01:11:43', NULL),
(870, 21, 'Rental Request Approved', 'Your rental request for \'Smart Student Rental Home\' has been approved.', 'success', 0, '../tenant/agreements.php', '2026-05-20 01:11:59', NULL),
(871, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 01:12:45', NULL),
(872, 1, 'Commission Earned', 'You have earned ETB 480.00 commission for wallet payment ID #29.', 'success', 0, '../admin/payments.php', '2026-05-20 01:13:18', NULL),
(873, 23, 'New payment received (Paid)', 'A new payment of ETB 4,200.00 has been paid via wallet for agreement #5 (Base rent: ETB 4,000.00, you will receive ETB 3,720.00 after 7% commission).', 'info', 0, '../owner/payments.php', '2026-05-20 01:13:30', NULL),
(874, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 01:14:24', NULL),
(875, 23, 'Withdrawal Successful', 'Your withdrawal of ETB 720.00 via Telebirr has been processed. Total deducted: ETB 734.40 (includes ETB 14.40 fee).', '', 0, NULL, '2026-05-20 01:20:45', NULL),
(876, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 04:55:15', NULL),
(877, 21, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 06:36:52', NULL),
(878, 21, 'Withdrawal Successful', 'Your withdrawal of ETB 3,000.00 via Telebirr has been processed. Total deducted: ETB 3,090.00 (includes ETB 90.00 fee).', '', 0, NULL, '2026-05-20 06:39:42', NULL),
(879, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 06:40:40', NULL),
(880, 28, 'Welcome to Aksum House Rental System!', 'Your account has been created successfully. Please check your email to verify your account.', 'success', 0, NULL, '2026-05-20 08:04:23', NULL),
(881, 28, 'Email Verified!', 'Your email has been successfully verified. You can now log in to your account.', 'success', 0, NULL, '2026-05-20 08:05:43', NULL),
(882, 28, 'ኢሜል ተረጋግጧል!', 'ኢሜልዎ በትክክል ተረጋግጧል። አሁን ወደ መለያዎ መግባት ይችላሉ።', 'success', 0, NULL, '2026-05-20 08:05:43', NULL),
(883, 20, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 08:07:20', NULL),
(884, 29, 'Welcome to Aksum House Rental System!', 'Your account has been created successfully. Please check your email to verify your account.', 'success', 0, NULL, '2026-05-20 08:15:50', NULL),
(885, 29, 'Email Verified!', 'Your email has been successfully verified. You can now log in to your account.', 'success', 0, NULL, '2026-05-20 08:16:15', NULL),
(886, 29, 'ኢሜል ተረጋግጧል!', 'ኢሜልዎ በትክክል ተረጋግጧል። አሁን ወደ መለያዎ መግባት ይችላሉ።', 'success', 0, NULL, '2026-05-20 08:16:15', NULL),
(887, 29, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 08:16:22', NULL),
(888, 29, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 08:22:03', NULL),
(889, 20, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 08:32:19', NULL),
(890, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 08:32:31', NULL),
(891, 29, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 13:23:12', NULL),
(892, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 13:23:26', NULL),
(893, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 13:50:12', NULL),
(894, 4, 'New Property Added', 'New property \'Modern House\' requires review', 'info', 0, NULL, '2026-05-20 13:52:21', NULL),
(895, 20, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 13:58:33', NULL),
(896, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 14:00:25', NULL),
(897, 23, 'Property Approved', 'Your property \'Modern House\' has been approved and is now visible to tenants.', 'success', 0, '../owner/edit-property.php?id=23', '2026-05-20 14:01:45', NULL),
(898, 20, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 14:02:41', NULL),
(899, 4, 'New Property Added', 'New property \'Luxury 5-Bedroom Family Villa with Beautiful Balcony\' requires review', 'info', 0, NULL, '2026-05-20 14:16:27', NULL),
(900, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 14:16:47', NULL),
(901, 23, 'Property Approved', 'Your property \'Luxury 5-Bedroom Family Villa with Beautiful Balcony\' has been approved and is now visible to tenants.', 'success', 0, '../owner/edit-property.php?id=24', '2026-05-20 14:17:23', NULL),
(902, 29, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 14:18:12', NULL),
(903, 23, 'New Rental Request', 'A tenant has requested to rent your property.', 'alert', 0, '../owner/requests.php', '2026-05-20 14:27:57', NULL),
(904, 29, 'Request Submitted', 'Your rental request has been submitted successfully.', 'success', 0, NULL, '2026-05-20 14:27:57', NULL),
(905, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 14:31:29', NULL),
(906, 29, 'Rental Request Approved', 'Your rental request for \'Luxury 5-Bedroom Family Villa with Beautiful Balcony\' has been approved.', 'success', 0, '../tenant/agreements.php', '2026-05-20 14:32:42', NULL),
(907, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 14:35:49', NULL),
(908, 4, 'New Property Added', 'New property \'Affordable Student Studio\' requires review', 'info', 0, NULL, '2026-05-20 14:40:58', NULL),
(909, 29, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 14:41:30', NULL),
(910, 23, 'New Rental Request', 'A tenant has requested to rent your property.', 'alert', 0, '../owner/requests.php', '2026-05-20 14:47:24', NULL),
(911, 29, 'Request Submitted', 'Your rental request has been submitted successfully.', 'success', 0, NULL, '2026-05-20 14:47:24', NULL),
(912, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 14:48:18', NULL),
(913, 29, 'Rental Request Approved', 'Your rental request for \'Modern House\' has been approved.', 'success', 0, '../tenant/agreements.php', '2026-05-20 14:49:31', NULL),
(914, 29, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 14:49:47', NULL),
(915, 29, 'Deposit Successful', 'Your deposit of ETB 250,000.00 has been completed successfully.', 'success', 0, '../tenant/payments.php', '2026-05-20 14:50:55', NULL),
(916, 1, 'Commission Earned', 'You have earned ETB 21,600.00 commission for wallet payment ID #31.', 'success', 0, '../admin/payments.php', '2026-05-20 14:53:02', NULL),
(917, 23, 'New payment received (Paid)', 'A new payment of ETB 189,000.00 has been paid via wallet for agreement #6 (Base rent: ETB 180,000.00, you will receive ETB 167,400.00 after 7% commission).', 'info', 0, '../owner/payments.php', '2026-05-20 14:53:31', NULL),
(918, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 14:55:07', NULL),
(919, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 15:01:08', NULL),
(920, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 16:50:55', NULL),
(921, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 17:13:40', NULL),
(922, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 17:22:27', NULL),
(923, 20, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 17:23:23', NULL),
(924, 29, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 17:34:42', NULL),
(925, 29, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 17:35:08', NULL),
(926, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 17:35:22', NULL),
(927, 4, 'New Property Added', 'New property \'Executive Modern Residence\' requires review', 'info', 0, NULL, '2026-05-20 17:38:57', NULL),
(928, 4, 'New Property Added', 'New property \'Fully Finished 3-Bedroom Condominium Unit\' requires review', 'info', 0, NULL, '2026-05-20 17:46:09', NULL),
(929, 29, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 17:47:46', NULL),
(930, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 17:48:06', NULL),
(931, 23, 'Property Approved', 'Your property \'Fully Finished 3-Bedroom Condominium Unit\' has been approved and is now visible to tenants.', 'success', 0, '../owner/edit-property.php?id=27', '2026-05-20 17:48:49', NULL),
(932, 23, 'Property Approved', 'Your property \'Affordable Student Studio\' has been approved and is now visible to tenants.', 'success', 0, '../owner/edit-property.php?id=25', '2026-05-20 17:48:54', NULL),
(933, 23, 'Property Approved', 'Your property \'Executive Modern Residence\' has been approved and is now visible to tenants.', 'success', 0, '../owner/edit-property.php?id=26', '2026-05-20 17:49:06', NULL),
(934, 29, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 17:49:16', NULL),
(935, 23, 'New Rental Request', 'A tenant has requested to rent your property.', 'alert', 0, '../owner/requests.php', '2026-05-20 17:51:27', NULL),
(936, 29, 'Request Submitted', 'Your rental request has been submitted successfully.', 'success', 0, NULL, '2026-05-20 17:51:27', NULL),
(937, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 17:52:01', NULL),
(938, 29, 'Rental Request Approved', 'Your rental request for \'Fully Finished 3-Bedroom Condominium Unit\' has been approved.', 'success', 0, '../tenant/agreements.php', '2026-05-20 17:52:24', NULL),
(939, 29, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 17:53:21', NULL),
(940, 23, 'New Rental Request', 'A tenant has requested to rent your property.', 'alert', 0, '../owner/requests.php', '2026-05-20 17:55:14', NULL),
(941, 29, 'Request Submitted', 'Your rental request has been submitted successfully.', 'success', 0, NULL, '2026-05-20 17:55:14', NULL),
(942, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 17:55:22', NULL),
(943, 29, 'Rental Request Approved', 'Your rental request for \'Executive Modern Residence\' has been approved.', 'success', 0, '../tenant/agreements.php', '2026-05-20 17:55:39', NULL),
(944, 29, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 17:56:36', NULL),
(945, 1, 'Commission Earned', 'You have earned ETB 3,600.00 commission for wallet payment ID #32.', 'success', 0, '../admin/payments.php', '2026-05-20 17:56:52', NULL),
(946, 23, 'New payment received (Paid)', 'A new payment of ETB 31,500.00 has been paid via wallet for agreement #6 (Base rent: ETB 30,000.00, you will receive ETB 27,900.00 after 7% commission).', 'info', 0, '../owner/payments.php', '2026-05-20 17:57:02', NULL),
(947, 29, 'Deposit Successful', 'Your deposit of ETB 5,000.00 has been completed successfully.', 'success', 0, '../tenant/payments.php', '2026-05-20 17:57:54', NULL),
(948, 30, 'Welcome to Aksum House Rental System!', 'Your account has been created successfully. Please check your email to verify your account.', 'success', 0, NULL, '2026-05-20 18:00:48', NULL),
(949, 30, 'Email Verified!', 'Your email has been successfully verified. You can now log in to your account.', 'success', 0, NULL, '2026-05-20 18:01:32', NULL),
(950, 30, 'ኢሜል ተረጋግጧል!', 'ኢሜልዎ በትክክል ተረጋግጧል። አሁን ወደ መለያዎ መግባት ይችላሉ።', 'success', 0, NULL, '2026-05-20 18:01:32', NULL),
(951, 30, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 18:02:02', NULL),
(952, 30, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 18:03:15', NULL),
(953, 23, 'New Rental Request', 'A tenant has requested to rent your property.', 'alert', 0, '../owner/requests.php', '2026-05-20 18:04:18', NULL),
(954, 30, 'Request Submitted', 'Your rental request has been submitted successfully.', 'success', 0, NULL, '2026-05-20 18:04:18', NULL),
(955, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 18:04:36', NULL),
(956, 30, 'Rental Request Approved', 'Your rental request for \'Affordable Student Studio\' has been approved.', 'success', 0, '../tenant/agreements.php', '2026-05-20 18:05:01', NULL),
(957, 30, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 18:06:13', NULL),
(958, 30, 'Deposit Successful', 'Your deposit of ETB 99,999,999.99 has been completed successfully.', 'success', 0, '../tenant/payments.php', '2026-05-20 18:07:24', NULL),
(959, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 18:16:16', NULL),
(960, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 18:24:26', NULL),
(961, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 18:27:06', NULL),
(962, 30, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 18:42:20', NULL),
(963, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 18:45:51', NULL),
(964, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 18:49:05', NULL),
(965, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 18:49:37', NULL),
(966, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 18:49:43', NULL),
(967, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 18:49:59', NULL),
(968, 31, 'Welcome to Aksum House Rental System!', 'Your account has been created successfully. Please check your email to verify your account.', 'success', 0, NULL, '2026-05-20 18:58:09', NULL),
(969, 31, 'Email Verified!', 'Your email has been successfully verified. You can now log in to your account.', 'success', 0, NULL, '2026-05-20 18:58:37', NULL),
(970, 31, 'ኢሜል ተረጋግጧል!', 'ኢሜልዎ በትክክል ተረጋግጧል። አሁን ወደ መለያዎ መግባት ይችላሉ።', 'success', 0, NULL, '2026-05-20 18:58:37', NULL),
(971, 31, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 18:58:56', NULL),
(972, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 18:59:34', NULL),
(973, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 19:03:28', NULL),
(974, 31, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 19:04:51', NULL),
(975, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 19:12:44', NULL),
(976, 24, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 19:13:39', NULL),
(977, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 19:50:48', NULL),
(978, 24, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 20:18:42', NULL),
(979, 4, 'New Property Added', 'New property \'Prime Commercial Space Near Hospital\' requires review', 'info', 0, NULL, '2026-05-20 20:31:09', NULL),
(980, 24, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 20:33:37', NULL),
(981, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 20:33:52', NULL),
(982, 24, 'Property Approved', 'Your property \'Prime Commercial Space Near Hospital\' has been approved and is now visible to tenants.', 'success', 0, '../owner/edit-property.php?id=28', '2026-05-20 20:34:11', NULL),
(983, 31, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 20:34:59', NULL),
(984, 24, 'New Rental Request', 'A tenant has requested to rent your property.', 'alert', 0, '../owner/requests.php', '2026-05-20 20:37:30', NULL),
(985, 31, 'Request Submitted', 'Your rental request has been submitted successfully.', 'success', 0, NULL, '2026-05-20 20:37:30', NULL),
(986, 24, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 20:38:23', NULL),
(987, 31, 'Rental Request Approved', 'Your rental request for \'Prime Commercial Space Near Hospital\' has been approved.', 'success', 0, '../tenant/agreements.php', '2026-05-20 20:38:34', NULL),
(988, 31, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 20:41:15', NULL),
(989, 31, 'Deposit Successful', 'Your deposit of ETB 100,000.00 has been completed successfully.', 'success', 0, '../tenant/payments.php', '2026-05-20 20:47:40', NULL),
(990, 1, 'Commission Earned', 'You have earned ETB 600.00 commission for wallet payment ID #36.', 'success', 0, '../admin/payments.php', '2026-05-20 20:53:46', NULL),
(991, 24, 'New payment received (Paid)', 'A new payment of ETB 5,250.00 has been paid via wallet for agreement #11 (Base rent: ETB 5,000.00, you will receive ETB 4,650.00 after 7% commission).', 'info', 0, '../owner/payments.php', '2026-05-20 20:53:55', NULL),
(992, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-20 22:53:11', NULL),
(993, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 01:25:35', NULL),
(994, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 02:35:12', NULL),
(995, 29, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 02:36:23', NULL),
(996, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 02:37:02', NULL),
(997, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 02:37:15', NULL),
(998, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 05:19:31', NULL),
(999, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 05:22:13', NULL),
(1000, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 05:28:03', NULL),
(1001, 29, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 05:46:03', NULL),
(1002, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 05:51:07', NULL),
(1003, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 06:20:53', NULL),
(1004, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 06:36:44', NULL),
(1005, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 06:50:48', NULL),
(1006, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 07:05:18', NULL),
(1007, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 07:11:59', NULL),
(1008, 29, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 07:21:48', NULL),
(1009, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 07:55:38', NULL),
(1010, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 08:10:12', NULL),
(1011, 20, 'title', 'Hello Every one', 'info', 0, '', '2026-05-21 08:35:51', NULL),
(1012, 21, 'title', 'Hello Every one', 'info', 0, '', '2026-05-21 08:35:51', NULL),
(1013, 29, 'title', 'Hello Every one', 'info', 0, '', '2026-05-21 08:35:51', NULL),
(1014, 31, 'title', 'Hello Every one', 'info', 0, '', '2026-05-21 08:35:51', NULL),
(1015, 32, 'title', 'Hello Every one', 'info', 0, '', '2026-05-21 08:35:51', NULL),
(1016, 23, 'title', 'Hello Every one', 'info', 0, '', '2026-05-21 08:35:51', NULL),
(1017, 24, 'title', 'Hello Every one', 'info', 0, '', '2026-05-21 08:35:51', NULL),
(1018, 4, 'title', 'Hello Every one', 'info', 0, '', '2026-05-21 08:35:51', NULL),
(1019, 1, 'title', 'Hello Every one', 'info', 0, '', '2026-05-21 08:35:51', NULL),
(1020, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 08:37:24', NULL),
(1021, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 09:47:44', NULL),
(1022, 20, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 10:24:24', NULL),
(1023, 20, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 10:30:26', NULL),
(1024, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 11:08:44', NULL),
(1025, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 11:14:19', NULL),
(1026, 23, 'Password Changed', 'Your password has been successfully changed.', 'success', 0, NULL, '2026-05-21 11:47:21', NULL),
(1027, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 11:49:17', NULL),
(1028, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 11:50:53', NULL),
(1029, 20, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 11:51:57', NULL),
(1030, 1, 'Commission Earned', 'You have earned ETB 960.00 commission for wallet payment ID #37.', 'success', 1, '../admin/payments.php', '2026-05-21 11:53:11', NULL),
(1031, 23, 'New payment received (Paid)', 'A new payment of ETB 8,400.00 has been paid via wallet for agreement #4 (Base rent: ETB 8,000.00, you will receive ETB 7,440.00 after 7% commission).', 'info', 0, '../owner/payments.php', '2026-05-21 11:53:20', NULL),
(1032, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 11:58:29', NULL),
(1033, 36, 'Welcome to Aksum House Rental System!', 'Your account has been created successfully. Please check your email to verify your account.', 'success', 0, NULL, '2026-05-21 12:46:45', NULL),
(1034, 36, 'Email Verified!', 'Your email has been successfully verified. You can now log in to your account.', 'success', 0, NULL, '2026-05-21 12:47:10', NULL),
(1035, 36, 'ኢሜል ተረጋግጧል!', 'ኢሜልዎ በትክክል ተረጋግጧል። አሁን ወደ መለያዎ መግባት ይችላሉ።', 'success', 0, NULL, '2026-05-21 12:47:10', NULL),
(1036, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 12:50:45', NULL),
(1037, 36, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 12:51:17', NULL),
(1038, 20, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 12:51:38', NULL),
(1039, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 12:52:11', NULL),
(1040, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 12:53:48', NULL),
(1041, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 12:54:37', NULL),
(1042, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 12:55:42', NULL),
(1043, 23, 'Password Changed', 'Your password has been successfully changed.', 'success', 0, NULL, '2026-05-21 12:56:50', NULL),
(1044, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 12:57:29', NULL),
(1045, 31, 'Password Changed', 'Your password has been successfully changed.', 'success', 0, NULL, '2026-05-21 12:59:19', NULL),
(1046, 31, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 13:02:12', NULL),
(1047, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 13:05:19', NULL),
(1048, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 13:40:26', NULL),
(1049, 31, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 13:55:34', NULL),
(1050, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:00:18', NULL),
(1051, 20, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:04:45', NULL),
(1052, 23, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:07:57', NULL),
(1053, 36, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:08:17', NULL),
(1054, 20, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:13:11', NULL),
(1055, 20, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:14:31', NULL),
(1056, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:15:28', NULL),
(1057, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:16:28', NULL),
(1058, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:16:36', NULL),
(1059, 36, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:18:04', NULL),
(1060, 20, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:25:46', NULL),
(1061, 20, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:32:09', NULL),
(1062, 36, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:34:39', NULL),
(1063, 36, 'Password Changed', 'Your password has been successfully changed.', 'success', 0, NULL, '2026-05-21 14:35:50', NULL),
(1064, 20, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:36:04', NULL),
(1065, 36, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:44:07', NULL),
(1066, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:46:08', NULL),
(1067, 36, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:47:35', NULL),
(1068, 20, 'Deposit Successful', 'Your deposit of ETB 50,000.00 has been completed successfully.', 'success', 0, '../tenant/payments.php', '2026-05-21 14:48:39', NULL),
(1069, 20, 'Deposit Successful', 'Your deposit of ETB 210,000.00 has been completed successfully.', 'success', 0, '../tenant/payments.php', '2026-05-21 14:50:55', NULL),
(1070, 4, 'New Property Added', 'New property \'Affordable 2-Bedroom Apartment\' requires review', 'info', 0, NULL, '2026-05-21 14:51:14', NULL),
(1071, 1, 'Commission Earned', 'You have earned ETB 28,800.00 commission for wallet payment ID #40.', 'success', 0, '../admin/payments.php', '2026-05-21 14:51:22', NULL),
(1072, 23, 'New payment received (Paid)', 'A new payment of ETB 252,000.00 has been paid via wallet for agreement #4 (Base rent: ETB 240,000.00, you will receive ETB 223,200.00 after 7% commission).', 'info', 0, '../owner/payments.php', '2026-05-21 14:51:29', NULL),
(1073, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:51:35', NULL),
(1074, 36, 'Property Approved', 'Your property \'Affordable 2-Bedroom Apartment\' has been approved and is now visible to tenants.', 'success', 0, '../owner/edit-property.php?id=29', '2026-05-21 14:52:01', NULL),
(1075, 36, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:52:26', NULL),
(1076, 20, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:57:17', NULL),
(1077, 31, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 14:58:03', NULL),
(1078, 4, 'New Property Added', 'New property \'High-End 3-Bedroom Penthouse with Panoramic Views\' requires review', 'info', 0, NULL, '2026-05-21 15:06:07', NULL),
(1079, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 15:06:19', NULL),
(1080, 36, 'Property Approved', 'Your property \'High-End 3-Bedroom Penthouse with Panoramic Views\' has been approved and is now visible to tenants.', 'success', 0, '../owner/edit-property.php?id=30', '2026-05-21 15:06:31', NULL),
(1081, 36, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 15:06:41', NULL),
(1082, 36, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 16:46:17', NULL),
(1083, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 16:52:54', NULL),
(1084, 1, 'Password Changed', 'Your password has been successfully changed.', 'success', 0, NULL, '2026-05-21 17:05:21', NULL),
(1085, 1, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 17:05:33', NULL),
(1086, 4, 'New Property Added', 'New property \'3-Bedroom House Near Historic Center\' requires review', 'info', 0, NULL, '2026-05-21 17:06:46', NULL),
(1087, 40, 'Welcome to Aksum House Rental System!', 'Your account has been created successfully. Please check your email to verify your account.', 'success', 0, NULL, '2026-05-21 17:06:55', NULL),
(1088, 4, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 17:07:08', NULL),
(1089, 36, 'Property Approved', 'Your property \'3-Bedroom House Near Historic Center\' has been approved and is now visible to tenants.', 'success', 0, '../owner/edit-property.php?id=31', '2026-05-21 17:07:20', NULL),
(1090, 40, 'Email Verified!', 'Your email has been successfully verified. You can now log in to your account.', 'success', 0, NULL, '2026-05-21 17:07:29', NULL),
(1091, 40, 'ኢሜል ተረጋግጧል!', 'ኢሜልዎ በትክክል ተረጋግጧል። አሁን ወደ መለያዎ መግባት ይችላሉ።', 'success', 0, NULL, '2026-05-21 17:07:29', NULL),
(1092, 36, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 17:07:29', NULL),
(1093, 40, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 17:07:41', NULL),
(1094, 40, 'Password Changed', 'Your password has been successfully changed.', 'success', 0, NULL, '2026-05-21 17:08:42', NULL),
(1095, 40, 'Welcome Back!', 'You have successfully logged in.', 'success', 0, NULL, '2026-05-21 17:08:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notification_templates`
--

CREATE TABLE `notification_templates` (
  `template_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'info',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`token_id`, `user_id`, `token`, `email`, `expires_at`, `used`, `created_at`) VALUES
(3, 22, '3a3b301e4a27020810f41adf9c37f7476b2e9fbdb772edb9cbc2b9800975e3b1', 'jyking029@gmail.com', '2026-03-26 12:53:42', 1, '2026-03-26 12:52:40'),
(4, 20, '7e2528d6bdb6d616ea25ee6ebea4754b00029d912d7f816e040abad56bd990e6', 'yeshujoy366@gmail.com', '2026-03-26 13:59:43', 1, '2026-03-26 13:58:38'),
(5, 4, '8d8827162ad82eba031c14b13a543d08b9e85bf8406f2f34264d6087c966b35f', 'teweldeananya24@gmail.com', '2026-05-08 19:38:53', 1, '2026-05-08 19:33:49'),
(6, 1, '76991e7ecccb1660fef6b989643a7e8aac0ec6fa7dbe23e67156efe1c144df22', 'hagomedhanye85@gmail.com', '2026-05-08 19:43:05', 1, '2026-05-08 19:41:54'),
(7, 32, 'e3fcb6e5fa18dedef81fbbed7bd8a0379a7e9a71d85e08706ebe298bd8e0e9b5', 'tgg.ananya@gmail.com', '2026-05-22 08:26:05', 0, '2026-05-21 08:26:05'),
(8, 33, '070ee126b9e25143667bbdf2076f949221c025241c5d3af256a90fff4cea4b39', 'fgfgdfg@gmail.com', '2026-05-22 08:29:28', 0, '2026-05-21 08:29:28'),
(15, 23, '35a984fb4b356849a0f2b0c6195560ce26a437ee34d382d0bce80ccfe3dd328c', 'jyking029@gmail.com', '2026-05-21 11:47:21', 1, '2026-05-21 11:44:30'),
(16, 34, '428fa6d791cd1c3642a1bdb66842cac77fa490fd596ce7e8a6ae684147f8d5e6', 'hfdejr@gmail.com', '2026-05-22 12:01:05', 0, '2026-05-21 12:01:05'),
(17, 35, '24e1553082ed35d6bbfec6afc38e72324eaae21e77faf5890044c9302d5def53', '2332@gmail.com', '2026-05-22 12:03:02', 0, '2026-05-21 12:03:02'),
(19, 23, 'e705109f0e6b4f93ae469d987123dff4d569112c3577a5159fbf66bbe0a10823', 'jyking029@gmail.com', '2026-05-21 12:56:49', 1, '2026-05-21 12:55:10'),
(20, 31, '4083aca18dee2543c51cce325a9f6d45d35d6737762ac05cf87678b101d07eda', 'kinfemuley0@gmail.com', '2026-05-21 12:59:19', 1, '2026-05-21 12:58:03'),
(23, 20, '03c3ef9b161bdcb0f72affe3995698cc5673cc2da9f4bb5cbd127f02d2d3609a', 'yeshujoy366@gmail.com', '2026-05-21 15:31:11', 0, '2026-05-21 14:31:11'),
(24, 24, 'e9029c2d77efecaef81bdc1dc2ba281bff282a78074f33140273186fc78a12a5', 'slatek0724@gmail.com', '2026-05-21 15:33:57', 0, '2026-05-21 14:33:57'),
(25, 36, 'd3e494f881a1f3ab245d61e1857eb6ddcb7647c5ce8efc6e23aaadd08a5b80a4', 'wmedaa27@gmail.com', '2026-05-21 14:35:50', 1, '2026-05-21 14:34:53'),
(26, 24, '487eecb1052d02294fc027b8ef9f310c098bf24bce988ba4f5eff3fee0861f32', 'slatek0724@gmail.com', '2026-05-21 17:54:59', 0, '2026-05-21 16:54:59'),
(27, 1, '88ea775822b3373802b0642c1886054d5a80464ca358e859d0b5f27a8f57ba78', 'hagomedhanye85@gmail.com', '2026-05-21 17:05:21', 1, '2026-05-21 17:02:45'),
(28, 40, '9002340de8862b791487483f6647f4773b95faa934232451a9d2435a69ad0d1a', 'medahg27@gmail.com', '2026-05-21 17:08:42', 1, '2026-05-21 17:07:59');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `agreement_id` int(11) DEFAULT NULL,
  `tenant_id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `balance_remaining` decimal(10,2) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_for` enum('advance','rent','deposit','utility','penalty','other') NOT NULL,
  `payment_type` enum('FULL','MINIMUM','MONTHLY','deposit') DEFAULT 'MONTHLY',
  `month_year` varchar(7) DEFAULT NULL,
  `payment_period` varchar(20) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `verification_code` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `gateway_response` text DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'pending',
  `payment_status` enum('Pending','Verified','Failed','Cancelled','Completed') DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `callback_received` tinyint(1) DEFAULT 0,
  `callback_data` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `agreement_id`, `tenant_id`, `property_id`, `amount`, `total_amount`, `amount_paid`, `balance_remaining`, `payment_date`, `payment_method`, `payment_for`, `payment_type`, `month_year`, `payment_period`, `transaction_id`, `transaction_reference`, `verification_code`, `notes`, `gateway_response`, `receipt_number`, `status`, `payment_status`, `verified_by`, `verified_at`, `callback_received`, `callback_data`, `created_at`) VALUES
(28, NULL, 21, NULL, 10000.00, 10000.00, 10000.00, 0.00, '2026-05-20', 'cbe', 'deposit', 'deposit', NULL, NULL, 'SIM_1779239196_8837', NULL, NULL, '', NULL, NULL, 'completed', 'Completed', NULL, NULL, 0, NULL, '2026-05-20 01:06:29'),
(29, 5, 21, 22, 4200.00, 4000.00, 4000.00, 0.00, '2026-05-20', 'wallet', 'rent', 'MONTHLY', NULL, NULL, '', NULL, NULL, '', NULL, NULL, 'completed', 'Verified', NULL, NULL, 0, NULL, '2026-05-20 01:13:17'),
(30, NULL, 29, NULL, 250000.00, 250000.00, 250000.00, 0.00, '2026-05-20', 'cbe', 'deposit', 'deposit', NULL, NULL, 'SIM_1779288655_3008', NULL, NULL, 'for payment', NULL, NULL, 'completed', 'Completed', NULL, NULL, 0, NULL, '2026-05-20 14:50:52'),
(31, 6, 29, 24, 189000.00, 180000.00, 180000.00, 0.00, '2026-05-20', 'wallet', 'rent', 'FULL', NULL, NULL, '', NULL, NULL, '', NULL, NULL, 'completed', 'Verified', NULL, NULL, 0, NULL, '2026-05-20 14:53:02'),
(32, 6, 29, 24, 31500.00, 30000.00, 30000.00, 0.00, '2026-05-20', 'wallet', 'rent', 'MONTHLY', NULL, NULL, '', NULL, NULL, '', NULL, NULL, 'completed', 'Verified', NULL, NULL, 0, NULL, '2026-05-20 17:56:52'),
(33, NULL, 29, NULL, 5000.00, 5000.00, 5000.00, 0.00, '2026-05-20', 'telebirr', 'deposit', 'deposit', NULL, NULL, 'SIM_1779299874_7934', NULL, NULL, '', NULL, NULL, 'completed', 'Completed', NULL, NULL, 0, NULL, '2026-05-20 17:57:51'),
(34, NULL, 30, NULL, 99999999.99, 99999999.99, 99999999.99, 0.00, '2026-05-20', 'telebirr', 'deposit', 'deposit', NULL, NULL, 'SIM_1779300444_6660', NULL, NULL, 'Dgskksxnjdhdbekk', NULL, NULL, 'completed', 'Completed', NULL, NULL, 0, NULL, '2026-05-20 18:07:21'),
(35, NULL, 31, NULL, 100000.00, 100000.00, 100000.00, 0.00, '2026-05-20', 'cbe', 'deposit', 'deposit', NULL, NULL, 'SIM_1779310059_2829', NULL, NULL, '', NULL, NULL, 'completed', 'Completed', NULL, NULL, 0, NULL, '2026-05-20 20:47:37'),
(36, 11, 31, 28, 5250.00, 25000.00, 5000.00, 20000.00, '2026-05-20', 'wallet', 'rent', 'MINIMUM', NULL, NULL, '', NULL, NULL, '', NULL, NULL, 'completed', 'Verified', NULL, NULL, 0, NULL, '2026-05-20 20:53:46'),
(37, 4, 20, 21, 8400.00, 40000.00, 8000.00, 32000.00, '2026-05-21', 'wallet', 'rent', 'MINIMUM', NULL, NULL, '', NULL, NULL, '', NULL, NULL, 'completed', 'Verified', NULL, NULL, 0, NULL, '2026-05-21 11:53:11'),
(38, NULL, 20, NULL, 50000.00, 50000.00, 50000.00, 0.00, '2026-05-21', 'cbe', 'deposit', 'deposit', NULL, NULL, 'SIM_1779374919_6379', NULL, NULL, '', NULL, NULL, 'completed', 'Completed', NULL, NULL, 0, NULL, '2026-05-21 14:48:36'),
(39, NULL, 20, NULL, 210000.00, 210000.00, 210000.00, 0.00, '2026-05-21', 'cbe', 'deposit', 'deposit', NULL, NULL, 'SIM_1779375054_3969', NULL, NULL, '', NULL, NULL, 'completed', 'Completed', NULL, NULL, 0, NULL, '2026-05-21 14:50:53'),
(40, 4, 20, 21, 252000.00, 240000.00, 240000.00, 0.00, '2026-05-21', 'wallet', 'rent', 'FULL', NULL, NULL, '', NULL, NULL, '', NULL, NULL, 'completed', 'Verified', NULL, NULL, 0, NULL, '2026-05-21 14:51:22');

-- --------------------------------------------------------

--
-- Table structure for table `payment_audit_log`
--

CREATE TABLE `payment_audit_log` (
  `audit_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `transaction_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `transaction_reference` varchar(100) NOT NULL,
  `gateway_provider` varchar(50) NOT NULL,
  `gateway_transaction_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'ETB',
  `status` enum('initiated','processing','completed','failed','cancelled') DEFAULT 'initiated',
  `gateway_response` text DEFAULT NULL,
  `callback_url` varchar(255) DEFAULT NULL,
  `callback_received` tinyint(1) DEFAULT 0,
  `callback_data` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `property_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `property_type` enum('house','apartment','villa','condominium','commercial') NOT NULL,
  `address` text NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `subcity` varchar(100) DEFAULT NULL,
  `bedrooms` int(11) DEFAULT NULL,
  `bathrooms` int(11) DEFAULT NULL,
  `area_sqm` decimal(10,2) DEFAULT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  `security_deposit` decimal(10,2) DEFAULT NULL,
  `is_furnished` tinyint(1) DEFAULT 0,
  `amenities` text DEFAULT NULL,
  `status` enum('available','requested','rented','maintenance','unavailable') DEFAULT 'available',
  `review_status` enum('pending','approved','rejected','needs_revision') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_date` timestamp NULL DEFAULT NULL,
  `review_comments` text DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `contact_preferences` text DEFAULT NULL,
  `contact_phone` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `property_rules` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`property_id`, `owner_id`, `title`, `description`, `property_type`, `address`, `location_id`, `subcity`, `bedrooms`, `bathrooms`, `area_sqm`, `monthly_rent`, `security_deposit`, `is_furnished`, `amenities`, `status`, `review_status`, `reviewed_by`, `review_date`, `review_comments`, `featured`, `latitude`, `longitude`, `contact_preferences`, `contact_phone`, `contact_email`, `property_rules`, `created_at`, `updated_at`) VALUES
(21, 23, 'Newly Built House Royalty', 'The home exudes a sophisticated yet inviting atmosphere. The use of natural materials like wood and the presence of vibrant greenery soften the sharp edges of the modern architecture, making it feel like a tranquil suburban retreat.', 'house', 'Medre Genet Area, Near the Airport Road', 9, NULL, 2, 3, 7.00, 40000.00, 3000.00, 0, 'High-speed WiFi, Tiled Floors, Close to Transport, Shared Laundry', 'rented', 'approved', 4, '2026-05-08 19:10:47', '', 1, 14.21646376, 38.72845324, 'phone,email,chat', '0987654652', 'jking029@gmail.com', '[{\"title\":\"No smoking\",\"description\":\"you can smoke but outside the home\"}]', '2026-05-08 18:42:19', '2026-05-21 14:57:36'),
(22, 23, 'Smart Student Rental Home', 'Smart Student Rental Home offers a comfortable, safe, and affordable living space designed especially for students. Located in a convenient and quiet area, the property provides a study-friendly environment with modern rooms, reliable utilities, and easy access to universities, transportation, shops, and essential services. Ideal for students seeking both comfort and convenience.', 'house', 'Aksum University, A2;B30, Axum, Central Tigray, Tigray, Ethiopia', 4, 'Educational', 2, 1, 60.00, 4000.00, 2000.00, 0, 'WiFi, Water Supply, Electricity, Shared Kitchen, Study Area, Security, Parking', 'rented', 'approved', 4, '2026-05-19 23:15:13', 'accepted', 0, 14.11442669, 38.71489307, 'phone,email,chat', '099661131', 'jyking029@gmail.com', '[{\"title\":\"Maintain cleanliness Respect quiet study environment No smoking inside rooms Pay rent on time Avoid damaging property\",\"description\":\"\"}]', '2026-05-19 22:45:19', '2026-05-21 10:27:11'),
(23, 23, 'Modern House', 'Perfect for young professionals or a small family. This top-floor apartment offers incredible city views, high-quality finishes, and is just a short walk away from local cafes, shops, and transport hubs.', 'house', 'Aksum University, A2;B30, Axum, Central Tigray, Tigray, Ethiopia', 4, 'Educational', 2, 1, 75.00, 18000.00, 3000.00, 0, 'High-speed WiFi, Secure Parking, Water Tank, Balcony, Gated Security', 'rented', 'approved', NULL, NULL, NULL, 0, 14.10597080, 38.70671370, 'phone,email,chat', '0968931862', 'jyking029@gmail.com', '[{\"title\":\"smoking is not allowed\",\"description\":\"\"}]', '2026-05-20 13:52:20', '2026-05-20 14:49:31'),
(24, 23, 'Luxury 5-Bedroom Family Villa with Beautiful Balcony', 'A stunning, modern multi-family villa located in a peaceful residential neighborhood. Features massive rooms, plenty of natural light, a modern kitchen layout, and an expansive compound perfect for family gatherings or parking multiple vehicles.', 'house', 'King Bazen\'s Tomb, Airport Street, Axum, Central Tigray, Tigray, Ethiopia', 9, NULL, 5, 3, 120.00, 30000.00, 5000.00, 0, 'Balcony, Reliable Water, Paved Access, Secure Entrance', 'rented', 'approved', 4, '2026-05-20 14:17:23', '', 1, 14.12516620, 38.72604670, 'phone,email,chat', '0968931862', 'hagomedhanye85@gmail.com', '[{\"title\":\"No smoking\",\"description\":\"you can smoke but outside the home\"}]', '2026-05-20 14:16:27', '2026-05-20 14:32:42'),
(26, 23, 'Executive Modern Residence', 'Premium executive home boasting high ceilings, Master bedroom with ensuite bathroom, built-in wardrobes, a separate maid\'s quarter, and an automated secure entrance gate.', 'house', 'Airport Street, Axum, Central Tigray, Tigray, Ethiopia', 9, NULL, 4, 3, 160.00, 35000.00, 7000.00, 1, 'Balcony, Reliable Water, Paved Access, Secure Entrance, Parking, Water Pump, Modern Kitchen', 'rented', 'approved', 4, '2026-05-20 17:49:06', '', 0, 14.12494380, 38.73930560, NULL, '', '', NULL, '2026-05-20 17:38:57', '2026-05-20 17:55:39'),
(27, 23, 'Fully Finished 3-Bedroom Condominium Unit', 'A beautifully renovated 3-bedroom condo located in a highly active and friendly neighborhood. Comes with an updated bathroom, dedicated kitchen spaces, and excellent natural ventilation.', 'house', 'New Cathedral of Our Lady Mary of Zion, Center Market, Axum, Central Tigray, Tigray, Ethiopia', 8, 'Religious', 3, 2, 90.00, 22000.00, 4000.00, 1, 'Balcony, Reliable Water, Secure Entrance, Near Main Road', 'rented', 'approved', NULL, NULL, NULL, 0, 14.13035250, 38.71964410, NULL, '', '', NULL, '2026-05-20 17:46:09', '2026-05-21 14:11:28'),
(28, 24, 'Prime Commercial Space Near Hospital', 'Prime Commercial Space Near Hospital is a modern and strategically located business property ideal for pharmacies, clinics, offices, cafés, mini markets, laboratories, and other commercial activities. Located near a busy hospital area, the property benefits from high customer traffic, excellent accessibility, and a secure environment. The building offers spacious rooms, reliable utilities, parking access, and a professional atmosphere suitable for growing businesses.', 'commercial', 'Aksum K\'Idist Maryam Hospital, Airport Street, Axum, Central Tigray, Tigray, Ethiopia', 6, NULL, 4, 2, 180.00, 25000.00, 15000.00, 0, 'Parking Area, Water Supply, Electricity, CCTV Security, Waiting Area, Storage Room, Backup Generator', 'rented', 'approved', 4, '2026-05-20 20:34:11', 'accepted', 0, 14.12034340, 38.72855370, 'phone,email,chat', '0986548096', 'slatek0724@gmail.com', '[{\"title\":\"No illegal activities allowed ,Rent payment must be on time\",\"description\":\"\"}]', '2026-05-20 20:31:08', '2026-05-20 20:38:34'),
(29, 36, 'Affordable 2-Bedroom Apartment', 'Ideal for small families or students sharing rent. This cozy unit offers practical living spaces, bright rooms, and a low-maintenance layout close to public transport lines.', 'apartment', 'aksum university', 4, 'Educational', 2, 1, 65.00, 14000.00, 2500.00, 0, 'Reliable Water, Secure Entrance, Paved Access', 'available', 'approved', NULL, NULL, NULL, 1, 14.12110000, 38.72410000, NULL, NULL, NULL, '[{\"title\":\"smoking is strictly prohibited,payment must be on time\",\"description\":\"\"}]', '2026-05-21 14:51:14', '2026-05-21 15:08:36'),
(30, 36, 'High-End 3-Bedroom Penthouse with Panoramic Views', 'Exquisite top-floor residence featuring premium tile flooring, floor-to-ceiling windows, an expansive private terrace, and high-end modern bathroom fixtures.', 'condominium', 'New Cathedral of Our Lady Mary of Zion, Center Market, Axum, Central Tigray, Tigray, Ethiopia', 11, 'Aksum Zion Area', 3, 3, 150.00, 50000.00, 15000.00, 1, 'Balcony, Reliable Water, Paved Access, Secure Entrance, City View, Elevator Access, Dedicated Parking', 'available', 'approved', NULL, NULL, NULL, 0, 14.13035250, 38.71964410, NULL, NULL, NULL, '[{\"title\":\"smoking is strictly prohibited,payment must be on time\",\"description\":\"\"}]', '2026-05-21 15:06:06', '2026-05-21 16:51:04'),
(31, 36, '3-Bedroom House Near Historic Center', 'A well-maintained stand-alone house blending traditional charm with modern interior updates. Features a small private front courtyard and excellent security.', 'villa', 'aksum zion', 11, NULL, 3, 2, 95.00, 19500.00, 4000.00, 0, 'Secure Entrance, Reliable Water, Courtyard, Near Tourist Sites', 'available', 'approved', 4, '2026-05-21 17:07:20', '', 1, 14.12110000, 38.72410000, NULL, NULL, NULL, '[{\"title\":\"smoking is not allowed,payment must be on time\",\"description\":\"\"}]', '2026-05-21 17:06:46', '2026-05-21 17:07:20');

-- --------------------------------------------------------

--
-- Table structure for table `property_activity_log`
--

CREATE TABLE `property_activity_log` (
  `log_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_images`
--

CREATE TABLE `property_images` (
  `image_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `property_images`
--

INSERT INTO `property_images` (`image_id`, `property_id`, `image_url`, `is_primary`, `uploaded_at`) VALUES
(1, 1, '../assets/uploads/properties/property_1_1_1769981193.jpg', 0, '2026-01-26 20:25:08'),
(2, 2, '../assets/uploads/properties/property_2_1_1769981735.jpg', 0, '2026-01-26 20:25:08'),
(3, 3, '../assets/uploads/properties/property_3_1_1769981771.jpg', 0, '2026-01-26 20:25:08'),
(4, 4, '../assets/uploads/properties/property_4_1_1769981787.jpg', 0, '2026-01-26 20:25:08'),
(5, 5, '../assets/uploads/properties/property_5_1_1769981799.jpg', 0, '2026-01-26 20:25:08'),
(6, 6, '../assets/uploads/properties/property_6_1_1769978681.jpg', 0, '2026-02-01 20:44:41'),
(7, 6, '../assets/uploads/properties/property_6_1_1769978681.jpg', 1, '2026-02-01 20:45:20'),
(8, 1, '../assets/uploads/properties/property_1_1_1769981193.jpg', 1, '2026-02-01 21:26:33'),
(9, 2, '../assets/uploads/properties/property_2_1_1769981735.jpg', 1, '2026-02-01 21:35:35'),
(10, 3, '../assets/uploads/properties/property_3_1_1769981771.jpg', 1, '2026-02-01 21:36:11'),
(11, 4, '../assets/uploads/properties/property_4_1_1769981787.jpg', 1, '2026-02-01 21:36:27'),
(12, 5, '../assets/uploads/properties/property_5_1_1769981799.jpg', 1, '2026-02-01 21:36:39'),
(13, 7, '../assets/uploads/properties/property_7_1_1770362625.webp', 1, '2026-02-06 07:23:45'),
(14, 8, '../assets/uploads/properties/property_8_1_1770362755.jpg', 1, '2026-02-06 07:25:55'),
(15, 9, '../assets/uploads/properties/property_9_1_1770366364.jpg', 1, '2026-02-06 08:26:04'),
(16, 10, '../assets/uploads/properties/property_10_1_1770366444.jpg', 1, '2026-02-06 08:27:24'),
(17, 12, '../assets/uploads/properties/property_12_1_1770461071.jpg', 1, '2026-02-07 10:44:31'),
(18, 12, '../assets/uploads/properties/property_12_1_1770461071.jpg', 0, '2026-02-07 10:44:39'),
(19, 11, '../assets/uploads/properties/property_11_1_1770461143.jpg', 0, '2026-02-07 10:45:43'),
(20, 11, '../assets/uploads/properties/property_11_1_1770461143.jpg', 1, '2026-02-07 10:46:01'),
(21, 17, '../assets/uploads/properties/property_17_1_1770462508.jpg', 0, '2026-02-07 10:53:04'),
(22, 17, '../assets/uploads/properties/property_17_1_1770462508.jpg', 1, '2026-02-07 11:08:28'),
(23, 18, '../assets/uploads/properties/property_18_1_1770464062.jpg', 0, '2026-02-07 11:33:22'),
(24, 18, '../assets/uploads/properties/property_18_1_1770464062.jpg', 0, '2026-02-07 11:33:22'),
(25, 18, '../assets/uploads/properties/property_18_1_1770464062.jpg', 0, '2026-02-07 11:34:22'),
(26, 18, '../assets/uploads/properties/property_18_1_1770464062.jpg', 0, '2026-02-07 11:34:36'),
(27, 19, '../assets/uploads/properties/property_19_1770464795_0.jpg', 1, '2026-02-07 11:46:35'),
(28, 19, '../assets/uploads/properties/property_19_1770464795_0.jpg', 0, '2026-02-07 11:46:35'),
(29, 19, '../assets/uploads/properties/property_19_1770464795_0.jpg', 0, '2026-02-07 11:46:35'),
(30, 18, '../assets/uploads/properties/property_18_1_1770465411.jpg', 1, '2026-02-07 11:56:51'),
(31, 20, '../assets/uploads/properties/property_20_1774532909_0.jpg', 0, '2026-03-26 13:48:29'),
(32, 20, '../assets/uploads/properties/property_20_1774532909_1.jpg', 0, '2026-03-26 13:48:29'),
(33, 20, '../assets/uploads/properties/property_20_1774532909_2.jpg', 0, '2026-03-26 13:48:29'),
(34, 20, '../assets/uploads/properties/property_20_1774532909_3.jpg', 1, '2026-03-26 13:48:29'),
(35, 20, '../assets/uploads/properties/property_20_1774532909_4.jpg', 0, '2026-03-26 13:48:29'),
(36, 21, '../assets/uploads/properties/property_21_1778265739_0.webp', 1, '2026-05-08 18:42:19'),
(37, 21, '../assets/uploads/properties/property_21_1778265739_1.jpg', 0, '2026-05-08 18:42:19'),
(38, 21, '../assets/uploads/properties/property_21_1778265739_2.jpg', 0, '2026-05-08 18:42:19'),
(39, 22, '../assets/uploads/properties/property_22_1779230720_0.jpg', 1, '2026-05-19 22:45:20'),
(40, 22, '../assets/uploads/properties/property_22_1779230721_1.jpg', 0, '2026-05-19 22:45:21'),
(41, 22, '../assets/uploads/properties/property_22_1779230721_2.jpg', 0, '2026-05-19 22:45:21'),
(42, 22, '../assets/uploads/properties/property_22_1779230721_3.jpg', 0, '2026-05-19 22:45:21'),
(43, 22, '../assets/uploads/properties/property_22_1779230722_4.jpg', 0, '2026-05-19 22:45:22'),
(44, 23, '../assets/uploads/properties/property_23_1779285141_0.jpg', 1, '2026-05-20 13:52:21'),
(45, 23, '../assets/uploads/properties/property_23_1779285141_1.jpg', 0, '2026-05-20 13:52:21'),
(46, 23, '../assets/uploads/properties/property_23_1779285141_2.jpg', 0, '2026-05-20 13:52:21'),
(47, 24, '../assets/uploads/properties/property_24_1779286587_0.jpg', 1, '2026-05-20 14:16:27'),
(48, 24, '../assets/uploads/properties/property_24_1779286587_1.jpg', 0, '2026-05-20 14:16:27'),
(49, 25, '../assets/uploads/properties/property_25_1779288058_0.jpg', 1, '2026-05-20 14:40:58'),
(50, 25, '../assets/uploads/properties/property_25_1779288058_1.jpg', 0, '2026-05-20 14:40:58'),
(51, 25, '../assets/uploads/properties/property_25_1779288058_2.jpg', 0, '2026-05-20 14:40:58'),
(52, 26, '../assets/uploads/properties/property_26_1779298737_0.jpg', 1, '2026-05-20 17:38:57'),
(53, 26, '../assets/uploads/properties/property_26_1779298737_1.jpg', 0, '2026-05-20 17:38:57'),
(54, 26, '../assets/uploads/properties/property_26_1779298737_2.jpg', 0, '2026-05-20 17:38:57'),
(55, 27, '../assets/uploads/properties/property_27_1779299169_0.jpg', 1, '2026-05-20 17:46:09'),
(56, 27, '../assets/uploads/properties/property_27_1779299169_1.jpg', 0, '2026-05-20 17:46:09'),
(57, 27, '../assets/uploads/properties/property_27_1779299169_2.jpg', 0, '2026-05-20 17:46:09'),
(58, 28, '../assets/uploads/properties/property_28_1779309068_0.jpg', 1, '2026-05-20 20:31:08'),
(59, 28, '../assets/uploads/properties/property_28_1779309069_1.webp', 0, '2026-05-20 20:31:09'),
(60, 28, '../assets/uploads/properties/property_28_1779309069_2.webp', 0, '2026-05-20 20:31:09'),
(62, 29, '../assets/uploads/properties/property_29_1779375074_1.jpg', 0, '2026-05-21 14:51:14'),
(63, 30, '../assets/uploads/properties/property_30_1779375966_0.jpg', 1, '2026-05-21 15:06:06'),
(64, 30, '../assets/uploads/properties/property_30_1779375966_1.jpg', 0, '2026-05-21 15:06:06'),
(65, 30, '../assets/uploads/properties/property_30_1779375967_2.jpg', 0, '2026-05-21 15:06:07'),
(66, 29, '../assets/uploads/properties/property_29_1_1779376064.jpg', 1, '2026-05-21 15:07:44'),
(68, 29, '../assets/uploads/properties/property_29_3_1779376064.jpg', 0, '2026-05-21 15:07:44'),
(69, 29, '../assets/uploads/properties/property_29_4_1779376064.jpg', 0, '2026-05-21 15:07:44'),
(70, 31, '../assets/uploads/properties/property_31_1779383206_0.jpg', 1, '2026-05-21 17:06:46'),
(71, 31, '../assets/uploads/properties/property_31_1779383206_1.jpg', 0, '2026-05-21 17:06:46');

-- --------------------------------------------------------

--
-- Table structure for table `property_reviews`
--

CREATE TABLE `property_reviews` (
  `review_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `review_status` enum('pending','approved','rejected','needs_revision') NOT NULL,
  `review_comments` text DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `property_reviews`
--

INSERT INTO `property_reviews` (`review_id`, `property_id`, `employee_id`, `review_status`, `review_comments`, `rejection_reason`, `created_at`) VALUES
(1, 8, 4, 'needs_revision', 'change the image', '', '2026-02-06 07:32:39'),
(2, 7, 4, 'approved', 'it is good', '', '2026-02-06 07:33:19'),
(3, 10, 4, 'approved', 'good', '', '2026-02-06 08:34:17'),
(4, 9, 4, 'approved', '', '', '2026-02-06 08:50:41'),
(5, 8, 4, 'rejected', '', 'Inappropriate content', '2026-02-06 08:53:10'),
(6, 8, 4, 'approved', 'good job', '', '2026-02-06 09:04:31');

-- --------------------------------------------------------

--
-- Table structure for table `property_review_notifications`
--

CREATE TABLE `property_review_notifications` (
  `notification_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `notification_type` enum('approved','rejected','needs_revision') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_views`
--

CREATE TABLE `property_views` (
  `view_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rental_agreements`
--

CREATE TABLE `rental_agreements` (
  `agreement_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  `security_deposit` decimal(10,2) NOT NULL,
  `advance_payment` decimal(10,2) NOT NULL,
  `agreement_document` varchar(255) DEFAULT NULL,
  `status` enum('active','terminated','expired','pending','partially_paid') DEFAULT 'pending',
  `payment_deadline` datetime DEFAULT NULL,
  `signed_by_tenant` tinyint(1) DEFAULT 0,
  `signed_by_owner` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rental_agreements`
--

INSERT INTO `rental_agreements` (`agreement_id`, `request_id`, `tenant_id`, `property_id`, `start_date`, `end_date`, `monthly_rent`, `security_deposit`, `advance_payment`, `agreement_document`, `status`, `payment_deadline`, `signed_by_tenant`, `signed_by_owner`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 1, '2026-02-02', '2026-08-02', 5000.00, 10000.00, 1000.00, NULL, 'active', NULL, 0, 1, '2026-02-01 21:40:02', '2026-02-01 21:40:02'),
(2, 3, 6, 3, '2026-02-02', '2026-08-02', 8000.00, 16000.00, 1600.00, NULL, 'active', NULL, 0, 1, '2026-02-02 07:47:35', '2026-02-02 07:47:35'),
(3, 6, 20, 20, '2026-03-26', '2026-09-26', 3000.00, 6000.00, 600.00, NULL, 'partially_paid', '2026-05-27 13:18:18', 0, 1, '2026-03-26 14:03:36', '2026-05-17 10:18:18'),
(4, 9, 20, 21, '2026-05-08', '2026-11-08', 40000.00, 3000.00, 8000.00, NULL, 'active', NULL, 0, 1, '2026-05-08 19:16:11', '2026-05-21 14:51:22'),
(5, 10, 21, 22, '2026-05-20', '2026-11-20', 4000.00, 2000.00, 800.00, NULL, 'active', NULL, 0, 1, '2026-05-20 01:11:59', '2026-05-20 01:13:17'),
(6, 11, 29, 24, '2026-05-20', '2026-11-20', 30000.00, 5000.00, 6000.00, NULL, 'active', NULL, 0, 1, '2026-05-20 14:32:42', '2026-05-20 17:56:52'),
(7, 12, 29, 23, '2026-05-20', '2026-11-20', 18000.00, 3000.00, 3600.00, NULL, 'active', NULL, 0, 1, '2026-05-20 14:49:31', '2026-05-20 14:49:31'),
(8, 13, 29, 27, '2026-05-20', '2026-11-20', 22000.00, 4000.00, 4400.00, NULL, 'active', NULL, 0, 1, '2026-05-20 17:52:23', '2026-05-20 17:52:23'),
(9, 14, 29, 26, '2026-05-20', '2026-11-20', 35000.00, 7000.00, 7000.00, NULL, 'active', NULL, 0, 1, '2026-05-20 17:55:39', '2026-05-20 17:55:39'),
(10, 15, 30, 25, '2026-05-20', '2026-11-20', 6500.00, 1500.00, 1300.00, NULL, 'active', NULL, 0, 1, '2026-05-20 18:05:01', '2026-05-20 18:05:01'),
(11, 16, 31, 28, '2026-05-20', '2026-11-20', 25000.00, 15000.00, 5000.00, NULL, 'partially_paid', '2026-05-30 23:53:46', 0, 1, '2026-05-20 20:38:33', '2026-05-20 20:53:46');

-- --------------------------------------------------------

--
-- Table structure for table `rental_requests`
--

CREATE TABLE `rental_requests` (
  `request_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `request_date` date NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `approval_code` varchar(50) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rental_requests`
--

INSERT INTO `rental_requests` (`request_id`, `tenant_id`, `property_id`, `request_date`, `message`, `status`, `approval_code`, `approved_by`, `approved_at`, `created_at`) VALUES
(1, 3, 1, '2026-01-26', 'I am very interested in this property. Please contact me for viewing.', 'approved', NULL, 2, '2026-02-01 21:40:02', '2026-01-26 20:25:08'),
(2, 6, 6, '2026-02-01', '', 'cancelled', '85B534A5', NULL, NULL, '2026-02-01 20:56:06'),
(3, 6, 3, '2026-02-02', '', 'approved', '44D722A4', 2, '2026-02-02 07:47:35', '2026-02-02 07:46:41'),
(4, 20, 19, '2026-03-17', '', 'cancelled', '182F3B1F', NULL, NULL, '2026-03-17 09:14:25'),
(5, 20, 11, '2026-03-26', '', 'cancelled', '55DA78B2', NULL, NULL, '2026-03-26 08:02:17'),
(6, 20, 20, '2026-03-26', 'i like it \n', 'approved', 'BED9C146', 23, '2026-03-26 14:03:36', '2026-03-26 14:02:25'),
(7, 26, 20, '2026-04-21', '', 'rejected', '9BF5D2AD', 23, '2026-04-23 20:55:23', '2026-04-21 08:43:22'),
(8, 21, 21, '2026-05-08', '', 'rejected', 'FD980309', 23, '2026-05-08 19:15:36', '2026-05-08 19:14:35'),
(9, 20, 21, '2026-05-08', '', 'approved', '0EA786AF', 23, '2026-05-08 19:16:11', '2026-05-08 19:15:44'),
(10, 21, 22, '2026-05-20', '', 'approved', 'D099C20C', 23, '2026-05-20 01:11:59', '2026-05-20 01:11:11'),
(11, 29, 24, '2026-05-20', 'i need ', 'approved', '974E4376', 23, '2026-05-20 14:32:42', '2026-05-20 14:27:37'),
(12, 29, 23, '2026-05-20', 'Hello, I am highly interested in your property listing. It perfectly fits what I am looking for. Is the place available for a viewing sometime this week? Please let me know your availability. Thank you!', 'approved', '22E1D8BB', 23, '2026-05-20 14:49:31', '2026-05-20 14:47:19'),
(13, 29, 27, '2026-05-20', 'Hi there! The house looks beautiful. Quick question before I apply: Are the water and electricity utility bills included in the monthly rent, or are they paid separately? Looking forward to hearing from you.', 'approved', 'D50816CE', 23, '2026-05-20 17:52:23', '2026-05-20 17:51:27'),
(14, 29, 26, '2026-05-20', '\"Hello, I love the listing and the location. I am ready to move in starting early next month and have the security deposit ready. Please contact me at your earliest convenience to discuss the next steps.\"', 'approved', 'EC30D017', 23, '2026-05-20 17:55:39', '2026-05-20 17:55:13'),
(15, 30, 25, '2026-05-20', '\"Hello, I love the listing and the location. I am ready to move in starting early next month and have the security deposit ready. Please contact me at your earliest convenience to discuss the next steps.\"', 'approved', '2EC4BDAE', 23, '2026-05-20 18:05:01', '2026-05-20 18:04:18'),
(16, 31, 28, '2026-05-20', '', 'approved', '96325C24', 24, '2026-05-20 20:38:33', '2026-05-20 20:37:25');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `parameters` text DEFAULT NULL,
  `generated_by` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_messages`
--

CREATE TABLE `support_messages` (
  `message_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sender_role` varchar(20) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `reply_to` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `support_messages`
--

INSERT INTO `support_messages` (`message_id`, `ticket_id`, `sender_role`, `sender_id`, `message`, `created_at`, `file_path`, `file_type`, `reply_to`, `is_deleted`, `updated_at`) VALUES
(2, 2, 'tenant', 6, 'i want .....', '2026-02-04 09:54:49', NULL, NULL, NULL, 0, NULL),
(3, 2, 'employee', 4, 'we will call you soon', '2026-02-04 10:14:18', NULL, NULL, NULL, 0, NULL),
(4, 3, 'tenant', 20, 'how can communicate with the owner?', '2026-04-15 20:18:01', NULL, NULL, NULL, 0, NULL),
(5, 3, 'tenant', 20, 'hey selam new lamakrh felge neber', '2026-04-15 20:18:47', NULL, NULL, NULL, 0, NULL),
(6, 3, 'employee', 4, 'eshi wd denbegnachn  mn lagzot ?', '2026-04-15 20:22:02', NULL, NULL, NULL, 0, NULL),
(7, 3, 'employee', 4, 'selam new', '2026-04-16 20:54:02', NULL, NULL, NULL, 0, NULL),
(8, 3, 'tenant', 20, 'መብራት መቀየር ፈልጌ ነበር እና ባለቤቱን እንዴት ማግኘት እችላለሁ', '2026-04-16 21:00:43', NULL, NULL, NULL, 0, NULL),
(9, 3, 'tenant', 20, 'نيمي', '2026-04-16 21:02:02', NULL, NULL, NULL, 0, NULL),
(10, 3, 'tenant', 20, 'hdjd jske', '2026-04-16 21:02:14', NULL, NULL, NULL, 0, NULL),
(11, 3, 'employee', 4, 'eshi', '2026-04-16 21:02:51', NULL, NULL, NULL, 0, NULL),
(12, 3, 'employee', 4, 'ye betu id yngerugn ?', '2026-04-16 21:15:39', NULL, NULL, NULL, 0, NULL),
(13, 3, 'tenant', 20, 'Ak123', '2026-04-16 21:15:54', NULL, NULL, NULL, 0, NULL),
(14, 4, 'tenant', 20, 'hey', '2026-04-16 21:35:07', NULL, NULL, NULL, 0, NULL),
(15, 4, 'owner', 23, 'sela, betu endet new tesmamchuhu', '2026-04-16 21:38:55', NULL, NULL, NULL, 0, NULL),
(16, 4, 'tenant', 20, 'Aw harif new wha gn ykoraratal emdet new', '2026-04-16 21:39:36', NULL, NULL, NULL, 0, NULL),
(18, 4, 'employee', 4, 'ahun be agbabu eyesera new', '2026-04-16 21:56:21', NULL, NULL, NULL, 0, NULL),
(19, 4, 'tenant', 20, 'Aw sertual', '2026-04-16 21:56:29', NULL, NULL, NULL, 0, NULL),
(20, 5, 'employee', 4, 'yes you can', '2026-04-16 21:57:14', NULL, NULL, NULL, 0, NULL),
(21, 4, 'tenant', 20, 'selam', '2026-04-21 09:20:03', NULL, NULL, NULL, 0, NULL),
(22, 6, 'tenant', 20, 'i lost my keys', '2026-04-23 22:57:40', NULL, NULL, NULL, 0, NULL),
(23, 6, 'owner', 23, 'i will come', '2026-04-23 22:58:38', NULL, NULL, NULL, 0, NULL),
(24, 7, 'tenant', 20, 'hey', '2026-04-23 23:18:10', NULL, NULL, NULL, 0, NULL),
(25, 7, 'employee', 4, 'hey', '2026-04-23 23:18:36', NULL, NULL, NULL, 1, '2026-04-25 19:44:44'),
(26, 7, 'owner', 23, 'hey', '2026-04-23 23:19:12', NULL, NULL, NULL, 0, NULL),
(27, 8, 'tenant', 27, 'Selam', '2026-04-24 12:47:25', NULL, NULL, NULL, 0, NULL),
(28, 8, 'employee', 4, 'Enquan dehna metu wede aksum house \r\n rental system', '2026-04-24 12:48:29', NULL, NULL, NULL, 0, NULL),
(29, 8, 'tenant', 27, 'Thank you', '2026-04-24 12:48:46', NULL, NULL, NULL, 0, NULL),
(30, 8, 'employee', 4, 'Ena yemifelgutn bet agegnu', '2026-04-24 12:49:02', NULL, NULL, NULL, 0, NULL),
(31, 8, 'tenant', 27, 'Alagegnehum', '2026-04-24 12:49:44', NULL, NULL, NULL, 0, NULL),
(32, 4, 'tenant', 20, 'hi', '2026-04-25 13:09:54', NULL, NULL, 0, 0, NULL),
(33, 4, 'tenant', 20, '', '2026-04-25 13:10:39', 'assets/uploads/chat/1777122639_20.jpg', 'image/jpeg', 0, 0, NULL),
(34, 4, 'tenant', 20, 'selam new', '2026-04-25 13:11:40', NULL, NULL, 32, 0, NULL),
(35, 4, 'tenant', 20, 'aw', '2026-04-25 13:11:57', NULL, NULL, 0, 0, NULL),
(36, 4, 'tenant', 20, 'se', '2026-04-25 13:19:40', NULL, NULL, 0, 0, NULL),
(37, 7, 'tenant', 20, 'hu h', '2026-04-25 17:23:39', NULL, NULL, 0, 0, '2026-04-25 19:43:41'),
(38, 7, 'employee', 4, '', '2026-04-25 19:44:56', 'assets/uploads/chat/1777146296_4.jpg', 'image/jpeg', 26, 0, NULL),
(39, 7, 'tenant', 20, '', '2026-04-25 20:12:23', 'assets/uploads/chat/1777147943_20.jpg', 'image/jpeg', 0, 1, '2026-04-25 20:12:32'),
(40, 7, 'tenant', 20, '', '2026-04-25 20:14:21', 'assets/uploads/chat/1777148061_20.jpg', 'image/jpeg', 0, 1, '2026-04-25 20:14:29'),
(41, 7, 'owner', 23, 'hi endet nachuhu', '2026-05-03 20:03:40', NULL, NULL, 0, 0, '2026-05-03 20:03:58'),
(42, 7, 'owner', 23, '', '2026-05-03 20:04:39', 'assets/uploads/chat/1777838679_23.jpg', 'image/jpeg', 0, 0, NULL),
(43, 8, 'employee', 4, 'selam ', '2026-05-08 17:13:58', NULL, NULL, NULL, 0, NULL),
(44, 9, 'tenant', 20, 'how i can make my payment for rent', '2026-05-08 17:28:04', NULL, NULL, NULL, 0, '2026-05-08 17:29:27'),
(45, 9, 'owner', 23, 'i don\'t know', '2026-05-08 18:13:09', NULL, NULL, 44, 0, NULL),
(46, 9, 'tenant', 20, 'what', '2026-05-08 18:13:28', NULL, NULL, 0, 0, NULL),
(47, 9, 'employee', 4, 'tumuy', '2026-05-08 18:24:29', NULL, NULL, 46, 0, NULL),
(48, 10, 'tenant', 20, 'Selam👋', '2026-05-17 21:27:17', NULL, NULL, NULL, 0, NULL),
(49, 10, 'employee', 4, 'endet neh desta', '2026-05-17 21:31:32', NULL, NULL, 48, 0, NULL),
(50, 10, 'tenant', 20, 'Endet new', '2026-05-17 21:33:19', 'assets/uploads/chat/1779053599_20.jpg', 'image/jpeg', 0, 0, NULL),
(51, 10, 'employee', 4, 'thanks', '2026-05-17 21:33:54', NULL, NULL, 50, 0, NULL),
(52, 10, 'owner', 23, 'desta', '2026-05-17 21:35:07', NULL, NULL, 48, 0, '2026-05-17 21:36:11'),
(53, 10, 'owner', 23, 'tnx', '2026-05-17 21:35:27', NULL, NULL, 50, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `ticket_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `assigned_employee_id` int(11) DEFAULT NULL,
  `target_role` enum('owner','employee','admin','all') NOT NULL DEFAULT 'employee',
  `subject` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`ticket_id`, `tenant_id`, `owner_id`, `assigned_employee_id`, `target_role`, `subject`, `category`, `priority`, `status`, `created_at`, `updated_at`) VALUES
(1, 6, NULL, NULL, 'employee', 'payment method showing', 'payment', 'high', 'closed', '2026-02-04 09:53:53', '2026-02-04 09:54:14'),
(2, 6, NULL, NULL, 'employee', 'payment method showing', 'payment', 'high', 'IN_PROGRESS', '2026-02-04 09:54:49', '2026-02-04 10:14:18'),
(3, 20, NULL, 4, 'employee', 'asking', 'maintenance', 'low', 'OPEN', '2026-04-15 20:18:01', '2026-04-16 21:15:54'),
(4, 20, 23, 4, 'employee', 'owner', 'general', 'high', 'open', '2026-04-16 21:35:07', '2026-04-25 13:19:41'),
(5, 20, NULL, 4, 'employee', 'Asking', 'payment', 'normal', 'closed', '2026-04-16 21:54:34', '2026-05-08 17:14:40'),
(6, 20, 23, NULL, 'owner', 'key', 'general', 'high', 'closed', '2026-04-23 22:56:37', '2026-04-25 12:35:10'),
(7, 20, 23, 4, 'all', 'group chat', 'general', 'normal', 'open', '2026-04-23 23:18:10', '2026-05-03 20:04:39'),
(8, 27, NULL, 4, 'employee', 'Request', 'general', 'low', 'OPEN', '2026-04-24 12:47:25', '2026-05-08 17:13:58'),
(9, 20, 23, 4, 'all', 'How i can pay', 'payment', 'normal', 'open', '2026-05-08 17:28:04', '2026-05-08 18:24:29'),
(10, 20, 23, 4, 'all', 'Important', 'general', 'normal', 'open', '2026-05-17 21:27:17', '2026-05-17 21:35:27');

-- --------------------------------------------------------

--
-- Table structure for table `system_news`
--

CREATE TABLE `system_news` (
  `news_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `excerpt` text DEFAULT NULL,
  `target_audience` enum('tenants','owners','all','employees') NOT NULL DEFAULT 'all',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `publication_date` datetime NOT NULL,
  `expiry_date` datetime DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `allow_comments` tinyint(1) DEFAULT 1,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `publish_date` date DEFAULT curdate(),
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notification_sent` tinyint(1) DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `view_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_news`
--

INSERT INTO `system_news` (`news_id`, `title`, `content`, `excerpt`, `target_audience`, `priority`, `publication_date`, `expiry_date`, `category_id`, `featured`, `allow_comments`, `meta_title`, `meta_description`, `publish_date`, `status`, `created_by`, `created_at`, `notification_sent`, `archived_at`, `view_count`) VALUES
(12, 'Welcome to the Aksum Rental System', 'This is a comprehensive welcome message for all tenants. Our rental system provides you with easy access to property search, rental applications, payment management, and maintenance requests. You can browse available properties, submit rental requests, make secure payments online, and track all your rental activities from your personal dashboard.', 'Welcome message for tenants with system overview', 'tenants', 'high', '2026-02-12 10:24:53', NULL, NULL, 1, 1, NULL, NULL, '2026-02-12', 'published', 1, '2026-02-12 09:24:53', 0, NULL, 31),
(14, 'New Payment Methods Available', 'We are excited to announce that we have added new payment methods to make your rental payments more convenient. You can now pay your rent using Telebirr, CBE Birr, Awash Bank, Dashen Bank, and Bank of Abyssinia mobile banking services. Simply go to your payment dashboard and select your preferred payment method. All transactions are secure and instantly recorded in your payment history.', 'New payment options for tenant convenience', 'tenants', 'medium', '2026-02-12 10:24:54', NULL, NULL, 0, 1, NULL, NULL, '2026-02-12', 'published', 1, '2026-02-12 09:24:54', 0, NULL, 10),
(15, 'Property Search Tips', 'Finding the perfect rental property is easy with our advanced search features. Use filters to narrow down properties by location, price range, number of bedrooms, and property type. You can also save properties to your favorites list and receive notifications when new properties matching your criteria become available. Remember to check property details carefully and contact property owners with any questions before submitting rental requests.', 'Tips for effective property searching', 'all', 'low', '2026-02-12 10:24:54', NULL, NULL, 0, 1, NULL, NULL, '2026-02-12', 'published', 1, '2026-02-12 09:24:54', 0, NULL, 18),
(20, 'Important Update on Rental Payment System', 'New Payment Options\r\n\r\nThe system now supports three payment methods:\r\n\r\nMonthly Payment (Full rent per month)\r\nMinimum Payment (20% advance reservation)\r\n6-Month Payment (Pay in advance for 6 months)\r\n📌 Minimum Payment Policy\r\n\r\nWhen selecting the 20% minimum payment option:\r\n\r\nTenants pay 20% of the monthly rent to reserve a property.\r\nThe remaining 80% must be paid within 10 days after booking.\r\nTenants can now pay the remaining balance directly from the “My Requests” page.\r\n⚠️ Important Rules\r\nIf the remaining balance is not paid within the deadline, the booking will be marked as Expired.\r\nThe property will become available for other tenants again.\r\nAdvance payments may become non-refundable according to system policy.\r\n🎉 New Feature\r\n\r\nYou can now:\r\n\r\nTrack your payment status in real-time\r\nPay remaining balances easily\r\nReceive automatic payment reminders\r\n\r\nThank you for using Aksum House Rental System.', 'We are pleased to inform all tenants and landlords that we have improved our rental payment system to make transactions more flexible and transparent.', 'all', 'urgent', '2026-05-16 19:56:00', '2026-05-21 05:00:00', 4, 0, 1, '', '', '2026-05-16', 'published', 4, '2026-05-16 17:01:00', 1, NULL, 53);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('allow_registrations', '1'),
('auto_backup', '1'),
('backup_frequency', 'daily'),
('backup_retention', '30'),
('email_notifications', '1'),
('enable_captcha', '1'),
('force_https', '1'),
('maintenance_mode', '0'),
('max_login_attempts', '5'),
('session_timeout', '30'),
('site_address', ''),
('site_email', 'admin@aksumrental.com'),
('site_name', 'Aksum Rental System'),
('site_phone', ''),
('smtp_host', ''),
('smtp_password', ''),
('smtp_port', ''),
('smtp_username', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('tenant','owner','employee','admin') NOT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `id_image` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `status` enum('active','inactive','suspended','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `login_attempts` int(11) DEFAULT 0 COMMENT 'Number of failed login attempts',
  `last_attempt_time` timestamp NULL DEFAULT NULL COMMENT 'Time of last failed login attempt',
  `lockout_until` timestamp NULL DEFAULT NULL COMMENT 'Time until account is locked out',
  `force_password_change` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `id_number`, `address`, `id_image`, `profile_image`, `profile_picture`, `is_verified`, `verification_token`, `token_expires_at`, `is_active`, `status`, `created_at`, `updated_at`, `login_attempts`, `last_attempt_time`, `lockout_until`, `force_password_change`) VALUES
(1, 'Admin User', 'hagomedhanye85@gmail.com', '0968931862', '$2y$10$av9sV5lYjt5ZxCLOWJ/5yOwYcTqqbZTX3RLfJttPWhxvOi5TbVWM.', 'admin', NULL, 'mekelle,tigray', NULL, NULL, 'profile_1_1771122765.png', 1, NULL, NULL, 1, 'active', '2026-01-26 20:25:08', '2026-05-21 17:05:33', 0, NULL, NULL, 0),
(4, 'Tewelde G/Ananya', 'teweldeananya24@gmail.com', '0986764608', '$2y$10$lkPrnJwDn.JwiZIOByHl..HJvVOGhUanSbvbTUKHLG1Acx9bjDBB6', 'employee', NULL, '', NULL, 'assets/uploads/profiles/employee_4_1769722128.jpg', NULL, 1, NULL, NULL, 1, 'active', '2026-01-26 20:25:08', '2026-05-21 17:07:08', 0, NULL, NULL, 0),
(20, 'Desta ', 'yeshujoy366@gmail.com', '0987121212', '$2y$10$CZNsMWL0BODcCr.JbMi1uOrLxfLuRAkAldSU0e0KyNiz6tM/5OC/G', 'tenant', '', 'Aksum', 'assets/uploads/ids/id_20_1778262780.jpg', 'assets/uploads/ids/id_69b915b67ab791.81797616.jpg', 'profile_20_1778262780.jpg', 1, NULL, NULL, 1, 'active', '2026-03-17 08:49:58', '2026-05-21 14:57:17', 0, NULL, NULL, 0),
(21, 'Robel', 'negasiroba12@gmail.com', '0986254124', '$2y$10$LOxIV7aw1.MExm6Kh5fnhuy09ovKjIhE6zJl5.Pny5CuHFk0z6ABe', 'tenant', '1203254', 'Adwa', NULL, 'assets/uploads/ids/id_69c4fc0aa5e6c0.41356954.jpg', 'profile_21_1776984228.jpg', 1, NULL, NULL, 1, 'active', '2026-03-26 09:27:38', '2026-05-20 06:36:52', 0, NULL, NULL, 0),
(23, 'Tewelde Gebreananya', 'jyking029@gmail.com', '0987654652', '$2y$10$e.9O9WumSOZ1r6Jr04eNYOLpg6nIW7FUtez7UvCH2/jEkgWf6KqCW', 'owner', '1234 3214 5467 7890', 'adigart', 'assets/uploads/ids/id_23_1779368457.jpg', 'assets/uploads/ids/id_69c52f2a47df15.32752892.jpg', 'profile_23_1775680386.jpg', 1, NULL, NULL, 1, 'active', '2026-03-26 13:05:46', '2026-05-21 14:07:57', 0, NULL, NULL, 0),
(31, 'muley K', 'kinfemuley0@gmail.com', '0987121212', '$2y$10$xIeIJtKG2ob2Zmy1/SCtauxa74nOmoeqWZlSKbtY6S6f39XGJ1V5C', 'tenant', '1233 2341 3231 1245', 'shire', 'assets/uploads/ids/id_6a0e044156caf7.12012259.jpg', NULL, NULL, 1, NULL, NULL, 1, 'active', '2026-05-20 18:58:09', '2026-05-21 14:58:03', 0, NULL, NULL, 0),
(36, 'Asmorom Haylemaryam', 'wmedaa27@gmail.com', '0985650024', '$2y$10$W7GGxJFpsZtBMF07qaxrXu6UNJRmhkCT66CMJFXeLKOpF.yxc9PkW', 'owner', '8970 7654 3452 8765', 'Near the Airport Road', 'assets/uploads/ids/id_6a0efeb4ac1313.87375392.jpg', NULL, NULL, 1, NULL, NULL, 1, 'active', '2026-05-21 12:46:44', '2026-05-21 17:07:29', 0, NULL, NULL, 0),
(39, 'Mebre Guesh', 'mebreg4@gmail.com', '0983765431', '$2y$10$.LJxqTs4CDFVEO/EW6OriOBva6dx6QZuYgh1aLlPGjzVaUkDgwDVO', 'tenant', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, 'active', '2026-05-21 13:56:25', '2026-05-21 13:56:25', 0, NULL, NULL, 1),
(40, 'Abel Enun', 'medahg27@gmail.com', '0955446789', '$2y$10$nYu7yzwUfSPFTNUOlP0TkOldAuBlKpS7pem6xitSv0fj0tTA2YdKW', 'tenant', '5907 7459 7865 3245', 'mekelle', 'assets/uploads/ids/id_6a0f3baf0bceb1.01743152.jpg', NULL, NULL, 1, NULL, NULL, 1, 'active', '2026-05-21 17:06:55', '2026-05-21 17:08:52', 0, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`session_id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `last_activity`, `expires_at`, `created_at`) VALUES
(2, 21, '7b420527b7f466c71ea4f38f4dd64a1de5b1ea0098f71b9f6f8afe6dafcfe3bb', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-26 09:49:21', '2026-04-25 12:49:21', '2026-03-26 09:49:21'),
(3, 1, '0bc91a7adc917fb8dc0a8d6684256100a32eeca3a3f895a731b8e9d4850c7e3b', '10.189.232.188', 'Mozilla/5.0 (Linux; Android 13; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.138 Mobile Safari/537.36', '2026-05-19 12:41:47', '2026-06-18 15:41:47', '2026-05-19 12:41:47');

-- --------------------------------------------------------

--
-- Table structure for table `vacating_notices`
--

CREATE TABLE `vacating_notices` (
  `notice_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `agreement_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `vacating_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `forwarding_address` text DEFAULT NULL,
  `contact_info` text DEFAULT NULL,
  `notice_date` date DEFAULT curdate(),
  `status` enum('pending','acknowledged','completed') DEFAULT 'pending',
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `wallet_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wallets`
--

INSERT INTO `wallets` (`wallet_id`, `user_id`, `balance`, `created_at`, `updated_at`) VALUES
(12, 20, 9600.00, '2026-05-17 21:39:45', '2026-05-21 14:51:22'),
(13, 21, 650.00, '2026-05-20 01:06:36', '2026-05-20 06:39:41'),
(14, 1, 56204.40, '2026-05-20 01:08:17', '2026-05-21 14:51:22'),
(15, 23, 428925.60, '2026-05-20 01:13:17', '2026-05-21 14:51:22'),
(16, 29, 34500.00, '2026-05-20 14:50:55', '2026-05-20 17:57:54'),
(17, 30, 99999999.99, '2026-05-20 18:07:24', '2026-05-20 18:07:24'),
(18, 31, 94750.00, '2026-05-20 20:47:39', '2026-05-20 20:53:46'),
(19, 24, 4650.00, '2026-05-20 20:53:46', '2026-05-20 20:53:46');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `transaction_id` int(11) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_type` enum('deposit','payment','withdrawal') NOT NULL,
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'pending',
  `reference_table` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_visible_admin` tinyint(1) DEFAULT 1,
  `is_visible_user` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`transaction_id`, `wallet_id`, `amount`, `transaction_type`, `status`, `reference_table`, `reference_id`, `description`, `created_at`, `is_visible_admin`, `is_visible_user`) VALUES
(81, 3, 10000.00, 'deposit', 'completed', 'payments', 25, ' Wallet Deposit via Telebirr', '2026-05-17 17:07:44', 1, 1),
(82, 3, -500.00, 'withdrawal', 'completed', NULL, NULL, 'Withdrawal to CBE (1000086687778)', '2026-05-17 17:10:21', 0, 1),
(83, 12, 10000.00, 'deposit', 'completed', 'payments', 27, ' Wallet Deposit via Telebirr', '2026-05-17 21:39:45', 1, 1),
(84, 13, 10000.00, 'deposit', 'completed', 'payments', 28, ' Wallet Deposit via Cbe', '2026-05-20 01:06:36', 1, 1),
(85, 13, -2060.00, 'withdrawal', 'completed', NULL, NULL, 'Withdrawal to CBE (1000345765786)', '2026-05-20 01:08:17', 1, 1),
(86, 14, 60.00, 'deposit', 'completed', 'wallet_transactions', 85, 'Withdrawal fee from user #21 (Request #85)', '2026-05-20 01:08:17', 1, 1),
(87, 13, -4200.00, 'payment', 'completed', 'payments', 29, 'Payment for Rent - Agreement #5 (including 5% fee)', '2026-05-20 01:13:17', 1, 1),
(88, 15, 3720.00, 'deposit', 'completed', 'payments', 29, 'Rent payment received for Agreement #5 - Amount: ETB 3,720.00', '2026-05-20 01:13:17', 1, 1),
(89, 14, 480.00, 'deposit', 'completed', 'payments', 29, 'Commission earned for payment ID 29 on Agreement #5', '2026-05-20 01:13:18', 1, 1),
(90, 15, -734.40, 'withdrawal', 'completed', NULL, NULL, 'Withdrawal to Telebirr (0987654327)', '2026-05-20 01:20:43', 1, 1),
(91, 14, 14.40, 'deposit', 'completed', 'wallet_transactions', 90, 'Withdrawal fee from user #23 (Request #90)', '2026-05-20 01:20:44', 1, 1),
(92, 13, -3090.00, 'withdrawal', 'completed', NULL, NULL, 'Withdrawal to Telebirr (0992826623)', '2026-05-20 06:39:41', 1, 1),
(93, 14, 90.00, 'deposit', 'completed', 'wallet_transactions', 92, 'Withdrawal fee from user #21 (Request #92)', '2026-05-20 06:39:41', 1, 1),
(94, 16, 250000.00, 'deposit', 'completed', 'payments', 30, ' Wallet Deposit via Cbe', '2026-05-20 14:50:55', 1, 1),
(95, 16, -189000.00, 'payment', 'completed', 'payments', 31, 'Payment for 6 Months Rent - Agreement #6 (including 5% fee)', '2026-05-20 14:53:02', 1, 1),
(96, 15, 167400.00, 'deposit', 'completed', 'payments', 31, 'Rent payment received for Agreement #6 - Amount: ETB 167,400.00', '2026-05-20 14:53:02', 1, 1),
(97, 14, 21600.00, 'deposit', 'completed', 'payments', 31, 'Commission earned for payment ID 31 on Agreement #6', '2026-05-20 14:53:02', 1, 1),
(98, 16, -31500.00, 'payment', 'completed', 'payments', 32, 'Payment for Rent - Agreement #6 (including 5% fee)', '2026-05-20 17:56:52', 1, 1),
(99, 15, 27900.00, 'deposit', 'completed', 'payments', 32, 'Rent payment received for Agreement #6 - Amount: ETB 27,900.00', '2026-05-20 17:56:52', 1, 1),
(100, 14, 3600.00, 'deposit', 'completed', 'payments', 32, 'Commission earned for payment ID 32 on Agreement #6', '2026-05-20 17:56:52', 1, 1),
(101, 16, 5000.00, 'deposit', 'completed', 'payments', 33, ' Wallet Deposit via Telebirr', '2026-05-20 17:57:54', 1, 1),
(102, 17, 99999999.99, 'deposit', 'completed', 'payments', 34, ' Wallet Deposit via Telebirr', '2026-05-20 18:07:24', 1, 1),
(103, 18, 100000.00, 'deposit', 'completed', 'payments', 35, ' Wallet Deposit via Cbe', '2026-05-20 20:47:39', 1, 1),
(104, 18, -5250.00, 'payment', 'completed', 'payments', 36, 'Payment for Rent - Agreement #11 (including 5% fee)', '2026-05-20 20:53:46', 1, 1),
(105, 19, 4650.00, 'deposit', 'completed', 'payments', 36, 'Rent payment received for Agreement #11 - Amount: ETB 4,650.00', '2026-05-20 20:53:46', 1, 1),
(106, 14, 600.00, 'deposit', 'completed', 'payments', 36, 'Commission earned for payment ID 36 on Agreement #11', '2026-05-20 20:53:46', 1, 1),
(107, 12, -8400.00, 'payment', 'completed', 'payments', 37, 'Payment for Rent - Agreement #4 (including 5% fee)', '2026-05-21 11:53:11', 1, 1),
(108, 15, 7440.00, 'deposit', 'completed', 'payments', 37, 'Rent payment received for Agreement #4 - Amount: ETB 7,440.00', '2026-05-21 11:53:11', 1, 1),
(109, 14, 960.00, 'deposit', 'completed', 'payments', 37, 'Commission earned for payment ID 37 on Agreement #4', '2026-05-21 11:53:11', 1, 1),
(110, 12, 50000.00, 'deposit', 'completed', 'payments', 38, ' Wallet Deposit via Cbe', '2026-05-21 14:48:39', 1, 1),
(111, 12, 210000.00, 'deposit', 'completed', 'payments', 39, ' Wallet Deposit via Cbe', '2026-05-21 14:50:55', 1, 1),
(112, 12, -252000.00, 'payment', 'completed', 'payments', 40, 'Payment for 6 Months Rent - Agreement #4 (including 5% fee)', '2026-05-21 14:51:22', 1, 1),
(113, 15, 223200.00, 'deposit', 'completed', 'payments', 40, 'Rent payment received for Agreement #4 - Amount: ETB 223,200.00', '2026-05-21 14:51:22', 1, 1),
(114, 14, 28800.00, 'deposit', 'completed', 'payments', 40, 'Commission earned for payment ID 40 on Agreement #4', '2026-05-21 14:51:22', 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`location_id`),
  ADD UNIQUE KEY `unique_location` (`location_name`,`subcity`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `news_attachments`
--
ALTER TABLE `news_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `idx_news_id` (`news_id`);

--
-- Indexes for table `news_categories`
--
ALTER TABLE `news_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `news_category_relations`
--
ALTER TABLE `news_category_relations`
  ADD PRIMARY KEY (`relation_id`),
  ADD UNIQUE KEY `unique_news_category` (`news_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `news_comments`
--
ALTER TABLE `news_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `idx_news_id` (`news_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `news_likes`
--
ALTER TABLE `news_likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `news_user_like` (`news_id`,`user_id`);

--
-- Indexes for table `news_views`
--
ALTER TABLE `news_views`
  ADD PRIMARY KEY (`view_id`),
  ADD UNIQUE KEY `unique_news_user` (`news_id`,`user_id`),
  ADD KEY `idx_news_id` (`news_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `notification_templates`
--
ALTER TABLE `notification_templates`
  ADD PRIMARY KEY (`template_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_user_email` (`user_id`,`email`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD UNIQUE KEY `transaction_reference` (`transaction_reference`),
  ADD KEY `agreement_id` (`agreement_id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `idx_payment_type` (`payment_type`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_transaction_reference` (`transaction_reference`),
  ADD KEY `idx_tenant_agreement` (`tenant_id`,`agreement_id`);

--
-- Indexes for table `payment_audit_log`
--
ALTER TABLE `payment_audit_log`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `idx_payment_id` (`payment_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD UNIQUE KEY `transaction_reference` (`transaction_reference`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `idx_transaction_reference` (`transaction_reference`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`property_id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`property_type`),
  ADD KEY `idx_location` (`location_id`),
  ADD KEY `idx_properties_review_status` (`review_status`),
  ADD KEY `idx_properties_reviewed_by` (`reviewed_by`);

--
-- Indexes for table `property_activity_log`
--
ALTER TABLE `property_activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_property_id` (`property_id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `property_images`
--
ALTER TABLE `property_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_property` (`property_id`);

--
-- Indexes for table `property_reviews`
--
ALTER TABLE `property_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `idx_property_review` (`property_id`),
  ADD KEY `idx_employee_review` (`employee_id`),
  ADD KEY `idx_review_status` (`review_status`);

--
-- Indexes for table `property_review_notifications`
--
ALTER TABLE `property_review_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `idx_property_notification` (`property_id`),
  ADD KEY `idx_owner_notification` (`owner_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `property_views`
--
ALTER TABLE `property_views`
  ADD PRIMARY KEY (`view_id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `idx_property_tenant` (`property_id`,`tenant_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `rental_agreements`
--
ALTER TABLE `rental_agreements`
  ADD PRIMARY KEY (`agreement_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indexes for table `rental_requests`
--
ALTER TABLE `rental_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `unique_request` (`tenant_id`,`property_id`,`status`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `support_messages`
--
ALTER TABLE `support_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_ticket_created` (`ticket_id`,`created_at`),
  ADD KEY `fk_support_messages_sender` (`sender_id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `idx_tenant_status` (`tenant_id`,`status`),
  ADD KEY `idx_assigned_status` (`assigned_employee_id`,`status`),
  ADD KEY `idx_owner_status` (`owner_id`,`status`),
  ADD KEY `idx_target_role` (`target_role`);

--
-- Indexes for table `system_news`
--
ALTER TABLE `system_news`
  ADD PRIMARY KEY (`news_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_view_count` (`view_count`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_profile_image` (`profile_image`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_login_attempts` (`login_attempts`,`last_attempt_time`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`session_token`);

--
-- Indexes for table `vacating_notices`
--
ALTER TABLE `vacating_notices`
  ADD PRIMARY KEY (`notice_id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `agreement_id` (`agreement_id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `acknowledged_by` (`acknowledged_by`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`wallet_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `wallet_id` (`wallet_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1433;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `news_attachments`
--
ALTER TABLE `news_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news_categories`
--
ALTER TABLE `news_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `news_category_relations`
--
ALTER TABLE `news_category_relations`
  MODIFY `relation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news_comments`
--
ALTER TABLE `news_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `news_likes`
--
ALTER TABLE `news_likes`
  MODIFY `like_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `news_views`
--
ALTER TABLE `news_views`
  MODIFY `view_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=190;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1096;

--
-- AUTO_INCREMENT for table `notification_templates`
--
ALTER TABLE `notification_templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `payment_audit_log`
--
ALTER TABLE `payment_audit_log`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `property_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `property_activity_log`
--
ALTER TABLE `property_activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_images`
--
ALTER TABLE `property_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `property_reviews`
--
ALTER TABLE `property_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `property_review_notifications`
--
ALTER TABLE `property_review_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `property_views`
--
ALTER TABLE `property_views`
  MODIFY `view_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rental_agreements`
--
ALTER TABLE `rental_agreements`
  MODIFY `agreement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `rental_requests`
--
ALTER TABLE `rental_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_messages`
--
ALTER TABLE `support_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `system_news`
--
ALTER TABLE `system_news`
  MODIFY `news_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vacating_notices`
--
ALTER TABLE `vacating_notices`
  MODIFY `notice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `wallet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
