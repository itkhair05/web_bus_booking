# BusBooking – Hệ thống đặt vé xe khách

BusBooking là một hệ thống đặt vé xe khách trực tuyến đơn giản, lấy cảm hứng từ VeXeRe. Dự án hỗ trợ các vai trò Người dùng, Đối tác (Nhà xe) và Quản trị viên, bao gồm tìm kiếm chuyến, đặt vé, quản lý ghế, thanh toán (VNPay, dự kiến MoMo), gửi email, báo cáo và quản lý vận hành.

## Tổng quan tính năng

- Người dùng: đăng ký/đăng nhập, tìm kiếm chuyến, xem sơ đồ ghế, đặt vé, thanh toán, quản lý hồ sơ, đánh giá, hủy vé.
- Đối tác (nhà xe): quản lý đội xe, tài xế, tuyến/chuyến, trạng thái chuyến, vé theo chuyến, thông báo.
- Quản trị viên: quản lý người dùng, đối tác, tuyến/chuyến, khuyến mãi, báo cáo, hoàn tiền, khóa/mở tài khoản.
- Thanh toán: tích hợp VNPay (sandbox) qua `core/VNPayService.php`, webhook MoMo (placeholder) tại `api/payments/webhook_momo.php`.
- Email: gửi xác nhận đặt vé, đặt lại mật khẩu qua `core/EmailService.php` (ưu tiên PHPMailer, fallback `mail()`).
- Bảo mật: phiên (`config/session.php`), CSRF (`core/csrf.php`), middleware/auth (`core/middleware.php`, `core/auth.php`).

## Công nghệ & yêu cầu hệ thống

- PHP 8.x (khuyến nghị) + Apache (XAMPP trên Windows) 
- MySQL/MariaDB (tên DB mặc định: `bus_booking`)
- Composer (tùy chọn, để cài PHPMailer)
- Trình duyệt hiện đại (Chrome/Edge)

## Cấu trúc thư mục chính

```
index.php
api/               # REST-like PHP endpoints theo chức năng
assets/            # CSS/JS/Images cho giao diện người dùng
config/            # Cấu hình ứng dụng, DB, email, thanh toán
core/              # Dịch vụ & tiện ích (Auth, CSRF, Email, VNPay)
admin/, partner/, user/  # Giao diện theo vai trò
database/          # File SQL khởi tạo DB
docs/              # SQL/ghi chú mẫu, fix, sample data
includes/          # Layouts/header/footer/navbar/sidebar
uploads/           # Upload người dùng (avatar, v.v.)
```

Một số file/đường dẫn tiêu biểu:

- Trang chủ: [index.php](index.php)
- Cấu hình DB: [config/db.php](config/db.php)
- Hằng số ứng dụng: [config/constants.php](config/constants.php)
- Email (SMTP): [config/email.php](config/email.php), dịch vụ: [core/EmailService.php](core/EmailService.php)
- VNPay (sandbox): [config/vnpay.php](config/vnpay.php), dịch vụ: [core/VNPayService.php](core/VNPayService.php)
- Session: [config/session.php](config/session.php)
- Tìm kiếm chuyến (API): [api/trips/search.php](api/trips/search.php)
- Đặt vé (API): [api/bookings/create.php](api/bookings/create.php)
- Thanh toán VNPay: [api/payments/create_invoice.php](api/payments/create_invoice.php), [api/payments/confirm.php](api/payments/confirm.php)
- Sơ đồ ghế: [api/user/seatmap.php](api/user/seatmap.php), assets: [assets/js/seatmap.js](assets/js/seatmap.js)

## Cài đặt nhanh (Windows/XAMPP)

1) Cài và bật XAMPP (Apache + MySQL). 
2) Sao chép dự án vào `C:\xampp\htdocs\Bus_Booking` (đã đúng nếu bạn đang ở thư mục này).
3) Tạo database MySQL tên `bus_booking`.
4) Import schema:
   - Mở phpMyAdmin → chọn DB `bus_booking` → Import file: [database/bus_booking.sql](database/bus_booking.sql)
   - Tuỳ chọn: import thêm dữ liệu mẫu từ thư mục [docs/](docs/) như: 
     - [docs/SAMPLE_DATA.sql](docs/SAMPLE_DATA.sql) (dữ liệu mẫu)
     - [docs/TEST_DATA_USER.sql](docs/TEST_DATA_USER.sql) (người dùng thử nghiệm)
     - Các file `FIX_*.sql` dùng để điều chỉnh nhanh một số vấn đề.
