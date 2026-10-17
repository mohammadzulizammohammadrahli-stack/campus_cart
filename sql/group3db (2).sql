-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2026 at 04:56 PM
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
-- Database: `group3db`
--

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(50) DEFAULT 'unread',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `product_id`, `message_text`, `created_at`) VALUES
(4, 11, 10, 6, 'hi', '2026-06-21 17:32:26'),
(5, 11, 10, 6, 'Hi, is this available?', '2026-06-21 18:06:15'),
(6, 11, 10, 6, 'Hi, is this available?', '2026-06-21 18:19:43'),
(7, 11, 10, 6, 'Hi, is this available?', '2026-06-21 18:24:01'),
(8, 12, 10, 6, 'Hi, is this available?', '2026-06-22 06:04:16'),
(9, 11, 10, 6, 'Hi, is this available?', '2026-06-22 07:35:35'),
(10, 11, 10, 6, 'hi', '2026-06-22 07:35:42'),
(11, 11, 10, 6, 'helo', '2026-06-22 07:50:37'),
(12, 11, 10, 6, 'can i buy more', '2026-06-22 07:50:51'),
(13, 10, 11, 7, 'Hi, is this available?', '2026-06-22 08:26:46'),
(14, 11, 10, 6, 'Hi, is this available?', '2026-06-22 13:01:55'),
(15, 11, 10, 7, 'yes', '2026-06-22 14:07:43'),
(16, 13, 10, 6, 'hi still available?', '2026-06-22 14:43:10');

-- --------------------------------------------------------

--
-- Table structure for table `message_reads`
--

CREATE TABLE `message_reads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `other_user_id` int(11) NOT NULL,
  `last_read_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `message_reads`
--

INSERT INTO `message_reads` (`id`, `user_id`, `product_id`, `other_user_id`, `last_read_at`) VALUES
(1, 11, 7, 10, '2026-06-22 22:13:40');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `method_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('Pending','Completed','Failed','Refunded') DEFAULT 'Pending',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `purchase_id`, `method_id`, `amount`, `payment_status`, `payment_date`) VALUES
(1, 6, 2, 8.00, 'Completed', '2026-06-22 07:40:47'),
(2, 7, 2, 2600.00, 'Completed', '2026-06-22 08:06:50'),
(3, 8, 2, 260.00, 'Completed', '2026-06-22 13:00:38'),
(4, 9, 2, 8.00, 'Completed', '2026-06-22 13:02:17');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 1,
  `contact_no` varchar(20) NOT NULL,
  `product_image` varchar(255) DEFAULT 'default_product.png',
  `category` varchar(50) DEFAULT 'Others',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `seller_id`, `product_name`, `description`, `location`, `price`, `stock`, `contact_no`, `product_image`, `category`, `created_at`) VALUES
(6, 10, 'Buldak Ramen Carbonara !!', 'Rm 8/1\r\nRm 15/2 \r\nPromotion buy now!', 'Beta 6', 8.00, 0, '01139342804', '1782060164_Samyang Carbonara Buldak Hot Chicken Flavor Ramen 3_7 oz Big Bowl, Dairy-Free Spicy Noodles.jpg', 'Others', '2026-06-21 16:42:44'),
(7, 11, 'Iphone 16', '10/10 condition\r\ncan nego\r\nurgent!!!', 'menara umskal', 2600.00, 1, '0123456789', '1782109660_Iphone 16 pro max desert titanium.jpg', 'Others', '2026-06-22 06:27:40'),
(8, 10, 'Wedding Dress', 'Just Bought, wrong size \r\n can nego', '', 260.00, 0, '', 'product_1782132812_10.jpg', 'Clothing', '2026-06-22 12:53:32');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `purchase_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `delivery_method` enum('Delivery','Pickup') NOT NULL,
  `payment_method` enum('Debit/Credit Card','Online Banking','Cash','QR Pay') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'Success',
  `purchased_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`purchase_id`, `buyer_id`, `seller_id`, `product_id`, `delivery_method`, `payment_method`, `amount`, `status`, `purchased_at`) VALUES
(6, 11, 0, 6, 'Pickup', 'Debit/Credit Card', 0.00, 'Success', '2026-06-22 07:40:47'),
(7, 10, 0, 7, 'Pickup', 'Debit/Credit Card', 0.00, 'Success', '2026-06-22 08:06:50'),
(8, 11, 0, 8, 'Pickup', 'Debit/Credit Card', 0.00, 'Success', '2026-06-22 13:00:38'),
(9, 11, 0, 6, 'Delivery', 'Debit/Credit Card', 0.00, 'Success', '2026-06-22 13:02:17'),
(10, 11, 10, 8, 'Pickup', '', 260.00, 'Paid', '2026-06-22 13:24:01'),
(11, 11, 10, 6, 'Pickup', '', 8.00, 'Paid', '2026-06-22 13:24:53'),
(12, 13, 10, 6, 'Pickup', '', 8.00, 'Paid', '2026-06-22 14:19:27'),
(13, 13, 10, 8, 'Pickup', '', 260.00, 'Paid', '2026-06-22 14:53:51');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `rating_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `stars` int(11) NOT NULL CHECK (`stars` between 1 and 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`rating_id`, `product_id`, `user_id`, `stars`, `created_at`) VALUES
(2, 7, 10, 5, '2026-06-22 08:15:42'),
(7, 6, 13, 5, '2026-06-22 14:39:28'),
(8, 8, 13, 5, '2026-06-22 14:53:55');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `reporter_id` int(11) DEFAULT NULL,
  `reported_user_id` int(11) DEFAULT NULL,
  `reported_product_id` int(11) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewed','closed') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `reporter_id`, `reported_user_id`, `reported_product_id`, `reason`, `description`, `status`, `created_at`) VALUES
