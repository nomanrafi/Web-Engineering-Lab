-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 20, 2025 at 09:05 AM
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
-- Database: `sales_navigator_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `territory` varchar(100) DEFAULT NULL,
  `total_orders` int(11) DEFAULT 0,
  `total_revenue` decimal(10,2) DEFAULT 0.00,
  `last_contact_date` date DEFAULT NULL,
  `status` enum('Active','New','Inactive') DEFAULT 'New',
  `added_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_name`, `contact_person`, `email`, `address`, `territory`, `total_orders`, `total_revenue`, `last_contact_date`, `status`, `added_by_user_id`, `created_at`) VALUES
(14, 'bb', 'bb', 'bb@gmail.com', 'bb', 'Gazipur Sadar North', 0, 0.00, '2025-08-20', 'New', 1, '2025-08-20 00:34:54'),
(15, 'c', 'c', 'c@gmail.com', 'c', 'Gazipur Sadar North', 0, 0.00, '2025-08-20', 'Inactive', 1, '2025-08-20 00:38:35'),
(17, 'ddd', 'dddd', 'aa@gmail.com', 'fgfdg', 'Gazipur Sadar North', 0, 0.00, '2025-08-20', 'New', 1, '2025-08-20 01:23:18');

-- --------------------------------------------------------

--
-- Table structure for table `customer_segments_monthly`
--

CREATE TABLE `customer_segments_monthly` (
  `segment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `report_month` int(11) NOT NULL,
  `report_year` int(11) NOT NULL,
  `segment_type` enum('Premium','Regular','New') NOT NULL,
  `customer_count` int(11) DEFAULT 0,
  `total_revenue_segment` decimal(15,2) DEFAULT 0.00,
  `avg_order_value_segment` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `sales_rep_id` int(11) NOT NULL,
  `order_total` decimal(10,2) NOT NULL,
  `product_details` text NOT NULL,
  `quantity` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `status` enum('Pending','Processing','Shipped','Delivered','Cancelled','Refunded') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `sales_rep_id`, `order_total`, `product_details`, `quantity`, `order_date`, `delivery_date`, `status`) VALUES
