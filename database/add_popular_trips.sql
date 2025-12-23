-- ============================================
-- Script thêm chuyến xe cho các tuyến phổ biến
-- Mỗi nhà xe sẽ có 3 chuyến xe với thời gian rải đều từ 12:00 đến 20:00
-- Từ ngày 22/12/2025 đến 31/12/2025 (10 ngày)
-- ============================================

-- Lưu ý: 
-- 1. File này giả định các routes đã tồn tại trong database
-- 2. Nếu routes chưa có, vui lòng tạo routes trước khi chạy script này
-- 3. Script sẽ tự động lấy vehicle_id đầu tiên của mỗi nhà xe
-- 4. Nếu nhà xe chưa có xe, vui lòng thêm xe trước

-- ============================================
-- CÁC TUYẾN PHỔ BIẾN (từ index.php)
-- ============================================
-- 1. Sài Gòn - Đà Lạt
-- 2. Quảng Ngãi - Đà Nẵng  
-- 3. Quảng Ngãi - Sài Gòn
-- 4. Sài Gòn - Vũng Tàu
-- 5. Hà Nội - Sapa
-- 6. Hà Nội - Quảng Ninh

-- ============================================
-- TẠO ROUTES NẾU CHƯA CÓ
-- ============================================
INSERT INTO `routes` (`route_name`, `origin`, `destination`, `start_point`, `end_point`, `distance_km`, `duration_hours`, `base_price`, `status`) 
SELECT 'Sài Gòn - Đà Lạt', 'Sài Gòn', 'Đà Lạt', 'TP. Hồ Chí Minh', 'Đà Lạt', 300.00, 6.50, 200000.00, 'active'
WHERE NOT EXISTS (SELECT 1 FROM routes WHERE start_point = 'TP. Hồ Chí Minh' AND end_point = 'Đà Lạt');

INSERT INTO `routes` (`route_name`, `origin`, `destination`, `start_point`, `end_point`, `distance_km`, `duration_hours`, `base_price`, `status`) 
SELECT 'Quảng Ngãi - Đà Nẵng', 'Quảng Ngãi', 'Đà Nẵng', 'Quảng Ngãi', 'Đà Nẵng', 130.00, 2.50, 90000.00, 'active'
WHERE NOT EXISTS (SELECT 1 FROM routes WHERE start_point = 'Quảng Ngãi' AND end_point = 'Đà Nẵng');

INSERT INTO `routes` (`route_name`, `origin`, `destination`, `start_point`, `end_point`, `distance_km`, `duration_hours`, `base_price`, `status`) 
SELECT 'Quảng Ngãi - Sài Gòn', 'Quảng Ngãi', 'Sài Gòn', 'Quảng Ngãi', 'TP. Hồ Chí Minh', 850.00, 14.00, 160000.00, 'active'
WHERE NOT EXISTS (SELECT 1 FROM routes WHERE start_point = 'Quảng Ngãi' AND end_point = 'TP. Hồ Chí Minh');

INSERT INTO `routes` (`route_name`, `origin`, `destination`, `start_point`, `end_point`, `distance_km`, `duration_hours`, `base_price`, `status`) 
SELECT 'Sài Gòn - Vũng Tàu', 'Sài Gòn', 'Vũng Tàu', 'TP. Hồ Chí Minh', 'Vũng Tàu', 125.00, 2.00, 180000.00, 'active'
WHERE NOT EXISTS (SELECT 1 FROM routes WHERE start_point = 'TP. Hồ Chí Minh' AND end_point = 'Vũng Tàu');

INSERT INTO `routes` (`route_name`, `origin`, `destination`, `start_point`, `end_point`, `distance_km`, `duration_hours`, `base_price`, `status`) 
SELECT 'Hà Nội - Sapa', 'Hà Nội', 'Sapa', 'Hà Nội', 'Sapa', 350.00, 7.00, 300000.00, 'active'
WHERE NOT EXISTS (SELECT 1 FROM routes WHERE start_point = 'Hà Nội' AND end_point = 'Sapa');

INSERT INTO `routes` (`route_name`, `origin`, `destination`, `start_point`, `end_point`, `distance_km`, `duration_hours`, `base_price`, `status`) 
SELECT 'Hà Nội - Quảng Ninh', 'Hà Nội', 'Quảng Ninh', 'Hà Nội', 'Quảng Ninh', 170.00, 3.00, 250000.00, 'active'
WHERE NOT EXISTS (SELECT 1 FROM routes WHERE start_point = 'Hà Nội' AND end_point = 'Quảng Ninh');

