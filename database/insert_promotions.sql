-- ============================================
-- INSERT 8 MÃ KHUYẾN MÃI CHO ƯU ĐÃI NỔI BẬT
-- Chạy file này trong phpMyAdmin hoặc MySQL CLI
-- ============================================

-- Xóa các mã cũ nếu đã tồn tại (tùy chọn)
DELETE FROM promotions WHERE code IN ('TET2025', 'FLASH30', '4FRIDAY', 'SINHVIEN10', 'HOTROUTE', 'EARLYBIRD', 'GOLDENHOUR', 'ROUNDTRIP');

-- 1. Vé Lễ/Tết – Mở bán sớm
INSERT INTO promotions (code, title, description, discount_type, discount_value, min_order_amount, max_discount_amount, start_date, end_date, usage_limit, used_count, status) 
VALUES (
    'TET2025',
    'Vé Lễ/Tết – Mở bán sớm',
    'Mở bán vé Tết Nguyên Đán 2025 sớm! Đặt ngay để có giá tốt nhất và chọn được chỗ ngồi ưng ý. Áp dụng cho tất cả tuyến đường.',
    'percentage',
    15.00,
    100000.00,
    200000.00,
    '2024-12-15 00:00:00',
    '2025-02-15 23:59:59',
    5000,
    0,
    'active'
);

-- 2. Chớp deal 2 giờ – Giảm đến 30%
INSERT INTO promotions (code, title, description, discount_type, discount_value, min_order_amount, max_discount_amount, start_date, end_date, usage_limit, used_count, status) 
VALUES (
    'FLASH30',
    'Chớp deal 2 giờ – Giảm đến 30%',
    'Deal sốc xuất hiện bất ngờ! Giảm đến 30% chỉ trong 2 giờ. Theo dõi thông báo để không bỏ lỡ. Số lượng có hạn.',
    'percentage',
    30.00,
    150000.00,
    150000.00,
    '2024-12-01 00:00:00',
    '2025-12-31 23:59:59',
    100,
    0,
    'active'
);

-- 3. Thứ 6 vui vẻ – Nhập mã 4FRIDAY giảm 20%
INSERT INTO promotions (code, title, description, discount_type, discount_value, min_order_amount, max_discount_amount, start_date, end_date, usage_limit, used_count, status) 
VALUES (
    '4FRIDAY',
    'Thứ 6 vui vẻ – Happy Friday',
    'Mỗi thứ 6, nhập mã 4FRIDAY để được giảm 20% cho tất cả chuyến đi. Đặt vé cuối tuần thật tiết kiệm! Thanh toán online để được áp dụng.',
    'percentage',
    20.00,
    0.00,
    100000.00,
    '2024-12-01 00:00:00',
    '2025-12-31 23:59:59',
    NULL,
    0,
    'active'
);

-- 4. Ưu đãi sinh viên – Giảm 10%
INSERT INTO promotions (code, title, description, discount_type, discount_value, min_order_amount, max_discount_amount, start_date, end_date, usage_limit, used_count, status) 
VALUES (
    'SINHVIEN10',
    'Ưu đãi sinh viên – Giảm 10%',
    'Sinh viên đặt vé được giảm ngay 10%! Dành cho sinh viên có thẻ sinh viên còn hạn. Mỗi tài khoản áp dụng 2 lần/tháng.',
    'percentage',
    10.00,
    0.00,
    50000.00,
    '2024-12-01 00:00:00',
    '2025-12-31 23:59:59',
    NULL,
    0,
    'active'
);

-- 5. Tuyến hot – Giảm đến 25%
INSERT INTO promotions (code, title, description, discount_type, discount_value, min_order_amount, max_discount_amount, start_date, end_date, usage_limit, used_count, status) 
VALUES (
    'HOTROUTE',
    'Tuyến hot – Giảm đến 25%',
    'Các tuyến đường hot nhất được giảm giá đặc biệt. Áp dụng: SG-Đà Lạt, HN-Sapa, SG-Vũng Tàu... Đặt trước ít nhất 3 ngày, thanh toán online.',
    'percentage',
    25.00,
    200000.00,
    120000.00,
    '2024-12-01 00:00:00',
    '2025-12-31 23:59:59',
    1000,
    0,
    'active'
);

-- 6. Đặt sớm – Giá tốt hơn (Early Bird)
INSERT INTO promotions (code, title, description, discount_type, discount_value, min_order_amount, max_discount_amount, start_date, end_date, usage_limit, used_count, status) 
VALUES (
    'EARLYBIRD',
    'Đặt sớm – Giá tốt hơn',
    'Đặt vé trước 7 ngày để nhận ưu đãi giảm giá đặc biệt. Càng đặt sớm, giá càng tốt! Không áp dụng dịp Lễ/Tết.',
    'percentage',
    15.00,
    0.00,
    80000.00,
    '2024-12-01 00:00:00',
    '2025-12-31 23:59:59',
    NULL,
    0,
    'active'
);

-- 7. Giờ vàng mỗi ngày – Deal đẹp (10h-12h)
INSERT INTO promotions (code, title, description, discount_type, discount_value, min_order_amount, max_discount_amount, start_date, end_date, usage_limit, used_count, status) 
VALUES (
    'GOLDENHOUR',
    'Giờ vàng mỗi ngày – Deal đẹp',
    'Mỗi ngày từ 10h-12h trưa, đặt vé với giá ưu đãi đặc biệt. Deal đẹp chờ bạn săn! Thanh toán trong khung giờ vàng để được áp dụng.',
    'percentage',
    18.00,
    0.00,
    90000.00,
    '2024-12-01 00:00:00',
    '2025-12-31 23:59:59',
    NULL,
    0,
    'active'
);

-- 8. Combo khứ hồi – Tiết kiệm hơn
INSERT INTO promotions (code, title, description, discount_type, discount_value, min_order_amount, max_discount_amount, start_date, end_date, usage_limit, used_count, status) 
VALUES (
    'ROUNDTRIP',
    'Combo khứ hồi – Tiết kiệm hơn',
    'Đặt vé khứ hồi cùng lúc để được giảm thêm. Tiết kiệm hơn và không lo hết vé chiều về! Khoảng cách 2 chiều tối thiểu 1 ngày.',
    'percentage',
    10.00,
    300000.00,
    100000.00,
    '2024-12-01 00:00:00',
    '2025-12-31 23:59:59',
    NULL,
    0,
    'active'
);

-- ============================================
-- KIỂM TRA KẾT QUẢ
-- ============================================
SELECT promotion_id, code, title, discount_type, discount_value, max_discount_amount, status 
FROM promotions 
WHERE code IN ('TET2025', 'FLASH30', '4FRIDAY', 'SINHVIEN10', 'HOTROUTE', 'EARLYBIRD', 'GOLDENHOUR', 'ROUNDTRIP')
ORDER BY promotion_id;