(2, 17, 1, 2.00, '0', 2, '2025-08-12', '0000-00-00', 'Delivered'),
(3, 15, 1, 33.00, '0', 22, '2025-08-21', '0000-00-00', 'Refunded');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `price`, `stock_quantity`, `description`, `category_id`) VALUES
(9, 'Hp', 60000.00, 3, 'laptop medium quality', NULL),
(10, 'Acer', 90000.00, 4, 'laptop High quality', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_analytics_summary`
--

CREATE TABLE `sales_analytics_summary` (
  `summary_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `total_sales` decimal(15,2) DEFAULT 0.00,
  `total_orders` int(11) DEFAULT 0,
  `new_customers` int(11) DEFAULT 0,
  `achievement_rate` decimal(5,2) DEFAULT 0.00,
  `avg_order_value` decimal(10,2) DEFAULT 0.00,
  `conversion_rate` decimal(5,2) DEFAULT 0.00,
  `monthly_growth` decimal(5,2) DEFAULT 0.00,
  `customer_growth` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_by_category_monthly`
--

CREATE TABLE `sales_by_category_monthly` (
  `category_sales_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `report_month` int(11) NOT NULL,
  `report_year` int(11) NOT NULL,
  `total_sales_category` decimal(15,2) DEFAULT 0.00,
  `total_orders_category` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_data`
--

CREATE TABLE `sales_data` (
  `data_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `month` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  `total_sales` decimal(10,2) NOT NULL,
  `sales_target` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_trends_monthly`
--

CREATE TABLE `sales_trends_monthly` (
  `trend_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `report_month` int(11) NOT NULL,
  `report_year` int(11) NOT NULL,
  `monthly_sales` decimal(15,2) DEFAULT 0.00,
  `monthly_orders` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `targets`
--

CREATE TABLE `targets` (
  `target_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `target_name` varchar(255) NOT NULL,
  `target_type` enum('Monthly','Quarterly','Yearly','Product Launch','Team Performance') NOT NULL,
  `target_value` decimal(10,2) NOT NULL,
  `achieved_value` decimal(10,2) DEFAULT 0.00,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `assigned_territory` varchar(100) DEFAULT NULL,
  `status` enum('On Track','At Risk','Critical','Complete') DEFAULT 'On Track',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `targets`
--

INSERT INTO `targets` (`target_id`, `user_id`, `target_name`, `target_type`, `target_value`, `achieved_value`, `start_date`, `end_date`, `assigned_territory`, `status`, `created_at`) VALUES
(1, 1, 'aaa', 'Yearly', 2.00, 22.00, '2025-08-19', '2025-08-21', 'Gazipur Sadar North', 'At Risk', '2025-08-20 03:09:23');

-- --------------------------------------------------------

--
-- Table structure for table `territory_sales_monthly`
--

CREATE TABLE `territory_sales_monthly` (
  `territory_sales_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_territory` varchar(100) NOT NULL,
  `report_month` int(11) NOT NULL,
  `report_year` int(11) NOT NULL,
  `monthly_sales` decimal(15,2) DEFAULT 0.00,
  `monthly_target` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `role` enum('HOM','NSM','DSM','ASM','TSM','SR') NOT NULL,
  `division` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `upazila` varchar(100) DEFAULT NULL,
  `territory` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `reports_to_user_id` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive','New') DEFAULT 'New'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone_number`, `role`, `division`, `district`, `upazila`, `territory`, `password`, `created_at`, `last_login`, `reports_to_user_id`, `status`) VALUES
(1, 'aaa', 'a@gmail.com', '1222222222', 'SR', 'Dhaka', 'Gazipur District', 'Gazipur Sadar', 'Gazipur Sadar North', '$2y$10$4uhFb3i.vZy8UStv36gnnO4ZkPEgVLw3T6RXChrpm.D1oZFNAN9cy', '2025-08-19 19:54:22', NULL, NULL, 'New'),
(2, 'aaaa', 'aa@gmail.com', '1834567890', 'HOM', '', '', '', '', '$2y$10$YsoPahVgMvfGYDRpyrufAe2qVlQeSI25oNdiwiEyhZbrb6J2YOekO', '2025-08-19 19:55:48', NULL, NULL, 'New'),
(3, 'dsfdsf', 'sdfds@gmail.com', '1822222222', 'HOM', '', '', '', '', '$2y$10$Zp.uyAPGkuhfQIPtyshxBeM60APWOX/JhPVp/bO7kA7XJUX3WaCiy', '2025-08-19 20:04:47', NULL, NULL, 'New'),
(12, 'Samir Hamza', 'bzsisqibmx@cross.edu.pl', '1518361267', 'TSM', 'Chittagong', 'Chattogram District', 'Anwara', '', '$2y$10$COW2WlccpUcy3UvuIMKViOx3.aLtWIVohjVruYW91FWwCZBILLu9K', '2025-08-19 20:11:49', NULL, NULL, 'New'),
(15, 'sdfsd', 'aaaaaa@gmail.com', '1897654321', 'DSM', 'Rajshahi', NULL, NULL, NULL, '$2y$10$Oi9yv8u3XaI9NgFAS8.KaOP2FzipqDSIJApOdj2mY0PJxFrIlh7/O', '2025-08-19 20:15:23', NULL, NULL, 'New'),
(16, 'dgd', 'aaaa@gmail.com', '1234567890', 'ASM', 'Dhaka', 'Dhaka District', NULL, NULL, '$2y$10$jQ.OEQwVso0vOS/Ag3SHf.fX6WLUrCbYjKLv8j9AR/ct0xt6q9EbC', '2025-08-19 20:16:25', NULL, NULL, 'New'),
(17, 'fdhdfh', 'fghbfgdhb@gmail.com', '1345555555', 'TSM', 'Khulna', 'Khulna District', 'Dumuria', NULL, '$2y$10$eRf/147427p3CAki/zTPTuCTSStb4XGMOCKuzX3..fsUdcchsueMa', '2025-08-19 20:17:28', NULL, NULL, 'New'),
(18, 'jodu modu', 'dsgds@gmi.com', '1899999996', 'SR', 'Dhaka', 'Dhaka District', 'Ashulia', 'Ashulia Ind. Zone', '$2y$10$82YGypLI5ZPnkCEuKPT6ceUrZ.gdcvLMdpI8bc4igE/XS9PnRUcUS', '2025-08-19 20:18:21', NULL, NULL, 'New'),
(19, 'dfgds', 'dsf@fgfg.com', '1222222222', 'NSM', NULL, NULL, NULL, NULL, '$2y$10$ZAsUc6ITgMzcckMDWexgdO5FtnfoCfMKGFo2IpcbBbtq77lsPbF46', '2025-08-19 20:18:52', NULL, NULL, 'New'),
(20, 'Samir Hamz', 'bzsisqibmx@cross.edu.p', '1234345566', 'SR', 'Chittagong', 'Chattogram District', 'Anwara', 'Anwara Coast Territory', '$2y$10$l.swmj5VeZ744f79tEeX.e6M1bzb0odpfQuoADcX5o1H//D7naIH.', '2025-08-19 20:38:52', NULL, NULL, 'New'),
(24, 'bfcx', 'fdgfdg@xfgfx.c', '1111111111', 'HOM', NULL, NULL, NULL, NULL, '$2y$10$PlF9T7158ipi9qI/laku9OilE5EeBLSfVWYBkIjzZvSryVrsomw9C', '2025-08-19 21:06:41', NULL, NULL, 'New');

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `setting_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `push_notifications` tinyint(1) DEFAULT 0,
  `sms_notifications` tinyint(1) DEFAULT 1,
  `order_alerts` tinyint(1) DEFAULT 1,
  `target_achievement_alerts` tinyint(1) DEFAULT 0,
  `system_maintenance_alerts` tinyint(1) DEFAULT 1,
  `location_tracking_enabled` tinyint(1) DEFAULT 1,
  `share_location_with_team` tinyint(1) DEFAULT 0,
  `tracking_frequency` enum('Normal (Every 2 minutes)','High (Every 30 seconds)','Low (Every 5 minutes)') DEFAULT 'Normal (Every 2 minutes)',
  `track_only_working_hours` tinyint(1) DEFAULT 1,
  `language` varchar(50) DEFAULT 'English',
  `currency` varchar(10) DEFAULT 'BDT',
  `date_format` varchar(20) DEFAULT 'DD/MM/YYYY',
  `auto_logout_minutes` int(11) DEFAULT 60,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD KEY `added_by_user_id` (`added_by_user_id`);

--
-- Indexes for table `customer_segments_monthly`
--
ALTER TABLE `customer_segments_monthly`
  ADD PRIMARY KEY (`segment_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`report_month`,`report_year`,`segment_type`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `sales_rep_id` (`sales_rep_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `sales_analytics_summary`
--
ALTER TABLE `sales_analytics_summary`
  ADD PRIMARY KEY (`summary_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`report_date`);

--
-- Indexes for table `sales_by_category_monthly`
--
ALTER TABLE `sales_by_category_monthly`
  ADD PRIMARY KEY (`category_sales_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`category_id`,`report_month`,`report_year`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `sales_data`
--
ALTER TABLE `sales_data`
  ADD PRIMARY KEY (`data_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sales_trends_monthly`
--
ALTER TABLE `sales_trends_monthly`
  ADD PRIMARY KEY (`trend_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`report_month`,`report_year`);

--
-- Indexes for table `targets`
--
ALTER TABLE `targets`
  ADD PRIMARY KEY (`target_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `territory_sales_monthly`
--
ALTER TABLE `territory_sales_monthly`
  ADD PRIMARY KEY (`territory_sales_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`assigned_territory`,`report_month`,`report_year`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_reports_to` (`reports_to_user_id`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `customer_segments_monthly`
--
ALTER TABLE `customer_segments_monthly`
  MODIFY `segment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_analytics_summary`
--
ALTER TABLE `sales_analytics_summary`
  MODIFY `summary_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_by_category_monthly`
--
ALTER TABLE `sales_by_category_monthly`
  MODIFY `category_sales_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_data`
--
ALTER TABLE `sales_data`
  MODIFY `data_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_trends_monthly`
--
ALTER TABLE `sales_trends_monthly`
  MODIFY `trend_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `targets`
--
ALTER TABLE `targets`
  MODIFY `target_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `territory_sales_monthly`
--
ALTER TABLE `territory_sales_monthly`
  MODIFY `territory_sales_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`added_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `customer_segments_monthly`
--
ALTER TABLE `customer_segments_monthly`
  ADD CONSTRAINT `customer_segments_monthly_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`sales_rep_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_analytics_summary`
--
ALTER TABLE `sales_analytics_summary`
  ADD CONSTRAINT `sales_analytics_summary_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_by_category_monthly`
--
ALTER TABLE `sales_by_category_monthly`
  ADD CONSTRAINT `sales_by_category_monthly_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_by_category_monthly_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_data`
--
ALTER TABLE `sales_data`
  ADD CONSTRAINT `sales_data_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_trends_monthly`
--
ALTER TABLE `sales_trends_monthly`
  ADD CONSTRAINT `sales_trends_monthly_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `targets`
--
ALTER TABLE `targets`
  ADD CONSTRAINT `targets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `territory_sales_monthly`
--
ALTER TABLE `territory_sales_monthly`
  ADD CONSTRAINT `territory_sales_monthly_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_reports_to` FOREIGN KEY (`reports_to_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
