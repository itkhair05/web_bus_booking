<?php
/**
 * Email Configuration Example
 * ===========================
 * 
 * HƯỚNG DẪN CẤU HÌNH EMAIL:
 * 
 * 1. Tạo file .env tại thư mục gốc của project với nội dung sau:
 * 
 * # SMTP Configuration (Gmail example)
 * SMTP_HOST=smtp.gmail.com
 * SMTP_PORT=587
 * SMTP_USERNAME=your-email@gmail.com
 * SMTP_PASSWORD=your-app-password-here
 * SMTP_FROM_EMAIL=your-email@gmail.com
 * SMTP_FROM_NAME="BusBooking System"
 * SMTP_REPLY_TO=your-email@gmail.com
 * 
 * # Company Info
 * COMPANY_NAME="BusBooking"
 * COMPANY_WEBSITE=http://localhost/Bus_Booking
 * SUPPORT_EMAIL=support@busbooking.com
 * SUPPORT_PHONE=1900-xxxx
 * 
 * 2. Để sử dụng Gmail, bạn cần:
 *    - Bật xác thực 2 bước: https://myaccount.google.com/security
 *    - Tạo App Password: https://myaccount.google.com/apppasswords
 *    - Sử dụng App Password (16 ký tự) thay vì mật khẩu Gmail thường
 * 
 * 3. Cài đặt PHPMailer:
 *    - Mở terminal/command prompt
 *    - cd đến thư mục Bus_Booking
 *    - Chạy: composer install
 * 
 * 4. Nếu chưa có Composer:
 *    - Tải từ: https://getcomposer.org/download/
 *    - Cài đặt và chạy lại: composer install
 */

// Test values for local development (không dùng trong production)
define('SMTP_HOST_DEFAULT', 'smtp.gmail.com');
define('SMTP_PORT_DEFAULT', 587);
define('SMTP_USERNAME_DEFAULT', '');
define('SMTP_PASSWORD_DEFAULT', '');
define('SMTP_FROM_EMAIL_DEFAULT', 'noreply@busbooking.com');
define('SMTP_FROM_NAME_DEFAULT', 'BusBooking System');
define('SMTP_REPLY_TO_DEFAULT', 'support@busbooking.com');

