-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 27, 2025 at 03:29 PM
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
-- Database: `pos_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `analytics`
--

CREATE TABLE `analytics` (
  `id` int(11) NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `json_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`json_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('meal','room') DEFAULT 'meal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `type`) VALUES
(2, 'Breakfast', 'meal'),
(3, 'Quick Foods', 'meal'),
(4, 'Lunch', 'meal'),
(5, 'Drinks', 'meal'),
(6, 'Pizza', 'meal');

-- --------------------------------------------------------

--
-- Table structure for table `discounts`
--

CREATE TABLE `discounts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `type` enum('percentage','fixed') DEFAULT NULL,
  `percentage` decimal(10,2) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `waiter_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `table_number` varchar(10) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','confirmed','closed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `waiter_id`, `room_id`, `table_number`, `total`, `status`, `created_at`) VALUES
(5, 6, 6, NULL, '5', 600.00, 'closed', '2025-05-21 15:38:38'),
(6, 6, 6, NULL, '5', 1500.00, 'closed', '2025-05-21 15:40:05'),
(7, 8, 8, NULL, '5', 1200.00, 'closed', '2025-05-21 16:05:26'),
(8, 8, 8, NULL, '5', 2800.00, 'closed', '2025-05-21 20:26:19'),
(9, 8, 8, NULL, '5', 1200.00, 'closed', '2025-05-21 22:45:14'),
(10, 8, 8, NULL, '5', 300.00, 'closed', '2025-05-22 21:43:25'),
(11, 8, 8, NULL, '5', 300.00, 'closed', '2025-05-22 23:07:36'),
(12, 8, 8, NULL, '5', 900.00, 'closed', '2025-05-22 23:21:06'),
(13, 8, 8, NULL, '5', 300.00, 'closed', '2025-05-23 13:26:07'),
(14, 8, 8, NULL, 'N/A', 1200.00, 'pending', '2025-05-23 14:09:00'),
(15, 8, 8, NULL, 'N/A', 1200.00, '', '2025-05-23 14:09:06'),
(16, 8, 8, NULL, 'N/A', 1200.00, '', '2025-05-23 14:09:10'),
(17, 8, 8, NULL, 'N/A', 1200.00, '', '2025-05-23 14:09:16'),
(18, 8, 8, NULL, 'N/A', 600.00, 'pending', '2025-05-23 15:42:30'),
(19, 8, 8, NULL, 'N/A', 600.00, 'pending', '2025-05-23 15:42:36'),
(20, 8, 8, NULL, 'N/A', 600.00, '', '2025-05-24 09:09:48'),
(21, 8, 8, NULL, '05', 1200.00, '', '2025-05-24 15:58:33'),
(22, 8, 8, NULL, '05', 600.00, '', '2025-05-24 16:04:04'),
(23, 8, 8, NULL, '05', 300.00, '', '2025-05-24 16:18:41'),
(24, 8, 8, NULL, '05', 600.00, 'pending', '2025-05-24 17:19:19'),
(25, 8, 8, NULL, '05', 1600.00, '', '2025-05-24 17:25:06'),
(26, 1, 1, NULL, '05', 1300.00, '', '2025-05-24 18:04:49'),
(27, 8, 8, NULL, '05', 1200.00, 'pending', '2025-05-24 19:53:15'),
(28, 8, 8, NULL, '05', 300.00, 'pending', '2025-05-24 20:31:25'),
(29, 8, 8, NULL, '05', 1200.00, 'pending', '2025-05-24 20:39:58'),
(30, 8, 8, NULL, '05', 1200.00, 'pending', '2025-05-24 20:51:40'),
(31, 8, 8, NULL, '05', 1200.00, 'pending', '2025-05-24 21:20:08'),
(32, 8, 8, NULL, '05', 600.00, 'pending', '2025-05-24 21:30:46'),
(33, 9, 9, NULL, '05', 400.00, 'pending', '2025-05-24 22:20:04'),
(34, 8, 8, NULL, '05', 1200.00, 'pending', '2025-05-24 22:32:46'),
(35, 8, 8, NULL, '05', 400.00, 'pending', '2025-05-25 10:49:19'),
(36, 8, 8, NULL, '05', 100.00, 'confirmed', '2025-05-25 10:50:09'),
(37, 8, 8, NULL, '05', 600.00, 'confirmed', '2025-05-25 10:50:21'),
(38, 8, 8, NULL, '05', 600.00, 'pending', '2025-05-25 10:52:35'),
(39, 8, 8, NULL, '05', 600.00, 'closed', '2025-05-25 11:20:08'),
(40, 8, 8, NULL, '05', 1200.00, 'closed', '2025-05-25 11:23:58'),
(41, 1, 1, NULL, '05', 400.00, 'closed', '2025-05-25 13:11:12'),
(42, 1, 1, NULL, '05', 1900.00, 'closed', '2025-05-25 13:59:09'),
(43, 1, 1, NULL, '05', 600.00, 'closed', '2025-05-25 14:17:19'),
(44, 8, 8, NULL, '05', 1200.00, 'closed', '2025-05-25 14:29:18'),
(45, 8, 8, NULL, '05', 400.00, 'closed', '2025-05-25 14:43:47'),
(46, 1, 1, NULL, '05', 300.00, 'closed', '2025-05-25 20:51:24'),
(47, 8, 8, NULL, '05', 1200.00, 'closed', '2025-05-25 21:07:19'),
(48, 8, 8, NULL, '05', 600.00, 'closed', '2025-05-25 21:35:33'),
(49, 8, 8, NULL, '05', 1200.00, 'confirmed', '2025-05-26 07:58:51'),
(50, 1, 1, NULL, '05', 400.00, 'confirmed', '2025-05-26 08:01:13'),
(51, 1, 1, NULL, '05', 1000.00, 'closed', '2025-05-26 12:09:36'),
(52, 1, 1, NULL, '05', 600.00, 'confirmed', '2025-05-26 12:12:20'),
(53, 8, 8, NULL, '05', 500.00, 'closed', '2025-05-26 13:16:15');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `status` enum('new','preparing','ready') DEFAULT 'new'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`, `status`) VALUES
(1, 5, 2, 'Caramel Special', 2, 300.00, 'new'),
(2, 6, 4, 'Ugali Kuku', 2, 600.00, 'new'),
(3, 6, 3, 'Lemon Mojito', 1, 300.00, 'new'),
(4, 7, 2, 'Caramel Special', 2, 300.00, 'new'),
(5, 7, 3, 'Lemon Mojito', 2, 300.00, 'new'),
(6, 8, 2, 'Caramel Special', 6, 300.00, 'new'),
(7, 8, 3, 'Lemon Mojito', 2, 300.00, 'new'),
(8, 8, 7, 'Chips Choma', 1, 400.00, 'new'),
(9, 9, 2, 'Caramel Special', 2, 300.00, 'new'),
(10, 9, 3, 'Lemon Mojito', 2, 300.00, 'new'),
(11, 10, 3, 'Lemon Mojito', 1, 300.00, 'new'),
(12, 11, 2, 'Caramel Special', 1, 300.00, 'new'),
(13, 12, 3, 'Lemon Mojito', 3, 300.00, 'new'),
(14, 13, 2, 'Caramel Special', 1, 300.00, 'new'),
(15, 14, 4, 'Ugali Kuku', 2, 600.00, 'new'),
(16, 15, 4, 'Ugali Kuku', 2, 600.00, 'new'),
(17, 16, 4, 'Ugali Kuku', 2, 600.00, 'new'),
(18, 17, 4, 'Ugali Kuku', 2, 600.00, 'new'),
(19, 18, 4, 'Ugali Kuku', 1, 600.00, 'new'),
(20, 19, 4, 'Ugali Kuku', 1, 600.00, 'new'),
(21, 20, 6, 'Sambusa', 6, 100.00, 'new'),
(22, 21, 4, 'Ugali Kuku', 2, 600.00, 'new'),
(23, 22, 3, 'Lemon Mojito', 2, 300.00, 'new'),
(24, 23, 2, 'Caramel Special', 1, 300.00, 'new'),
(25, 24, 2, 'Caramel Special', 2, 300.00, 'new'),
(26, 25, 2, 'Caramel Special', 1, 300.00, 'new'),
(27, 25, 5, 'Capuchino', 1, 200.00, 'new'),
(28, 25, 6, 'Sambusa', 1, 100.00, 'new'),
(29, 25, 4, 'Ugali Kuku', 1, 600.00, 'new'),
(30, 25, 7, 'Chips Choma', 1, 400.00, 'new'),
(31, 26, 3, 'Lemon Mojito', 1, 300.00, 'new'),
(32, 26, 4, 'Ugali Kuku', 1, 600.00, 'new'),
(33, 26, 7, 'Chips Choma', 1, 400.00, 'new'),
(34, 27, 4, 'Ugali Kuku', 2, 600.00, 'new'),
(35, 28, 2, 'Caramel Special', 1, 300.00, 'new'),
(36, 29, 4, 'Ugali Kuku', 2, 600.00, 'new'),
(37, 30, 4, 'Ugali Kuku', 2, 600.00, 'new'),
(38, 31, 4, 'Ugali Kuku', 2, 600.00, 'new'),
(39, 32, 3, 'Lemon Mojito', 2, 300.00, 'new'),
(40, 33, 5, 'Capuchino', 2, 200.00, 'new'),
(41, 34, 4, 'Ugali Kuku', 2, 600.00, 'new'),
(42, 35, 2, 'Caramel Special', 1, 300.00, 'new'),
(43, 35, 6, 'Sambusa', 1, 100.00, 'new'),
(44, 36, 6, 'Sambusa', 1, 100.00, 'new'),
(45, 37, 4, 'Ugali Kuku', 1, 600.00, 'new'),
(46, 38, 2, 'Caramel Special', 2, 300.00, 'new'),
(47, 39, 3, 'Lemon Mojito', 2, 300.00, 'new'),
(48, 40, 4, 'Ugali Kuku', 2, 600.00, 'new'),
(49, 41, 2, 'Caramel Special', 1, 300.00, 'new'),
(50, 41, 6, 'Sambusa', 1, 100.00, 'new'),
(51, 42, 2, 'Caramel Special', 4, 300.00, 'new'),
(52, 42, 5, 'Capuchino', 2, 200.00, 'new'),
(53, 42, 6, 'Sambusa', 3, 100.00, 'new'),
(54, 43, 3, 'Lemon Mojito', 2, 300.00, 'new'),
(55, 44, 4, 'Ugali Kuku', 2, 600.00, 'new'),
(56, 45, 5, 'Capuchino', 2, 200.00, 'new'),
(57, 46, 3, 'Lemon Mojito', 1, 300.00, 'new'),
(58, 47, 4, 'Ugali Kuku', 2, 600.00, 'new'),
(59, 48, 3, 'Lemon Mojito', 2, 300.00, 'new'),
(60, 49, 3, 'Lemon Mojito', 2, 300.00, 'new'),
(61, 49, 2, 'Caramel Special', 2, 300.00, 'new'),
(62, 50, 7, 'Chips Choma', 1, 400.00, 'new'),
(63, 51, 7, 'Chips Choma', 1, 400.00, 'new'),
(64, 51, 4, 'Ugali Kuku', 1, 600.00, 'new'),
(65, 52, 4, 'Ugali Kuku', 1, 600.00, 'new'),
(66, 53, 2, 'Caramel Special', 1, 300.00, 'new'),
(67, 53, 5, 'Capuchino', 1, 200.00, 'new');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category_id`, `price`, `is_available`, `created_at`, `description`, `image`) VALUES
(2, 'Caramel Special', 2, 300.00, 1, '2025-05-20 19:35:28', 'Camel milk special serve', 'prod_682cd9803be276.25204157.jfif'),
(3, 'Lemon Mojito', 5, 300.00, 1, '2025-05-21 07:45:20', 'Lemonade classic juice with ice', 'prod_682d8490199186.77427255.jfif'),
(4, 'Ugali Kuku', 4, 600.00, 1, '2025-05-21 11:20:38', 'Kienyeji Kuku, greens with Ugali', 'prod_682db7065786c1.22453634.jfif'),
(5, 'Capuchino', 2, 200.00, 1, '2025-05-21 17:01:25', 'Camel milk tea seasoned with black tea', 'prod_682e06e579c239.05478406.jfif'),
(6, 'Sambusa', 2, 100.00, 1, '2025-05-21 17:02:45', 'Delicious meat,ndengu sambusa', 'prod_682e07351be5f3.21547799.jfif'),
(7, 'Chips Choma', 3, 400.00, 1, '2025-05-21 17:05:41', 'Chips with seasoned  beefmeat', 'prod_682e07e58f4334.31490771.jfif'),
(8, 'pizza', 6, 800.00, 1, '2025-05-26 13:13:59', 'chicken flavour', 'prod_683469175b4395.17316483.png');

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

CREATE TABLE `receipts` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `receipt_number` varchar(20) DEFAULT NULL,
  `printed_by` int(11) DEFAULT NULL,
  `cashier_id` int(11) DEFAULT NULL,
  `payment_status` enum('unpaid','paid') DEFAULT 'unpaid',
  `payment_time` timestamp NULL DEFAULT NULL,
  `unique_code` char(8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` enum('cash','card','mobile') DEFAULT 'cash',
  `amount_received` decimal(10,2) DEFAULT NULL,
  `change_given` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipts`
--

INSERT INTO `receipts` (`id`, `order_id`, `receipt_number`, `printed_by`, `cashier_id`, `payment_status`, `payment_time`, `unique_code`, `created_at`, `payment_method`, `amount_received`, `change_given`) VALUES
(1, 14, 'RCPT-20250523-0014', 8, NULL, '', NULL, '06353732', '2025-05-24 20:30:25', 'cash', NULL, NULL),
(2, 15, 'RCPT-20250523-0015', 8, NULL, '', NULL, '63889502', '2025-05-24 20:30:25', 'cash', NULL, NULL),
(3, 16, 'RCPT-20250523-0016', 8, NULL, '', NULL, '05359021', '2025-05-24 20:30:25', 'cash', NULL, NULL),
(4, 17, 'RCPT-20250523-0017', 8, NULL, '', NULL, '94741139', '2025-05-24 20:30:25', 'cash', NULL, NULL),
(5, 18, 'RCPT-20250523-0018', 8, NULL, '', NULL, '73882554', '2025-05-24 20:30:25', 'cash', NULL, NULL),
(6, 19, 'RCPT-20250523-0019', 8, NULL, '', NULL, '19416544', '2025-05-24 20:30:25', 'cash', NULL, NULL),
(7, 20, 'RCPT-20250524-0020', 8, NULL, '', NULL, '45484588', '2025-05-24 20:30:25', 'cash', NULL, NULL),
(8, 21, 'RCPT-20250524-0021', 8, NULL, '', NULL, '18277670', '2025-05-24 20:30:25', 'cash', NULL, NULL),
(9, 22, 'RCPT-20250524-0022', 8, NULL, '', NULL, '20924529', '2025-05-24 20:30:25', 'cash', NULL, NULL),
(10, 23, 'RCPT-20250524-0023', 8, NULL, '', NULL, '32254492', '2025-05-24 20:30:25', 'cash', NULL, NULL),
(11, 24, 'RCPT-20250524-0024', 8, NULL, '', NULL, '09888392', '2025-05-24 20:30:25', 'cash', NULL, NULL),
(12, 25, 'RCPT-20250524-0025', 8, NULL, '', NULL, '92662203', '2025-05-24 20:30:25', 'cash', NULL, NULL),
(13, 26, 'RCPT-20250524-0026', 1, NULL, '', NULL, '45343173', '2025-05-24 20:30:25', 'cash', NULL, NULL),
(14, 27, 'RCPT-20250524-0027', 8, NULL, '', NULL, '34918419', '2025-05-24 20:30:25', 'cash', NULL, NULL),
(15, 28, 'RCPT-20250524-0028', 8, NULL, '', NULL, '76931967', '2025-05-24 20:31:25', 'cash', NULL, NULL),
(16, 29, 'RCPT-20250524-0029', 8, NULL, '', NULL, '85611562', '2025-05-24 20:39:58', 'cash', NULL, NULL),
(17, 30, 'RCPT-20250524-0030', 8, NULL, '', NULL, '61972053', '2025-05-24 20:51:40', 'cash', NULL, NULL),
(18, 31, 'RCPT-20250524-0031', 8, NULL, '', NULL, '83778047', '2025-05-24 21:20:08', 'cash', NULL, NULL),
(19, 32, 'RCPT-20250524-0032', 8, NULL, '', NULL, '70465301', '2025-05-24 21:30:46', 'cash', NULL, NULL),
(20, 33, 'RCPT-20250525-0033', 9, NULL, '', NULL, '65863243', '2025-05-24 22:20:04', 'cash', NULL, NULL),
(21, 34, 'RCPT-20250525-0034', 8, NULL, '', NULL, '01211509', '2025-05-24 22:32:46', 'cash', NULL, NULL),
(22, 35, 'RCPT-20250525-0035', 8, NULL, '', NULL, '64762512', '2025-05-25 10:49:19', 'cash', NULL, NULL),
(23, 36, 'RCPT-20250525-0036', 8, NULL, '', NULL, '94742267', '2025-05-25 10:50:09', 'cash', NULL, NULL),
(24, 37, 'RCPT-20250525-0037', 8, NULL, '', NULL, '81913423', '2025-05-25 10:50:21', 'cash', NULL, NULL),
(25, 38, 'RCPT-20250525-0038', 8, NULL, '', NULL, '40565741', '2025-05-25 10:52:35', 'cash', NULL, NULL),
(26, 39, 'RCPT-20250525-0039', 8, 9, 'paid', '2025-05-25 13:10:38', '41981924', '2025-05-25 11:20:08', 'cash', 600.00, 0.00),
(27, 40, 'RCPT-20250525-0040', 8, 9, 'paid', '2025-05-25 13:09:59', '40444491', '2025-05-25 11:23:58', 'cash', 1200.00, 0.00),
(28, 41, 'RCPT-20250525-0041', 1, 9, 'paid', '2025-05-25 13:47:57', '38691031', '2025-05-25 13:11:12', 'cash', 400.00, 0.00),
(29, 42, 'RCPT-20250525-0042', 1, 9, 'paid', '2025-05-25 13:59:33', '78169107', '2025-05-25 13:59:09', 'cash', 2000.00, 100.00),
(30, 43, 'RCPT-20250525-0043', 1, 9, 'paid', '2025-05-25 14:20:37', '71842156', '2025-05-25 14:17:19', 'cash', 600.00, 0.00),
(31, 44, 'RCPT-20250525-0044', 8, 9, 'paid', '2025-05-25 14:42:47', '81518335', '2025-05-25 14:29:18', 'cash', 1200.00, 0.00),
(32, 45, 'RCPT-20250525-0045', 8, 9, 'paid', '2025-05-25 15:09:28', '09196245', '2025-05-25 14:43:47', 'cash', 400.00, 0.00),
(33, 46, 'RCPT-20250525-0046', 1, 9, 'paid', '2025-05-25 20:53:57', '99787998', '2025-05-25 20:51:24', 'cash', 1000.00, 700.00),
(34, 47, 'RCPT-20250525-0047', 8, 9, 'paid', '2025-05-25 21:20:34', '43585131', '2025-05-25 21:07:19', 'cash', 1200.00, 0.00),
(35, 48, 'RCPT-20250525-0048', 8, 9, 'paid', '2025-05-25 21:36:36', '17647535', '2025-05-25 21:35:33', 'cash', 600.00, 0.00),
(36, 49, 'RCPT-20250526-0049', 8, NULL, 'unpaid', NULL, '42766529', '2025-05-26 07:58:51', 'cash', NULL, NULL),
(37, 50, 'RCPT-20250526-0050', 1, NULL, 'unpaid', NULL, '91374838', '2025-05-26 08:01:13', 'cash', NULL, NULL),
(38, 51, 'RCPT-20250526-0051', 1, 9, 'paid', '2025-05-26 12:11:08', '74060879', '2025-05-26 12:09:36', 'cash', 2500.00, 1500.00),
(39, 52, 'RCPT-20250526-0052', 1, NULL, 'unpaid', NULL, '26839055', '2025-05-26 12:12:20', 'cash', NULL, NULL),
(40, 53, 'RCPT-20250526-0053', 8, 9, 'paid', '2025-05-26 13:17:32', '50030755', '2025-05-26 13:16:15', 'cash', 500.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `receipt_status`
--

CREATE TABLE `receipt_status` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) NOT NULL,
  `status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipt_status`
--

INSERT INTO `receipt_status` (`id`, `receipt_id`, `status`, `updated_at`) VALUES
(1, 1, 'pending', '2025-05-23 14:09:00'),
(2, 2, 'pending', '2025-05-23 14:09:06'),
(3, 3, 'pending', '2025-05-23 14:09:10'),
(4, 4, 'pending', '2025-05-23 14:09:16'),
(5, 5, 'pending', '2025-05-23 15:42:30'),
(6, 6, 'pending', '2025-05-23 15:42:36'),
(7, 7, 'pending', '2025-05-24 09:09:48'),
(8, 8, 'pending', '2025-05-24 15:58:33'),
(9, 9, 'pending', '2025-05-24 16:04:04'),
(10, 10, 'pending', '2025-05-24 16:18:41'),
(11, 27, 'paid', '2025-05-25 13:09:59'),
(12, 26, 'paid', '2025-05-25 13:10:25'),
(13, 26, 'paid', '2025-05-25 13:10:38'),
(14, 28, 'paid', '2025-05-25 13:47:26'),
(15, 28, 'paid', '2025-05-25 13:47:57'),
(16, 29, 'paid', '2025-05-25 13:59:33'),
(17, 30, 'paid', '2025-05-25 14:20:37'),
(18, 31, 'paid', '2025-05-25 14:42:03'),
(19, 31, 'paid', '2025-05-25 14:42:47');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_type` varchar(50) DEFAULT NULL,
  `price_per_night` decimal(10,2) DEFAULT NULL,
  `is_occupied` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `role` enum('admin','waiter','cashier','kitchen') NOT NULL,
  `pin` char(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `full_name`, `username`, `role`, `pin`, `created_at`, `active`) VALUES
