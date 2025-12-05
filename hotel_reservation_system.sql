-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 05, 2025 at 02:40 PM
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
-- Database: `hotel_reservation_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `check_in_date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_date` date NOT NULL,
  `number_of_guests` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected','cancelled','completed') DEFAULT 'pending',
  `payment_status` enum('unpaid','paid') DEFAULT 'unpaid',
  `payment_method` enum('cash','online','gcash','paymaya') DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `checkout_date` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_reference` varchar(100) DEFAULT NULL,
  `has_review` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `room_type` enum('single','double','suite','deluxe','family') NOT NULL,
  `capacity` int(11) NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `amenities` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('available','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type`, `capacity`, `price_per_night`, `description`, `amenities`, `image`, `status`, `created_at`, `updated_at`) VALUES
(2, '500', 'single', 1, 2500.00, 'Comfortable single room with city view. Ideal for business travelers seeking a peaceful retreat.', 'Free WiFi, Air Conditioning, TV, Work Desk, Private Bathroom, Coffee Maker', 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?w=800', 'available', '2025-11-26 05:18:48', '2025-12-01 12:57:40'),
(3, '201', 'double', 2, 1500.00, 'Spacious double room with queen-size bed. Perfect for couples or friends traveling together.', 'Free WiFi, Air Conditioning, TV, Mini Bar, Private Bathroom, Balcony, Room Service', 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=800', 'available', '2025-11-26 05:18:48', '2025-11-27 15:30:20'),
(4, '202', 'double', 2, 1800.00, 'Elegant double room featuring modern amenities and stunning views. A perfect romantic getaway.', 'Free WiFi, Air Conditioning, Smart TV, Mini Bar, Jacuzzi, Private Bathroom, City View', 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=800', 'available', '2025-11-26 05:18:48', '2025-11-26 05:56:48'),
(5, '301', 'suite', 3, 2500.00, 'Luxurious suite with separate living area. Ideal for families or extended stays with premium comfort.', 'Free WiFi, Air Conditioning, Smart TV, Full Kitchen, Sofa Bed, Private Bathroom, Balcony, Room Service, Premium Toiletries', 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800', 'available', '2025-11-26 05:18:48', '2025-11-26 05:57:04'),
(6, '302', 'suite', 3, 2500.00, 'Executive suite with panoramic views. Features upscale furnishings and exclusive amenities.', 'Free WiFi, Air Conditioning, Smart TV, Mini Bar, Jacuzzi, Private Bathroom, Ocean View, Concierge Service', 'https://images.unsplash.com/photo-1615460549969-36fa19521a4f?w=800', 'available', '2025-11-26 05:18:48', '2025-11-26 05:57:14'),
(7, '401', 'deluxe', 2, 1800.00, 'Premium deluxe room with king-size bed and modern luxury. Experience ultimate comfort and style.', 'Free WiFi, Air Conditioning, Smart TV, Mini Bar, Walk-in Shower, Premium Bedding, City View, 24/7 Room Service', 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800', 'available', '2025-11-26 05:18:48', '2025-11-26 05:57:20'),
(8, '402', 'deluxe', 2, 2000.00, 'Sophisticated deluxe room featuring contemporary design and top-tier amenities for discerning guests.', 'Free WiFi, Air Conditioning, Smart TV, Nespresso Machine, Bathtub, Premium Toiletries, Garden View', 'https://images.unsplash.com/photo-1591088398332-8a7791972843?w=800', 'available', '2025-11-26 05:18:48', '2025-11-26 05:57:26'),
(9, '501', 'family', 4, 3000.00, 'Spacious family room with multiple beds. Perfect for families traveling with children.', 'Free WiFi, Air Conditioning, Smart TV, Kitchenette, 2 Bathrooms, Sofa Bed, Kids Amenities, Balcony', 'https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?w=800', 'available', '2025-11-26 05:18:48', '2025-11-26 05:57:32'),
(10, '502', 'family', 4, 3500.00, 'Large family suite with connecting rooms. Provides comfort and convenience for the whole family.', 'Free WiFi, Air Conditioning, 2 Smart TVs, Full Kitchen, 2 Bathrooms, Gaming Console, Kids Play Area, Balcony', 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?w=800', 'available', '2025-11-26 05:18:48', '2025-11-26 05:57:41'),
(11, '103', 'single', 1, 3000.00, 'Modern single room with minimalist design. A peaceful sanctuary for the modern traveler.', 'Free WiFi, Air Conditioning, TV, Work Desk, Private Bathroom, Blackout Curtains', 'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?w=800', 'available', '2025-11-26 05:18:48', '2025-12-01 12:49:08'),
(12, '203', 'double', 2, 3000.00, 'Stylish double room with premium bedding. Enjoy a restful night in comfort and elegance.', 'Free WiFi, Air Conditioning, Smart TV, Mini Fridge, Private Bathroom, Premium Linens', 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=800', 'available', '2025-11-26 05:18:48', '2025-11-26 05:56:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `role`, `status`, `created_at`, `updated_at`) VALUES
(7, 'Test User', 'user@gmail.com', '$2y$10$HgbL5wawvW.7480wjnBn7eRoNrE7MT9BGCmdkszGSvmpKYr8TTiJy', '9741052231', 'Golden shower st', 'user', 'active', '2025-12-01 12:39:17', '2025-12-01 12:58:12'),
(8, 'admin', 'admin@gmail.com', '$2y$10$LTAdGKXUUp6nKEKntir/CutAt0Ufnm9m3DALbqnrkINUIuJ.DwfaO', '09654654545', 'ewew', 'admin', 'active', '2025-12-01 12:39:34', '2025-12-01 12:39:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_read` (`read_at`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_check_in` (`check_in_date`),
  ADD KEY `idx_check_out` (`check_out_date`),
  ADD KEY `idx_payment_status` (`payment_status`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reservation_review` (`reservation_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_reservation_id` (`reservation_id`),
  ADD KEY `idx_rating` (`rating`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `idx_room_number` (`room_number`),
  ADD KEY `idx_room_type` (`room_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_price` (`price_per_night`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
