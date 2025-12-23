-- ============================================
-- Table: seat_holds
-- Mục đích: Lưu trữ ghế đang được giữ tạm thời khi user chọn ghế
-- ============================================

CREATE TABLE IF NOT EXISTS `seat_holds` (
  `hold_id` int(11) NOT NULL AUTO_INCREMENT,
  `trip_id` int(11) NOT NULL COMMENT 'ID của chuyến xe',
  `seat_number` varchar(10) NOT NULL COMMENT 'Số ghế (VD: A1, B2)',
  `user_id` int(11) DEFAULT NULL COMMENT 'ID người dùng (NULL nếu guest)',
  `session_id` varchar(255) DEFAULT NULL COMMENT 'Session ID cho guest booking',
  `status` enum('holding','confirmed','expired','released') NOT NULL DEFAULT 'holding' COMMENT 'Trạng thái giữ ghế',
  `expired_at` datetime NOT NULL COMMENT 'Thời gian hết hạn giữ ghế',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`hold_id`),
  KEY `trip_id` (`trip_id`),
  KEY `seat_number` (`seat_number`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`),
  KEY `status` (`status`),
  KEY `expired_at` (`expired_at`),
  KEY `idx_trip_seat` (`trip_id`, `seat_number`),
  CONSTRAINT `seat_holds_ibfk_1` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`trip_id`) ON DELETE CASCADE,
  CONSTRAINT `seat_holds_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lưu trữ ghế đang được giữ tạm thời';

-- ============================================
-- Stored Procedure: Release Expired Seat Holds
-- Mục đích: Tự động giải phóng ghế đã hết hạn
-- ============================================

DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `ReleaseExpiredSeatHolds`()
BEGIN
    UPDATE `seat_holds`
    SET `status` = 'expired',
        `updated_at` = NOW()
    WHERE `status` = 'holding'
    AND `expired_at` < NOW();
END$$

DELIMITER ;

-- ============================================
-- Event: Auto-release expired holds every minute
-- Mục đích: Tự động chạy stored procedure mỗi phút
-- ============================================

-- Note: Events require EVENT_PRIVILEGE. Run this manually if needed:
-- SET GLOBAL event_scheduler = ON;

-- CREATE EVENT IF NOT EXISTS `auto_release_expired_holds`
-- ON SCHEDULE EVERY 1 MINUTE
-- DO
--   CALL ReleaseExpiredSeatHolds();

