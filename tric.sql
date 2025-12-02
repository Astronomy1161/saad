-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 02, 2025 at 05:49 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tric`
--

-- --------------------------------------------------------

--
-- Table structure for table `application`
--

CREATE TABLE `application` (
  `applicationId` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `requirements` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `date_applied` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application`
--

INSERT INTO `application` (`applicationId`, `firstname`, `lastname`, `email`, `contact`, `requirements`, `status`, `date_applied`) VALUES
(1, 'andrew', 'borja', 'fredierick.ugalino@cvsu.edu.ph', '09944672207', '1763724774_bgg.jpg', 'approved', '2025-11-21 11:32:54'),
(2, 'kiko', 'matos', 'kikomatos@g', '1234', '1763804059_130b7ab6-66e4-4253-b7f2-d7553fe3b54a.jpg', 'approved', '2025-11-22 09:34:19');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pin` varchar(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rider`
--

CREATE TABLE `rider` (
  `riderId` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plate_number` varchar(50) NOT NULL,
  `profile` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `application_id` int(11) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rider`
--

INSERT INTO `rider` (`riderId`, `user_id`, `plate_number`, `profile`, `address`, `application_id`, `latitude`, `longitude`, `last_update`) VALUES
(1, 2, '1234', 'default.jpg', 'Not Set', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rides`
--

CREATE TABLE `rides` (
  `rideId` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rider_id` int(11) DEFAULT NULL,
  `pickup_location` varchar(255) NOT NULL,
  `dropoff_location` varchar(255) NOT NULL,
  `status` enum('pending','accepted','in_progress','completed','cancelled') DEFAULT 'pending',
  `fare` decimal(10,2) DEFAULT NULL,
  `date_requested` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_completed` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rides`
--

INSERT INTO `rides` (`rideId`, `user_id`, `rider_id`, `pickup_location`, `dropoff_location`, `status`, `fare`, `date_requested`, `date_completed`) VALUES
(2, 6, 1, 'molino 1', 'maliksi 2', 'accepted', 138.00, '2025-11-23 17:42:58', NULL),
(3, 6, 1, 'molino 1', 'maliksi 2', 'accepted', 102.00, '2025-11-27 06:47:24', NULL),
(4, 6, 1, 'molino 1', 'maliksi 2', 'accepted', 90.00, '2025-11-28 11:11:20', NULL),
(5, 6, 1, 'Muntinlupa District 2, Muntinlupa, Southern Manila District, Metro Manila, Philippines', 'Imus, Cavite, Calabarzon, 4103, Philippines', 'completed', 199.73, '2025-11-28 12:11:33', '2025-11-28 12:41:17'),
(6, 6, 1, '14.462110, 120.949665', '14.452052, 120.932393', 'accepted', 50.00, '2025-11-28 12:46:21', NULL),
(7, 6, 1, 'Francel Town Homes, Bacoor, Cavite, Calabarzon, 4102, Philippines', 'Samala-Marquez, Binakayan, Kawit, Cavite, Calabarzon, 4104, Philippines', 'accepted', 63.92, '2025-11-28 13:07:40', NULL),
(8, 6, 1, '', '', 'accepted', 50.00, '2025-11-29 06:26:24', NULL),
(9, 6, 1, 'Imus, Cavite, Calabarzon, 4103, Philippines', 'Justinville, Panapaan V, Bacoor, Cavite, Calabarzon, 4102, Philippines', 'completed', 50.00, '2025-11-29 06:39:20', '2025-11-29 06:39:48'),
(10, 6, 1, 'Imus, Cavite, Calabarzon, 4103, Philippines', '', 'pending', 266576.97, '2025-11-29 06:44:05', NULL),
(11, 6, 1, 'Imus, Cavite, Calabarzon, 4103, Philippines', 'Bayanan, Bacoor, Cavite, Calabarzon, 4102, Philippines', 'pending', 50.00, '2025-11-29 06:44:26', NULL),
(12, 6, 1, 'Imus, Cavite, Calabarzon, 4103, Philippines', 'Panapaan VI, Bacoor, Cavite, Calabarzon, 4102, Philippines', 'accepted', 50.00, '2025-11-29 06:44:39', NULL),
(13, 6, 1, 'Imus, Cavite, Calabarzon, 4103, Philippines', '', 'pending', 266576.94, '2025-11-29 06:47:03', NULL),
(14, 6, 1, 'Imus, Cavite, Calabarzon, 4103, Philippines', 'Mambog, Bacoor, Cavite, Calabarzon, 4102, Philippines', 'pending', 50.00, '2025-11-29 06:47:16', NULL),
(15, 6, 1, 'Imus, Cavite, Calabarzon, 4103, Philippines', 'Panapaan VI, Bacoor, Cavite, Calabarzon, 4102, Philippines', 'pending', 50.00, '2025-11-29 06:47:26', NULL),
(16, 6, 1, 'Imus, Cavite, Calabarzon, 4103, Philippines', 'Balimbing Drive, Meadowood Executive Village, Panapaan VI, Bacoor, Cavite, Calabarzon, 4102, Philippines', 'pending', 50.00, '2025-11-29 06:47:35', NULL),
(17, 6, 1, 'Gate, Sorsogon, Bicol Region, Philippines', 'Gate, Sorsogon, Bicol Region, Philippines', 'pending', 50.00, '2025-11-29 06:48:00', NULL),
(18, 6, 1, 'Gate, Sorsogon, Bicol Region, Philippines', 'Gate, Sorsogon, Bicol Region, Philippines', 'pending', 50.00, '2025-11-29 06:48:08', NULL),
(19, 6, 1, 'Gate, Sorsogon, Bicol Region, Philippines', 'Bulan, Sorsogon, Bicol Region, 4706, Philippines', 'pending', 130.44, '2025-11-29 06:48:15', NULL),
(20, 6, 1, 'Gate, Sorsogon, Bicol Region, Philippines', '14.436010, 120.967155', 'pending', 7617.43, '2025-11-29 06:51:01', NULL),
(21, 6, 1, 'Gate, Sorsogon, Bicol Region, Philippines', '14.436010, 120.967155', 'pending', 7617.43, '2025-11-29 06:51:38', NULL),
(22, 6, 1, 'Gate, Sorsogon, Bicol Region, Philippines', '14.436010, 120.967155', 'pending', 7617.43, '2025-11-29 06:52:24', NULL),
(23, 6, 1, 'Gate, Sorsogon, Bicol Region, Philippines', '14.436010, 120.967155', 'pending', 7617.43, '2025-11-29 06:52:27', NULL),
(24, 6, 1, '', '14.457454, 120.960588', 'pending', 266596.89, '2025-11-29 08:35:06', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','rider','admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `contact`, `password`, `role`) VALUES
(1, 'fredierick', 'ugalino', 'ufredierick@gmail.com', '09485209464', '$2y$10$htIjMyb257/z.n/c/v4pQe0Vexb3yMh0bM2aq2pr9mWK6WUgXqd..', 'admin'),
(2, 'andrew', 'borja', 'fredierick.ugalino@cvsu.edu.ph', '09944672207', '$2y$10$uXGKVTtzwVRPboJrSE1w.OGNAJGGAvKGN7.T9AN2XJ.am/z/RIyZ6', 'rider'),
(3, 'Rodelyn', 'De paz', 'DEPAZ@gmail.com', '09123456789', '$2y$10$k.AlbPeNYzT.zLJrEkiuxOgqEUtNl1dOAGHDliv9NEnKBmAfXcZ8S', 'user'),
(4, 'wyene', 'de castro', 'wyene@gmail.com', '09944672207', '$2y$10$BrNbR8e2oLln4uMi9OQLpO6yhc1JMeITLWl4vmqSftLTwzI1bAkw.', 'user'),
(5, 'kiko', 'matos', 'kikomatos@g', '1234', '$2y$10$zQgpRa1QUlKFOqzsxpW3iu/FQlwyQhOuAMrCvosklMKh/uxJTWyTW', 'rider'),
(6, 'klein', 'morreti', 'Klein@gmail.com', '09999999999', '$2y$10$qr7UN4C3KkdjzzAMX2JfoOrUOWFd5e9TEdrBVs5le9Hxq8dZk2Tqu', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `application`
--
ALTER TABLE `application`
  ADD PRIMARY KEY (`applicationId`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rider`
--
ALTER TABLE `rider`
  ADD PRIMARY KEY (`riderId`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `rides`
--
ALTER TABLE `rides`
  ADD PRIMARY KEY (`rideId`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `rider_id` (`rider_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `application`
--
ALTER TABLE `application`
  MODIFY `applicationId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rider`
--
ALTER TABLE `rider`
  MODIFY `riderId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rides`
--
ALTER TABLE `rides`
  MODIFY `rideId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `rider`
--
ALTER TABLE `rider`
  ADD CONSTRAINT `rider_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `rider_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `application` (`applicationId`);

--
-- Constraints for table `rides`
--
ALTER TABLE `rides`
  ADD CONSTRAINT `rides_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `rides_ibfk_2` FOREIGN KEY (`rider_id`) REFERENCES `rider` (`riderId`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