-- ============================================
-- TẠO VEHICLES NẾU CHƯA CÓ (cho các nhà xe chưa có xe)
-- ============================================
INSERT INTO `vehicles` (`partner_id`, `license_plate`, `vehicle_type`, `type`, `total_seats`, `seat_layout`, `status`)
SELECT 1, '29A-12347', 'ghế ngồi', 'Giường nằm', 40, '2-1', 'active'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE partner_id = 1 LIMIT 1);

INSERT INTO `vehicles` (`partner_id`, `license_plate`, `vehicle_type`, `type`, `total_seats`, `seat_layout`, `status`)
SELECT 2, '30B-54323', 'ghế ngồi', 'Giường nằm', 36, '2-1', 'active'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE partner_id = 2 LIMIT 1);

INSERT INTO `vehicles` (`partner_id`, `license_plate`, `vehicle_type`, `type`, `total_seats`, `seat_layout`, `status`)
SELECT 3, '29B-11111', 'ghế ngồi', 'Giường nằm', 40, '2-1', 'active'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE partner_id = 3 LIMIT 1);

INSERT INTO `vehicles` (`partner_id`, `license_plate`, `vehicle_type`, `type`, `total_seats`, `seat_layout`, `status`)
SELECT 4, '29B-22222', 'ghế ngồi', 'Giường nằm', 40, '2-1', 'active'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE partner_id = 4 LIMIT 1);

INSERT INTO `vehicles` (`partner_id`, `license_plate`, `vehicle_type`, `type`, `total_seats`, `seat_layout`, `status`)
SELECT 5, '29B-33333', 'ghế ngồi', 'Giường nằm', 40, '2-1', 'active'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE partner_id = 5 LIMIT 1);

INSERT INTO `vehicles` (`partner_id`, `license_plate`, `vehicle_type`, `type`, `total_seats`, `seat_layout`, `status`)
SELECT 44, '29B-44444', 'ghế ngồi', 'Giường nằm', 40, '2-1', 'active'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE partner_id = 44 LIMIT 1);

-- ============================================
-- THÊM CHUYẾN XE CHO 10 NGÀY (22/12/2025 - 31/12/2025)
-- Mỗi nhà xe có 3 chuyến mỗi ngày: 12:00, 16:00, 20:00
-- ============================================

-- Helper function để thêm chuyến cho một ngày
-- Sử dụng stored procedure để lặp qua các ngày

DELIMITER $$

