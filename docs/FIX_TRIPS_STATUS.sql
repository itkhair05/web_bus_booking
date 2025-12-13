-- ================================================
-- SỬA STATUS CỦA TRIPS
-- Update tất cả trips thành 'active' nếu status là NULL
-- ================================================

-- Kiểm tra trips có status NULL
SELECT 
    COUNT(*) as total_trips,
    SUM(CASE WHEN status IS NULL THEN 1 ELSE 0 END) as null_status,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_status,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_status
FROM trips;

-- Update tất cả trips NULL thành 'active'
UPDATE trips 
SET status = 'active' 
WHERE status IS NULL;

-- Kiểm tra lại
SELECT 
    COUNT(*) as total_trips,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_status
FROM trips;









