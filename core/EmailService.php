<?php
/**
 * Email Service Class
 * Simple SMTP Email Sender using PHPMailer
 */

require_once __DIR__ . '/../config/email.php';

// Load Composer autoload if available (to enable PHPMailer)
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Download PHPMailer if not exists
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    // Fallback: Use simple mail() function
    class SimpleMailer {
        public static function send($to, $subject, $body, $altBody = '') {
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">" . "\r\n";
            $headers .= "Reply-To: " . SMTP_REPLY_TO . "\r\n";
            
            return mail($to, $subject, $body, $headers);
        }
    }
}

class EmailService {
    private static $usePHPMailer = false;
    
    /**
     * Send email using PHPMailer or fallback to mail()
     */
    public static function send($to, $subject, $htmlBody, $altBody = '') {
        // Try PHPMailer first
        $phpmailerResult = self::sendWithPHPMailer($to, $subject, $htmlBody, $altBody);
        if ($phpmailerResult) {
            return true;
        }
        
        // Log that PHPMailer failed, trying fallback
        error_log("PHPMailer failed, trying mail() fallback for: {$to}");
        
        // Fallback to mail() function
        $mailResult = self::sendWithMailFunction($to, $subject, $htmlBody);
        if ($mailResult) {
            error_log("mail() fallback succeeded for: {$to}");
        } else {
            error_log("Both PHPMailer and mail() failed for: {$to}");
        }
        
        return $mailResult;
    }
    
    /**
     * Send with PHPMailer (if available)
     */
    private static function sendWithPHPMailer($to, $subject, $htmlBody, $altBody) {
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return false;
        }
        
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // SMTP Settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            
            // From & Reply-To
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addReplyTo(SMTP_REPLY_TO, SMTP_FROM_NAME);
            
            // To
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $altBody ?: strip_tags($htmlBody);
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            $errorMsg = isset($mail) ? $mail->ErrorInfo : $e->getMessage();
            error_log("PHPMailer Error: {$errorMsg}");
            error_log("PHPMailer Exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send with mail() function (fallback)
     */
    private static function sendWithMailFunction($to, $subject, $htmlBody) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">" . "\r\n";
        $headers .= "Reply-To: " . SMTP_REPLY_TO . "\r\n";
        
        return @mail($to, $subject, $htmlBody, $headers);
    }
    
    /**
     * Send Password Reset Email
     */
    public static function sendPasswordReset($to, $userName, $resetLink, $expiryMinutes = 15) {
        $subject = "Đặt lại mật khẩu - " . COMPANY_NAME;
        
        $body = self::getEmailTemplate([
            'title' => 'Yêu cầu đặt lại mật khẩu',
            'greeting' => "Xin chào <strong>{$userName}</strong>,",
            'message' => "Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Nhấn vào nút bên dưới để tạo mật khẩu mới:",
            'button_text' => 'Đặt lại mật khẩu',
            'button_link' => $resetLink,
            'footer_message' => "Link này sẽ hết hạn sau <strong>{$expiryMinutes} phút</strong>.<br>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.",
            'warning' => 'Không chia sẻ link này với bất kỳ ai!'
        ]);
        
        return self::send($to, $subject, $body);
    }
    
    /**
     * Send Booking Confirmation Email
     */
    public static function sendBookingConfirmation($to, $userName, $bookingCode, $tripDetails) {
        $subject = "Xác nhận đặt vé - " . $bookingCode;
        
        // Format trip details
        $route = $tripDetails['route'] ?? 'N/A';
        $departureTime = $tripDetails['departure_time'] ?? 'N/A';
        $seats = $tripDetails['seats'] ?? 'N/A';
        $totalPrice = $tripDetails['total_price'] ?? '0';
        $partnerName = $tripDetails['partner_name'] ?? '';
        $vehicleType = $tripDetails['vehicle_type'] ?? '';
        $pickupStation = $tripDetails['pickup_station'] ?? '';
        $pickupTime = $tripDetails['pickup_time'] ?? '';
        $dropoffStation = $tripDetails['dropoff_station'] ?? '';
        $dropoffTime = $tripDetails['dropoff_time'] ?? '';
        
        $tripInfo = "
            <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #1976d2;'>
                <h3 style='margin-top: 0; color: #1e293b; font-size: 18px;'>📋 Chi tiết đặt vé</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; width: 140px;'><strong>Mã đặt vé:</strong></td>
                        <td style='padding: 8px 0; color: #1e293b;'><span style='background: #e3f2fd; padding: 4px 12px; border-radius: 4px; font-weight: bold; color: #1976d2;'>{$bookingCode}</span></td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b;'><strong>📍 Tuyến đường:</strong></td>
                        <td style='padding: 8px 0; color: #1e293b; font-weight: 600;'>{$route}</td>
                    </tr>";
        
        if ($partnerName) {
            $tripInfo .= "
                    <tr>
                        <td style='padding: 8px 0; color: #64748b;'><strong>🚌 Nhà xe:</strong></td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$partnerName}" . ($vehicleType ? " - {$vehicleType}" : "") . "</td>
                    </tr>";
        }
        
