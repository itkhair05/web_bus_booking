-- Database Schema for Bus Booking System
-- Version: 1.0
-- Charset: utf8mb4

-- ============================================
-- 1. USERS & AUTHENTICATION
-- ============================================

CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    address TEXT,
    role ENUM('user', 'partner', 'admin') DEFAULT 'user',
    status ENUM('active', 'locked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    reset_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. PARTNERS & FLEET MANAGEMENT
-- ============================================

CREATE TABLE IF NOT EXISTS partners (
    partner_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    company_name VARCHAR(200) NOT NULL,
    business_license VARCHAR(50),
    logo VARCHAR(255),
    description TEXT,
    status ENUM('pending', 'approved', 'suspended') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vehicles (
    vehicle_id INT PRIMARY KEY AUTO_INCREMENT,
    partner_id INT NOT NULL,
    license_plate VARCHAR(20) UNIQUE NOT NULL,
    vehicle_type ENUM('ghế ngồi', 'giường nằm', 'limousine') NOT NULL,
    total_seats INT NOT NULL,
    seat_layout VARCHAR(10) NOT NULL, -- e.g., '2-2', '2-1'
    status ENUM('active', 'maintenance', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(partner_id) ON DELETE CASCADE,
    INDEX idx_partner (partner_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS drivers (
    driver_id INT PRIMARY KEY AUTO_INCREMENT,
    partner_id INT NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    license_number VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(partner_id) ON DELETE CASCADE,
    INDEX idx_partner (partner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. ROUTES & TRIPS
-- ============================================

CREATE TABLE IF NOT EXISTS routes (
    route_id INT PRIMARY KEY AUTO_INCREMENT,
    route_name VARCHAR(200) NOT NULL,
    origin VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    distance_km DECIMAL(10,2),
    duration_hours DECIMAL(5,2),
    base_price DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_origin_dest (origin, destination),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trips (
    trip_id INT PRIMARY KEY AUTO_INCREMENT,
    route_id INT NOT NULL,
    partner_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    driver_id INT,
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    available_seats INT NOT NULL,
    status ENUM('scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(route_id) ON DELETE CASCADE,
    FOREIGN KEY (partner_id) REFERENCES partners(partner_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(driver_id) ON DELETE SET NULL,
    INDEX idx_departure (departure_time),
    INDEX idx_route (route_id),
    INDEX idx_partner (partner_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. BOOKINGS & TICKETS
-- ============================================

CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    trip_id INT NOT NULL,
    booking_code VARCHAR(20) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    final_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid',
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (trip_id) REFERENCES trips(trip_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_trip (trip_id),
    INDEX idx_booking_code (booking_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tickets (
    ticket_id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    passenger_name VARCHAR(100) NOT NULL,
    passenger_phone VARCHAR(20) NOT NULL,
    passenger_email VARCHAR(100),
    qr_code VARCHAR(255),
    status ENUM('active', 'used', 'cancelled') DEFAULT 'active',
    checked_in_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id),
    INDEX idx_qr (qr_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. PAYMENTS
-- ============================================

CREATE TABLE IF NOT EXISTS payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    transaction_id VARCHAR(100) UNIQUE,
    payment_method ENUM('momo', 'vnpay', 'zalopay', 'cod', 'bank_transfer') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'success', 'failed', 'refunded') DEFAULT 'pending',
    payment_data TEXT, -- JSON data from payment gateway
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id),
    INDEX idx_transaction (transaction_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS refunds (
    refund_id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    payment_id INT NOT NULL,
    refund_amount DECIMAL(10,2) NOT NULL,
    refund_reason TEXT,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    processed_by INT, -- admin user_id
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_booking (booking_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. REVIEWS & RATINGS
-- ============================================

CREATE TABLE IF NOT EXISTS reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    trip_id INT NOT NULL,
    partner_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (trip_id) REFERENCES trips(trip_id) ON DELETE CASCADE,
    FOREIGN KEY (partner_id) REFERENCES partners(partner_id) ON DELETE CASCADE,
    INDEX idx_trip (trip_id),
    INDEX idx_partner (partner_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. PROMOTIONS & DISCOUNTS
-- ============================================

CREATE TABLE IF NOT EXISTS promotions (
    promotion_id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_amount DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2),
    usage_limit INT,
    used_count INT DEFAULT 0,
    valid_from DATETIME NOT NULL,
    valid_to DATETIME NOT NULL,
    status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_valid_dates (valid_from, valid_to),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS promotion_usage (
    usage_id INT PRIMARY KEY AUTO_INCREMENT,
    promotion_id INT NOT NULL,
    user_id INT NOT NULL,
    booking_id INT NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (promotion_id) REFERENCES promotions(promotion_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_promotion (promotion_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. NOTIFICATIONS
-- ============================================

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('booking', 'payment', 'promotion', 'system', 'trip_update') NOT NULL,
    related_id INT, -- booking_id, trip_id, etc.
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. COMPLAINTS & FEEDBACK
-- ============================================

CREATE TABLE IF NOT EXISTS complaints (
    complaint_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    booking_id INT,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'processing', 'resolved', 'closed') DEFAULT 'pending',
    admin_response TEXT,
    responded_by INT, -- admin user_id
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL,
    FOREIGN KEY (responded_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. ACTIVITY LOGS
-- ============================================

CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_data TEXT, -- JSON
    new_data TEXT, -- JSON
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAMPLE DATA (Optional - for testing)
-- ============================================

-- Admin user (password: admin123)
INSERT INTO users (fullname, email, phone, password, role) VALUES
('Admin', 'admin@busbooking.com', '0123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample routes
INSERT INTO routes (route_name, origin, destination, distance_km, duration_hours, base_price, status) VALUES
('Hà Nội - Hải Phòng', 'Hà Nội', 'Hải Phòng', 120, 2.5, 150000, 'active'),
('Hà Nội - Đà Nẵng', 'Hà Nội', 'Đà Nẵng', 800, 14, 450000, 'active'),
('TP.HCM - Đà Lạt', 'TP. Hồ Chí Minh', 'Đà Lạt', 300, 6, 250000, 'active');