5) Cấu hình kết nối database trong [config/db.php](config/db.php):

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bus_booking');
```

6) Cấu hình hằng số ứng dụng trong [config/constants.php](config/constants.php):

```php
define('APP_DEBUG', true);                 // set false khi triển khai production
define('APP_NAME', 'BusBooking');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/Bus_Booking');

// Đường dẫn lưu trữ (tạo nếu chưa có)
define('UPLOAD_PATH', BASE_PATH . '/storage/uploads');
define('QR_PATH', BASE_PATH . '/storage/qrcodes');
define('LOG_PATH', BASE_PATH . '/storage/logs');
```

7) Tạo thư mục lưu trữ nếu chưa tồn tại (Windows):

```
storage/ 
  uploads/
  qrcodes/
  logs/
```

8) Cấu hình Email SMTP trong [config/email.php](config/email.php):

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // TLS
define('SMTP_USERNAME', '<your-gmail>@gmail.com');
define('SMTP_PASSWORD', '<app-password>');   // Mật khẩu ứng dụng Gmail
define('SMTP_FROM_EMAIL', '<your-gmail>@gmail.com');
define('SMTP_FROM_NAME', 'BusBooking System');
define('SMTP_REPLY_TO', '<your-gmail>@gmail.com');

define('COMPANY_NAME', 'BusBooking');
define('COMPANY_WEBSITE', 'http://localhost/Bus_Booking');
define('SUPPORT_EMAIL', 'support@busbooking.com');
define('SUPPORT_PHONE', '1900-xxxx');
```

Khuyến nghị cài PHPMailer (Composer) để gửi mail ổn định:

```bash
composer require phpmailer/phpmailer
```

9) Cấu hình VNPay (sandbox) trong [config/vnpay.php](config/vnpay.php):

```php
define('VNPAY_TMN_CODE', '<sandbox_tmn_code>');
define('VNPAY_HASH_SECRET', '<sandbox_secret>');
define('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
define('VNPAY_RETURN_URL', APP_URL . '/user/payments/vnpay_return.php');
```

10) Khởi chạy: bật Apache + MySQL trong XAMPP, truy cập:

- Trang chủ: http://localhost/Bus_Booking 
- Trang người dùng: [user/dashboard.php](user/dashboard.php)
- Trang quản trị: [admin/admin_dashboard.php](admin/admin_dashboard.php)
- Trang đối tác: [partner/dashboard.php](partner/dashboard.php)

## Hướng dẫn sử dụng nhanh

### Người dùng
- Tìm chuyến: từ [index.php](index.php) hoặc [user/search](user/search) → điền điểm đi/đến, ngày → xem kết quả.
- Chọn ghế: xem sơ đồ ghế, chọn ghế phù hợp.
- Đặt vé & thanh toán: thực hiện đặt vé, chọn phương thức (VNPay). Kết quả trả về tại `user/payments/vnpay_return.php`.
- Quản lý tài khoản: cập nhật hồ sơ, đổi mật khẩu, quản lý vé.

### Đối tác
- Quản lý đội xe/tài xế: [api/partner/fleet_crud.php](api/partner/fleet_crud.php), [api/partner/drivers_crud.php](api/partner/drivers_crud.php)
- Quản lý chuyến: [api/partner/trips_crud.php](api/partner/trips_crud.php), cập nhật trạng thái: [api/partner/trip_status.php](api/partner/trip_status.php)
- Vé theo chuyến: [api/partner/tickets_by_trip.php](api/partner/tickets_by_trip.php)

### Quản trị viên
- Người dùng: [api/admin/users_list.php](api/admin/users_list.php), khóa/mở: [api/admin/users_lock.php](api/admin/users_lock.php)
- Đối tác: duyệt/treo: [api/admin/partners_approve.php](api/admin/partners_approve.php), [api/admin/partners_suspend.php](api/admin/partners_suspend.php)
- Tuyến/chuyến: [api/admin/routes_crud.php](api/admin/routes_crud.php), [api/admin/trips_admin_crud.php](api/admin/trips_admin_crud.php)
- Khuyến mãi: [api/admin/promotions_crud.php](api/admin/promotions_crud.php)
- Hoàn tiền: [api/admin/refund_ticket.php](api/admin/refund_ticket.php)

