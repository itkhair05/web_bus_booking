<?php
/**
 * Trang Chính sách & Quy định
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/auth.php';

$pageTitle = 'Chính sách & Quy định - BusBooking';
$pageDescription = 'Chính sách hoàn tiền, hủy vé, bảo mật và các quy định khác của BusBooking';

include '../../includes/header_user.php';
?>

<style>
/* Policies Page Styles */
.policies-page {
    background: #fff;
    min-height: 100vh;
}

.policies-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 20px 80px;
}

.policies-breadcrumb {
    font-size: 14px;
    color: #2196F3;
    margin-bottom: 16px;
}

.policies-breadcrumb a {
    color: #2196F3;
    text-decoration: none;
}

.policies-breadcrumb a:hover {
    text-decoration: underline;
}

.policies-breadcrumb span {
    color: #666;
}

.policies-header {
    text-align: center;
    margin-bottom: 48px;
}

.policies-header h1 {
    font-size: 42px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 16px;
}

.policies-header p {
    font-size: 18px;
    color: #666;
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.6;
}

.policies-section {
    margin-bottom: 48px;
}

.policies-section h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 3px solid #1E90FF;
}

.policies-section p {
    font-size: 16px;
    line-height: 1.8;
    color: #333;
    margin-bottom: 16px;
}

.policy-box {
    background: #f8fafc;
    border-left: 4px solid #1E90FF;
    padding: 24px;
    border-radius: 0 12px 12px 0;
    margin: 24px 0;
}

