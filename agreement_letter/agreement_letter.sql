-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 20, 2025 at 06:59 PM
-- Server version: 10.4.17-MariaDB
-- PHP Version: 8.0.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `agreement_letter`
--

-- --------------------------------------------------------

--
-- Table structure for table `agreements`
--

CREATE TABLE `agreements` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `address` text COLLATE utf8mb4_bin DEFAULT NULL,
  `nic` varchar(20) COLLATE utf8mb4_bin DEFAULT NULL,
  `agreement_date` date DEFAULT NULL,
  `payment_day` int(11) DEFAULT NULL,
  `period_months` int(11) DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `amount_number` decimal(12,2) DEFAULT NULL,
  `amount_words` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `interest_rate` decimal(5,2) DEFAULT NULL,
  `product_sinhala` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `vehicle_number` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `chassis_number` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
  `engine_number` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
  `make` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
  `vehicle_class_sinhala` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `agreements`
--

INSERT INTO `agreements` (`id`, `name`, `address`, `nic`, `agreement_date`, `payment_day`, `period_months`, `end_date`, `amount_number`, `amount_words`, `interest_rate`, `product_sinhala`, `vehicle_number`, `chassis_number`, `engine_number`, `make`, `model`, `vehicle_class_sinhala`, `created_at`, `updated_at`) VALUES
(1, 'නිමල් ප්‍රියන්ත පෙරේරා.', 'අංක 145, නුවරවැව පාර, අනුරාධපුර', '902345678X', '2025-12-20', 10, 24, '2027-12-20', '500000.00', 'රුපියල් පන්ලක්ෂයක් පමණි.', '3.00', 'මෝටර් කාර්', 'WP CAB-1234', 'MHFAXXMR2KJ123456', 'EN123456789', 'Toyota', 'Axio', 'මෝටර් කාර්', '2025-12-20 06:53:21', '2025-12-20 17:05:31'),
(6, 'D. Nathasha Nirmani Amarasekara', 'df df gfsdfg fs', '893252398V', '2025-12-19', 21, 6, '2026-06-19', '67777.00', '5 nertrtgrt', '7.00', 'වාහන', 'ncHI2016', 'MHFAXXMR2KJ123456', '879564a-sadsd', 'china', 'Alto', 'van', '2025-12-20 17:15:17', '2025-12-20 17:15:17'),
(4, 'Suneth Nanayakkara', '555545ර 34 24', '752301042V', '2025-12-13', 7, 5, '2026-05-13', '555.00', 'පන්දදහසයි', '5.00', 'වාහන', 'ncHI2016', 'MHFAXXMR2KJ123456', '879564a-sadsd', 'china', 'bbb', 'van', '2025-12-20 16:01:30', '2025-12-20 16:21:31'),
(8, 'D. Nathasha Nirmani Amarasekara', 'df df gfsdfg fs', '893252398V', '2025-12-19', 15, 6, '2026-06-19', '67777.00', '5 nertrtgrt', '7.00', 'වාහන', 'ncHI2016', 'MHFAXXMR2KJ123456', '879564a-sadsd', 'china', 'Alto', 'van', '2025-12-20 17:16:53', '2025-12-20 17:16:53'),
(7, 'D. Nathasha Nirmani Amarasekara', 'df df gfsdfg fs', '893252398V', '2025-12-19', 21, 6, '2026-06-19', '67777.00', '5 nertrtgrt', '7.00', 'වාහන', 'ncHI2016', 'MHFAXXMR2KJ123456', '879564a-sadsd', 'china', 'Alto', 'van', '2025-12-20 17:15:47', '2025-12-20 17:15:47'),
(9, 'a', 'hjhjhj', '893252398V', '2025-12-18', 8, 6, '2026-06-18', '67777.00', '565656', '7.00', '', '', '', '', '', '', '', '2025-12-20 17:19:09', '2025-12-20 17:19:09'),
(10, 'a', 'hjhjhj', '893252398V', '2025-12-18', 8, 6, '2026-06-18', '67777.00', '565656', '7.00', '', '', '', '', '', '', '', '2025-12-20 17:19:50', '2025-12-20 17:19:50'),
(11, 'fanta', 'ytui drtuhy rthyu', '752301042V', '2025-12-20', 20, 7, '2026-07-20', '10000.00', 'rtyryrtyrty m', '1.00', '', '', '', '', '', '', '', '2025-12-20 17:26:00', '2025-12-20 17:26:00'),
(12, 'fanta', 'ytui drtuhy rthyu', '752301042V', '2025-12-20', 20, 7, '2026-07-20', '10000.00', 'rtyryrtyrty m', '1.00', '', '', '', '', '', '', '', '2025-12-20 17:27:05', '2025-12-20 17:27:05'),
(13, 'fanta', 'ytui drtuhy rthyu', '752301042V', '2025-12-20', 20, 7, '2026-07-20', '10000.00', 'rtyryrtyrty m', '1.00', '', '', '', '', '', '', '', '2025-12-20 17:27:08', '2025-12-20 17:27:08'),
(14, 'fanta', 'ytui drtuhy rthyu', '752301042V', '2025-12-20', 20, 7, '2026-07-20', '10000.00', 'rtyryrtyrty m', '1.00', '', '', '', '', '', '', '', '2025-12-20 17:27:34', '2025-12-20 17:27:34'),
(15, 'fanta', 'ytui drtuhy rthyu', '752301042V', '2025-12-20', 20, 7, '2026-07-20', '10000.00', 'rtyryrtyrty m', '1.00', '', '', '', '', '', '', '', '2025-12-20 17:28:24', '2025-12-20 17:28:24'),
(16, 'fanta', 'ytui drtuhy rthyu', '752301042V', '2025-12-20', 20, 7, '2026-07-20', '10000.00', 'rtyryrtyrty m', '1.00', '', '', '', '', '', '', '', '2025-12-20 17:29:41', '2025-12-20 17:29:41'),
(17, 'fanta', 'ytui drtuhy rthyu', '752301042V', '2025-12-20', 20, 7, '2026-07-20', '10000.00', 'rtyryrtyrty m', '1.00', '', '', '', '', '', '', '', '2025-12-20 17:30:23', '2025-12-20 17:30:23'),
(18, 'fanta', 'ytui drtuhy rthyu', '752301042V', '2025-12-20', 20, 7, '2026-07-20', '10000.00', 'rtyryrtyrty m', '1.00', '', '', '', '', '', '', '', '2025-12-20 17:30:30', '2025-12-20 17:30:30'),
(19, 'D. Nathasha Nirmani Amarasekara', 'fghfghgfh', '752301042V', '2025-12-20', 20, 6, '2026-06-20', '78888.00', 'fghfgh', '8.00', '', '', '', '', '', '', '', '2025-12-20 17:31:06', '2025-12-20 17:31:06'),
(20, 'D. Nathasha Nirmani Amarasekara', 'fghfghgfh', '752301042V', '2025-12-20', 20, 6, '2026-06-20', '78888.00', 'fghfgh', '8.00', '', '', '', '', '', '', '', '2025-12-20 17:31:28', '2025-12-20 17:31:28'),
(21, 'D. Nathasha Nirmani Amarasekara', 'cggvhnh', '752301042V', '2025-12-20', 20, 5, '2026-05-20', '5.00', '5', '5.00', '', '', '', '', '', '', '', '2025-12-20 17:33:01', '2025-12-20 17:33:01'),
(22, 'Hemantha Kumara Eramudugolla', 'ghfhfg', '752301042V', '2025-12-20', 20, 6, '2026-06-20', '6.00', '6', '6.00', '', '', '', '', '', '', '', '2025-12-20 17:36:47', '2025-12-20 17:36:47'),
(23, 'Hemantha Kumara Eramudugolla', 'ghfhfg', '752301042V', '2025-12-20', 20, 6, '2026-06-20', '6.00', '6', '6.00', '', '', '', '', '', '', '', '2025-12-20 17:36:56', '2025-12-20 17:36:56'),
(24, 'D. Nathasha Nirmani Amarasekara', 'hbnh', '735690566V', '2025-12-20', 20, 6, '2026-06-20', '6.00', 'ghghghgh', '6.00', '', '', '', '', '', '', '', '2025-12-20 17:37:34', '2025-12-20 17:37:34'),
(25, 'fghfgh', 'dfhdfhfgh', '752301042V', '2025-12-20', 20, 5, '2026-05-20', '5.00', 'dfgdfgfs', '5.00', '', '', '', '', '', '', '', '2025-12-20 17:40:26', '2025-12-20 17:40:26'),
(26, 'fghfgh', 'dfhdfhfgh', '752301042V', '2025-12-20', 20, 5, '2026-05-20', '5.00', 'dfgdfgfs', '5.00', '', '', '', '', '', '', '', '2025-12-20 17:40:40', '2025-12-20 17:40:40'),
(27, 'D. Nathasha Nirmani Amarasekara', 'fdghgfh', '752301042V', '2025-12-20', 20, 5, '2026-05-20', '5.00', 'fghfgh', '56.00', '', '', '', '', '', '', '', '2025-12-20 17:41:33', '2025-12-20 17:41:33'),
(28, 'Suneth Nanayakkara', 'sfsfsf', '752301042V', '2025-12-20', 20, 7, '2026-07-20', '7.00', 'hjghj', '7.00', '', '', '', '', '', '', '', '2025-12-20 17:42:18', '2025-12-20 17:42:18'),
(29, 'Hemantha Kumara Eramudugolla', 'ftgfdg', '735690566V', '2025-12-20', 20, 5, '2026-05-20', '5.00', 'frdgfdgf', '5.00', '', '', '', '', '', '', '', '2025-12-20 17:43:03', '2025-12-20 17:43:03'),
(30, 'Hemantha Kumara Eramudugolla', 'fdgdfgfg', '752301042V', '2025-12-20', 20, 6, '2026-06-20', '6.00', 'ghg', '6.00', '', '', '', '', '', '', '', '2025-12-20 17:47:37', '2025-12-20 17:47:37'),
(31, 'Hemantha Kumara Eramudugolla', 'sdfsdf', '752301042V', '2025-12-20', 20, 5, '2026-05-20', '5.00', 'dfdsf', '5.00', '', '', '', '', '', '', '', '2025-12-20 17:57:26', '2025-12-20 17:57:26'),
(32, 'Hemantha Kumara Eramudugolla', 'sdfsdf', '752301042V', '2025-12-20', 20, 5, '2026-05-20', '5.00', 'dfdsf', '5.00', '', '', '', '', '', '', '', '2025-12-20 17:57:36', '2025-12-20 17:57:36'),
(33, 'Hemantha Kumara Eramudugolla', 'sdfsdf', '752301042V', '2025-12-20', 20, 5, '2026-05-20', '5.00', 'dfdsf', '5.00', '', '', '', '', '', '', '', '2025-12-20 17:57:54', '2025-12-20 17:57:54');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '123456', '2025-12-20 05:24:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agreements`
--
ALTER TABLE `agreements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agreements`
--
ALTER TABLE `agreements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