DROP PROCEDURE IF EXISTS AddTripsForDate$$
CREATE PROCEDURE AddTripsForDate(IN trip_date DATE)
BEGIN
    DECLARE v_partner_id INT;
    DECLARE v_vehicle_id INT;
    DECLARE v_route_id INT;
    DECLARE v_duration_hours DECIMAL(5,2);
    DECLARE v_base_price DECIMAL(10,2);
    DECLARE v_final_price DECIMAL(10,2);
    DECLARE done INT DEFAULT FALSE;
    
    -- Cursor để lặp qua các nhà xe đã approved
    DECLARE partner_cursor CURSOR FOR 
        SELECT partner_id FROM partners WHERE status = 'approved';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Tuyến 1: Sài Gòn - Đà Lạt
    SET v_route_id = (SELECT route_id FROM routes WHERE start_point = 'TP. Hồ Chí Minh' AND end_point = 'Đà Lạt' LIMIT 1);
    SET v_duration_hours = 6.5;
    SET v_base_price = 200000.00;
    
    OPEN partner_cursor;
    partner_loop: LOOP
        FETCH partner_cursor INTO v_partner_id;
        IF done THEN
            LEAVE partner_loop;
        END IF;
        
        SET v_vehicle_id = (SELECT vehicle_id FROM vehicles WHERE partner_id = v_partner_id LIMIT 1);
        
        -- Tính giá khác nhau cho từng nhà xe (dựa trên partner_id)
        SET v_final_price = CASE v_partner_id
            WHEN 1 THEN v_base_price * 1.0      -- Phương Trang: giá gốc
            WHEN 2 THEN v_base_price * 0.95     -- Mai Linh: giảm 5%
            WHEN 3 THEN v_base_price * 1.05    -- Hoàng Long: tăng 5%
            WHEN 4 THEN v_base_price * 0.98    -- Kumho Samco: giảm 2%
            WHEN 5 THEN v_base_price * 1.02    -- Thành Bưởi: tăng 2%
            WHEN 44 THEN v_base_price * 0.97   -- Chín Nghĩa: giảm 3%
            ELSE v_base_price                   -- Nhà xe khác: giá gốc
        END;
        
        IF v_vehicle_id IS NOT NULL AND v_route_id IS NOT NULL THEN
            INSERT INTO `trips` (`partner_id`, `route_id`, `vehicle_id`, `departure_time`, `arrival_time`, `price`, `available_seats`, `status`)
            VALUES 
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 12:00:00'), DATE_ADD(CONCAT(trip_date, ' 12:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open'),
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 16:00:00'), DATE_ADD(CONCAT(trip_date, ' 16:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open'),
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 20:00:00'), DATE_ADD(CONCAT(trip_date, ' 20:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open');
        END IF;
    END LOOP;
    CLOSE partner_cursor;
    SET done = FALSE;
    
    -- Tuyến 2: Quảng Ngãi - Đà Nẵng
    SET v_route_id = (SELECT route_id FROM routes WHERE start_point = 'Quảng Ngãi' AND end_point = 'Đà Nẵng' LIMIT 1);
    SET v_duration_hours = 2.5;
    SET v_base_price = 90000.00;
    
    OPEN partner_cursor;
    partner_loop2: LOOP
        FETCH partner_cursor INTO v_partner_id;
        IF done THEN
            LEAVE partner_loop2;
        END IF;
        
        SET v_vehicle_id = (SELECT vehicle_id FROM vehicles WHERE partner_id = v_partner_id LIMIT 1);
        
        -- Tính giá khác nhau cho từng nhà xe
        SET v_final_price = CASE v_partner_id
            WHEN 1 THEN v_base_price * 1.0      -- Phương Trang: giá gốc
            WHEN 2 THEN v_base_price * 0.95     -- Mai Linh: giảm 5%
            WHEN 3 THEN v_base_price * 1.05     -- Hoàng Long: tăng 5%
            WHEN 4 THEN v_base_price * 0.98     -- Kumho Samco: giảm 2%
            WHEN 5 THEN v_base_price * 1.02     -- Thành Bưởi: tăng 2%
            WHEN 44 THEN v_base_price * 0.97    -- Chín Nghĩa: giảm 3%
            ELSE v_base_price
        END;
        
        IF v_vehicle_id IS NOT NULL AND v_route_id IS NOT NULL THEN
            INSERT INTO `trips` (`partner_id`, `route_id`, `vehicle_id`, `departure_time`, `arrival_time`, `price`, `available_seats`, `status`)
            VALUES 
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 12:00:00'), DATE_ADD(CONCAT(trip_date, ' 12:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open'),
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 16:00:00'), DATE_ADD(CONCAT(trip_date, ' 16:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open'),
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 20:00:00'), DATE_ADD(CONCAT(trip_date, ' 20:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open');
        END IF;
    END LOOP;
    CLOSE partner_cursor;
    SET done = FALSE;
    
    -- Tuyến 3: Quảng Ngãi - Sài Gòn
    SET v_route_id = (SELECT route_id FROM routes WHERE start_point = 'Quảng Ngãi' AND end_point = 'TP. Hồ Chí Minh' LIMIT 1);
    SET v_duration_hours = 14.0;
    SET v_base_price = 160000.00;
    
    OPEN partner_cursor;
    partner_loop3: LOOP
        FETCH partner_cursor INTO v_partner_id;
        IF done THEN
            LEAVE partner_loop3;
        END IF;
        
        SET v_vehicle_id = (SELECT vehicle_id FROM vehicles WHERE partner_id = v_partner_id LIMIT 1);
        
        -- Tính giá khác nhau cho từng nhà xe
        SET v_final_price = CASE v_partner_id
            WHEN 1 THEN v_base_price * 1.0      -- Phương Trang: giá gốc
            WHEN 2 THEN v_base_price * 0.95      -- Mai Linh: giảm 5%
            WHEN 3 THEN v_base_price * 1.05     -- Hoàng Long: tăng 5%
            WHEN 4 THEN v_base_price * 0.98     -- Kumho Samco: giảm 2%
            WHEN 5 THEN v_base_price * 1.02     -- Thành Bưởi: tăng 2%
            WHEN 44 THEN v_base_price * 0.97    -- Chín Nghĩa: giảm 3%
            ELSE v_base_price
        END;
        
        IF v_vehicle_id IS NOT NULL AND v_route_id IS NOT NULL THEN
            INSERT INTO `trips` (`partner_id`, `route_id`, `vehicle_id`, `departure_time`, `arrival_time`, `price`, `available_seats`, `status`)
            VALUES 
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 12:00:00'), DATE_ADD(CONCAT(trip_date, ' 12:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open'),
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 16:00:00'), DATE_ADD(CONCAT(trip_date, ' 16:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open'),
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 20:00:00'), DATE_ADD(CONCAT(trip_date, ' 20:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open');
        END IF;
    END LOOP;
    CLOSE partner_cursor;
    SET done = FALSE;
    
    -- Tuyến 4: Sài Gòn - Vũng Tàu
    SET v_route_id = (SELECT route_id FROM routes WHERE start_point = 'TP. Hồ Chí Minh' AND end_point = 'Vũng Tàu' LIMIT 1);
    SET v_duration_hours = 2.0;
    SET v_base_price = 180000.00;
    
    OPEN partner_cursor;
    partner_loop4: LOOP
        FETCH partner_cursor INTO v_partner_id;
        IF done THEN
            LEAVE partner_loop4;
        END IF;
        
        SET v_vehicle_id = (SELECT vehicle_id FROM vehicles WHERE partner_id = v_partner_id LIMIT 1);
        
        -- Tính giá khác nhau cho từng nhà xe
        SET v_final_price = CASE v_partner_id
            WHEN 1 THEN v_base_price * 1.0      -- Phương Trang: giá gốc
            WHEN 2 THEN v_base_price * 0.95      -- Mai Linh: giảm 5%
            WHEN 3 THEN v_base_price * 1.05      -- Hoàng Long: tăng 5%
            WHEN 4 THEN v_base_price * 0.98     -- Kumho Samco: giảm 2%
            WHEN 5 THEN v_base_price * 1.02     -- Thành Bưởi: tăng 2%
            WHEN 44 THEN v_base_price * 0.97    -- Chín Nghĩa: giảm 3%
            ELSE v_base_price
        END;
        
        IF v_vehicle_id IS NOT NULL AND v_route_id IS NOT NULL THEN
            INSERT INTO `trips` (`partner_id`, `route_id`, `vehicle_id`, `departure_time`, `arrival_time`, `price`, `available_seats`, `status`)
            VALUES 
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 12:00:00'), DATE_ADD(CONCAT(trip_date, ' 12:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open'),
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 16:00:00'), DATE_ADD(CONCAT(trip_date, ' 16:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open'),
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 20:00:00'), DATE_ADD(CONCAT(trip_date, ' 20:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open');
        END IF;
    END LOOP;
    CLOSE partner_cursor;
    SET done = FALSE;
    
    -- Tuyến 5: Hà Nội - Sapa
    SET v_route_id = (SELECT route_id FROM routes WHERE start_point = 'Hà Nội' AND end_point = 'Sapa' LIMIT 1);
    SET v_duration_hours = 7.0;
    SET v_base_price = 300000.00;
    
    OPEN partner_cursor;
    partner_loop5: LOOP
        FETCH partner_cursor INTO v_partner_id;
        IF done THEN
            LEAVE partner_loop5;
        END IF;
        
        SET v_vehicle_id = (SELECT vehicle_id FROM vehicles WHERE partner_id = v_partner_id LIMIT 1);
        
        -- Tính giá khác nhau cho từng nhà xe
        SET v_final_price = CASE v_partner_id
            WHEN 1 THEN v_base_price * 1.0      -- Phương Trang: giá gốc
            WHEN 2 THEN v_base_price * 0.95     -- Mai Linh: giảm 5%
            WHEN 3 THEN v_base_price * 1.05     -- Hoàng Long: tăng 5%
            WHEN 4 THEN v_base_price * 0.98     -- Kumho Samco: giảm 2%
            WHEN 5 THEN v_base_price * 1.02     -- Thành Bưởi: tăng 2%
            WHEN 44 THEN v_base_price * 0.97    -- Chín Nghĩa: giảm 3%
            ELSE v_base_price
        END;
        
        IF v_vehicle_id IS NOT NULL AND v_route_id IS NOT NULL THEN
            INSERT INTO `trips` (`partner_id`, `route_id`, `vehicle_id`, `departure_time`, `arrival_time`, `price`, `available_seats`, `status`)
            VALUES 
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 12:00:00'), DATE_ADD(CONCAT(trip_date, ' 12:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open'),
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 16:00:00'), DATE_ADD(CONCAT(trip_date, ' 16:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open'),
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 20:00:00'), DATE_ADD(CONCAT(trip_date, ' 20:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open');
        END IF;
    END LOOP;
    CLOSE partner_cursor;
    SET done = FALSE;
    
    -- Tuyến 6: Hà Nội - Quảng Ninh
    SET v_route_id = (SELECT route_id FROM routes WHERE start_point = 'Hà Nội' AND end_point = 'Quảng Ninh' LIMIT 1);
    SET v_duration_hours = 3.0;
    SET v_base_price = 250000.00;
    
    OPEN partner_cursor;
    partner_loop6: LOOP
        FETCH partner_cursor INTO v_partner_id;
        IF done THEN
            LEAVE partner_loop6;
        END IF;
        
        SET v_vehicle_id = (SELECT vehicle_id FROM vehicles WHERE partner_id = v_partner_id LIMIT 1);
        
        -- Tính giá khác nhau cho từng nhà xe
        SET v_final_price = CASE v_partner_id
            WHEN 1 THEN v_base_price * 1.0      -- Phương Trang: giá gốc
            WHEN 2 THEN v_base_price * 0.95     -- Mai Linh: giảm 5%
            WHEN 3 THEN v_base_price * 1.05      -- Hoàng Long: tăng 5%
            WHEN 4 THEN v_base_price * 0.98     -- Kumho Samco: giảm 2%
            WHEN 5 THEN v_base_price * 1.02     -- Thành Bưởi: tăng 2%
            WHEN 44 THEN v_base_price * 0.97    -- Chín Nghĩa: giảm 3%
            ELSE v_base_price
        END;
        
        IF v_vehicle_id IS NOT NULL AND v_route_id IS NOT NULL THEN
            INSERT INTO `trips` (`partner_id`, `route_id`, `vehicle_id`, `departure_time`, `arrival_time`, `price`, `available_seats`, `status`)
            VALUES 
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 12:00:00'), DATE_ADD(CONCAT(trip_date, ' 12:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open'),
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 16:00:00'), DATE_ADD(CONCAT(trip_date, ' 16:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open'),
            (v_partner_id, v_route_id, v_vehicle_id, CONCAT(trip_date, ' 20:00:00'), DATE_ADD(CONCAT(trip_date, ' 20:00:00'), INTERVAL v_duration_hours HOUR), v_final_price, 40, 'open');
        END IF;
    END LOOP;
    CLOSE partner_cursor;
