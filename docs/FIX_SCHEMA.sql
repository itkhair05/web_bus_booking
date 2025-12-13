-- ============================================
-- FIX DATABASE SCHEMA
-- Thêm các columns còn thiếu vào database
-- Chạy file này nếu không muốn import lại toàn bộ
-- ============================================

USE bus_booking;

-- Fix routes table
ALTER TABLE routes 
ADD COLUMN IF NOT EXISTS route_name VARCHAR(200) NOT NULL AFTER route_id,
ADD COLUMN IF NOT EXISTS origin VARCHAR(100) NOT NULL AFTER route_name,
ADD COLUMN IF NOT EXISTS destination VARCHAR(100) NOT NULL AFTER origin,
ADD COLUMN IF NOT EXISTS distance_km DECIMAL(10,2) AFTER destination,
ADD COLUMN IF NOT EXISTS duration_hours DECIMAL(5,2) AFTER distance_km,
ADD COLUMN IF NOT EXISTS base_price DECIMAL(10,2) NOT NULL AFTER duration_hours;

-- Update route_name if it's empty (generate from start_location and end_location if they exist)
UPDATE routes 
SET route_name = CONCAT(
    COALESCE(origin, start_location, ''),
    ' - ',
    COALESCE(destination, end_location, '')
)
WHERE route_name = '' OR route_name IS NULL;

-- Fix trips table - make sure 'price' column exists
ALTER TABLE trips 
ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER driver_id;

-- Copy from base_price or price_per_seat if exists
UPDATE trips t
JOIN routes r ON t.route_id = r.route_id
SET t.price = COALESCE(
    t.price_per_seat,
    t.base_price,
    r.base_price,
    0
)
WHERE t.price = 0;

-- Fix vehicles table - rename buses to vehicles if needed
-- Skip if table already named correctly
CREATE TABLE IF NOT EXISTS vehicles (
    vehicle_id INT PRIMARY KEY AUTO_INCREMENT,
    partner_id INT NOT NULL,
    license_plate VARCHAR(20) UNIQUE NOT NULL,
    vehicle_type ENUM('ghế ngồi', 'giường nằm', 'limousine') NOT NULL,
    total_seats INT NOT NULL,
    seat_layout VARCHAR(10) NOT NULL,
    status ENUM('active', 'maintenance', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(partner_id) ON DELETE CASCADE,
    INDEX idx_partner (partner_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- If buses table exists, migrate data to vehicles
-- INSERT IGNORE INTO vehicles 
-- SELECT * FROM buses WHERE NOT EXISTS (SELECT 1 FROM vehicles);

-- Add trip_schedules table if not exists
CREATE TABLE IF NOT EXISTS trip_schedules (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    stop_order INT NOT NULL,
    departure_station VARCHAR(255),
    arrival_station VARCHAR(255),
    departure_time DATETIME,
    arrival_time DATETIME,
    is_pickup TINYINT(1) DEFAULT 0,
    is_dropoff TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(trip_id) ON DELETE CASCADE,
    INDEX idx_trip (trip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add booking_seats table if not exists
CREATE TABLE IF NOT EXISTS booking_seats (
    booking_seat_id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update bookings table - add missing columns
ALTER TABLE bookings
ADD COLUMN IF NOT EXISTS booking_code VARCHAR(50) UNIQUE AFTER booking_id,
ADD COLUMN IF NOT EXISTS passenger_name VARCHAR(100) AFTER booking_code,
ADD COLUMN IF NOT EXISTS passenger_phone VARCHAR(20) AFTER passenger_name,
ADD COLUMN IF NOT EXISTS passenger_email VARCHAR(100) AFTER passenger_phone,
ADD COLUMN IF NOT EXISTS pickup_schedule_id INT AFTER passenger_email,
ADD COLUMN IF NOT EXISTS dropoff_schedule_id INT AFTER pickup_schedule_id,
ADD COLUMN IF NOT EXISTS total_seats INT DEFAULT 1 AFTER dropoff_schedule_id,
ADD COLUMN IF NOT EXISTS total_price DECIMAL(10,2) AFTER total_seats,
ADD COLUMN IF NOT EXISTS insurance_amount DECIMAL(10,2) DEFAULT 0 AFTER total_price,
ADD COLUMN IF NOT EXISTS final_amount DECIMAL(10,2) AFTER insurance_amount,
ADD COLUMN IF NOT EXISTS booking_status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending' AFTER final_amount,
ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending' AFTER booking_status;

-- Add notifications table if not exists
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error', 'booking') DEFAULT 'info',
    related_id INT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add seats table for seat management
CREATE TABLE IF NOT EXISTS seats (
    seat_id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    status ENUM('available', 'booked', 'locked') DEFAULT 'available',
    booked_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(trip_id) ON DELETE CASCADE,
    FOREIGN KEY (booked_by) REFERENCES users(user_id) ON DELETE SET NULL,
    UNIQUE KEY unique_seat (trip_id, seat_number),
    INDEX idx_trip (trip_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Success message
SELECT '✅ Database schema fixed successfully!' AS Status;
SELECT 'Please check database_check.php to verify all columns are present.' AS Message;

