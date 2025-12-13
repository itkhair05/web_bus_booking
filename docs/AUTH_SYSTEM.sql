-- ============================================
-- AUTH SYSTEM SQL
-- Tạo users mẫu và password_resets table
-- ============================================

USE bus_booking;

-- 1. Đảm bảo bảng users có column fullname
-- Thêm column fullname nếu chưa có
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'fullname';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN fullname VARCHAR(100) NOT NULL DEFAULT "" AFTER user_id',
    'SELECT "Column fullname already exists" AS Info');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Nếu đang có column 'name', copy data sang 'fullname' 
UPDATE users SET fullname = COALESCE(name, fullname, '') WHERE (fullname IS NULL OR fullname = '');

-- 2. Đảm bảo bảng password_resets đã có
CREATE TABLE IF NOT EXISTS password_resets (
    reset_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tạo users mẫu (password mặc định: "password")
-- Password hash của "password" = $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO users (fullname, email, phone, password, role, status) VALUES
-- Regular users
('Nguyễn Văn An', 'user1@gmail.com', '0901234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active'),
('Trần Thị Bình', 'user2@gmail.com', '0902345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active'),
('Test User', 'test@gmail.com', '0123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active'),

-- Partner accounts
('Rang Đông Buslines', 'partner1@gmail.com', '0987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'partner', 'active'),
('Phương Trang Company', 'partner2@gmail.com', '0986543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'partner', 'active'),

-- Admin account
('Admin BusBooking', 'admin@busbooking.com', '0900000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active')
ON DUPLICATE KEY UPDATE email=email;

-- 4. Hiển thị thông tin
SELECT '✅ Auth System SQL executed successfully!' AS Status;
SELECT '' AS '';
SELECT 'Available Test Accounts:' AS Info;
SELECT 
    email AS 'Email',
    role AS 'Role',
    'password' AS 'Password'
FROM users
ORDER BY FIELD(role, 'admin', 'partner', 'user'), email;

SELECT '' AS '';
SELECT 'Login URL: http://localhost/Bus_Booking/user/auth/login.php' AS '';

