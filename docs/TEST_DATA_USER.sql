-- ============================================
-- DỮ LIỆU ẢO ĐỂ TEST USER FLOW
-- Chạy file này để có đủ data test phần user
-- ============================================

USE bus_booking;

-- ============================================
-- 1. TẠO USERS (Nếu chưa có)
-- ============================================
INSERT INTO users (fullname, email, phone, password, role, status) VALUES
('Nguyễn Văn Test', 'test@gmail.com', '0123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active'),
('Trần Thị User', 'user@gmail.com', '0987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active')
ON DUPLICATE KEY UPDATE fullname=fullname;

-- Password cho tất cả: "password"

-- ============================================
-- 2. TẠO PARTNERS (Nhà xe)
-- ============================================
-- Schema: name, email, phone, password, logo_url, policy, status
-- Xóa partners cũ nếu muốn tạo lại (tùy chọn)
-- DELETE FROM partners WHERE email IN ('phuongtrang@example.com', 'mailinh@example.com', 'hoanglong@example.com', 'kumho@example.com', 'thanhbuoi@example.com');

INSERT INTO partners (name, email, phone, password, logo_url, policy, status) VALUES
('Phương Trang', 'phuongtrang@example.com', '19006067', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'Chính sách hủy vé: Hoàn 80% nếu hủy trước 24h', 'approved'),
('Mai Linh', 'mailinh@example.com', '19005454', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'Chính sách hủy vé: Hoàn 80% nếu hủy trước 24h', 'approved'),
('Hoàng Long', 'hoanglong@example.com', '19001234', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'Chính sách hủy vé: Hoàn 80% nếu hủy trước 24h', 'approved'),
('Kumho Samco', 'kumho@example.com', '19005678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'Chính sách hủy vé: Hoàn 80% nếu hủy trước 24h', 'approved'),
('Thành Bưởi', 'thanhbuoi@example.com', '19009090', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'Chính sách hủy vé: Hoàn 80% nếu hủy trước 24h', 'approved')
ON DUPLICATE KEY UPDATE 
    name=VALUES(name),
    status='approved';

-- Get partner IDs - Đảm bảo tìm được bằng email (unique)
SET @partner1 = (SELECT partner_id FROM partners WHERE email = 'phuongtrang@example.com' LIMIT 1);
SET @partner2 = (SELECT partner_id FROM partners WHERE email = 'mailinh@example.com' LIMIT 1);
SET @partner3 = (SELECT partner_id FROM partners WHERE email = 'hoanglong@example.com' LIMIT 1);
SET @partner4 = (SELECT partner_id FROM partners WHERE email = 'kumho@example.com' LIMIT 1);
SET @partner5 = (SELECT partner_id FROM partners WHERE email = 'thanhbuoi@example.com' LIMIT 1);

-- Kiểm tra partners đã được tạo chưa
SELECT 
    CASE 
        WHEN @partner1 IS NULL THEN '❌ Lỗi: Không tìm thấy Phương Trang'
        WHEN @partner2 IS NULL THEN '❌ Lỗi: Không tìm thấy Mai Linh'
        WHEN @partner3 IS NULL THEN '❌ Lỗi: Không tìm thấy Hoàng Long'
        WHEN @partner4 IS NULL THEN '❌ Lỗi: Không tìm thấy Kumho Samco'
        WHEN @partner5 IS NULL THEN '❌ Lỗi: Không tìm thấy Thành Bưởi'
        ELSE '✅ Tất cả partners đã được tạo thành công!'
    END AS 'Kiểm tra Partners';

-- ============================================
-- 3. TẠO VEHICLES (Xe)
-- ============================================
-- Chỉ tạo nếu partners đã tồn tại
INSERT INTO vehicles (partner_id, license_plate, vehicle_type, total_seats, seat_layout, status) 
SELECT @partner1, '51B-12345', 'limousine', 34, '2-1', 'active'
WHERE @partner1 IS NOT NULL
ON DUPLICATE KEY UPDATE license_plate=license_plate;

INSERT INTO vehicles (partner_id, license_plate, vehicle_type, total_seats, seat_layout, status) 
SELECT @partner1, '51B-12346', 'giường nằm', 40, '2-2', 'active'
WHERE @partner1 IS NOT NULL
ON DUPLICATE KEY UPDATE license_plate=license_plate;

INSERT INTO vehicles (partner_id, license_plate, vehicle_type, total_seats, seat_layout, status) 
SELECT @partner2, '29B-23456', 'ghế ngồi', 45, '2-2', 'active'
WHERE @partner2 IS NOT NULL
ON DUPLICATE KEY UPDATE license_plate=license_plate;

INSERT INTO vehicles (partner_id, license_plate, vehicle_type, total_seats, seat_layout, status) 
SELECT @partner2, '29B-23457', 'limousine', 34, '2-1', 'active'
WHERE @partner2 IS NOT NULL
ON DUPLICATE KEY UPDATE license_plate=license_plate;

INSERT INTO vehicles (partner_id, license_plate, vehicle_type, total_seats, seat_layout, status) 
SELECT @partner3, '43B-34567', 'giường nằm', 40, '2-2', 'active'
WHERE @partner3 IS NOT NULL
ON DUPLICATE KEY UPDATE license_plate=license_plate;

INSERT INTO vehicles (partner_id, license_plate, vehicle_type, total_seats, seat_layout, status) 
SELECT @partner3, '43B-34568', 'ghế ngồi', 45, '2-2', 'active'
WHERE @partner3 IS NOT NULL
ON DUPLICATE KEY UPDATE license_plate=license_plate;

INSERT INTO vehicles (partner_id, license_plate, vehicle_type, total_seats, seat_layout, status) 
SELECT @partner4, '92A-45678', 'limousine', 34, '2-1', 'active'
WHERE @partner4 IS NOT NULL
ON DUPLICATE KEY UPDATE license_plate=license_plate;

INSERT INTO vehicles (partner_id, license_plate, vehicle_type, total_seats, seat_layout, status) 
SELECT @partner5, '30B-56789', 'giường nằm', 40, '2-2', 'active'
WHERE @partner5 IS NOT NULL
ON DUPLICATE KEY UPDATE license_plate=license_plate;

-- ============================================
-- 4. TẠO DRIVERS (Tài xế)
-- ============================================
-- Schema: partner_id, name, phone, license_number (KHÔNG có status)
-- Chỉ tạo nếu partners đã tồn tại
INSERT INTO drivers (partner_id, name, phone, license_number) 
SELECT @partner1, 'Nguyễn Văn Lái', '0901234567', 'DL123456'
WHERE @partner1 IS NOT NULL
ON DUPLICATE KEY UPDATE name=name;

INSERT INTO drivers (partner_id, name, phone, license_number) 
SELECT @partner1, 'Trần Văn Tài', '0901234568', 'DL123457'
WHERE @partner1 IS NOT NULL
ON DUPLICATE KEY UPDATE name=name;

INSERT INTO drivers (partner_id, name, phone, license_number) 
SELECT @partner2, 'Lê Văn Xe', '0902345678', 'DL234567'
WHERE @partner2 IS NOT NULL
ON DUPLICATE KEY UPDATE name=name;

INSERT INTO drivers (partner_id, name, phone, license_number) 
SELECT @partner3, 'Phạm Văn Lái', '0903456789', 'DL345678'
WHERE @partner3 IS NOT NULL
ON DUPLICATE KEY UPDATE name=name;

INSERT INTO drivers (partner_id, name, phone, license_number) 
SELECT @partner4, 'Hoàng Văn Tài', '0904567890', 'DL456789'
WHERE @partner4 IS NOT NULL
ON DUPLICATE KEY UPDATE name=name;

INSERT INTO drivers (partner_id, name, phone, license_number) 
SELECT @partner5, 'Vũ Văn Xe', '0905678901', 'DL567890'
WHERE @partner5 IS NOT NULL
ON DUPLICATE KEY UPDATE name=name;

-- ============================================
-- 5. TẠO ROUTES (Tuyến đường) - Nếu chưa có
-- ============================================
INSERT INTO routes (route_name, origin, destination, distance_km, duration_hours, base_price, status) VALUES
('Sài Gòn - Đà Lạt', 'Sài Gòn', 'Đà Lạt', 300, 6, 250000, 'active'),
('Sài Gòn - Nha Trang', 'Sài Gòn', 'Nha Trang', 450, 8, 350000, 'active'),
('Sài Gòn - Quảng Ngãi', 'Sài Gòn', 'Quảng Ngãi', 900, 14, 400000, 'active'),
('Sài Gòn - Vũng Tàu', 'Sài Gòn', 'Vũng Tàu', 100, 2, 120000, 'active'),
('Hà Nội - Hải Phòng', 'Hà Nội', 'Hải Phòng', 120, 2.5, 150000, 'active'),
('Hà Nội - Đà Nẵng', 'Hà Nội', 'Đà Nẵng', 800, 14, 450000, 'active'),
('Hà Nội - Sapa', 'Hà Nội', 'Sapa', 350, 8, 300000, 'active'),
('Đà Nẵng - Huế', 'Đà Nẵng', 'Huế', 100, 2, 100000, 'active'),
('Sài Gòn - Cần Thơ', 'Sài Gòn', 'Cần Thơ', 170, 3.5, 180000, 'active'),
('Hà Nội - Quảng Ninh', 'Hà Nội', 'Quảng Ninh', 150, 3, 160000, 'active')
ON DUPLICATE KEY UPDATE route_name=route_name;

-- ============================================
-- 6. TẠO TRIPS (Chuyến xe) - Ngày mai và các ngày sau
-- ============================================

-- Lấy route IDs
SET @route_sg_dl = (SELECT route_id FROM routes WHERE origin = 'Sài Gòn' AND destination = 'Đà Lạt' LIMIT 1);
SET @route_sg_nt = (SELECT route_id FROM routes WHERE origin = 'Sài Gòn' AND destination = 'Nha Trang' LIMIT 1);
SET @route_sg_qn = (SELECT route_id FROM routes WHERE origin = 'Sài Gòn' AND destination = 'Quảng Ngãi' LIMIT 1);
SET @route_sg_vt = (SELECT route_id FROM routes WHERE origin = 'Sài Gòn' AND destination = 'Vũng Tàu' LIMIT 1);
SET @route_hn_hp = (SELECT route_id FROM routes WHERE origin = 'Hà Nội' AND destination = 'Hải Phòng' LIMIT 1);
SET @route_hn_dn = (SELECT route_id FROM routes WHERE origin = 'Hà Nội' AND destination = 'Đà Nẵng' LIMIT 1);
SET @route_hn_sp = (SELECT route_id FROM routes WHERE origin = 'Hà Nội' AND destination = 'Sapa' LIMIT 1);
SET @route_dn_hue = (SELECT route_id FROM routes WHERE origin = 'Đà Nẵng' AND destination = 'Huế' LIMIT 1);
SET @route_sg_ct = (SELECT route_id FROM routes WHERE origin = 'Sài Gòn' AND destination = 'Cần Thơ' LIMIT 1);
SET @route_hn_qn = (SELECT route_id FROM routes WHERE origin = 'Hà Nội' AND destination = 'Quảng Ninh' LIMIT 1);

-- Lấy vehicle IDs
SET @v1 = (SELECT vehicle_id FROM vehicles WHERE license_plate = '51B-12345' LIMIT 1);
SET @v2 = (SELECT vehicle_id FROM vehicles WHERE license_plate = '51B-12346' LIMIT 1);
SET @v3 = (SELECT vehicle_id FROM vehicles WHERE license_plate = '29B-23456' LIMIT 1);
SET @v4 = (SELECT vehicle_id FROM vehicles WHERE license_plate = '29B-23457' LIMIT 1);
SET @v5 = (SELECT vehicle_id FROM vehicles WHERE license_plate = '43B-34567' LIMIT 1);
SET @v6 = (SELECT vehicle_id FROM vehicles WHERE license_plate = '43B-34568' LIMIT 1);
SET @v7 = (SELECT vehicle_id FROM vehicles WHERE license_plate = '92A-45678' LIMIT 1);
SET @v8 = (SELECT vehicle_id FROM vehicles WHERE license_plate = '30B-56789' LIMIT 1);

-- Lấy driver IDs (dùng name, không phải fullname)
SET @d1 = (SELECT driver_id FROM drivers WHERE name = 'Nguyễn Văn Lái' LIMIT 1);
SET @d2 = (SELECT driver_id FROM drivers WHERE name = 'Trần Văn Tài' LIMIT 1);
SET @d3 = (SELECT driver_id FROM drivers WHERE name = 'Lê Văn Xe' LIMIT 1);
SET @d4 = (SELECT driver_id FROM drivers WHERE name = 'Phạm Văn Lái' LIMIT 1);
SET @d5 = (SELECT driver_id FROM drivers WHERE name = 'Hoàng Văn Tài' LIMIT 1);
SET @d6 = (SELECT driver_id FROM drivers WHERE name = 'Vũ Văn Xe' LIMIT 1);

-- Tạo trips cho NGÀY MAI (nhiều giờ khác nhau)
INSERT INTO trips (route_id, partner_id, vehicle_id, driver_id, departure_time, arrival_time, price, available_seats, status) VALUES
-- Sài Gòn - Đà Lạt (ngày mai)
(@route_sg_dl, @partner1, @v1, @d1, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 6 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 12 HOUR, 250000, 34, 'scheduled'),
(@route_sg_dl, @partner2, @v3, @d3, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 8 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 14 HOUR, 280000, 45, 'scheduled'),
(@route_sg_dl, @partner3, @v5, @d4, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 20 HOUR, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 2 HOUR, 240000, 40, 'scheduled'),

-- Sài Gòn - Nha Trang (ngày mai)
(@route_sg_nt, @partner1, @v2, @d2, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 7 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 15 HOUR, 350000, 40, 'scheduled'),
(@route_sg_nt, @partner4, @v7, @d5, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 22 HOUR, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 6 HOUR, 320000, 34, 'scheduled'),

-- Sài Gòn - Quảng Ngãi (ngày mai)
(@route_sg_qn, @partner2, @v4, @d3, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 19 HOUR, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 9 HOUR, 400000, 34, 'scheduled'),
(@route_sg_qn, @partner5, @v8, @d6, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 21 HOUR, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 11 HOUR, 380000, 40, 'scheduled'),

-- Sài Gòn - Vũng Tàu (ngày mai)
(@route_sg_vt, @partner1, @v1, @d1, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 8 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 10 HOUR, 120000, 34, 'scheduled'),
(@route_sg_vt, @partner3, @v6, @d4, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 14 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 16 HOUR, 110000, 45, 'scheduled'),

-- Hà Nội - Hải Phòng (ngày mai)
(@route_hn_hp, @partner2, @v3, @d3, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 6 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 8 HOUR + INTERVAL 30 MINUTE, 150000, 45, 'scheduled'),
(@route_hn_hp, @partner5, @v8, @d6, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 18 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 20 HOUR + INTERVAL 30 MINUTE, 140000, 40, 'scheduled'),

-- Hà Nội - Đà Nẵng (ngày mai)
(@route_hn_dn, @partner2, @v4, @d3, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 20 HOUR, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 10 HOUR, 450000, 34, 'scheduled'),
(@route_hn_dn, @partner3, @v5, @d4, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 22 HOUR, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 12 HOUR, 430000, 40, 'scheduled'),

-- Hà Nội - Sapa (ngày mai)
(@route_hn_sp, @partner3, @v6, @d4, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 7 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 15 HOUR, 300000, 45, 'scheduled'),
(@route_hn_sp, @partner5, @v8, @d6, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 21 HOUR, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 5 HOUR, 280000, 40, 'scheduled'),

-- Sài Gòn - Cần Thơ (ngày mai)
(@route_sg_ct, @partner1, @v2, @d2, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 9 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 12 HOUR + INTERVAL 30 MINUTE, 180000, 40, 'scheduled'),
(@route_sg_ct, @partner4, @v7, @d5, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 15 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 18 HOUR + INTERVAL 30 MINUTE, 170000, 34, 'scheduled')

ON DUPLICATE KEY UPDATE route_id=route_id;

-- ============================================
-- 7. THỐNG KÊ
-- ============================================
SELECT '✅ Dữ liệu test đã được tạo!' AS Status;
SELECT CONCAT('📊 Tổng số trips ngày mai: ', COUNT(*)) AS Info
FROM trips 
WHERE DATE(departure_time) = DATE_ADD(CURDATE(), INTERVAL 1 DAY);

SELECT 
    r.origin AS 'Điểm đi',
    r.destination AS 'Điểm đến',
    COUNT(t.trip_id) AS 'Số chuyến',
    MIN(t.price) AS 'Giá thấp nhất',
    MAX(t.price) AS 'Giá cao nhất'
FROM trips t
JOIN routes r ON t.route_id = r.route_id
WHERE DATE(t.departure_time) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
GROUP BY r.origin, r.destination
ORDER BY COUNT(t.trip_id) DESC;