        $tripInfo .= "
                    <tr>
                        <td style='padding: 8px 0; color: #64748b;'><strong>🕐 Khởi hành:</strong></td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$departureTime}</td>
                    </tr>";
        
        if ($pickupStation) {
            $tripInfo .= "
                    <tr>
                        <td style='padding: 8px 0; color: #64748b;'><strong>🚏 Điểm đón:</strong></td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$pickupStation}" . ($pickupTime ? " ({$pickupTime})" : "") . "</td>
                    </tr>";
        }
        
        if ($dropoffStation) {
            $tripInfo .= "
                    <tr>
                        <td style='padding: 8px 0; color: #64748b;'><strong>🚏 Điểm trả:</strong></td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$dropoffStation}" . ($dropoffTime ? " ({$dropoffTime})" : "") . "</td>
                    </tr>";
        }
        
        $tripInfo .= "
                    <tr>
                        <td style='padding: 8px 0; color: #64748b;'><strong>💺 Ghế đã chọn:</strong></td>
                        <td style='padding: 8px 0; color: #1e293b; font-weight: 600;'>{$seats}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b;'><strong>💰 Tổng tiền:</strong></td>
                        <td style='padding: 8px 0; color: #ef4444; font-size: 20px; font-weight: bold;'>{$totalPrice}</td>
                    </tr>
                </table>
            </div>
        ";
        
        $paymentNote = "
            <div style='background: #fff3cd; border-left: 4px solid #f59e0b; padding: 16px; margin: 20px 0; border-radius: 4px;'>
                <p style='margin: 0; color: #92400e; font-size: 14px; line-height: 1.6;'>
                    <strong>⚠️ Lưu ý thanh toán:</strong><br>
                    Vui lòng thanh toán trong vòng <strong>15 phút</strong> để hoàn tất đặt vé. Sau khi thanh toán, vé của bạn sẽ được xác nhận tự động.
                </p>
            </div>
        ";
        
        $body = self::getEmailTemplate([
            'title' => 'Đặt vé thành công! 🎉',
            'greeting' => "Xin chào <strong>{$userName}</strong>,",
            'message' => "Cảm ơn bạn đã đặt vé tại <strong>" . COMPANY_NAME . "</strong>!<br><br>Dưới đây là thông tin chi tiết vé của bạn:{$tripInfo}{$paymentNote}",
            'button_text' => 'Thanh toán ngay',
            'button_link' => COMPANY_WEBSITE . "/user/booking/payment.php?booking_id=" . ($tripDetails['booking_id'] ?? ''),
            'footer_message' => "Mã đặt vé của bạn: <strong style='color: #1976d2; font-size: 18px; letter-spacing: 2px;'>{$bookingCode}</strong><br><br>Vui lòng lưu lại mã này và xuất trình khi lên xe. Nếu có thắc mắc, vui lòng liên hệ hotline: <strong>" . SUPPORT_PHONE . "</strong>"
        ]);
        
        return self::send($to, $subject, $body);
    }
    
    /**
     * Send Payment Confirmation Email
     */
    public static function sendPaymentConfirmation($to, $userName, $bookingCode, $tripDetails) {
        $subject = "Thanh toán thành công - " . $bookingCode;
        
        // Format trip details
        $route = $tripDetails['route'] ?? 'N/A';
        $departureTime = $tripDetails['departure_time'] ?? 'N/A';
        $seats = $tripDetails['seats'] ?? 'N/A';
        $totalPrice = $tripDetails['total_price'] ?? '0';
        $partnerName = $tripDetails['partner_name'] ?? '';
        $vehicleType = $tripDetails['vehicle_type'] ?? '';
        $transactionCode = $tripDetails['transaction_code'] ?? 'N/A';
        
        $tripInfo = "
            <div style='background: #d1fae5; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #10b981;'>
                <h3 style='margin-top: 0; color: #065f46; font-size: 18px;'>✅ Thanh toán thành công!</h3>
                <p style='margin: 0; color: #047857;'>Mã giao dịch: <strong>{$transactionCode}</strong></p>
            </div>
            <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #1976d2;'>
                <h3 style='margin-top: 0; color: #1e293b; font-size: 18px;'>📋 Chi tiết vé</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; width: 140px;'><strong>Mã đặt vé:</strong></td>
                        <td style='padding: 8px 0; color: #1e293b;'><span style='background: #e3f2fd; padding: 4px 12px; border-radius: 4px; font-weight: bold; color: #1976d2;'>{$bookingCode}</span></td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b;'><strong>📍 Tuyến đường:</strong></td>
                        <td style='padding: 8px 0; color: #1e293b; font-weight: 600;'>{$route}</td>
                    </tr>";
        
        if ($partnerName) {
            $tripInfo .= "
                    <tr>
                        <td style='padding: 8px 0; color: #64748b;'><strong>🚌 Nhà xe:</strong></td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$partnerName}" . ($vehicleType ? " - {$vehicleType}" : "") . "</td>
                    </tr>";
        }
        
        $tripInfo .= "
                    <tr>
                        <td style='padding: 8px 0; color: #64748b;'><strong>🕐 Khởi hành:</strong></td>
                        <td style='padding: 8px 0; color: #1e293b;'>{$departureTime}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b;'><strong>💺 Ghế đã chọn:</strong></td>
                        <td style='padding: 8px 0; color: #1e293b; font-weight: 600;'>{$seats}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b;'><strong>💰 Tổng tiền:</strong></td>
                        <td style='padding: 8px 0; color: #10b981; font-size: 20px; font-weight: bold;'>{$totalPrice}</td>
                    </tr>
                </table>
            </div>
        ";
        
        $body = self::getEmailTemplate([
            'title' => 'Thanh toán thành công! 🎉',
            'greeting' => "Xin chào <strong>{$userName}</strong>,",
            'message' => "Thanh toán của bạn đã được xác nhận thành công!<br><br>Dưới đây là thông tin chi tiết:{$tripInfo}",
            'button_text' => 'Xem vé điện tử',
            'button_link' => COMPANY_WEBSITE . "/user/tickets/eticket.php?booking_id=" . ($tripDetails['booking_id'] ?? ''),
            'footer_message' => "Mã đặt vé của bạn: <strong style='color: #1976d2; font-size: 18px; letter-spacing: 2px;'>{$bookingCode}</strong><br><br>Vui lòng xuất trình mã đặt vé khi lên xe. Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!"
        ]);
        
        return self::send($to, $subject, $body);
    }
    
    /**
     * Get Email HTML Template
     */
    private static function getEmailTemplate($data) {
        $title = $data['title'] ?? 'Thông báo';
        $greeting = $data['greeting'] ?? 'Xin chào,';
        $message = $data['message'] ?? '';
        $buttonText = $data['button_text'] ?? '';
        $buttonLink = $data['button_link'] ?? '';
        $footerMessage = $data['footer_message'] ?? '';
        $warning = $data['warning'] ?? '';
        
        $buttonHtml = '';
        if ($buttonText && $buttonLink) {
            $buttonHtml = "
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$buttonLink}' style='background: #3498db; color: white; padding: 15px 40px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>
                        {$buttonText}
                    </a>
                </div>
                <p style='color: #7f8c8d; font-size: 13px; text-align: center;'>
                    Hoặc copy link sau vào trình duyệt:<br>
                    <a href='{$buttonLink}' style='color: #3498db; word-break: break-all;'>{$buttonLink}</a>
                </p>
            ";
        }
        
        $warningHtml = '';
        if ($warning) {
            $warningHtml = "
                <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 20px 0;'>
                    <strong style='color: #856404;'>⚠️ Lưu ý:</strong>
                    <p style='color: #856404; margin: 5px 0 0 0;'>{$warning}</p>
                </div>
            ";
        }
        
        return "
        <!DOCTYPE html>
        <html lang='vi'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$title}</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4;'>
            <div style='max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                
                <!-- Header -->
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center;'>
                    <h1 style='margin: 0; font-size: 28px;'>" . COMPANY_NAME . "</h1>
                    <p style='margin: 10px 0 0 0; opacity: 0.9;'>{$title}</p>
                </div>
                
                <!-- Body -->
                <div style='padding: 30px;'>
                    <p style='font-size: 16px; margin: 0 0 15px 0;'>{$greeting}</p>
                    
                    <p style='color: #555; margin: 15px 0;'>{$message}</p>
                    
                    {$buttonHtml}
                    
                    {$warningHtml}
                    
                    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                        <p style='color: #7f8c8d; font-size: 14px; margin: 0;'>{$footerMessage}</p>
                    </div>
                </div>
                
                <!-- Footer -->
                <div style='background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 13px;'>
                    <p style='margin: 0 0 10px 0;'>
                        <strong>" . COMPANY_NAME . "</strong><br>
                        Email: " . SUPPORT_EMAIL . " | Hotline: " . SUPPORT_PHONE . "
                    </p>
                    <p style='margin: 0;'>
                        <a href='" . COMPANY_WEBSITE . "' style='color: #3498db; text-decoration: none;'>Truy cập website</a> | 
                        <a href='" . COMPANY_WEBSITE . "/user/profile' style='color: #3498db; text-decoration: none;'>Quản lý tài khoản</a>
                    </p>
                    <p style='margin: 10px 0 0 0; color: #adb5bd; font-size: 12px;'>
                        © 2024 " . COMPANY_NAME . ". All rights reserved.
                    </p>
                </div>
                
            </div>
        </body>
        </html>
        ";
    }
}

