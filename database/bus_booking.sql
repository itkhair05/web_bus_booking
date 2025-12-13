-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 11, 2025 at 03:50 PM
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
-- Database: `bus_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_data` text DEFAULT NULL COMMENT 'JSON',
  `new_data` text DEFAULT NULL COMMENT 'JSON',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Log hoạt động của người dùng';

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `trip_id` int(11) DEFAULT NULL COMMENT 'ID của chuyến xe',
  `booking_code` varchar(20) NOT NULL COMMENT 'Mã đặt vé duy nhất (VD: BK20241024ABC123)',
  `total_price` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `final_price` decimal(10,2) NOT NULL COMMENT 'Giá sau khi giảm',
  `status` enum('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lưu trữ đơn hàng tổng, một đơn có thể có nhiều vé';

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `trip_id`, `booking_code`, `total_price`, `discount_amount`, `final_price`, `status`, `payment_status`, `created_at`, `updated_at`) VALUES
(8, 17, 288, 'BK251118969C6F', 400000.00, 0.00, 400000.00, 'pending', 'unpaid', '2025-11-18 05:13:29', '2025-12-08 14:34:48'),
(9, 17, 288, 'BK251118D56E0E', 400000.00, 0.00, 400000.00, 'pending', 'unpaid', '2025-11-18 07:57:49', '2025-12-08 14:34:48'),
(10, 17, 288, 'BK251118A642B3', 400000.00, 0.00, 400000.00, 'pending', 'unpaid', '2025-11-18 08:03:54', '2025-12-08 14:34:48'),
(11, 17, 288, 'BK2511183BF228', 400000.00, 0.00, 400000.00, 'pending', 'unpaid', '2025-11-18 08:52:35', '2025-12-08 14:34:48'),
(16, 18, 1, 'BK2025120821523701', 250000.00, 0.00, 250000.00, 'pending', 'unpaid', '2025-12-08 13:52:37', '2025-12-08 14:56:02'),
(17, 18, 2, 'BK2025120821523702', 400000.00, 40000.00, 360000.00, 'confirmed', 'paid', '2025-12-06 14:52:37', '2025-12-08 14:56:02'),
(18, 18, 3, 'BK2025120821523703', 180000.00, 0.00, 180000.00, 'completed', 'paid', '2025-12-01 14:52:37', '2025-12-08 14:56:02'),
(19, 18, 1, 'BK2025120821523704', 200000.00, 0.00, 200000.00, 'cancelled', 'refunded', '2025-12-05 14:52:37', '2025-12-08 14:56:02'),
(20, 18, 289, 'BK251208B2A881', 400000.00, 0.00, 400000.00, 'pending', 'unpaid', '2025-12-08 15:38:35', '2025-12-08 15:38:35'),
(21, 18, 289, 'BK251208A3BA46', 400000.00, 0.00, 400000.00, 'pending', 'unpaid', '2025-12-08 15:49:30', '2025-12-08 15:49:30'),
(22, 18, 289, 'BK251208F1A8E5', 400000.00, 0.00, 400000.00, 'confirmed', 'paid', '2025-12-08 15:51:59', '2025-12-08 15:53:04'),
(23, 18, 289, 'BK251208690A30', 400000.00, 0.00, 420000.00, 'confirmed', 'paid', '2025-12-08 16:01:42', '2025-12-08 16:01:48'),
(24, 18, 289, 'BK2512089D9B33', 400000.00, 0.00, 400000.00, 'confirmed', 'paid', '2025-12-08 16:16:41', '2025-12-08 16:16:55'),
(25, 18, 290, 'BK251209626726', 400000.00, 0.00, 400000.00, 'confirmed', 'paid', '2025-12-09 06:31:18', '2025-12-09 06:31:24'),
(26, 18, 293, 'BK624683', 450000.00, 0.00, 0.00, 'confirmed', 'paid', '2025-12-04 07:00:52', '2025-12-04 07:00:52'),
(27, 18, 293, 'BK767681', 450000.00, 0.00, 0.00, 'confirmed', 'paid', '2025-12-04 07:02:34', '2025-12-04 07:02:34'),
(28, 18, 293, 'BK886279', 300000.00, 0.00, 0.00, 'pending', 'unpaid', '2025-12-09 05:02:34', '2025-12-09 05:02:34'),
(29, 18, 293, 'BK199392', 225000.00, 0.00, 0.00, 'confirmed', 'paid', '2025-11-29 07:02:34', '2025-11-29 07:02:34'),
(30, 18, 289, 'BK251209F43BB5', 400000.00, 0.00, 420000.00, 'pending', 'unpaid', '2025-12-09 11:11:11', '2025-12-09 11:16:42'),
(31, 18, 290, 'BK251209EE100B', 400000.00, 0.00, 420000.00, 'confirmed', 'paid', '2025-12-09 11:28:30', '2025-12-09 11:28:36'),
(32, 18, 289, 'BK251209077AAD', 400000.00, 0.00, 400000.00, 'pending', 'unpaid', '2025-12-09 11:33:04', '2025-12-09 11:33:04'),
(33, 18, 289, 'BK25120927DFCB', 400000.00, 0.00, 400000.00, 'pending', 'unpaid', '2025-12-09 11:40:50', '2025-12-09 11:40:50'),
(34, 18, 289, 'BK25120966E412', 400000.00, 0.00, 400000.00, 'pending', 'unpaid', '2025-12-09 11:48:06', '2025-12-09 11:48:06'),
(35, 18, 289, 'BK251209AAE8D5', 400000.00, 0.00, 400000.00, 'pending', 'unpaid', '2025-12-09 12:23:22', '2025-12-09 12:23:22'),
(36, 18, 289, 'BK251209731863', 400000.00, 0.00, 400000.00, 'confirmed', '', '2025-12-09 15:51:51', '2025-12-09 15:52:20'),
(37, 18, 289, 'BK251209928C67', 400000.00, 50000.00, 350000.00, 'confirmed', 'paid', '2025-12-09 16:04:09', '2025-12-09 16:04:31'),
(38, 18, 289, 'BK25120930DBFE', 400000.00, 0.00, 400000.00, 'pending', 'unpaid', '2025-12-09 16:21:39', '2025-12-09 16:21:39'),
(39, 18, 292, 'BK25121099FB5A', 400000.00, 0.00, 400000.00, 'pending', 'unpaid', '2025-12-10 14:49:29', '2025-12-10 14:49:29'),
(40, 10, 294, 'BK251211422C2F', 400000.00, 0.00, 400000.00, 'pending', 'unpaid', '2025-12-11 05:56:04', '2025-12-11 05:56:04'),
(41, 10, 294, 'BK2512116B4A61', 400000.00, 0.00, 400000.00, 'confirmed', 'paid', '2025-12-11 05:57:26', '2025-12-11 05:59:35'),
(42, 18, 294, 'BK251211559B80', 300000.00, 0.00, 300000.00, 'pending', 'unpaid', '2025-12-11 07:24:53', '2025-12-11 07:24:53'),
(43, 18, 294, 'BK251211188CC2', 300000.00, 0.00, 300000.00, 'cancelled', 'unpaid', '2025-12-11 07:48:33', '2025-12-11 08:00:28'),
(44, 18, 294, 'BK25121131832C', 300000.00, 0.00, 300000.00, 'confirmed', '', '2025-12-11 08:01:07', '2025-12-11 08:01:53'),
(45, 18, 294, 'BK251211F23CF6', 300000.00, 0.00, 300000.00, 'confirmed', '', '2025-12-11 08:11:27', '2025-12-11 08:11:30'),
(46, 18, 294, 'BK251211F93FE1', 300000.00, 0.00, 300000.00, 'pending', 'unpaid', '2025-12-11 08:18:07', '2025-12-11 08:18:07'),
(47, 18, 294, 'BK251211A30DB9', 300000.00, 0.00, 300000.00, 'pending', 'unpaid', '2025-12-11 08:26:18', '2025-12-11 08:26:18'),
(48, 18, 294, 'BK251211A40922', 300000.00, 0.00, 300000.00, 'pending', 'unpaid', '2025-12-11 09:15:38', '2025-12-11 09:15:38'),
(49, 18, 294, 'BK251211B1F67D', 300000.00, 0.00, 300000.00, 'confirmed', 'paid', '2025-12-11 09:22:51', '2025-12-11 09:22:59'),
(50, 18, 294, 'BK25121145836A', 300000.00, 0.00, 320000.00, 'confirmed', 'paid', '2025-12-11 12:57:56', '2025-12-11 12:58:07'),
(51, 18, 295, 'BK251211A914F9', 5000000.00, 0.00, 5020000.00, 'cancelled', 'refunded', '2025-12-11 13:18:50', '2025-12-11 13:19:16'),
(52, 18, 295, 'BK251211473D9A', 5000000.00, 0.00, 5000000.00, 'cancelled', 'refunded', '2025-12-11 13:20:36', '2025-12-11 14:14:09');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `complaint_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `partner_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','in_progress','resolved') NOT NULL DEFAULT 'pending',
  `response` text DEFAULT NULL COMMENT 'Phản hồi của Admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Khiếu nại từ user hoặc partner gửi lên admin';

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `driver_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `license_number` varchar(20) DEFAULT NULL COMMENT 'Số bằng lái',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Quản lý tài xế của từng nhà xe';

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`driver_id`, `partner_id`, `name`, `phone`, `license_number`, `created_at`) VALUES
(1, 1, 'Phạm Văn C', '0911111111', 'B2-123456', '2025-11-12 13:49:57'),
(2, 1, 'Lê Văn D', '0922222222', 'B2-234567', '2025-11-12 13:49:57'),
(3, 2, 'Hoàng Văn E', '0933333333', 'B2-345678', '2025-11-12 13:49:57'),
(4, 1, 'Nguyễn Văn Lái', '0901234567', 'DL123456', '2025-12-11 05:18:03'),
(5, 1, 'Trần Văn Tài', '0901234568', 'DL123457', '2025-12-11 05:18:03'),
(6, 2, 'Lê Văn Xe', '0902345678', 'DL234567', '2025-12-11 05:18:03'),
(7, 3, 'Phạm Văn Lái', '0903456789', 'DL345678', '2025-12-11 05:18:03'),
(8, 4, 'Hoàng Văn Tài', '0904567890', 'DL456789', '2025-12-11 05:18:03'),
(9, 5, 'Vũ Văn Xe', '0905678901', 'DL567890', '2025-12-11 05:18:03'),
(40, 1, 'trường', '0941340257', 'b2 122re', '2025-12-11 06:45:04');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `partner_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL COMMENT 'Tiêu đề thông báo',
  `message` text NOT NULL,
  `type` enum('booking','payment','promotion','system','trip_update') NOT NULL DEFAULT 'system',
  `related_id` int(11) DEFAULT NULL COMMENT 'ID liên quan (booking_id, trip_id...)',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Thông báo hệ thống, khuyến mãi, hủy chuyến...';

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `partner_id`, `title`, `message`, `type`, `related_id`, `is_read`, `link`, `created_at`) VALUES
(1, 4, NULL, 'Chào mừng bạn đến với BusBooking!', 'Cảm ơn bạn đã đăng ký. Chúc bạn có những chuyến đi vui vẻ!', 'system', NULL, 0, NULL, '2025-11-13 07:52:38'),
(2, 2, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 15:10 13/11/2025', 'system', NULL, 0, NULL, '2025-11-13 14:10:53'),
(3, 2, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 15:13 13/11/2025', 'system', NULL, 0, NULL, '2025-11-13 14:13:58'),
(4, 9, NULL, 'Chào mừng bạn đến với BusBooking!', 'Cảm ơn bạn đã đăng ký. Chúc bạn có những chuyến đi vui vẻ!', 'system', NULL, 0, NULL, '2025-11-13 14:16:46'),
(5, 9, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 15:16 13/11/2025', 'system', NULL, 0, NULL, '2025-11-13 14:16:55'),
(6, 10, NULL, 'Chào mừng bạn đến với BusBooking!', 'Cảm ơn bạn đã đăng ký. Chúc bạn có những chuyến đi vui vẻ!', 'system', NULL, 0, NULL, '2025-11-13 15:58:03'),
(7, 10, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 16:58 13/11/2025', 'system', NULL, 0, NULL, '2025-11-13 15:58:08'),
(8, 10, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 06:20 14/11/2025', 'system', NULL, 0, NULL, '2025-11-14 05:20:26'),
(9, 10, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 05:34 18/11/2025', 'system', NULL, 0, NULL, '2025-11-18 04:34:41'),
(10, 17, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251118969C6F đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 8, 0, NULL, '2025-11-18 05:13:29'),
(11, 10, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 06:58 18/11/2025', 'system', NULL, 0, NULL, '2025-11-18 05:58:13'),
(12, 17, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251118D56E0E đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 9, 0, NULL, '2025-11-18 07:57:49'),
(13, 10, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 08:58 18/11/2025', 'system', NULL, 0, NULL, '2025-11-18 07:58:06'),
(14, 17, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251118A642B3 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 10, 0, NULL, '2025-11-18 08:03:54'),
(15, 10, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 09:05 18/11/2025', 'system', NULL, 0, NULL, '2025-11-18 08:05:10'),
(16, NULL, 1, 'Chào mừng đến với hệ thống!', 'Chúc mừng bạn đã đăng ký thành công tài khoản nhà xe. Hãy bắt đầu quản lý chuyến đi của bạn.', 'system', NULL, 1, NULL, '2025-11-18 08:43:05'),
(17, NULL, 1, 'Cập nhật hệ thống', 'Hệ thống đã được cập nhật với nhiều tính năng mới. Vui lòng làm mới trang để trải nghiệm.', 'system', NULL, 1, NULL, '2025-11-18 08:43:05'),
(18, NULL, 1, 'Lưu ý về bảo mật', 'Vui lòng thay đổi mật khẩu mặc định và bảo mật tài khoản của bạn.', 'system', NULL, 1, NULL, '2025-11-18 08:43:05'),
(19, 17, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK2511183BF228 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 11, 0, NULL, '2025-11-18 08:52:35'),
(20, 18, NULL, 'Chào mừng bạn đến với BusBooking!', 'Cảm ơn bạn đã đăng ký. Chúc bạn có những chuyến đi vui vẻ!', 'system', NULL, 1, NULL, '2025-12-08 14:21:00'),
(21, 18, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 15:21 08/12/2025', 'system', NULL, 1, NULL, '2025-12-08 14:21:07'),
(22, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251208B2A881 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 20, 1, NULL, '2025-12-08 15:38:35'),
(23, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251208A3BA46 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 21, 1, NULL, '2025-12-08 15:49:30'),
(24, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251208F1A8E5 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 22, 1, NULL, '2025-12-08 15:51:59'),
(25, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251208690A30 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 23, 1, NULL, '2025-12-08 16:01:42'),
(26, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK2512089D9B33 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 24, 1, NULL, '2025-12-08 16:16:41'),
(27, 20, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 05:47 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 04:47:31'),
(28, 20, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 05:52 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 04:52:28'),
(29, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 05:55 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 04:55:57'),
(30, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 06:05 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 05:05:29'),
(31, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 06:05 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 05:05:32'),
(32, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 06:05 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 05:05:41'),
(33, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 06:06 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 05:06:14'),
(34, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 06:06 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 05:06:42'),
(35, 20, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 06:06 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 05:06:56'),
(36, 20, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 06:07 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 05:07:00'),
(37, 20, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 06:09 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 05:09:20'),
(38, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 06:09 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 05:09:30'),
(39, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 06:47 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 05:47:32'),
(40, 20, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 06:48 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 05:48:50'),
(41, 20, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 07:10 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 06:10:13'),
(42, 20, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 07:11 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 06:11:37'),
(43, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 07:23 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 06:23:19'),
(44, 20, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 07:26 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 06:26:09'),
(45, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 07:29 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 06:29:48'),
(46, 18, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 07:30 09/12/2025', 'system', NULL, 1, NULL, '2025-12-09 06:30:57'),
(47, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251209626726 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 25, 1, NULL, '2025-12-09 06:31:18'),
(56, 2, NULL, 'Chào mừng bạn đến với Bus Booking!', 'Cảm ơn bạn đã đăng ký tài khoản. Hãy bắt đầu đặt vé ngay hôm nay!', '', NULL, 0, NULL, '2025-12-09 06:40:59'),
(57, 2, NULL, 'Đặt vé thành công', 'Bạn đã đặt vé thành công cho chuyến Hà Nội - Hải Phòng. Mã đặt vé: BK123456', '', NULL, 0, NULL, '2025-12-09 04:40:59'),
(58, 2, NULL, 'Nhắc nhở thanh toán', 'Đơn hàng BK123456 của bạn chưa được thanh toán. Vui lòng thanh toán trong vòng 24 giờ.', '', NULL, 0, NULL, '2025-12-09 01:40:59'),
(59, 2, NULL, 'Thanh toán thành công', 'Giao dịch của bạn đã được xử lý thành công. Số tiền: 300,000đ', '', NULL, 1, NULL, '2025-12-08 06:40:59'),
(60, 2, NULL, 'Chuyến xe sắp khởi hành', 'Chuyến xe Hà Nội - Hải Phòng của bạn sẽ khởi hành vào 08:00 ngày mai.', '', NULL, 1, NULL, '2025-12-07 06:40:59'),
(61, 2, NULL, 'Khuyến mãi đặc biệt', 'Giảm 20% cho tất cả các chuyến xe trong tuần này. Áp dụng mã: SALE20', '', NULL, 1, NULL, '2025-12-06 06:40:59'),
(62, 2, NULL, 'Cập nhật hệ thống', 'Hệ thống sẽ bảo trì vào 02:00 - 04:00 sáng ngày 15/12. Vui lòng hoàn tất đặt vé trước thời gian này.', 'system', NULL, 1, NULL, '2025-12-04 06:40:59'),
(63, 2, NULL, 'Đánh giá chuyến đi', 'Hãy chia sẻ trải nghiệm của bạn về chuyến đi vừa rồi!', '', NULL, 1, NULL, '2025-12-02 06:40:59'),
(64, 18, NULL, 'Chào mừng bạn đến với Bus Booking!', 'Cảm ơn bạn đã đăng ký tài khoản. Hãy bắt đầu đặt vé ngay hôm nay!', '', NULL, 1, NULL, '2025-12-09 07:03:02'),
(65, 18, NULL, 'Đặt vé thành công', 'Bạn đã đặt vé thành công cho chuyến Hà Nội - Hải Phòng. Mã đặt vé: BK123456', '', NULL, 1, NULL, '2025-12-09 05:03:02'),
(66, 18, NULL, 'Nhắc nhở thanh toán', 'Đơn hàng BK123456 của bạn chưa được thanh toán. Vui lòng thanh toán trong vòng 24 giờ.', '', NULL, 1, NULL, '2025-12-09 02:03:02'),
(67, 18, NULL, 'Thanh toán thành công', 'Giao dịch của bạn đã được xử lý thành công. Số tiền: 300,000đ', '', NULL, 1, NULL, '2025-12-08 07:03:02'),
(68, 18, NULL, 'Chuyến xe sắp khởi hành', 'Chuyến xe Hà Nội - Hải Phòng của bạn sẽ khởi hành vào 08:00 ngày mai.', '', NULL, 1, NULL, '2025-12-07 07:03:02'),
(69, 18, NULL, 'Khuyến mãi đặc biệt', 'Giảm 20% cho tất cả các chuyến xe trong tuần này. Áp dụng mã: SALE20', '', NULL, 1, NULL, '2025-12-06 07:03:02'),
(70, 18, NULL, 'Cập nhật hệ thống', 'Hệ thống sẽ bảo trì vào 02:00 - 04:00 sáng ngày 15/12. Vui lòng hoàn tất đặt vé trước thời gian này.', 'system', NULL, 1, NULL, '2025-12-04 07:03:02'),
(71, 18, NULL, 'Đánh giá chuyến đi', 'Hãy chia sẻ trải nghiệm của bạn về chuyến đi vừa rồi!', '', NULL, 1, NULL, '2025-12-02 07:03:02'),
(72, 18, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 08:17 09/12/2025', 'system', NULL, 1, NULL, '2025-12-09 07:17:08'),
(73, 18, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 11:10 09/12/2025', 'system', NULL, 1, NULL, '2025-12-09 10:10:35'),
(74, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251209F43BB5 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 30, 1, NULL, '2025-12-09 11:11:11'),
(75, 18, NULL, 'Thanh toán thất bại', 'Thanh toán đơn BK251209F43BB5 thất bại. Lý do: Giao dịch không thành công do: Khách hàng hủy giao dịch', 'payment', 30, 1, NULL, '2025-12-09 11:16:42'),
(76, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251209EE100B đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 31, 1, NULL, '2025-12-09 11:28:30'),
(77, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251209077AAD đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 32, 1, NULL, '2025-12-09 11:33:04'),
(78, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK25120927DFCB đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 33, 1, NULL, '2025-12-09 11:40:50'),
(79, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK25120966E412 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 34, 1, NULL, '2025-12-09 11:48:06'),
(80, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251209AAE8D5 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 35, 1, NULL, '2025-12-09 12:23:22'),
(81, 18, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 14:05 09/12/2025', 'system', NULL, 1, NULL, '2025-12-09 13:05:48'),
(82, 18, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 14:15 09/12/2025', 'system', NULL, 1, NULL, '2025-12-09 13:15:28'),
(83, 20, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 15:08 09/12/2025', 'system', NULL, 0, NULL, '2025-12-09 14:08:21'),
(84, 18, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 15:09 09/12/2025', 'system', NULL, 1, NULL, '2025-12-09 14:09:39'),
(85, 18, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 16:51 09/12/2025', 'system', NULL, 1, NULL, '2025-12-09 15:51:26'),
(86, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251209731863 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 36, 1, NULL, '2025-12-09 15:51:51'),
(87, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251209928C67 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 37, 1, NULL, '2025-12-09 16:04:09'),
(88, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK25120930DBFE đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 38, 1, NULL, '2025-12-09 16:21:39'),
(89, 18, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 15:48 10/12/2025', 'system', NULL, 1, NULL, '2025-12-10 14:48:59'),
(90, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK25121099FB5A đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 39, 1, NULL, '2025-12-10 14:49:29'),
(91, 10, NULL, 'Mật khẩu đã được đổi', 'Mật khẩu của bạn đã được thay đổi thành công lúc 15:59 10/12/2025', 'system', NULL, 0, NULL, '2025-12-10 14:59:57'),
(92, 10, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 16:00 10/12/2025', 'system', NULL, 0, NULL, '2025-12-10 15:00:10'),
(93, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 06:52 11/12/2025', 'system', NULL, 0, NULL, '2025-12-11 05:52:29'),
(94, 10, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 06:55 11/12/2025', 'system', NULL, 0, NULL, '2025-12-11 05:55:41'),
(95, 10, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251211422C2F đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 40, 0, NULL, '2025-12-11 05:56:04'),
(96, 10, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK2512116B4A61 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 41, 0, NULL, '2025-12-11 05:57:26'),
(97, 10, NULL, 'Thanh toán thành công', 'Đơn đặt vé BK2512116B4A61 đã được thanh toán thành công qua VNPay. Mã GD: 15334493', 'payment', 41, 0, NULL, '2025-12-11 05:59:35'),
(98, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 07:07 11/12/2025', 'system', NULL, 0, NULL, '2025-12-11 06:07:40'),
(99, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 07:53 11/12/2025', 'system', NULL, 0, NULL, '2025-12-11 06:53:28'),
(100, 20, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 08:01 11/12/2025', 'system', NULL, 0, NULL, '2025-12-11 07:01:02'),
(101, 20, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 08:11 11/12/2025', 'system', NULL, 0, NULL, '2025-12-11 07:11:56'),
(102, 20, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 08:12 11/12/2025', 'system', NULL, 0, NULL, '2025-12-11 07:12:38'),
(103, 20, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 08:13 11/12/2025', 'system', NULL, 0, NULL, '2025-12-11 07:13:30'),
(104, 33, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 08:13 11/12/2025', 'system', NULL, 0, NULL, '2025-12-11 07:13:51'),
(105, NULL, 44, 'Chào mừng đến với hệ thống!', 'Chúc mừng bạn đã đăng ký thành công tài khoản nhà xe. Hãy bắt đầu quản lý chuyến đi của bạn.', 'system', NULL, 0, NULL, '2025-12-11 07:13:58'),
(106, NULL, 44, 'Cập nhật hệ thống', 'Hệ thống đã được cập nhật với nhiều tính năng mới. Vui lòng làm mới trang để trải nghiệm.', 'system', NULL, 0, NULL, '2025-12-11 07:13:58'),
(107, NULL, 44, 'Lưu ý về bảo mật', 'Vui lòng thay đổi mật khẩu mặc định và bảo mật tài khoản của bạn.', 'system', NULL, 0, NULL, '2025-12-11 07:13:58'),
(108, 20, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 08:18 11/12/2025', 'system', NULL, 0, NULL, '2025-12-11 07:18:40'),
(109, 20, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 08:18 11/12/2025', 'system', NULL, 0, NULL, '2025-12-11 07:18:49'),
(110, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 08:18 11/12/2025', 'system', NULL, 0, NULL, '2025-12-11 07:18:53'),
(111, 33, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 08:19 11/12/2025', 'system', NULL, 0, NULL, '2025-12-11 07:19:02'),
(112, 18, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 08:19 11/12/2025', 'system', NULL, 1, NULL, '2025-12-11 07:19:29'),
(113, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 08:23 11/12/2025', 'system', NULL, 0, NULL, '2025-12-11 07:23:07'),
(114, 18, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 08:23 11/12/2025', 'system', NULL, 1, NULL, '2025-12-11 07:23:41'),
(115, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251211559B80 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 42, 1, NULL, '2025-12-11 07:24:53'),
(116, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251211188CC2 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 43, 1, NULL, '2025-12-11 07:48:33'),
(117, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK25121131832C đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 44, 1, NULL, '2025-12-11 08:01:07'),
(118, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251211F23CF6 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 45, 1, NULL, '2025-12-11 08:11:27'),
(119, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251211F93FE1 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 46, 1, NULL, '2025-12-11 08:18:07'),
(120, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251211A30DB9 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 47, 1, NULL, '2025-12-11 08:26:18'),
(121, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 10:14 11/12/2025', 'system', NULL, 0, NULL, '2025-12-11 09:14:12'),
(122, 18, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 10:14 11/12/2025', 'system', NULL, 1, NULL, '2025-12-11 09:14:37'),
(123, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251211A40922 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 48, 1, NULL, '2025-12-11 09:15:38'),
(124, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251211B1F67D đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 49, 1, NULL, '2025-12-11 09:22:51'),
(125, 18, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 13:57 11/12/2025', 'system', NULL, 1, NULL, '2025-12-11 12:57:26'),
(126, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK25121145836A đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 50, 1, NULL, '2025-12-11 12:57:56'),
(127, 22, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 14:17 11/12/2025', 'system', NULL, 0, NULL, '2025-12-11 13:17:14'),
(128, 18, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 14:18 11/12/2025', 'system', NULL, 1, NULL, '2025-12-11 13:18:23'),
(129, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251211A914F9 đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 51, 1, NULL, '2025-12-11 13:18:50'),
(130, 18, NULL, 'Đơn hàng đã hủy', 'Đơn hàng BK251211A914F9 đã được hủy thành công.', '', 51, 1, NULL, '2025-12-11 13:19:16'),
(131, 18, NULL, 'Đặt vé thành công', 'Đơn đặt vé BK251211473D9A đã được tạo. Vui lòng thanh toán trong 15 phút.', 'booking', 52, 1, NULL, '2025-12-11 13:20:36'),
(132, 18, NULL, 'Thanh toán thành công', 'Đơn đặt vé BK251211473D9A đã được thanh toán thành công qua VNPay. Mã GD: 15335438', 'payment', 52, 1, NULL, '2025-12-11 13:22:16'),
(133, 18, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 15:13 11/12/2025', 'system', NULL, 1, NULL, '2025-12-11 14:13:43'),
(134, 18, NULL, 'Đăng nhập thành công', 'Bạn vừa đăng nhập vào hệ thống lúc 15:13 11/12/2025', 'system', NULL, 1, NULL, '2025-12-11 14:13:56'),
(135, 18, NULL, 'Đơn hàng đã hủy', 'Đơn hàng BK251211473D9A đã được hủy thành công.', '', 52, 1, NULL, '2025-12-11 14:14:09');

-- --------------------------------------------------------

--
-- Table structure for table `partners`
--

CREATE TABLE `partners` (
  `partner_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 4.50 COMMENT 'Đánh giá trung bình (0.00 - 5.00)',
  `policy` text DEFAULT NULL COMMENT 'Chính sách, thông tin liên hệ của nhà xe',
  `status` enum('pending','approved','suspended') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lưu trữ thông tin đối tác nhà xe';

--
-- Dumping data for table `partners`
--

INSERT INTO `partners` (`partner_id`, `name`, `email`, `phone`, `password`, `logo_url`, `rating`, `policy`, `status`, `created_at`) VALUES
(1, 'Phương Trang', 'phuongtrang@example.com', '0901234567', '$2y$10$qU9y27GuaZUTbvjcBfIypO2Pa8yOBSJYWpxytmOrg4deCiuagqsOe', 'uploads/partners/logos/partner_1_1765437798.png', 4.50, 'hủy vé', 'approved', '2025-11-12 13:49:57'),
(2, 'Mai Linh', 'mailinh@example.com', '0902345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 4.50, 'Chính sách hủy vé: Hoàn 80% nếu hủy trước 24h', 'approved', '2025-11-12 13:49:57'),
(3, 'Hoàng Long', 'hoanglong@example.com', '19001234', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 4.50, 'Chính sách hủy vé: Hoàn 80% nếu hủy trước 24h', 'approved', '2025-11-15 06:18:16'),
(4, 'Kumho Samco', 'kumho@example.com', '19005678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 4.50, 'Chính sách hủy vé: Hoàn 80% nếu hủy trước 24h', 'approved', '2025-11-15 06:18:16'),
(5, 'Thành Bưởi', 'thanhbuoi@example.com', '19009090', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 4.50, 'Chính sách hủy vé: Hoàn 80% nếu hủy trước 24h', 'approved', '2025-11-15 06:18:16'),
(44, 'Chín Nghĩa', 'nvt7000@gmail.com', '0962175776', '$2y$10$98dpkeazurNF1le2QqIHpeje7WuagmL5NWztY2eHRThmM5SwAWm2u', 'uploads/partners/partner_1765437036_4d0df93f.jpg', 4.50, 'Địa chỉ: 123 deaad\r\n123 adde | GPKD: 0987654325', 'approved', '2025-12-11 07:10:36');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Quản lý token reset mật khẩu';

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`reset_id`, `user_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(2, 9, '578160c63cc358ece69d650a029c9e8479d5e1075f08fa78cd5f101b5e3f01ab', '2025-11-13 09:21:44', 0, '2025-11-13 14:21:44'),
(12, 18, 'd1a05952a955d6732de412593923e684a9a0801f7800197226dc3eecc921f6bb', '2025-12-10 09:46:38', 0, '2025-12-10 14:46:38');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `method` varchar(50) NOT NULL COMMENT 'COD, momo, vnpay...',
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','success','failed','refunded') NOT NULL DEFAULT 'pending',
  `transaction_code` varchar(100) DEFAULT NULL COMMENT 'Mã giao dịch từ bên thứ 3',
  `payment_data` text DEFAULT NULL COMMENT 'JSON data từ payment gateway',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Quản lý lịch sử và trạng thái thanh toán';

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `method`, `amount`, `status`, `transaction_code`, `payment_data`, `paid_at`, `created_at`) VALUES
(1, 27, 'bank_transfer', 450000.00, 'success', 'TXN163719157', NULL, '2025-12-04 07:02:34', '2025-12-04 07:02:34'),
(2, 28, 'bank_transfer', 300000.00, 'pending', NULL, NULL, NULL, '2025-12-09 05:02:34'),
(3, 29, 'bank_transfer', 225000.00, 'success', 'TXN482021746', NULL, '2025-11-29 07:02:34', '2025-11-29 07:02:34'),
(4, 30, 'vnpay', 420000.00, 'failed', '0', '{\"txn_ref\":\"30_1765278894\",\"amount\":420000,\"bank_code\":\"VNPAY\",\"card_type\":\"QRCODE\",\"order_info\":\"Thanh toan ve xe BK251209F43BB5\",\"pay_date\":\"20251209181454\",\"response_code\":\"24\",\"transaction_no\":\"0\",\"transaction_status\":\"02\",\"tmn_code\":\"WCRPJXB2\"}', NULL, '2025-12-09 11:16:42'),
(5, 36, 'cod', 400000.00, 'pending', NULL, NULL, NULL, '2025-12-09 15:52:20'),
(6, 41, 'vnpay', 400000.00, 'success', '15334493', '{\"txn_ref\":\"41_1765432656\",\"amount\":400000,\"bank_code\":\"NCB\",\"card_type\":\"ATM\",\"order_info\":\"Thanh toan ve xe BK2512116B4A61\",\"pay_date\":\"20251211125929\",\"response_code\":\"00\",\"transaction_no\":\"15334493\",\"transaction_status\":\"00\",\"tmn_code\":\"WCRPJXB2\"}', '2025-12-11 05:59:35', '2025-12-11 05:59:35'),
(7, 44, 'cod', 300000.00, 'pending', NULL, NULL, NULL, '2025-12-11 08:01:53'),
(8, 45, 'cod', 300000.00, 'pending', NULL, NULL, NULL, '2025-12-11 08:11:30'),
(9, 52, 'vnpay', 5000000.00, 'refunded', '15335438', '{\"txn_ref\":\"52_1765459239\",\"amount\":5000000,\"bank_code\":\"NCB\",\"card_type\":\"ATM\",\"order_info\":\"Thanh toan ve xe BK251211473D9A\",\"pay_date\":\"20251211202211\",\"response_code\":\"00\",\"transaction_no\":\"15335438\",\"transaction_status\":\"00\",\"tmn_code\":\"WCRPJXB2\"}', '2025-12-11 13:22:16', '2025-12-11 13:22:16');

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `promotion_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL COMMENT 'Tên chương trình khuyến mãi',
  `description` text DEFAULT NULL,
  `discount_type` enum('fixed','percentage') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Giá trị đơn hàng tối thiểu',
  `max_discount_amount` decimal(10,2) DEFAULT NULL COMMENT 'Giảm tối đa (cho percentage)',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `usage_limit` int(11) DEFAULT NULL COMMENT 'Số lần sử dụng tối đa (NULL = không giới hạn)',
  `used_count` int(11) DEFAULT 0 COMMENT 'Số lần đã sử dụng',
  `status` enum('active','expired','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Các mã khuyến mãi do admin tạo';

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`promotion_id`, `code`, `title`, `description`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount_amount`, `start_date`, `end_date`, `usage_limit`, `used_count`, `status`, `created_at`) VALUES
(1, 'NEWUSER2024', 'Giảm giá cho khách hàng mới', 'Giảm 20% cho lần đặt vé đầu tiên', 'percentage', 20.00, 100000.00, 50000.00, '2024-01-01 00:00:00', '2025-12-31 23:59:59', 1000, 0, 'active', '2025-11-12 13:49:57'),
(2, 'SAVE50K', 'Giảm 50K cho đơn từ 300K', 'Giảm ngay 50.000đ cho đơn hàng từ 300.000đ', 'fixed', 50000.00, 300000.00, NULL, '2024-10-01 00:00:00', '2025-12-31 23:59:59', NULL, 0, 'active', '2025-11-12 13:49:57'),
(3, 'TETHOLIDAY', 'Khuyến mãi Tết 2025', 'Giảm 15% cho tất cả các chuyến', 'percentage', 15.00, 0.00, 100000.00, '2025-01-20 00:00:00', '2025-02-10 23:59:59', 5000, 0, 'active', '2025-11-12 13:49:57'),
(4, 'GIAM50K', 'Giảm 50.000đ', 'Giảm trực tiếp 50k cho đơn từ 200k', 'fixed', 50000.00, 200000.00, NULL, '2025-12-08 21:27:04', '2026-01-08 21:27:04', 200, 1, 'active', '2025-12-09 14:27:04'),
(5, 'GIAM20P', 'Giảm 20%', 'Giảm 20% tối đa 100k', 'percentage', 20.00, 0.00, 100000.00, '2025-12-08 21:27:04', '2026-01-08 21:27:04', 300, 0, 'active', '2025-12-09 14:27:04'),
(6, 'FLASH15', 'Flash Sale 15%', 'Giảm 15% tối đa 70k cho đơn từ 150k', 'percentage', 15.00, 150000.00, 70000.00, '2025-12-08 21:27:04', '2025-12-19 21:27:04', 50, 0, 'active', '2025-12-09 14:27:04'),
(7, 'VIP100K', 'Ưu đãi VIP 100k', 'Giảm 100k cho đơn từ 500k', 'fixed', 100000.00, 500000.00, NULL, '2025-12-08 21:27:04', '2026-01-23 21:27:04', 20, 0, 'active', '2025-12-09 14:27:04');

-- --------------------------------------------------------

--
-- Table structure for table `promotion_assignments`
--

CREATE TABLE `promotion_assignments` (
  `assignment_id` int(11) NOT NULL,
  `promotion_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `partner_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Phân phối KM cho user hoặc nhà xe. Nếu cả 2 NULL là cho toàn hệ thống';

-- --------------------------------------------------------

--
-- Table structure for table `promotion_usage`
--

CREATE TABLE `promotion_usage` (
  `usage_id` int(11) NOT NULL,
  `promotion_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lịch sử sử dụng mã khuyến mãi';

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `refund_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `refund_reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `processed_by` int(11) DEFAULT NULL COMMENT 'Admin xử lý',
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Quản lý hoàn tiền khi hủy vé';

--
-- Dumping data for table `refunds`
--

INSERT INTO `refunds` (`refund_id`, `booking_id`, `payment_id`, `refund_amount`, `refund_reason`, `status`, `processed_by`, `processed_at`, `created_at`) VALUES
(1, 52, 9, 4000000.00, 'User cancel - refund 80%', 'completed', NULL, NULL, '2025-12-11 14:14:09');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL COMMENT 'Từ 1 đến 5 sao',
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lưu trữ đánh giá và bình luận của user cho chuyến đi';

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `route_id` int(11) NOT NULL,
  `route_name` varchar(200) NOT NULL DEFAULT '',
  `origin` varchar(100) NOT NULL DEFAULT '',
  `destination` varchar(100) NOT NULL DEFAULT '',
  `distance_km` decimal(10,2) DEFAULT NULL,
  `duration_hours` decimal(5,2) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_point` varchar(100) NOT NULL,
  `end_point` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lưu trữ các tuyến đường (ví dụ: Hà Nội - Sài Gòn)';

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`route_id`, `route_name`, `origin`, `destination`, `distance_km`, `duration_hours`, `base_price`, `status`, `created_at`, `start_point`, `end_point`, `description`) VALUES
(1, '', '', '', NULL, NULL, 0.00, 'active', '2025-11-13 07:44:03', 'Hà Nội', 'TP. Hồ Chí Minh', 'Tuyến Hà Nội - Hải Phòng, khoảng cách 120km'),
(2, '', '', '', NULL, NULL, 0.00, 'active', '2025-11-13 07:44:03', 'Hà Nội', 'Đà Nẵng', 'Tuyến Hà Nội - Đà Nẵng, khoảng cách 800km'),
(3, '', '', '', NULL, NULL, 0.00, 'active', '2025-11-13 07:44:03', 'Hà Nội', 'Hải Phòng', 'Tuyến TP.HCM - Đà Lạt, khoảng cách 300km'),
(4, '', '', '', NULL, NULL, 0.00, 'active', '2025-11-13 07:44:03', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Tuyến TP.HCM - Nha Trang, khoảng cách 450km'),
(5, 'Sài Gòn - Đà Lạt', 'Sài Gòn', 'Đà Lạt', 300.00, 6.50, 250000.00, 'active', '2025-11-13 07:44:03', 'TP. Hồ Chí Minh', 'Nha Trang', NULL),
(6, 'Sài Gòn - Quảng Ngãi', 'Sài Gòn', 'Quảng Ngãi', 850.00, 14.00, 400000.00, 'active', '2025-11-13 07:44:03', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(7, 'Hà Nội - Hải Phòng', 'Hà Nội', 'Hải Phòng', 120.00, 2.50, 150000.00, 'active', '2025-11-13 07:44:03', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(8, 'Hà Nội - Sapa', 'Hà Nội', 'Sapa', 350.00, 7.00, 300000.00, 'active', '2025-11-13 07:44:03', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(9, 'Quảng Ngãi - Đà Nẵng', 'Quảng Ngãi', 'Đà Nẵng', 130.00, 2.50, 120000.00, 'active', '2025-11-13 07:44:03', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(10, 'Sài Gòn - Vũng Tàu', 'Sài Gòn', 'Vũng Tàu', 125.00, 2.00, 100000.00, 'active', '2025-11-13 07:44:03', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(11, 'Hà Nội - Quảng Ninh', 'Hà Nội', 'Quảng Ninh', 170.00, 3.00, 180000.00, 'active', '2025-11-13 07:44:03', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(12, 'Sài Gòn - Đà Lạt', 'Sài Gòn', 'Đà Lạt', 308.00, 7.00, 250000.00, 'active', '2025-11-14 05:32:49', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(13, 'Sài Gòn - Nha Trang', 'Sài Gòn', 'Nha Trang', 450.00, 9.00, 300000.00, 'active', '2025-11-14 05:32:49', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(14, 'Hà Nội - Hải Phòng', 'Hà Nội', 'Hải Phòng', 120.00, 2.50, 150000.00, 'active', '2025-11-14 05:32:49', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(15, 'Hà Nội - Sapa', 'Hà Nội', 'Sapa', 350.00, 6.00, 280000.00, 'active', '2025-11-14 05:32:49', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(16, 'Đà Nẵng - Hội An', 'Đà Nẵng', 'Hội An', 30.00, 0.50, 50000.00, 'active', '2025-11-14 05:32:49', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(17, 'Sài Gòn - Vũng Tàu', 'Sài Gòn', 'Vũng Tàu', 125.00, 2.00, 120000.00, 'active', '2025-11-14 05:32:49', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(18, 'Hà Nội - Ninh Bình', 'Hà Nội', 'Ninh Bình', 95.00, 2.00, 100000.00, 'active', '2025-11-14 05:32:49', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(19, 'Sài Gòn - Phan Thiết', 'Sài Gòn', 'Phan Thiết', 200.00, 4.00, 180000.00, 'active', '2025-11-14 05:32:49', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(20, 'Sài Gòn - Cần Thơ', 'Sài Gòn', 'Cần Thơ', 170.00, 3.50, 160000.00, 'active', '2025-11-14 05:32:49', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(21, 'Hà Nội - Đà Nẵng', 'Hà Nội', 'Đà Nẵng', 760.00, 14.00, 450000.00, 'active', '2025-11-14 05:32:49', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(22, 'Sài Gòn - Quảng Ngãi', 'Sài Gòn', 'Quảng Ngãi', 850.00, 15.00, 400000.00, 'active', '2025-11-14 05:32:49', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(23, 'Sài Gòn - Đà Lạt', 'Sài Gòn', 'Đà Lạt', 300.00, 6.00, 250000.00, 'active', '2025-11-15 06:22:19', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(24, 'Sài Gòn - Nha Trang', 'Sài Gòn', 'Nha Trang', 450.00, 8.00, 350000.00, 'active', '2025-11-15 06:22:19', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(25, 'Sài Gòn - Quảng Ngãi', 'Sài Gòn', 'Quảng Ngãi', 900.00, 14.00, 400000.00, 'active', '2025-11-15 06:22:19', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(26, 'Sài Gòn - Vũng Tàu', 'Sài Gòn', 'Vũng Tàu', 100.00, 2.00, 120000.00, 'active', '2025-11-15 06:22:19', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(27, 'Hà Nội - Hải Phòng', 'Hà Nội', 'Hải Phòng', 120.00, 2.50, 150000.00, 'active', '2025-11-15 06:22:19', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(28, 'Hà Nội - Đà Nẵng', 'Hà Nội', 'Đà Nẵng', 800.00, 14.00, 450000.00, 'active', '2025-11-15 06:22:19', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(29, 'Hà Nội - Sapa', 'Hà Nội', 'Sapa', 350.00, 8.00, 300000.00, 'active', '2025-11-15 06:22:19', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(30, 'Đà Nẵng - Huế', 'Đà Nẵng', 'Huế', 100.00, 2.00, 100000.00, 'active', '2025-11-15 06:22:19', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(31, 'Sài Gòn - Cần Thơ', 'Sài Gòn', 'Cần Thơ', 170.00, 3.50, 180000.00, 'active', '2025-11-15 06:22:19', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(32, 'Hà Nội - Quảng Ninh', 'Hà Nội', 'Quảng Ninh', 150.00, 3.00, 160000.00, 'active', '2025-11-15 06:22:19', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(33, 'Sài Gòn - Đà Lạt', 'Sài Gòn', 'Đà Lạt', 300.00, 6.00, 250000.00, 'active', '2025-11-15 06:26:51', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(34, 'Sài Gòn - Nha Trang', 'Sài Gòn', 'Nha Trang', 450.00, 8.00, 350000.00, 'active', '2025-11-15 06:26:51', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(35, 'Sài Gòn - Quảng Ngãi', 'Sài Gòn', 'Quảng Ngãi', 900.00, 14.00, 400000.00, 'active', '2025-11-15 06:26:51', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(36, 'Sài Gòn - Vũng Tàu', 'Sài Gòn', 'Vũng Tàu', 100.00, 2.00, 120000.00, 'active', '2025-11-15 06:26:51', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(37, 'Hà Nội - Hải Phòng', 'Hà Nội', 'Hải Phòng', 120.00, 2.50, 150000.00, 'active', '2025-11-15 06:26:51', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(38, 'Hà Nội - Đà Nẵng', 'Hà Nội', 'Đà Nẵng', 800.00, 14.00, 450000.00, 'active', '2025-11-15 06:26:51', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(39, 'Hà Nội - Sapa', 'Hà Nội', 'Sapa', 350.00, 8.00, 300000.00, 'active', '2025-11-15 06:26:51', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(40, 'Đà Nẵng - Huế', 'Đà Nẵng', 'Huế', 100.00, 2.00, 100000.00, 'active', '2025-11-15 06:26:51', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(41, 'Sài Gòn - Cần Thơ', 'Sài Gòn', 'Cần Thơ', 170.00, 3.50, 180000.00, 'active', '2025-11-15 06:26:51', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(42, 'Hà Nội - Quảng Ninh', 'Hà Nội', 'Quảng Ninh', 150.00, 3.00, 160000.00, 'active', '2025-11-15 06:26:51', 'Hà Nội', 'TP. Hồ Chí Minh', NULL),
(43, '', '', '', NULL, NULL, 0.00, 'active', '2025-11-17 13:36:52', 'Sài Gòn', 'Quảng Ngãi', 'Tuyến thử nghiệm Sài Gòn - Quảng Ngãi'),
(44, 'Sài Gòn - Đà Lạt', 'Sài Gòn', 'Đà Lạt', 300.00, 6.00, 250000.00, 'active', '2025-12-11 05:18:03', '', '', NULL),
(45, 'Sài Gòn - Nha Trang', 'Sài Gòn', 'Nha Trang', 450.00, 8.00, 350000.00, 'active', '2025-12-11 05:18:03', '', '', NULL),
(46, 'Hà Nội - Đà Nẵng', 'Hà Nội', 'Đà Nẵng', 800.00, 14.00, 450000.00, 'active', '2025-12-11 05:18:03', '', '', NULL),
(47, 'Hà Nội - Sapa', 'Hà Nội', 'Sapa', 350.00, 8.00, 300000.00, 'active', '2025-12-11 05:18:03', '', '', NULL),
(48, 'Đà Nẵng - Huế', 'Đà Nẵng', 'Huế', 100.00, 3.00, 100000.00, 'active', '2025-12-11 05:18:03', '', '', NULL),
(49, 'Sài Gòn - Đà Lạt', 'Sài Gòn', 'Đà Lạt', 300.00, 6.00, 250000.00, 'active', '2025-12-11 05:24:48', '', '', NULL),
(50, 'Sài Gòn - Nha Trang', 'Sài Gòn', 'Nha Trang', 450.00, 8.00, 350000.00, 'active', '2025-12-11 05:24:48', '', '', NULL),
(51, 'Hà Nội - Đà Nẵng', 'Hà Nội', 'Đà Nẵng', 800.00, 14.00, 450000.00, 'active', '2025-12-11 05:24:48', '', '', NULL),
(52, 'Hà Nội - Sapa', 'Hà Nội', 'Sapa', 350.00, 8.00, 300000.00, 'active', '2025-12-11 05:24:48', '', '', NULL),
(53, 'Đà Nẵng - Huế', 'Đà Nẵng', 'Huế', 100.00, 3.00, 100000.00, 'active', '2025-12-11 05:24:48', '', '', NULL),
(54, 'Sài Gòn - Đà Lạt', 'Sài Gòn', 'Đà Lạt', 300.00, 6.00, 250000.00, 'active', '2025-12-11 05:26:21', '', '', NULL),
(55, 'Sài Gòn - Nha Trang', 'Sài Gòn', 'Nha Trang', 450.00, 8.00, 350000.00, 'active', '2025-12-11 05:26:21', '', '', NULL),
(56, 'Hà Nội - Đà Nẵng', 'Hà Nội', 'Đà Nẵng', 800.00, 14.00, 450000.00, 'active', '2025-12-11 05:26:21', '', '', NULL),
(57, 'Hà Nội - Sapa', 'Hà Nội', 'Sapa', 350.00, 8.00, 300000.00, 'active', '2025-12-11 05:26:21', '', '', NULL),
(58, 'Đà Nẵng - Huế', 'Đà Nẵng', 'Huế', 100.00, 3.00, 100000.00, 'active', '2025-12-11 05:26:21', '', '', NULL),
(59, 'Sài Gòn - Đà Lạt', 'Sài Gòn', 'Đà Lạt', 300.00, 6.00, 250000.00, 'active', '2025-12-11 05:27:55', '', '', NULL),
(60, 'Sài Gòn - Nha Trang', 'Sài Gòn', 'Nha Trang', 450.00, 8.00, 350000.00, 'active', '2025-12-11 05:27:55', '', '', NULL),
(61, 'Hà Nội - Đà Nẵng', 'Hà Nội', 'Đà Nẵng', 800.00, 14.00, 450000.00, 'active', '2025-12-11 05:27:55', '', '', NULL),
(62, 'Hà Nội - Sapa', 'Hà Nội', 'Sapa', 350.00, 8.00, 300000.00, 'active', '2025-12-11 05:27:55', '', '', NULL),
(63, 'Đà Nẵng - Huế', 'Đà Nẵng', 'Huế', 100.00, 3.00, 100000.00, 'active', '2025-12-11 05:27:55', '', '', NULL),
(64, 'Sài Gòn - Đà Lạt', 'Sài Gòn', 'Đà Lạt', 300.00, 6.00, 250000.00, 'active', '2025-12-11 05:30:53', '', '', NULL),
(65, 'Sài Gòn - Nha Trang', 'Sài Gòn', 'Nha Trang', 450.00, 8.00, 350000.00, 'active', '2025-12-11 05:30:53', '', '', NULL),
(66, 'Hà Nội - Đà Nẵng', 'Hà Nội', 'Đà Nẵng', 800.00, 14.00, 450000.00, 'active', '2025-12-11 05:30:53', '', '', NULL),
(67, 'Hà Nội - Sapa', 'Hà Nội', 'Sapa', 350.00, 8.00, 300000.00, 'active', '2025-12-11 05:30:53', '', '', NULL),
(68, 'Đà Nẵng - Huế', 'Đà Nẵng', 'Huế', 100.00, 3.00, 100000.00, 'active', '2025-12-11 05:30:53', '', '', NULL),
(69, 'Sài Gòn - Đà Lạt', 'Sài Gòn', 'Đà Lạt', 300.00, 6.00, 250000.00, 'active', '2025-12-11 05:31:52', '', '', NULL),
(70, 'Sài Gòn - Nha Trang', 'Sài Gòn', 'Nha Trang', 450.00, 8.00, 350000.00, 'active', '2025-12-11 05:31:52', '', '', NULL),
(71, 'Hà Nội - Đà Nẵng', 'Hà Nội', 'Đà Nẵng', 800.00, 14.00, 450000.00, 'active', '2025-12-11 05:31:52', '', '', NULL),
(72, 'Hà Nội - Sapa', 'Hà Nội', 'Sapa', 350.00, 8.00, 300000.00, 'active', '2025-12-11 05:31:52', '', '', NULL),
(73, 'Đà Nẵng - Huế', 'Đà Nẵng', 'Huế', 100.00, 3.00, 100000.00, 'active', '2025-12-11 05:31:52', '', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `ticket_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `passenger_name` varchar(100) NOT NULL,
  `passenger_phone` varchar(15) NOT NULL,
  `passenger_email` varchar(100) DEFAULT NULL,
  `seat_number` varchar(10) NOT NULL,
  `ticket_code` varchar(50) NOT NULL COMMENT 'Mã QR / vé điện tử',
  `qr_code_path` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn file QR code',
  `status` enum('active','cancelled','checked_in','used') NOT NULL DEFAULT 'active',
  `checked_in_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lưu trữ thông tin chi tiết của từng vé';

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`ticket_id`, `booking_id`, `trip_id`, `passenger_name`, `passenger_phone`, `passenger_email`, `seat_number`, `ticket_code`, `qr_code_path`, `status`, `checked_in_at`, `created_at`) VALUES
(1, 8, 288, 'uy', '+847178546650', 'truongpri123@1trick.net', 'A2', 'TKT2511189701B10', NULL, 'active', NULL, '2025-11-18 05:13:29'),
(2, 9, 288, 'sfd', '+847178546650', 'truongpri123@1trick.net', 'E3', 'TKT251118D5E1DF0', NULL, 'active', NULL, '2025-11-18 07:57:49'),
(3, 10, 288, 'sfd', '+847178546650', 'truongpri123@1trick.net', 'A2', 'TKT251118A6E6450', NULL, 'active', NULL, '2025-11-18 08:03:54'),
(4, 11, 288, 'sfd', '+847178546650', 'truongpri123@1trick.net', 'D4', 'TKT2511183C96D10', NULL, 'active', NULL, '2025-11-18 08:52:35'),
(8, 16, 1, 'Nguyễn Văn A', '0987654321', 'nguyenvana@example.com', 'A1', 'TK2025120821523701', NULL, 'active', NULL, '2025-12-08 13:52:37'),
(9, 17, 2, 'Trần Thị B', '0987654322', 'tranthib@example.com', 'B1', 'TK2025120821523702A', NULL, 'checked_in', NULL, '2025-12-06 14:52:37'),
(10, 17, 2, 'Lê Văn C', '0987654323', 'levanc@example.com', 'B2', 'TK2025120821523702B', NULL, 'checked_in', NULL, '2025-12-06 14:52:37'),
(11, 18, 3, 'Phạm Văn D', '0987654324', 'phamvand@example.com', 'C1', 'TK2025120821523703', NULL, 'used', NULL, '2025-12-01 14:52:37'),
(12, 19, 1, 'Hoàng Thị E', '0987654325', 'hoangthie@example.com', 'D1', 'TK2025120821523704', NULL, 'cancelled', NULL, '2025-12-05 14:52:37'),
(13, 20, 289, 'gfdgdgf', '+840987654342', 'nvt7041@gmail.com', 'D4', 'TKT251208B302BA0', NULL, 'active', NULL, '2025-12-08 15:38:35'),
(14, 21, 289, 'gfđg', '+840987654342', 'nvt7041@gmail.com', 'C1', 'TKT251208A407A20', NULL, 'active', NULL, '2025-12-08 15:49:30'),
(15, 22, 289, 'hgghg', '+840987654342', 'nvt7041@gmail.com', 'D4', 'TKT251208F1E1090', NULL, '', NULL, '2025-12-08 15:51:59'),
(16, 23, 289, 'gfhghfghgf', '+840987654342', 'nvt7041@gmail.com', 'C1', 'TKT25120869418C0', NULL, '', NULL, '2025-12-08 16:01:42'),
(17, 24, 289, 'gdggfd', '+840987654342', 'nvt7041@gmail.com', 'C1', 'TKT2512089DE6CE0', NULL, '', NULL, '2025-12-08 16:16:41'),
(18, 25, 290, 'dssd', '+840987654342', 'nvt7041@gmail.com', 'D2', 'TKT25120963177B0', NULL, '', NULL, '2025-12-09 06:31:18'),
(19, 27, 293, '', '0987654342', 'nvt7041@gmail.com', 'A1', 'TICKET515548529', NULL, 'active', NULL, '2025-12-04 07:02:34'),
(20, 27, 293, 'Người thân', '0909123456', 'nvt7041@gmail.com', 'A2', 'TICKET086585205', NULL, 'active', NULL, '2025-12-04 07:02:34'),
(21, 28, 293, '', '0987654342', 'nvt7041@gmail.com', 'B5', 'TICKET171646753', NULL, 'active', NULL, '2025-12-09 05:02:34'),
(22, 29, 293, '', '0987654342', 'nvt7041@gmail.com', 'C10', 'TICKET811931577', NULL, 'active', NULL, '2025-11-29 07:02:34'),
(23, 30, 289, 'trterert', '+840987654342', 'nvt7041@gmail.com', 'A2', 'TKT251209F479010', NULL, 'active', NULL, '2025-12-09 11:11:11'),
(24, 31, 290, 'trrtttr', '+840987654342', 'nvt7041@gmail.com', 'D4', 'TKT251209EE75DD0', NULL, '', NULL, '2025-12-09 11:28:30'),
(25, 32, 289, 'ghghgfhgf', '+840987654342', 'nvt7041@gmail.com', 'A2', 'TKT25120907B20D0', NULL, 'active', NULL, '2025-12-09 11:33:04'),
(26, 33, 289, 'frd', '+840987654342', 'nvt7041@gmail.com', 'E2', 'TKT251209282FB30', NULL, 'active', NULL, '2025-12-09 11:40:50'),
(27, 34, 289, 'fd', '+840987654342', 'nvt7041@gmail.com', 'E3', 'TKT2512096727220', NULL, 'active', NULL, '2025-12-09 11:48:06'),
(28, 35, 289, 'rfdf', '+840987654342', 'nvt7041@gmail.com', 'C1', 'TKT251209AB357C0', NULL, 'active', NULL, '2025-12-09 12:23:22'),
(29, 36, 289, 'fdsfds', '+840987654342', 'nvt7041@gmail.com', 'E2', 'TKT2512097391B80', NULL, 'active', NULL, '2025-12-09 15:51:51'),
(30, 37, 289, 'fdsdfs', '+840987654342', 'nvt7041@gmail.com', 'D4', 'TKT25120992FFA30', NULL, '', NULL, '2025-12-09 16:04:09'),
(31, 38, 289, 'gdfgd', '+840987654342', 'nvt7041@gmail.com', 'D4', 'TKT25120931761B0', NULL, 'active', NULL, '2025-12-09 16:21:39'),
(32, 39, 292, 'vxcvcx', '+840987654342', 'nvt7041@gmail.com', 'D2', 'TKT2512109A42290', NULL, 'active', NULL, '2025-12-10 14:49:29'),
(33, 40, 294, 'fdfđf', '+840987654343', 'nvt7040@gmail.com', 'A2', 'TKT2512114273340', NULL, 'active', NULL, '2025-12-11 05:56:04'),
(34, 41, 294, 'gfgfg', '+840987654343', 'nvt7040@gmail.com', 'D2', 'TKT2512116B8D730', NULL, 'active', NULL, '2025-12-11 05:57:26'),
(35, 42, 294, 'gdfgfdg', '+840987654342', 'nvt7041@gmail.com', '15', 'TKT25121155EE610', NULL, 'active', NULL, '2025-12-11 07:24:53'),
(36, 43, 294, 'fdfd', 'null0987654342', 'nvt7041@gmail.com', 'A3', 'TKT25121118E58D0', NULL, 'cancelled', NULL, '2025-12-11 07:48:33'),
(37, 44, 294, 'sfd', 'null0987654342', 'nvt7041@gmail.com', 'A1', 'TKT25121131CAFD0', NULL, 'active', NULL, '2025-12-11 08:01:07'),
(38, 45, 294, 'gfdgdgf', 'null0987654342', 'nvt7041@gmail.com', 'A3', 'TKT251211F27D810', NULL, 'active', NULL, '2025-12-11 08:11:27'),
(39, 46, 294, 'uy', 'null0987654342', 'nvt7041@gmail.com', 'B1', 'TKT251211F97A090', NULL, 'active', NULL, '2025-12-11 08:18:07'),
(40, 47, 294, 'è', 'null0987654342', 'nvt7041@gmail.com', 'A4', 'TKT251211A343E80', NULL, 'active', NULL, '2025-12-11 08:26:18'),
(41, 48, 294, 'hghgf', 'null0987654342', 'nvt7041@gmail.com', 'A5', 'TKT251211A4E31E0', NULL, 'active', NULL, '2025-12-11 09:15:38'),
(42, 49, 294, 'bgb', 'null0987654342', 'nvt7041@gmail.com', 'B2', 'TKT251211B230B80', NULL, '', NULL, '2025-12-11 09:22:51'),
(43, 50, 294, 'Nguyen Van Truong', 'null0987654342', 'nvt7041@gmail.com', 'A6', 'TKT25121145BD830', NULL, '', NULL, '2025-12-11 12:57:56'),
(44, 51, 295, 'Nguyen Van Truong', 'null0987654342', 'nvt7041@gmail.com', 'A1', 'TKT251211A948410', NULL, '', NULL, '2025-12-11 13:18:50'),
(45, 52, 295, 'Nguyen Van Truong', 'null0987654342', 'nvt7041@gmail.com', 'A2', 'TKT251211476DC30', NULL, 'active', NULL, '2025-12-11 13:20:36');

-- --------------------------------------------------------

--
-- Table structure for table `trips`
--

CREATE TABLE `trips` (
  `trip_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `departure_time` datetime NOT NULL,
  `arrival_time` datetime NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `available_seats` int(11) NOT NULL COMMENT 'Số ghế còn trống',
  `status` enum('scheduled','open','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Thông tin chi tiết của từng chuyến xe';

--
-- Dumping data for table `trips`
--

INSERT INTO `trips` (`trip_id`, `partner_id`, `route_id`, `vehicle_id`, `driver_id`, `departure_time`, `arrival_time`, `price`, `available_seats`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, '2025-10-25 08:00:00', '2025-10-25 10:30:00', 150000.00, 40, 'scheduled', '2025-11-12 13:49:57', '2025-11-12 13:49:57'),
(2, 1, 1, 2, 2, '2025-10-25 14:00:00', '2025-10-25 16:30:00', 120000.00, 45, 'scheduled', '2025-11-12 13:49:57', '2025-11-12 13:49:57'),
(3, 1, 2, 1, 1, '2025-10-26 20:00:00', '2025-10-27 10:00:00', 450000.00, 40, 'scheduled', '2025-12-11 13:49:57', '2025-12-12 13:49:57'),
(4, 2, 3, 3, 3, '2025-10-25 07:00:00', '2025-10-25 13:00:00', 250000.00, 22, 'scheduled', '2025-11-12 13:49:57', '2025-11-12 13:49:57'),
(5, 2, 4, 4, 3, '2025-10-26 09:00:00', '2025-10-26 18:00:00', 350000.00, 36, 'scheduled', '2025-11-12 13:49:57', '2025-11-12 13:49:57'),
(6, 1, 5, 1, NULL, '2025-11-15 06:00:00', '2025-11-15 13:00:00', 250000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(7, 1, 5, 1, NULL, '2025-11-15 08:30:00', '2025-11-15 15:30:00', 250000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(8, 1, 5, 1, NULL, '2025-11-15 11:00:00', '2025-11-15 18:00:00', 250000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(9, 1, 5, 1, NULL, '2025-11-15 14:00:00', '2025-11-15 21:00:00', 250000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(10, 1, 5, 1, NULL, '2025-11-15 17:00:00', '2025-11-16 00:00:00', 250000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(11, 1, 5, 1, NULL, '2025-11-15 20:00:00', '2025-11-16 03:00:00', 250000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(12, 1, 5, 1, NULL, '2025-11-15 22:30:00', '2025-11-16 05:30:00', 250000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(13, 1, 13, 1, NULL, '2025-11-15 07:00:00', '2025-11-15 16:00:00', 300000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(14, 1, 13, 1, NULL, '2025-11-15 09:00:00', '2025-11-15 18:00:00', 300000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(15, 1, 13, 1, NULL, '2025-11-15 13:00:00', '2025-11-15 22:00:00', 300000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(16, 1, 13, 1, NULL, '2025-11-15 16:00:00', '2025-11-16 01:00:00', 300000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(17, 1, 13, 1, NULL, '2025-11-15 19:00:00', '2025-11-16 04:00:00', 300000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(18, 1, 13, 1, NULL, '2025-11-15 21:00:00', '2025-11-16 06:00:00', 300000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(20, 1, 10, 1, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 120000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(21, 1, 10, 1, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 120000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(22, 1, 10, 1, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 120000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(23, 1, 10, 1, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 120000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(24, 1, 10, 1, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 120000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(25, 1, 10, 1, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 120000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(26, 1, 10, 1, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 120000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(27, 1, 7, 1, NULL, '2025-11-15 05:00:00', '2025-11-15 08:00:00', 150000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(28, 1, 7, 2, NULL, '2025-11-15 05:00:00', '2025-11-15 08:00:00', 150000.00, 45, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(29, 2, 7, 3, NULL, '2025-11-15 05:00:00', '2025-11-15 08:00:00', 150000.00, 22, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(30, 2, 7, 4, NULL, '2025-11-15 05:00:00', '2025-11-15 08:00:00', 150000.00, 36, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(31, 1, 7, 5, NULL, '2025-11-15 05:00:00', '2025-11-15 08:00:00', 150000.00, 20, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(32, 1, 7, 6, NULL, '2025-11-15 05:00:00', '2025-11-15 08:00:00', 150000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(33, 2, 7, 7, NULL, '2025-11-15 05:00:00', '2025-11-15 08:00:00', 150000.00, 24, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(34, 2, 7, 8, NULL, '2025-11-15 05:00:00', '2025-11-15 08:00:00', 150000.00, 45, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(42, 1, 16, 1, NULL, '2025-11-15 06:00:00', '2025-11-15 06:30:00', 50000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(43, 1, 16, 2, NULL, '2025-11-15 06:00:00', '2025-11-15 06:30:00', 50000.00, 45, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(44, 2, 16, 3, NULL, '2025-11-15 06:00:00', '2025-11-15 06:30:00', 50000.00, 22, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(45, 2, 16, 4, NULL, '2025-11-15 06:00:00', '2025-11-15 06:30:00', 50000.00, 36, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(46, 1, 16, 5, NULL, '2025-11-15 06:00:00', '2025-11-15 06:30:00', 50000.00, 20, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(47, 1, 16, 6, NULL, '2025-11-15 06:00:00', '2025-11-15 06:30:00', 50000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(48, 2, 16, 7, NULL, '2025-11-15 06:00:00', '2025-11-15 06:30:00', 50000.00, 24, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(49, 2, 16, 8, NULL, '2025-11-15 06:00:00', '2025-11-15 06:30:00', 50000.00, 45, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(50, 1, 16, 1, NULL, '2025-11-15 07:30:00', '2025-11-15 08:00:00', 50000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(51, 1, 16, 2, NULL, '2025-11-15 07:30:00', '2025-11-15 08:00:00', 50000.00, 45, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(57, 1, 19, 1, NULL, '2025-11-15 06:00:00', '2025-11-15 10:00:00', 180000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(58, 1, 19, 1, NULL, '2025-11-15 09:00:00', '2025-11-15 13:00:00', 180000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(59, 1, 19, 1, NULL, '2025-11-15 12:00:00', '2025-11-15 16:00:00', 180000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(60, 1, 19, 1, NULL, '2025-11-15 15:00:00', '2025-11-15 19:00:00', 180000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(61, 1, 19, 1, NULL, '2025-11-15 18:00:00', '2025-11-15 22:00:00', 180000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(64, 1, 20, 1, NULL, '2025-11-15 05:00:00', '2025-11-15 09:00:00', 160000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(65, 1, 20, 1, NULL, '2025-11-15 08:00:00', '2025-11-15 12:00:00', 160000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(66, 1, 20, 1, NULL, '2025-11-15 11:00:00', '2025-11-15 15:00:00', 160000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(67, 1, 20, 1, NULL, '2025-11-15 14:00:00', '2025-11-15 18:00:00', 160000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(68, 1, 20, 1, NULL, '2025-11-15 17:00:00', '2025-11-15 21:00:00', 160000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(69, 1, 20, 1, NULL, '2025-11-15 20:00:00', '2025-11-16 00:00:00', 160000.00, 40, '', '2025-11-14 05:32:50', '2025-11-14 05:32:50'),
(71, 1, 1, 1, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(72, 1, 1, 2, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(73, 2, 1, 3, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(74, 2, 1, 4, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(75, 1, 1, 5, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(76, 1, 1, 6, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(77, 2, 1, 7, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(78, 2, 1, 8, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(79, 1, 1, 1, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(80, 1, 1, 2, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(81, 2, 1, 3, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(82, 2, 1, 4, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(83, 1, 1, 5, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(84, 1, 1, 6, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(85, 2, 1, 7, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(86, 2, 1, 8, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(87, 1, 1, 1, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(88, 1, 1, 2, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(89, 2, 1, 3, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(90, 2, 1, 4, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(91, 1, 1, 5, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(92, 1, 1, 6, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(93, 2, 1, 7, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(94, 2, 1, 8, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(95, 1, 1, 1, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(96, 1, 1, 2, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(97, 2, 1, 3, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(98, 2, 1, 4, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(99, 1, 1, 5, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(100, 1, 1, 6, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(101, 2, 1, 7, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(102, 2, 1, 8, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(103, 1, 1, 1, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(104, 1, 1, 2, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(105, 2, 1, 3, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(106, 2, 1, 4, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(107, 1, 1, 5, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(108, 1, 1, 6, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(109, 2, 1, 7, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(110, 2, 1, 8, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(111, 1, 1, 1, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(112, 1, 1, 2, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(113, 2, 1, 3, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(114, 2, 1, 4, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(115, 1, 1, 5, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(116, 1, 1, 6, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(117, 2, 1, 7, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(118, 2, 1, 8, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(119, 1, 1, 1, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(120, 1, 1, 2, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(121, 2, 1, 3, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(122, 2, 1, 4, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(123, 1, 1, 5, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(124, 1, 1, 6, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(125, 2, 1, 7, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(126, 2, 1, 8, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(127, 1, 1, 1, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(128, 1, 1, 2, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(129, 2, 1, 3, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(130, 2, 1, 4, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(131, 1, 1, 5, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(132, 1, 1, 6, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(133, 2, 1, 7, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(134, 2, 1, 8, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(135, 1, 2, 1, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(136, 1, 2, 2, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(137, 2, 2, 3, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(138, 2, 2, 4, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(139, 1, 2, 5, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(140, 1, 2, 6, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(141, 2, 2, 7, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(142, 2, 2, 8, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(143, 1, 2, 1, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(144, 1, 2, 2, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(145, 2, 2, 3, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(146, 2, 2, 4, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(147, 1, 2, 5, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(148, 1, 2, 6, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(149, 2, 2, 7, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(150, 2, 2, 8, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(151, 1, 2, 1, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(152, 1, 2, 2, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(153, 2, 2, 3, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(154, 2, 2, 4, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(155, 1, 2, 5, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(156, 1, 2, 6, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(157, 2, 2, 7, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(158, 2, 2, 8, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(159, 1, 2, 1, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(160, 1, 2, 2, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(161, 2, 2, 3, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(162, 2, 2, 4, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(163, 1, 2, 5, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(164, 1, 2, 6, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(165, 2, 2, 7, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(166, 2, 2, 8, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(167, 1, 2, 1, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(168, 1, 2, 2, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(169, 2, 2, 3, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(170, 2, 2, 4, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(171, 1, 2, 5, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(172, 1, 2, 6, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(173, 2, 2, 7, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(174, 2, 2, 8, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(175, 1, 2, 1, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(176, 1, 2, 2, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(177, 2, 2, 3, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(178, 2, 2, 4, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(179, 1, 2, 5, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(180, 1, 2, 6, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(181, 2, 2, 7, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(182, 2, 2, 8, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(183, 1, 2, 1, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(184, 1, 2, 2, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(185, 2, 2, 3, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(186, 2, 2, 4, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(187, 1, 2, 5, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(188, 1, 2, 6, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(189, 2, 2, 7, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(190, 2, 2, 8, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(191, 1, 2, 1, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(192, 1, 2, 2, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(193, 2, 2, 3, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(194, 2, 2, 4, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(195, 1, 2, 5, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(196, 1, 2, 6, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(197, 2, 2, 7, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(198, 2, 2, 8, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(199, 1, 3, 1, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(200, 1, 3, 2, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(201, 2, 3, 3, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(202, 2, 3, 4, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(203, 1, 3, 5, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(204, 1, 3, 6, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(205, 2, 3, 7, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(206, 2, 3, 8, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(207, 1, 3, 1, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(208, 1, 3, 2, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(209, 2, 3, 3, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(210, 2, 3, 4, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(211, 1, 3, 5, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(212, 1, 3, 6, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(213, 2, 3, 7, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(214, 2, 3, 8, NULL, '2025-11-15 08:00:00', '2025-11-15 10:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(215, 1, 3, 1, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(216, 1, 3, 2, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(217, 2, 3, 3, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(218, 2, 3, 4, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(219, 1, 3, 5, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(220, 1, 3, 6, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(221, 2, 3, 7, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(222, 2, 3, 8, NULL, '2025-11-15 10:00:00', '2025-11-15 12:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(223, 1, 3, 1, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(224, 1, 3, 2, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(225, 2, 3, 3, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(226, 2, 3, 4, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(227, 1, 3, 5, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(228, 1, 3, 6, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(229, 2, 3, 7, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(230, 2, 3, 8, NULL, '2025-11-15 12:00:00', '2025-11-15 14:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(231, 1, 3, 1, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(232, 1, 3, 2, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(233, 2, 3, 3, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(234, 2, 3, 4, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(235, 1, 3, 5, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(236, 1, 3, 6, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(237, 2, 3, 7, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(238, 2, 3, 8, NULL, '2025-11-15 14:00:00', '2025-11-15 16:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(239, 1, 3, 1, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(240, 1, 3, 2, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(241, 2, 3, 3, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(242, 2, 3, 4, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(243, 1, 3, 5, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(244, 1, 3, 6, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(245, 2, 3, 7, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(246, 2, 3, 8, NULL, '2025-11-15 16:00:00', '2025-11-15 18:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(247, 1, 3, 1, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(248, 1, 3, 2, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(249, 2, 3, 3, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(250, 2, 3, 4, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(251, 1, 3, 5, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(252, 1, 3, 6, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(253, 2, 3, 7, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(254, 2, 3, 8, NULL, '2025-11-15 18:00:00', '2025-11-15 20:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(255, 1, 3, 1, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(256, 1, 3, 2, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(257, 2, 3, 3, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(258, 2, 3, 4, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(259, 1, 3, 5, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(260, 1, 3, 6, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(261, 2, 3, 7, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(262, 2, 3, 8, NULL, '2025-11-15 20:00:00', '2025-11-15 22:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(263, 1, 4, 1, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(264, 1, 4, 2, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(265, 2, 4, 3, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 22, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(266, 2, 4, 4, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 36, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(267, 1, 4, 5, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 20, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(268, 1, 4, 6, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 40, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(269, 2, 4, 7, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 24, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(270, 2, 4, 8, NULL, '2025-11-15 06:00:00', '2025-11-15 08:00:00', 50000.00, 45, '', '2025-11-14 05:39:51', '2025-11-14 05:39:51'),
(272, 2, 5, 19, NULL, '2025-11-16 08:00:00', '2025-11-16 14:00:00', 280000.00, 45, 'scheduled', '2025-11-15 06:22:19', '2025-11-15 06:22:19'),
(273, 3, 5, 21, NULL, '2025-11-16 20:00:00', '2025-11-17 02:00:00', 240000.00, 40, 'scheduled', '2025-11-15 06:22:19', '2025-11-15 06:22:19'),
(275, 4, 13, 23, NULL, '2025-11-16 22:00:00', '2025-11-17 06:00:00', 320000.00, 34, 'scheduled', '2025-11-15 06:22:19', '2025-11-15 06:22:19'),
(276, 2, 6, 20, NULL, '2025-11-16 19:00:00', '2025-11-17 09:00:00', 400000.00, 34, 'scheduled', '2025-11-15 06:22:19', '2025-11-15 06:22:19'),
(277, 5, 6, 24, NULL, '2025-11-16 21:00:00', '2025-11-17 11:00:00', 380000.00, 40, 'scheduled', '2025-11-15 06:22:19', '2025-11-15 06:22:19'),
(279, 3, 10, 22, NULL, '2025-11-16 14:00:00', '2025-11-16 16:00:00', 110000.00, 45, 'scheduled', '2025-11-15 06:22:19', '2025-11-15 06:22:19'),
(280, 2, 7, 19, NULL, '2025-11-16 06:00:00', '2025-11-16 08:30:00', 150000.00, 45, 'scheduled', '2025-11-15 06:22:19', '2025-11-15 06:22:19'),
(281, 5, 7, 24, NULL, '2025-11-16 18:00:00', '2025-11-16 20:30:00', 140000.00, 40, 'scheduled', '2025-11-15 06:22:19', '2025-11-15 06:22:19'),
(282, 2, 21, 20, NULL, '2025-11-16 20:00:00', '2025-11-17 10:00:00', 450000.00, 34, 'scheduled', '2025-11-15 06:22:19', '2025-11-15 06:22:19'),
(283, 3, 21, 21, NULL, '2025-11-16 22:00:00', '2025-11-17 12:00:00', 430000.00, 40, 'scheduled', '2025-11-15 06:22:19', '2025-11-15 06:22:19'),
(284, 3, 8, 22, NULL, '2025-11-16 07:00:00', '2025-11-16 15:00:00', 300000.00, 45, 'scheduled', '2025-11-15 06:22:19', '2025-11-15 06:22:19'),
(285, 5, 8, 24, NULL, '2025-11-16 21:00:00', '2025-11-17 05:00:00', 280000.00, 40, 'scheduled', '2025-11-15 06:22:19', '2025-11-15 06:22:19'),
(287, 4, 20, 23, NULL, '2025-11-16 15:00:00', '2025-11-16 18:30:00', 170000.00, 34, 'scheduled', '2025-11-15 06:22:19', '2025-11-15 06:22:19'),
(288, 1, 5, 2, 3, '2025-11-19 21:00:00', '2025-11-20 11:30:00', 450000.00, 34, 'open', '2025-11-17 13:37:26', '2025-11-17 14:51:03'),
(289, 1, 21, 17, NULL, '2025-12-09 08:00:00', '2025-12-09 20:00:00', 350000.00, 40, 'open', '2025-12-08 15:24:43', '2025-12-08 15:24:43'),
(290, 1, 21, 17, NULL, '2025-12-09 14:00:00', '2025-12-10 02:00:00', 350000.00, 40, 'open', '2025-12-08 15:24:43', '2025-12-08 15:24:43'),
(291, 1, 21, 17, NULL, '2025-12-09 20:00:00', '2025-12-10 08:00:00', 350000.00, 40, 'open', '2025-12-08 15:24:43', '2025-12-08 15:24:43'),
(292, 1, 21, 17, NULL, '2025-12-10 08:00:00', '2025-12-10 20:00:00', 350000.00, 40, 'open', '2025-12-08 15:24:43', '2025-12-08 15:24:43'),
(293, 1, 21, 17, NULL, '2025-12-11 08:00:00', '2025-12-11 20:00:00', 350000.00, 40, 'open', '2025-12-08 15:24:43', '2025-12-08 15:24:43'),
(294, 1, 43, 17, 2, '2025-12-11 15:00:00', '2025-12-13 12:53:00', 300000.00, 34, 'open', '2025-12-11 05:53:45', '2025-12-11 05:54:54'),
(295, 1, 21, 17, 2, '2025-12-12 15:17:00', '2025-12-13 15:17:00', 5000000.00, 34, 'scheduled', '2025-12-11 13:18:01', '2025-12-11 13:18:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn ảnh đại diện',
  `role` enum('user','admin','partner') NOT NULL DEFAULT 'user',
  `status` enum('active','locked') NOT NULL DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0 COMMENT 'Xác thực email',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fullname` varchar(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lưu trữ thông tin người dùng và admin';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `phone`, `password`, `address`, `avatar`, `role`, `status`, `email_verified`, `created_at`, `updated_at`, `fullname`) VALUES
(2, 'Nguyễn Văn A', 'user1@example.com', '0987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123 Đường ABC, Hà Nội', NULL, 'user', 'active', 0, '2025-11-12 13:49:57', '2025-11-13 08:33:59', 'Nguyễn Văn A'),
(3, 'Trần Thị B', 'user2@example.com', '0976543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '456 Đường XYZ, TP.HCM', NULL, 'user', 'active', 0, '2025-11-12 13:49:57', '2025-11-13 08:33:59', 'Trần Thị B'),
(4, 'bus_booking', 'admin1@gmail.com', '0987654312', '$2y$10$sXpoAB/8kHtlTZKzbQxc1O9LQSsGz6bqQLmi.HssPBTkV9ImF0Mja', NULL, NULL, 'user', 'active', 0, '2025-11-13 07:52:38', '2025-11-13 08:33:59', 'bus_booking'),
(5, '', 'user1@gmail.com', '0901234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'user', 'active', 0, '2025-11-13 08:34:14', '2025-11-13 08:34:14', 'Nguyễn Văn An'),
(6, 'Mai Linh', 'user2@gmail.com', '0902345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'partner', 'active', 0, '2025-11-13 08:34:14', '2025-12-11 05:18:03', 'Trần Thị Bình'),
(7, '', 'partner2@gmail.com', '0986543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, '', 'active', 0, '2025-11-13 08:34:14', '2025-11-13 08:34:14', 'Phương Trang Company'),
(9, '', 'truong1@gmail.com', '0987654341', '$2y$10$NuQl0InQv/Rqzuovp4wHM.fE04U4HbY9TGyQEjPJrSDjsCw2DJUaa', NULL, NULL, 'user', 'active', 0, '2025-11-13 14:16:46', '2025-11-13 14:16:46', 'nvt'),
(10, '', 'nvt7040@gmail.com', '0987654343', '$2y$10$MV6FSQ.pBLWb7rfzGrAJZOks6Ju7E/Zm.ZAzX7X24DO2Wexa42MDW', NULL, NULL, 'user', 'active', 0, '2025-11-13 15:58:03', '2025-12-10 14:59:57', 'nvt'),
(17, 'Guest User', 'guest@system.local', '0000000000', '$2y$10$7boxG/mglTAqvi204qrhvObrSyPR4bBSfozoCYGh03P/u5W4ZqDp2', NULL, NULL, 'user', 'active', 0, '2025-11-18 05:11:03', '2025-11-18 05:11:03', ''),
(18, 'Nguyen Van Truong', 'nvt7041@gmail.com', '0987654342', '$2y$10$sL5KBX73OyQz1DVd3tZwp.k9hQyQwHdu77SxXNPmyu6XZCDrD36Bm', '123 deaad\r\n123 adde', NULL, 'user', 'active', 0, '2025-12-08 14:21:00', '2025-12-11 09:25:34', 'nvt'),
(20, 'Admin User', 'admin@busbooking.com', '0909999999', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'admin', 'active', 0, '2025-12-08 17:11:18', '2025-12-08 17:11:18', ''),
(22, 'Phương Trang', 'phuongtrang@example.com', '0908888888', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'partner', 'active', 0, '2025-12-08 17:13:14', '2025-12-09 05:44:42', ''),
(24, 'Hoàng Long', 'hoanglong@example.com', '19001234', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'partner', 'active', 0, '2025-12-11 05:18:03', '2025-12-11 05:18:03', ''),
(25, 'Kumho Samco', 'kumho@example.com', '19005678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'partner', 'active', 0, '2025-12-11 05:18:03', '2025-12-11 05:18:03', ''),
(26, 'Thành Bưởi', 'thanhbuoi@example.com', '19009090', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'partner', 'active', 0, '2025-12-11 05:18:03', '2025-12-11 05:18:03', ''),
(33, 'trường', 'nvt7000@gmail.com', '0962175776', '$2y$10$98dpkeazurNF1le2QqIHpeje7WuagmL5NWztY2eHRThmM5SwAWm2u', NULL, NULL, 'partner', 'active', 0, '2025-12-11 07:10:36', '2025-12-11 07:13:33', '');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `vehicle_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `license_plate` varchar(15) NOT NULL,
  `vehicle_type` enum('ghế ngồi','giường nằm','limousine') NOT NULL DEFAULT 'ghế ngồi',
  `type` varchar(100) NOT NULL COMMENT 'Loại xe (giường nằm, ghế ngồi)',
  `total_seats` int(11) NOT NULL,
  `seat_layout` varchar(10) DEFAULT '2-2' COMMENT 'Sơ đồ ghế (VD: 2-2, 2-1)',
  `status` enum('active','maintenance','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Quản lý đội xe của từng nhà xe';

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`vehicle_id`, `partner_id`, `license_plate`, `vehicle_type`, `type`, `total_seats`, `seat_layout`, `status`, `created_at`) VALUES
(1, 1, '29A-12345', 'ghế ngồi', 'Giường nằm', 40, '2-1', 'active', '2025-11-12 13:49:57'),
(2, 1, '29A-12346', 'ghế ngồi', 'Ghế ngồi', 45, '2-2', 'active', '2025-11-12 13:49:57'),
(3, 2, '30B-54321', 'ghế ngồi', 'Limousine', 22, '2-1', 'active', '2025-11-12 13:49:57'),
(4, 2, '30B-54322', 'ghế ngồi', 'Giường nằm', 36, '2-1', 'active', '2025-11-12 13:49:57'),
(5, 1, '51G-502.16', '', '', 20, '2-2', 'active', '2025-11-14 05:32:49'),
(6, 1, '51H-994.79', '', '', 40, '2-2', 'active', '2025-11-14 05:32:49'),
(7, 2, '51B-878.11', '', '', 24, '2-2', 'active', '2025-11-14 05:32:50'),
(8, 2, '51C-534.43', '', '', 45, '2-2', 'active', '2025-11-14 05:32:50'),
(17, 1, '51B-12345', 'limousine', '', 34, '2-1', 'active', '2025-11-15 06:22:19'),
(18, 1, '51B-12346', 'giường nằm', '', 40, '2-2', 'active', '2025-11-15 06:22:19'),
(19, 2, '29B-23456', 'ghế ngồi', '', 45, '2-2', 'active', '2025-11-15 06:22:19'),
(20, 2, '29B-23457', 'limousine', '', 34, '2-1', 'active', '2025-11-15 06:22:19'),
(21, 3, '43B-34567', 'giường nằm', '', 40, '2-2', 'active', '2025-11-15 06:22:19'),
(22, 3, '43B-34568', 'ghế ngồi', '', 45, '2-2', 'active', '2025-11-15 06:22:19'),
(23, 4, '92A-45678', 'limousine', '', 34, '2-1', 'active', '2025-11-15 06:22:19'),
(24, 5, '30B-56789', 'giường nằm', '', 40, '2-2', 'active', '2025-11-15 06:22:19'),
(81, 1, '51h1', 'ghế ngồi', 'Limo', 34, '2-2', 'active', '2025-12-11 06:45:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `booking_code` (`booking_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `payment_status` (`payment_status`),
  ADD KEY `idx_trip_id` (`trip_id`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`complaint_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `partner_id` (`partner_id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`driver_id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `partner_id` (`partner_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `partner_id` (`partner_id`);

--
-- Indexes for table `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`partner_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`promotion_id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `promotion_assignments`
--
ALTER TABLE `promotion_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `promotion_id` (`promotion_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `partner_id` (`partner_id`);

--
-- Indexes for table `promotion_usage`
--
ALTER TABLE `promotion_usage`
  ADD PRIMARY KEY (`usage_id`),
  ADD KEY `promotion_id` (`promotion_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`refund_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `trip_id` (`trip_id`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`route_id`),
  ADD KEY `start_point` (`start_point`),
  ADD KEY `end_point` (`end_point`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD UNIQUE KEY `ticket_code` (`ticket_code`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `trip_id` (`trip_id`);

--
-- Indexes for table `trips`
--
ALTER TABLE `trips`
  ADD PRIMARY KEY (`trip_id`),
  ADD KEY `partner_id` (`partner_id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `departure_time` (`departure_time`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`vehicle_id`),
  ADD UNIQUE KEY `license_plate` (`license_plate`),
  ADD KEY `partner_id` (`partner_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `complaint_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `driver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT for table `partners`
--
ALTER TABLE `partners`
  MODIFY `partner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `promotion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `promotion_assignments`
--
ALTER TABLE `promotion_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotion_usage`
--
ALTER TABLE `promotion_usage`
  MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `refund_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `route_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `trips`
--
ALTER TABLE `trips`
  MODIFY `trip_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=296;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `vehicle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `complaints_ibfk_2` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`partner_id`) ON DELETE SET NULL;

--
-- Constraints for table `drivers`
--
ALTER TABLE `drivers`
  ADD CONSTRAINT `drivers_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`partner_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`partner_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `promotion_assignments`
--
ALTER TABLE `promotion_assignments`
  ADD CONSTRAINT `promotion_assignments_ibfk_1` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`promotion_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_assignments_ibfk_3` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`partner_id`) ON DELETE CASCADE;

--
-- Constraints for table `promotion_usage`
--
ALTER TABLE `promotion_usage`
  ADD CONSTRAINT `promotion_usage_ibfk_1` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`promotion_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_usage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_usage_ibfk_3` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `refunds`
--
ALTER TABLE `refunds`
  ADD CONSTRAINT `refunds_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refunds_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refunds_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`trip_id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`trip_id`) ON DELETE CASCADE;

--
-- Constraints for table `trips`
--
ALTER TABLE `trips`
  ADD CONSTRAINT `trips_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`partner_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trips_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`route_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trips_ibfk_3` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trips_ibfk_4` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE SET NULL;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`partner_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
