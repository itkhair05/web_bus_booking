-- ============================================
-- SAMPLE DATA FOR TESTING BOOKING FLOW
-- Chạy file này để có data test
-- ============================================

USE bus_booking;

-- 1. Tạo user test
INSERT INTO users (fullname, email, phone, password, role, status) VALUES
('Nguyễn Văn Test', 'test@gmail.com', '0123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active'),
('Trần Thị Partner', 'partner@gmail.com', '0987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'partner', 'active')
ON DUPLICATE KEY UPDATE fullname=fullname;

-- Get user IDs
SET @user_id = (SELECT user_id FROM users WHERE email = 'test@gmail.com' LIMIT 1);
SET @partner_user_id = (SELECT user_id FROM users WHERE email = 'partner@gmail.com' LIMIT 1);

-- 2. Tạo partner (nhà xe)
INSERT INTO partners (user_id, company_name, business_license, status) VALUES
(@partner_user_id, 'Rang Đông Buslines', 'BL123456', 'approved'),
(@partner_user_id, 'Phương Trang', 'BL789012', 'approved'),
(@partner_user_id, 'Kumho Samco', 'BL345678', 'approved')
ON DUPLICATE KEY UPDATE company_name=company_name;

-- Get partner IDs
SET @partner1 = (SELECT partner_id FROM partners WHERE company_name = 'Rang Đông Buslines' LIMIT 1);
SET @partner2 = (SELECT partner_id FROM partners WHERE company_name = 'Phương Trang' LIMIT 1);
SET @partner3 = (SELECT partner_id FROM partners WHERE company_name = 'Kumho Samco' LIMIT 1);

-- 3. Tạo vehicles (xe)
INSERT INTO vehicles (partner_id, license_plate, vehicle_type, total_seats, seat_layout, status) VALUES
(@partner1, '51B-12345', 'limousine', 34, '2-1', 'active'),
(@partner2, '92A-67890', 'giường nằm', 40, '2-2', 'active'),
(@partner3, '79B-11111', 'ghế ngồi', 45, '2-2', 'active')
ON DUPLICATE KEY UPDATE license_plate=license_plate;

-- Get vehicle IDs
SET @vehicle1 = (SELECT vehicle_id FROM vehicles WHERE license_plate = '51B-12345' LIMIT 1);
SET @vehicle2 = (SELECT vehicle_id FROM vehicles WHERE license_plate = '92A-67890' LIMIT 1);
SET @vehicle3 = (SELECT vehicle_id FROM vehicles WHERE license_plate = '79B-11111' LIMIT 1);

-- 4. Tạo drivers (tài xế)
INSERT INTO drivers (partner_id, fullname, phone, license_number, status) VALUES
(@partner1, 'Nguyễn Văn Lái', '0901234567', 'DL123456', 'active'),
(@partner2, 'Trần Văn Tài', '0907654321', 'DL789012', 'active')
ON DUPLICATE KEY UPDATE fullname=fullname;

-- Get driver IDs
SET @driver1 = (SELECT driver_id FROM drivers WHERE fullname = 'Nguyễn Văn Lái' LIMIT 1);
SET @driver2 = (SELECT driver_id FROM drivers WHERE fullname = 'Trần Văn Tài' LIMIT 1);

-- 5. Routes đã có từ FIX_SCHEMA.sql, lấy ID
SET @route1 = (SELECT route_id FROM routes WHERE origin = 'Sài Gòn' AND destination = 'Quảng Ngãi' LIMIT 1);
SET @route2 = (SELECT route_id FROM routes WHERE origin = 'Sài Gòn' AND destination = 'Đà Lạt' LIMIT 1);
SET @route3 = (SELECT route_id FROM routes WHERE origin = 'Hà Nội' AND destination = 'Sapa' LIMIT 1);

-- 6. Tạo trips (chuyến xe) - Ngày mai
INSERT INTO trips (route_id, partner_id, vehicle_id, driver_id, departure_time, arrival_time, price, available_seats, status) VALUES
-- Sài Gòn - Quảng Ngãi (ngày mai 15:01)
(@route1, @partner1, @vehicle1, @driver1, 
 DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 15 HOUR + INTERVAL 1 MINUTE, 
 DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 6 HOUR + INTERVAL 21 MINUTE,
 400000, 34, 'scheduled'),

-- Sài Gòn - Quảng Ngãi (ngày mai 19:45) 
(@route1, @partner2, @vehicle2, @driver2,
 DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 19 HOUR + INTERVAL 45 MINUTE,
 DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 9 HOUR + INTERVAL 35 MINUTE,
 400000, 40, 'scheduled'),

-- Sài Gòn - Đà Lạt
(@route2, @partner1, @vehicle1, @driver1,
 DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 8 HOUR,
 DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 14 HOUR + INTERVAL 30 MINUTE,
 250000, 34, 'scheduled'),

-- Hà Nội - Sapa
(@route3, @partner3, @vehicle3, NULL,
 DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 20 HOUR,
 DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 3 HOUR,
 300000, 45, 'scheduled')
ON DUPLICATE KEY UPDATE route_id=route_id;

-- Get trip IDs
SET @trip1 = (SELECT trip_id FROM trips WHERE route_id = @route1 AND departure_time LIKE CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 15:01:%') LIMIT 1);
SET @trip2 = (SELECT trip_id FROM trips WHERE route_id = @route1 AND departure_time LIKE CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 19:45:%') LIMIT 1);

-- 7. Tạo trip schedules (điểm đón/trả)
INSERT INTO trip_schedules (trip_id, stop_order, departure_station, arrival_station, departure_time, arrival_time, is_pickup, is_dropoff) VALUES
-- Trip 1: Sài Gòn - Quảng Ngãi
(@trip1, 1, 'Bến xe An Sương, Quận 12, Hồ Chí Minh', NULL, 
 DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 15 HOUR + INTERVAL 1 MINUTE, NULL, 1, 0),
(@trip1, 2, '163B Lê Văn Sỹ, Quận 3, Hồ Chí Minh', NULL,
 DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 15 HOUR + INTERVAL 30 MINUTE, NULL, 1, 0),
(@trip1, 3, NULL, 'Bến xe Quảng Ngãi, 27/10',
 NULL, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 6 HOUR, 0, 1),
(@trip1, 4, NULL, 'Đức Phổ, Quảng Ngãi',
 NULL, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 6 HOUR + INTERVAL 21 MINUTE, 0, 1),

-- Trip 2: Sài Gòn - Quảng Ngãi (chuyến 2)
(@trip2, 1, 'Bến xe Miền Đông, TP.HCM', NULL,
 DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 19 HOUR + INTERVAL 45 MINUTE, NULL, 1, 0),
(@trip2, 2, NULL, 'Bến xe Quảng Ngãi',
 NULL, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 9 HOUR + INTERVAL 35 MINUTE, 0, 1)
ON DUPLICATE KEY UPDATE trip_id=trip_id;

-- 8. Tạo seats cho mỗi trip
-- Trip 1: 34 ghế
INSERT INTO seats (trip_id, seat_number, status)
SELECT @trip1, CONCAT(CHAR(64 + CEILING(n/2)), IF(n%2=1, '1', '2')), 'available'
FROM (
    SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION 
    SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION
    SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION
    SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION
    SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION
    SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30 UNION
    SELECT 31 UNION SELECT 32 UNION SELECT 33 UNION SELECT 34
) numbers
ON DUPLICATE KEY UPDATE seat_number=seat_number;

SELECT '✅ Sample data created successfully!' AS Status;
SELECT 'You can now test the booking flow!' AS Message;
SELECT CONCAT('Test with trip_id: ', @trip1) AS Test_URL;

