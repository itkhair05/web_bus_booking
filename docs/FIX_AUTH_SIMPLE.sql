-- ============================================
-- FIX AUTH SYSTEM - SIMPLE VERSION
-- Chạy file này để fix lỗi fullname
-- ============================================

USE bus_booking;

-- 1. Thêm column fullname (nếu chưa có sẽ báo lỗi, ignore nó)
ALTER TABLE users ADD COLUMN fullname VARCHAR(100) NOT NULL DEFAULT '';

-- 2. Copy data từ name sang fullname (nếu có)
UPDATE users SET fullname = name WHERE (fullname IS NULL OR fullname = '') AND name IS NOT NULL;

-- 3. Tạo users mẫu (password: "password")
INSERT INTO users (fullname, email, phone, password, role, status) VALUES
('Nguyễn Văn An', 'user1@gmail.com', '0901234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active'),
('Trần Thị Bình', 'user2@gmail.com', '0902345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active'),
('Test User', 'test@gmail.com', '0123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active'),
('Rang Đông Buslines', 'partner1@gmail.com', '0987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'partner', 'active'),
('Phương Trang Company', 'partner2@gmail.com', '0986543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'partner', 'active'),
('Admin BusBooking', 'admin@busbooking.com', '0900000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active')
ON DUPLICATE KEY UPDATE email=email;

SELECT '✅ Auth users created successfully!' AS Status;
SELECT 'Login: test@gmail.com / password' AS Info;