## API (tổng quan nhanh)

Các endpoint PHP theo nhóm trong thư mục [api/](api):

- Xác thực: [api/auth/login.php](api/auth/login.php), [api/auth/register.php](api/auth/register.php), [api/auth/forgot_password.php](api/auth/forgot_password.php), [api/auth/reset_password.php](api/auth/reset_password.php)
- Chuyến/ghế: [api/trips/search.php](api/trips/search.php), [api/seats/availability.php](api/seats/availability.php)
- Đặt vé: [api/user/book_ticket.php](api/user/book_ticket.php), [api/bookings/create.php](api/bookings/create.php), hủy vé: [api/user/cancel_ticket.php](api/user/cancel_ticket.php)
- Thanh toán: tạo hóa đơn [api/payments/create_invoice.php](api/payments/create_invoice.php), xác nhận [api/payments/confirm.php](api/payments/confirm.php), webhook MoMo: [api/payments/webhook_momo.php](api/payments/webhook_momo.php)
- Hồ sơ người dùng: [api/user/update_profile.php](api/user/update_profile.php), avatar: [api/user/upload_avatar.php](api/user/upload_avatar.php)

Lưu ý: đa số endpoint nhận/đáp JSON; kiểm tra tham số yêu cầu trước khi gọi.

## Thanh toán

### VNPay (Sandbox)
- Cấu hình tại [config/vnpay.php](config/vnpay.php). Service tạo URL: [core/VNPayService.php](core/VNPayService.php).
- `VNPAY_RETURN_URL` mặc định trỏ tới [user/payments/vnpay_return.php](user/payments/vnpay_return.php) (xử lý kết quả).
- Số tiền gửi tới VNPay cần nhân 100 (đã xử lý trong service).
- Mã phản hồi `00` là thành công; bản đồ mã xem trong `VNPAY_RESPONSE_CODES`.

### MoMo
- Webhook placeholder: [api/payments/webhook_momo.php](api/payments/webhook_momo.php) (cần triển khai/hoàn thiện theo tài liệu MoMo).

## Email

- Dùng PHPMailer (nếu cài bằng Composer) hoặc fallback `mail()`.
- Mẫu email: xác nhận đặt vé, đặt lại mật khẩu; xem [core/EmailService.php](core/EmailService.php) và cấu hình [config/email.php](config/email.php).
- Khi dùng Gmail, hãy tạo “App Password” và bật bảo mật 2 lớp.

## Bảo mật & cấu hình

- Session: [config/session.php](config/session.php) – thiết lập timeout 30 phút.
- CSRF: [core/csrf.php](core/csrf.php) – xác thực token cho form/API.
- `APP_DEBUG`: đặt `false` khi triển khai thực tế; bật HTTPS; ẩn thông tin nhạy cảm.
- Không commit mật khẩu thực vào VCS; thay bằng placeholder, refactor sang `.env` nếu có thể.

## Khởi chạy & phát triển

- Khởi chạy local: bật Apache + MySQL (XAMPP) → duyệt http://localhost/Bus_Booking
- Frontend assets: xem [assets/css](assets/css) và [assets/js](assets/js) (không dùng bundler, có thể mở rộng sang Vite/Webpack nếu cần).
- Thêm API mới: tạo file PHP trong thư mục phù hợp dưới [api/](api), tái dùng helpers trong [core/](core).

## Gỡ lỗi thường gặp

- Lỗi kết nối DB: kiểm tra [config/db.php](config/db.php) và trạng thái MySQL/XAMPP; đảm bảo DB `bus_booking` tồn tại.
- Email không gửi: cài PHPMailer (`composer require phpmailer/phpmailer`), kiểm tra `SMTP_USERNAME/PASSWORD`, “App Password” Gmail.
- VNPay báo sai chữ ký: đảm bảo `VNPAY_HASH_SECRET` đúng; không thay đổi tham số sau khi tạo URL.
- Thiếu thư mục `storage/*`: tạo `storage/uploads`, `storage/qrcodes`, `storage/logs` và cấp quyền ghi.
- Lỗi đường dẫn asset: xác nhận `APP_URL` trong [config/constants.php](config/constants.php) khớp URL đang truy cập.

## Đóng góp & liên hệ

- Mở issue/PR cho tính năng mới hoặc bug.
- Hỗ trợ/trao đổi: `4fbusbooking.noreply@gmail.com`