(1, 'John Doe', NULL, 'user1', 'waiter', '123456', '2025-05-20 08:08:49', 0),
(6, 'Admin', NULL, 'user6', 'admin', '234567', '2025-05-20 12:18:47', 0),
(7, 'Jose', 'Joseph Ogachi', 'ogachi', 'waiter', '345678', '2025-05-20 20:58:51', 0),
(8, 'Halima', 'Halima Sheihk', 'SheihkH', 'waiter', '000000', '2025-05-21 16:04:41', 0),
(9, 'Abdi', 'Abdi Hasan', 'Hasan', 'cashier', '111111', '2025-05-21 21:58:34', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `analytics`
--
ALTER TABLE `analytics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `discounts`
--
ALTER TABLE `discounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `waiter_id` (`waiter_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `fk_user_order` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `receipts`
--
ALTER TABLE `receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD UNIQUE KEY `unique_code` (`unique_code`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `printed_by` (`printed_by`),
  ADD KEY `cashier_id` (`cashier_id`);

--
-- Indexes for table `receipt_status`
--
ALTER TABLE `receipt_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receipt_id` (`receipt_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pin` (`pin`),
  ADD UNIQUE KEY `username_2` (`username`),
  ADD UNIQUE KEY `username_3` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `analytics`
--
ALTER TABLE `analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `discounts`
--
ALTER TABLE `discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `receipts`
--
ALTER TABLE `receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `receipt_status`
--
ALTER TABLE `receipt_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_user_order` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`waiter_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `receipts`
--
ALTER TABLE `receipts`
  ADD CONSTRAINT `receipts_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `receipts_ibfk_2` FOREIGN KEY (`printed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `receipts_ibfk_3` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `receipt_status`
--
ALTER TABLE `receipt_status`
  ADD CONSTRAINT `receipt_status_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `receipts` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
