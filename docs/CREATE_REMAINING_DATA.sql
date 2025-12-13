-- ============================================
-- TẠO PHẦN CÒN LẠI: Vehicles, Drivers, Routes, Trips
-- Chạy file này sau khi đã có Partners
-- ============================================

USE bus_booking;

-- Lấy partner IDs (đã có sẵn)
SET @partner1 = (SELECT partner_id FROM partners WHERE email = 'phuongtrang@example.com' LIMIT 1);
SET @partner2 = (SELECT partner_id FROM partners WHERE email = 'mailinh@example.com' LIMIT 1);
SET @partner3 = (SELECT partner_id FROM partners WHERE email = 'hoanglong@example.com' LIMIT 1);
SET @partner4 = (SELECT partner_id FROM partners WHERE email = 'kumho@example.com' LIMIT 1);
SET @partner5 = (SELECT partner_id FROM partners WHERE email = 'thanhbuoi@example.com' LIMIT 1);

-- Nếu chưa có partner 3, 4, 5 thì tạo thêm
INSERT INTO partners (name, email, phone, password, logo_url, policy, status) VALUES
('Hoàng Long', 'hoanglong@example.com', '19001234', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'Chính sách hủy vé: Hoàn 80% nếu hủy trước 24h', 'approved'),
('Kumho Samco', 'kumho@example.com', '19005678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'Chính sách hủy vé: Hoàn 80% nếu hủy trước 24h', 'approved'),
('Thành Bưởi', 'thanhbuoi@example.com', '19009090', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'Chính sách hủy vé: Hoàn 80% nếu hủy trước 24h', 'approved')
ON DUPLICATE KEY UPDATE status='approved';

-- Lấy lại partner IDs
SET @partner1 = (SELECT partner_id FROM partners WHERE email = 'phuongtrang@example.com' LIMIT 1);
SET @partner2 = (SELECT partner_id FROM partners WHERE email = 'mailinh@example.com' LIMIT 1);
SET @partner3 = (SELECT partner_id FROM partners WHERE email = 'hoanglong@example.com' LIMIT 1);
SET @partner4 = (SELECT partner_id FROM partners WHERE email = 'kumho@example.com' LIMIT 1);
SET @partner5 = (SELECT partner_id FROM partners WHERE email = 'thanhbuoi@example.com' LIMIT 1);

-- ============================================
-- 1. TẠO VEHICLES (Xe)
-- ============================================
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
-- 2. TẠO DRIVERS (Tài xế)
-- ============================================
-- Schema: partner_id, name, phone, license_number (KHÔNG có status)
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
-- 3. TẠO ROUTES (Tuyến đường)
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
-- 4. TẠO TRIPS (Chuyến xe) - NGÀY MAI
-- ============================================

-- Lấy route IDs
SET @route_sg_dl = (SELECT route_id FROM routes WHERE origin = 'Sài Gòn' AND destination = 'Đà Lạt' LIMIT 1);
SET @route_sg_nt = (SELECT route_id FROM routes WHERE origin = 'Sài Gòn' AND destination = 'Nha Trang' LIMIT 1);
SET @route_sg_qn = (SELECT route_id FROM routes WHERE origin = 'Sài Gòn' AND destination = 'Quảng Ngãi' LIMIT 1);
SET @route_sg_vt = (SELECT route_id FROM routes WHERE origin = 'Sài Gòn' AND destination = 'Vũng Tàu' LIMIT 1);
SET @route_hn_hp = (SELECT route_id FROM routes WHERE origin = 'Hà Nội' AND destination = 'Hải Phòng' LIMIT 1);
SET @route_hn_dn = (SELECT route_id FROM routes WHERE origin = 'Hà Nội' AND destination = 'Đà Nẵng' LIMIT 1);
SET @route_hn_sp = (SELECT route_id FROM routes WHERE origin = 'Hà Nội' AND destination = 'Sapa' LIMIT 1);
SET @route_sg_ct = (SELECT route_id FROM routes WHERE origin = 'Sài Gòn' AND destination = 'Cần Thơ' LIMIT 1);

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

-- Tạo trips cho NGÀY MAI
INSERT INTO trips (route_id, partner_id, vehicle_id, driver_id, departure_time, arrival_time, price, available_seats, status) 
SELECT @route_sg_dl, @partner1, @v1, @d1, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 6 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 12 HOUR, 250000, 34, 'scheduled'
WHERE @route_sg_dl IS NOT NULL AND @partner1 IS NOT NULL AND @v1 IS NOT NULL AND @d1 IS NOT NULL
ON DUPLICATE KEY UPDATE route_id=route_id;

INSERT INTO trips (route_id, partner_id, vehicle_id, driver_id, departure_time, arrival_time, price, available_seats, status) 
SELECT @route_sg_dl, @partner2, @v3, @d3, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 8 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 14 HOUR, 280000, 45, 'scheduled'
WHERE @route_sg_dl IS NOT NULL AND @partner2 IS NOT NULL AND @v3 IS NOT NULL AND @d3 IS NOT NULL
ON DUPLICATE KEY UPDATE route_id=route_id;

INSERT INTO trips (route_id, partner_id, vehicle_id, driver_id, departure_time, arrival_time, price, available_seats, status) 
SELECT @route_sg_nt, @partner1, @v2, @d2, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 7 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 15 HOUR, 350000, 40, 'scheduled'
WHERE @route_sg_nt IS NOT NULL AND @partner1 IS NOT NULL AND @v2 IS NOT NULL AND @d2 IS NOT NULL
ON DUPLICATE KEY UPDATE route_id=route_id;

INSERT INTO trips (route_id, partner_id, vehicle_id, driver_id, departure_time, arrival_time, price, available_seats, status) 
SELECT @route_sg_vt, @partner1, @v1, @d1, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 8 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 10 HOUR, 120000, 34, 'scheduled'
WHERE @route_sg_vt IS NOT NULL AND @partner1 IS NOT NULL AND @v1 IS NOT NULL AND @d1 IS NOT NULL
ON DUPLICATE KEY UPDATE route_id=route_id;

INSERT INTO trips (route_id, partner_id, vehicle_id, driver_id, departure_time, arrival_time, price, available_seats, status) 
SELECT @route_hn_hp, @partner2, @v3, @d3, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 6 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 8 HOUR + INTERVAL 30 MINUTE, 150000, 45, 'scheduled'
WHERE @route_hn_hp IS NOT NULL AND @partner2 IS NOT NULL AND @v3 IS NOT NULL AND @d3 IS NOT NULL
ON DUPLICATE KEY UPDATE route_id=route_id;

-- ============================================
-- 5. THỐNG KÊ
-- ============================================
SELECT '✅ Hoàn thành!' AS Status;
SELECT CONCAT('📊 Vehicles: ', COUNT(*)) AS Info FROM vehicles
UNION ALL
SELECT CONCAT('👨‍✈️ Drivers: ', COUNT(*)) FROM drivers
UNION ALL
SELECT CONCAT('🛣️ Routes: ', COUNT(*)) FROM routes
UNION ALL
SELECT CONCAT('🚌 Trips ngày mai: ', COUNT(*)) FROM trips WHERE DATE(departure_time) = DATE_ADD(CURDATE(), INTERVAL 1 DAY);

