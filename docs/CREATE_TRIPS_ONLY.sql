-- ================================================
-- TẠO TRIPS CHO NGÀY MAI
-- Sử dụng routes và vehicles CÓ SẴN (22 routes, 8 vehicles, 2 partners)
-- ================================================

-- XÓA TRIPS CŨ (nếu có - để test lại từ đầu)
-- DELETE FROM trips WHERE DATE(departure_time) = DATE_ADD(CURDATE(), INTERVAL 1 DAY);

-- ================================================
-- TẠO TRIPS TỪ CÁC ROUTES VÀ VEHICLES CÓ SẴN
-- ================================================

-- Tạo nhiều trips cho mỗi route, mỗi vehicle, các giờ khác nhau
INSERT INTO trips (route_id, partner_id, vehicle_id, departure_time, arrival_time, price, available_seats, status)
SELECT 
    r.route_id,
    v.partner_id,
    v.vehicle_id,
    CONCAT(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '%Y-%m-%d'), ' ', times.hour, ':00:00') as departure_time,
    CONCAT(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '%Y-%m-%d'), ' ', times.hour, ':00:00') + INTERVAL COALESCE(r.duration_hours, 2) HOUR as arrival_time,
    GREATEST(COALESCE(r.base_price, 100000), 50000) as price,
    v.total_seats as available_seats,
    'active' as status
FROM routes r
CROSS JOIN vehicles v
CROSS JOIN (
    SELECT '06' as hour UNION ALL
    SELECT '08' UNION ALL
    SELECT '10' UNION ALL
    SELECT '12' UNION ALL
    SELECT '14' UNION ALL
    SELECT '16' UNION ALL
    SELECT '18' UNION ALL
    SELECT '20'
) as times
WHERE r.status = 'active'
AND v.status = 'active'
AND r.origin IS NOT NULL
AND r.destination IS NOT NULL
-- Giới hạn để không tạo quá nhiều
LIMIT 200;

-- ================================================
-- KIỂM TRA KẾT QUẢ
-- ================================================

SELECT '========================================' as '';
SELECT 'THỐNG KÊ SAU KHI TẠO TRIPS' as '';
SELECT '========================================' as '';

SELECT 
    'Partners' as Loại,
    COUNT(*) as Tổng,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as Hoạt_động
FROM partners
UNION ALL
SELECT 
    'Routes' as Loại,
    COUNT(*) as Tổng,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as Hoạt_động
FROM routes
UNION ALL
SELECT 
    'Vehicles' as Loại,
    COUNT(*) as Tổng,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as Hoạt_động
FROM vehicles
UNION ALL
SELECT 
    'Trips' as Loại,
    COUNT(*) as Tổng,
    SUM(CASE WHEN DATE(departure_time) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND status = 'active' THEN 1 ELSE 0 END) as Ngày_mai
FROM trips;

SELECT '========================================' as '';
SELECT 'MỘT SỐ CHUYẾN MẪU' as '';
SELECT '========================================' as '';

SELECT 
    t.trip_id as ID,
    r.origin as Từ,
    r.destination as Đến,
    p.name as Nhà_xe,
    v.vehicle_type as Loại_xe,
    v.license_plate as Biển_số,
    DATE_FORMAT(t.departure_time, '%d/%m %H:%i') as Khởi_hành,
    DATE_FORMAT(t.arrival_time, '%H:%i') as Đến_nơi,
    FORMAT(t.price, 0) as Giá,
    t.available_seats as Chỗ
FROM trips t
JOIN routes r ON t.route_id = r.route_id
JOIN partners p ON t.partner_id = p.partner_id
JOIN vehicles v ON t.vehicle_id = v.vehicle_id
WHERE DATE(t.departure_time) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
AND t.status = 'active'
ORDER BY r.origin, r.destination, t.departure_time
LIMIT 30;

SELECT '========================================' as '';
SELECT 'THỐNG KÊ THEO TUYẾN' as '';
SELECT '========================================' as '';

SELECT 
    r.origin as Điểm_đi,
    r.destination as Điểm_đến,
    COUNT(t.trip_id) as Số_chuyến,
    MIN(t.price) as Giá_thấp_nhất,
    MAX(t.price) as Giá_cao_nhất
FROM trips t
JOIN routes r ON t.route_id = r.route_id
WHERE DATE(t.departure_time) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
AND t.status = 'active'
GROUP BY r.origin, r.destination
ORDER BY COUNT(t.trip_id) DESC
LIMIT 20;