END$$

DELIMITER ;

-- ============================================
-- GỌI PROCEDURE CHO 10 NGÀY (22/12/2025 - 31/12/2025)
-- ============================================
CALL AddTripsForDate('2025-12-22');
CALL AddTripsForDate('2025-12-23');
CALL AddTripsForDate('2025-12-24');
CALL AddTripsForDate('2025-12-25');
CALL AddTripsForDate('2025-12-26');
CALL AddTripsForDate('2025-12-27');
CALL AddTripsForDate('2025-12-28');
CALL AddTripsForDate('2025-12-29');
CALL AddTripsForDate('2025-12-30');
CALL AddTripsForDate('2025-12-31');

-- ============================================
-- XÓA PROCEDURE SAU KHI SỬ DỤNG (tùy chọn)
-- ============================================
DROP PROCEDURE IF EXISTS AddTripsForDate;

-- ============================================
-- HOÀN TẤT
-- ============================================
-- Tổng số chuyến đã thêm: 6 tuyến x số nhà xe x 3 chuyến x 10 ngày
-- Thời gian khởi hành mỗi ngày: 12:00, 16:00, 20:00
-- Ngày khởi hành: 22/12/2025 đến 31/12/2025 (10 ngày)
--
-- BẢNG GIÁ THEO NHÀ XE (tỷ lệ so với giá gốc):
-- - Phương Trang (ID: 1): 100% (giá gốc)
-- - Mai Linh (ID: 2): 95% (giảm 5%)
-- - Hoàng Long (ID: 3): 105% (tăng 5%)
-- - Kumho Samco (ID: 4): 98% (giảm 2%)
-- - Thành Bưởi (ID: 5): 102% (tăng 2%)
-- - Chín Nghĩa (ID: 44): 97% (giảm 3%)
-- - Nhà xe khác: 100% (giá gốc)