(2, 10, NULL, NULL, 'Fake Account', 'fake profile user ZUL', 'pending', '2026-06-22 16:57:58');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `phone_no` varchar(20) NOT NULL,
  `location` varchar(255) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `bank_no` varchar(50) NOT NULL,
  `profile_picture` varchar(255) DEFAULT 'default.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen` datetime DEFAULT NULL,
  `bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `phone`, `phone_no`, `location`, `bank_name`, `bank_no`, `profile_picture`, `created_at`, `last_seen`, `bio`) VALUES
(9, 'admin', '$2y$10$jRIYMfe0iXme21Kt1qrXh.1y380SiVqKV1WrJfM8CKbUbnQWoSVei', 'nurf4537@gmail.com', NULL, '0164314696', 'labuan', 'bsn', '1210541100081405', '1782057048_6a380858411dc.png', '2026-06-21 15:50:48', NULL, NULL),
(10, 'tya', '$2y$10$yqVNz.Nxo0rcCQ2Ecys5T.ec3NTEOJwfcVPVwNvoMy0EauErMbE1a', 'fatihah@ums.edu.my', '', '', '', 'bsn', '1210541100081405', 'profile/profile_10_1782130983.jpg', '2026-06-21 15:59:41', NULL, 'seller'),
(11, 'leah', '$2y$10$xgfJKiZ6tzbZPpupTEClOey.px/L3AlogGCcI0xZEhQrj5Mv6pfUa', 'leah@ums.edu.my', NULL, '', '', '', '', 'default.png', '2026-06-21 17:24:45', '2026-06-22 22:13:38', NULL),
(12, 'zul', '$2y$10$rh/exB/sG6A7LvWF6amWDesnBd7vFMdOkmkPVy36FjZQUSYV.n/Wu', 'zul@ums.edu.my', NULL, '', '', '', '', 'default.png', '2026-06-22 06:03:47', NULL, NULL),
(13, 'nabil', '$2y$10$fnGyX/Ctkxugbej0PvapruV7GSTFuUSJTaj.IG6jMfoymxGCYEPmi', 'nabil@ums.edu.my', NULL, '', '', '', '', 'default.png', '2026-06-22 14:17:05', '2026-06-22 22:53:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `wishlist_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlists`
--

INSERT INTO `wishlists` (`wishlist_id`, `user_id`, `product_id`, `added_at`) VALUES
(1, 11, 6, '2026-06-22 06:41:25'),
(2, 10, 6, '2026-06-22 12:54:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `message_reads`
--
ALTER TABLE `message_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_read` (`user_id`,`product_id`,`other_user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`purchase_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `reported_user_id` (`reported_user_id`),
  ADD KEY `reported_product_id` (`reported_product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `message_reads`
--
ALTER TABLE `message_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `purchase_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`reported_product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