.policy-box h3 {
    font-size: 20px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.policy-box h3 i {
    color: #1E90FF;
}

.policy-box p {
    margin-bottom: 12px;
}

.policy-box ul {
    list-style: none;
    padding: 0;
    margin: 16px 0;
}

.policy-box ul li {
    padding-left: 28px;
    margin-bottom: 12px;
    font-size: 15px;
    color: #666;
    line-height: 1.7;
    position: relative;
}

.policy-box ul li::before {
    content: '•';
    position: absolute;
    left: 0;
    color: #1E90FF;
    font-weight: bold;
    font-size: 20px;
}

.policy-table {
    width: 100%;
    border-collapse: collapse;
    margin: 24px 0;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.policy-table thead {
    background: linear-gradient(135deg, #1E90FF 0%, #0d6efd 100%);
    color: white;
}

.policy-table th {
    padding: 16px;
    text-align: left;
    font-weight: 600;
    font-size: 15px;
}

.policy-table td {
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
    color: #666;
}

.policy-table tbody tr:hover {
    background: #f8fafc;
}

.policy-table tbody tr:last-child td {
    border-bottom: none;
}

.policy-warning {
    background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
    border-left: 4px solid #ffc107;
    padding: 24px;
    border-radius: 0 12px 12px 0;
    margin: 24px 0;
}

.policy-warning h3 {
    font-size: 20px;
    font-weight: 600;
    color: #856404;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.policy-warning h3 i {
    color: #ffc107;
}

.policy-warning p {
    color: #856404;
    margin: 0;
}

.policy-info {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-left: 4px solid #1E90FF;
    padding: 24px;
    border-radius: 0 12px 12px 0;
    margin: 24px 0;
}

.policy-info h3 {
    font-size: 20px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.policy-info h3 i {
    color: #1E90FF;
}

.policy-info p {
    color: #333;
    margin: 0;
}

@media (max-width: 768px) {
    .policies-header h1 {
        font-size: 32px;
    }
    
    .policy-table {
        font-size: 12px;
    }
    
    .policy-table th,
    .policy-table td {
        padding: 12px 8px;
    }
}
</style>

<main class="policies-page">
    <div class="policies-container">
        <!-- Breadcrumb -->
        <div class="policies-breadcrumb">
            <a href="<?php echo appUrl(); ?>">Trang chủ</a> <span>/</span> Chính sách & Quy định
        </div>

        <!-- Header -->
        <div class="policies-header">
            <h1>Chính sách & Quy định</h1>
            <p>Các chính sách và quy định về đặt vé, hủy vé, hoàn tiền và bảo mật thông tin của BusBooking</p>
        </div>

        <!-- Chính sách hủy vé -->
        <div class="policies-section">
            <h2>Chính sách hủy vé</h2>
            
            <div class="policy-box">
                <h3><i class="fas fa-calendar-times"></i> Thời gian hủy vé</h3>
                <p>Khách hàng có thể hủy vé trước giờ khởi hành tối thiểu <strong>24 giờ</strong>. Thời gian hủy vé có thể khác nhau tùy theo chính sách của từng nhà xe.</p>
                <p><strong>Lưu ý:</strong> Vé không thể hủy sau khi xe đã khởi hành hoặc trong vòng 24 giờ trước giờ khởi hành (trừ trường hợp đặc biệt).</p>
            </div>

            <div class="policy-box">
                <h3><i class="fas fa-money-bill-wave"></i> Phí hủy vé</h3>
                <p>Phí hủy vé được tính như sau:</p>
                <ul>
                    <li><strong>Hủy trước 48 giờ:</strong> Hoàn tiền 100% (trừ phí dịch vụ 5%)</li>
                    <li><strong>Hủy trước 24-48 giờ:</strong> Hoàn tiền 80% (trừ phí dịch vụ 5%)</li>
                    <li><strong>Hủy trong vòng 24 giờ:</strong> Hoàn tiền 50% (trừ phí dịch vụ 5%)</li>
                    <li><strong>Hủy sau khi xe khởi hành:</strong> Không được hoàn tiền</li>
                </ul>
            </div>

            <div class="policy-warning">
                <h3><i class="fas fa-exclamation-triangle"></i> Lưu ý quan trọng</h3>
                <p>Một số nhà xe có chính sách hủy vé riêng. Vui lòng xem chi tiết chính sách của từng nhà xe trước khi đặt vé. Chính sách của nhà xe sẽ được ưu tiên áp dụng.</p>
            </div>
        </div>

        <!-- Chính sách hoàn tiền -->
        <div class="policies-section">
            <h2>Chính sách hoàn tiền</h2>
            
            <div class="policy-box">
                <h3><i class="fas fa-undo"></i> Điều kiện hoàn tiền</h3>
                <p>Hoàn tiền được áp dụng trong các trường hợp sau:</p>
                <ul>
                    <li>Khách hàng hủy vé đúng thời hạn quy định</li>
                    <li>Nhà xe hủy chuyến hoặc thay đổi lịch trình không phù hợp</li>
                    <li>Nhà xe không cung cấp dịch vụ như đã cam kết</li>
                    <li>Có lỗi kỹ thuật từ phía hệ thống BusBooking</li>
                </ul>
            </div>

            <div class="policy-box">
                <h3><i class="fas fa-clock"></i> Thời gian hoàn tiền</h3>
                <p>Thời gian hoàn tiền tùy thuộc vào phương thức thanh toán:</p>
                <table class="policy-table">
                    <thead>
                        <tr>
                            <th>Phương thức thanh toán</th>
                            <th>Thời gian hoàn tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>MoMo / ZaloPay</td>
                            <td>3-5 ngày làm việc</td>
                        </tr>
                        <tr>
                            <td>VNPay (Thẻ ngân hàng)</td>
                            <td>5-7 ngày làm việc</td>
                        </tr>
                        <tr>
                            <td>Internet Banking</td>
                            <td>5-7 ngày làm việc</td>
                        </tr>
                        <tr>
                            <td>Thanh toán khi lên xe</td>
                            <td>Không áp dụng hoàn tiền</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="policy-info">
                <h3><i class="fas fa-info-circle"></i> Thông tin bổ sung</h3>
                <p>Tiền hoàn sẽ được chuyển về tài khoản/ngân hàng bạn đã sử dụng để thanh toán. Nếu có thắc mắc về việc hoàn tiền, vui lòng liên hệ hotline <strong>1900 123 456</strong> để được hỗ trợ.</p>
            </div>
        </div>

        <!-- Chính sách đổi vé -->
        <div class="policies-section">
            <h2>Chính sách đổi vé</h2>
            
            <div class="policy-box">
                <h3><i class="fas fa-exchange-alt"></i> Điều kiện đổi vé</h3>
                <p>Khách hàng có thể đổi vé trong các trường hợp sau:</p>
                <ul>
                    <li>Đổi vé trước giờ khởi hành tối thiểu 24 giờ</li>
                    <li>Chuyến xe mới còn ghế trống</li>
                    <li>Chấp nhận chênh lệch giá (nếu có)</li>
                    <li>Chỉ được đổi 1 lần cho mỗi vé</li>
                </ul>
            </div>

            <div class="policy-box">
                <h3><i class="fas fa-calculator"></i> Phí đổi vé</h3>
                <ul>
                    <li><strong>Đổi vé cùng giá:</strong> Phí dịch vụ 50.000 VNĐ/vé</li>
                    <li><strong>Đổi vé giá cao hơn:</strong> Thanh toán chênh lệch + phí dịch vụ 50.000 VNĐ/vé</li>
                    <li><strong>Đổi vé giá thấp hơn:</strong> Hoàn tiền chênh lệch (trừ phí dịch vụ 50.000 VNĐ/vé)</li>
                </ul>
            </div>
        </div>

        <!-- Chính sách hành lý -->
        <div class="policies-section">
            <h2>Quy định về hành lý</h2>
            
            <div class="policy-box">
                <h3><i class="fas fa-suitcase"></i> Hành lý xách tay</h3>
                <p>Mỗi hành khách được mang theo:</p>
                <ul>
                    <li>Tối đa <strong>7kg</strong> hành lý xách tay</li>
                    <li>Kích thước không quá <strong>40cm x 30cm x 20cm</strong></li>
                    <li>Hành lý phải để ở khoang trên đầu hoặc dưới ghế</li>
                </ul>
            </div>

            <div class="policy-box">
                <h3><i class="fas fa-box"></i> Hành lý ký gửi</h3>
                <p>Quy định về hành lý ký gửi:</p>
                <ul>
                    <li>Tối đa <strong>20kg</strong> hành lý ký gửi (miễn phí)</li>
                    <li>Hành lý vượt quá sẽ tính phí: <strong>10.000 VNĐ/kg</strong></li>
                    <li>Không nhận các vật phẩm cấm: chất lỏng dễ cháy, vũ khí, chất độc hại...</li>
                </ul>
            </div>
        </div>

        <!-- Chính sách bảo mật -->
        <div class="policies-section">
            <h2>Chính sách bảo mật thông tin</h2>
            
            <div class="policy-box">
                <h3><i class="fas fa-shield-alt"></i> Bảo vệ thông tin cá nhân</h3>
                <p>BusBooking cam kết bảo vệ thông tin cá nhân của khách hàng:</p>
                <ul>
                    <li>Mã hóa thông tin thanh toán bằng công nghệ SSL/TLS</li>
                    <li>Không chia sẻ thông tin với bên thứ ba không liên quan</li>
                    <li>Chỉ sử dụng thông tin để cung cấp dịch vụ và cải thiện trải nghiệm</li>
                    <li>Tuân thủ Luật An ninh mạng và Bảo vệ dữ liệu cá nhân</li>
                </ul>
            </div>

            <div class="policy-box">
                <h3><i class="fas fa-lock"></i> Thanh toán an toàn</h3>
                <p>BusBooking sử dụng các cổng thanh toán uy tín:</p>
                <ul>
                    <li>MoMo - Ví điện tử hàng đầu Việt Nam</li>
                    <li>VNPay - Cổng thanh toán được Ngân hàng Nhà nước cấp phép</li>
                    <li>ZaloPay - Ví điện tử của Zalo</li>
                    <li>Tất cả giao dịch đều được mã hóa và bảo mật</li>
                </ul>
            </div>
        </div>

        <!-- Quy định khác -->
        <div class="policies-section">
            <h2>Quy định khác</h2>
            
            <div class="policy-box">
                <h3><i class="fas fa-user-check"></i> Độ tuổi và giấy tờ</h3>
                <ul>
                    <li>Trẻ em dưới <strong>5 tuổi</strong>: Miễn phí (không có ghế riêng, ngồi cùng người lớn)</li>
                    <li>Trẻ em từ <strong>5-12 tuổi</strong>: Giá vé 75% (có ghế riêng)</li>
                    <li>Trẻ em từ <strong>12 tuổi trở lên</strong>: Giá vé người lớn</li>
                    <li>Mang theo CMND/CCCD/Hộ chiếu khi lên xe để đối chiếu</li>
                </ul>
            </div>

            <div class="policy-box">
                <h3><i class="fas fa-clock"></i> Đến bến xe</h3>
                <ul>
                    <li>Đến bến xe trước giờ khởi hành <strong>ít nhất 30 phút</strong></li>
                    <li>Xuất trình mã đặt vé hoặc CMND/CCCD khi lên xe</li>
                    <li>Nếu trễ, vé sẽ không được hoàn tiền</li>
                </ul>
            </div>

            <div class="policy-box">
                <h3><i class="fas fa-ban"></i> Hành vi bị cấm</h3>
                <ul>
                    <li>Hút thuốc trên xe</li>
                    <li>Mang theo chất cấm, vũ khí</li>
                    <li>Làm phiền hành khách khác</li>
                    <li>Phá hoại tài sản của nhà xe</li>
                    <li>Đặt vé giả hoặc gian lận</li>
                </ul>
            </div>
        </div>

        <!-- Liên hệ -->
        <div class="policies-section">
            <h2>Liên hệ hỗ trợ</h2>
            <div class="policy-info">
                <h3><i class="fas fa-headset"></i> Cần hỗ trợ?</h3>
                <p>Nếu bạn có thắc mắc về chính sách hoặc cần hỗ trợ, vui lòng liên hệ:</p>
                <ul style="margin-top: 16px;">
                    <li><strong>Hotline:</strong> 1900 123 456 (24/7)</li>
                    <li><strong>Email:</strong> support@busbooking.com</li>
                    <li><strong>Thời gian:</strong> Hỗ trợ 24/7 qua hotline, email phản hồi trong 24 giờ</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<?php include '../../includes/footer_user.php'; ?>

