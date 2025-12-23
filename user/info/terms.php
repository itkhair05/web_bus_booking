<?php
/**
 * Trang Điều khoản sử dụng
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/auth.php';

$pageTitle = 'Điều khoản sử dụng - BusBooking';
$pageDescription = 'Điều khoản và điều kiện sử dụng dịch vụ BusBooking';

include '../../includes/header_user.php';
?>

<style>
/* Terms Page Styles */
.terms-page {
    background: #fff;
    min-height: 100vh;
}

.terms-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 20px 80px;
}

.terms-breadcrumb {
    font-size: 14px;
    color: #2196F3;
    margin-bottom: 16px;
}

.terms-breadcrumb a {
    color: #2196F3;
    text-decoration: none;
}

.terms-breadcrumb a:hover {
    text-decoration: underline;
}

.terms-breadcrumb span {
    color: #666;
}

.terms-header {
    text-align: center;
    margin-bottom: 48px;
}

.terms-header h1 {
    font-size: 42px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 16px;
}

.terms-header p {
    font-size: 18px;
    color: #666;
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.6;
}

.terms-updated {
    text-align: center;
    color: #666;
    font-size: 14px;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.terms-section {
    margin-bottom: 48px;
}

.terms-section h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 3px solid #1E90FF;
}

.terms-section p {
    font-size: 16px;
    line-height: 1.8;
    color: #333;
    margin-bottom: 16px;
}

.terms-section h3 {
    font-size: 20px;
    font-weight: 600;
    color: #1a1a2e;
    margin: 32px 0 16px 0;
}

.terms-section ul {
    list-style: none;
    padding: 0;
    margin: 16px 0;
}

.terms-section ul li {
    padding-left: 28px;
    margin-bottom: 12px;
    font-size: 15px;
    color: #666;
    line-height: 1.7;
    position: relative;
}

.terms-section ul li::before {
    content: '•';
    position: absolute;
    left: 0;
    color: #1E90FF;
    font-weight: bold;
    font-size: 20px;
}

.terms-section ol {
    padding-left: 28px;
    margin: 16px 0;
}

.terms-section ol li {
    margin-bottom: 12px;
    font-size: 15px;
    color: #666;
    line-height: 1.7;
}

.terms-highlight {
    background: #f8fafc;
    border-left: 4px solid #1E90FF;
    padding: 20px 24px;
    border-radius: 0 12px 12px 0;
    margin: 24px 0;
}

.terms-highlight p {
    margin: 0;
    font-weight: 500;
    color: #1a1a2e;
}

.terms-warning {
    background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
    border-left: 4px solid #ffc107;
    padding: 20px 24px;
    border-radius: 0 12px 12px 0;
    margin: 24px 0;
}

.terms-warning p {
    margin: 0;
    color: #856404;
}

@media (max-width: 768px) {
    .terms-header h1 {
        font-size: 32px;
    }
}
</style>

<main class="terms-page">
    <div class="terms-container">
        <!-- Breadcrumb -->
        <div class="terms-breadcrumb">
            <a href="<?php echo appUrl(); ?>">Trang chủ</a> <span>/</span> Điều khoản sử dụng
        </div>

        <!-- Header -->
        <div class="terms-header">
            <h1>Điều khoản sử dụng</h1>
            <p>Vui lòng đọc kỹ các điều khoản và điều kiện sử dụng dịch vụ BusBooking trước khi sử dụng</p>
        </div>

        <div class="terms-updated">
            <p>Cập nhật lần cuối: <?php echo date('d/m/Y'); ?></p>
        </div>

        <!-- 1. Chấp nhận điều khoản -->
        <div class="terms-section">
            <h2>1. Chấp nhận điều khoản</h2>
            <p>Bằng việc truy cập và sử dụng website BusBooking, bạn đồng ý tuân thủ và bị ràng buộc bởi các điều khoản và điều kiện sử dụng được nêu trong tài liệu này. Nếu bạn không đồng ý với bất kỳ điều khoản nào, vui lòng không sử dụng dịch vụ của chúng tôi.</p>
            
            <div class="terms-highlight">
                <p>Việc bạn tiếp tục sử dụng dịch vụ sau khi các điều khoản được cập nhật được coi là bạn đã chấp nhận các thay đổi đó.</p>
            </div>
        </div>

        <!-- 2. Định nghĩa -->
        <div class="terms-section">
            <h2>2. Định nghĩa</h2>
            <p>Trong các điều khoản này, các thuật ngữ sau đây có nghĩa như sau:</p>
            <ul>
                <li><strong>"BusBooking"</strong> hoặc <strong>"Chúng tôi"</strong>: Chỉ công ty vận hành nền tảng đặt vé xe khách trực tuyến BusBooking.</li>
                <li><strong>"Người dùng"</strong> hoặc <strong>"Bạn"</strong>: Chỉ bất kỳ cá nhân hoặc tổ chức nào truy cập và sử dụng dịch vụ của BusBooking.</li>
                <li><strong>"Dịch vụ"</strong>: Chỉ các dịch vụ đặt vé xe khách, thanh toán và các dịch vụ liên quan được cung cấp trên nền tảng BusBooking.</li>
                <li><strong>"Nhà xe"</strong> hoặc <strong>"Đối tác"</strong>: Chỉ các công ty vận tải hành khách hợp tác với BusBooking.</li>
                <li><strong>"Vé"</strong>: Chỉ vé điện tử hoặc mã đặt vé được cấp sau khi đặt vé thành công.</li>
            </ul>
        </div>

        <!-- 3. Điều kiện sử dụng -->
        <div class="terms-section">
            <h2>3. Điều kiện sử dụng dịch vụ</h2>
            
            <h3>3.1. Độ tuổi</h3>
            <p>Bạn phải đủ <strong>18 tuổi</strong> trở lên hoặc có sự đồng ý của người giám hộ hợp pháp để sử dụng dịch vụ của BusBooking.</p>
            
            <h3>3.2. Thông tin tài khoản</h3>
            <p>Khi đăng ký tài khoản, bạn cam kết:</p>
            <ul>
                <li>Cung cấp thông tin chính xác, đầy đủ và cập nhật</li>
                <li>Bảo mật thông tin đăng nhập của bạn</li>
                <li>Chịu trách nhiệm về mọi hoạt động diễn ra dưới tài khoản của bạn</li>
                <li>Thông báo ngay cho chúng tôi nếu phát hiện vi phạm bảo mật</li>
            </ul>
            
            <h3>3.3. Sử dụng hợp pháp</h3>
            <p>Bạn cam kết sử dụng dịch vụ một cách hợp pháp và không:</p>
            <ul>
                <li>Sử dụng dịch vụ cho mục đích bất hợp pháp hoặc gian lận</li>
                <li>Đặt vé giả hoặc sử dụng thông tin không chính xác</li>
                <li>Can thiệp, phá hoại hoặc làm gián đoạn hoạt động của hệ thống</li>
                <li>Sử dụng robot, bot hoặc công cụ tự động để truy cập dịch vụ</li>
                <li>Copy, sao chép hoặc phân phối nội dung của website mà không có sự cho phép</li>
            </ul>
        </div>

        <!-- 4. Đặt vé và thanh toán -->
        <div class="terms-section">
            <h2>4. Đặt vé và thanh toán</h2>
            
            <h3>4.1. Quy trình đặt vé</h3>
            <p>Khi đặt vé trên BusBooking, bạn đồng ý:</p>
            <ul>
                <li>Cung cấp thông tin chính xác về hành khách</li>
                <li>Thanh toán đầy đủ số tiền theo giá vé đã công bố</li>
                <li>Chấp nhận các điều kiện và quy định của nhà xe</li>
                <li>Tuân thủ các quy định về hủy và đổi vé</li>
            </ul>
            
            <h3>4.2. Giá vé</h3>
            <p>Giá vé được hiển thị trên website là giá cuối cùng bao gồm thuế và phí dịch vụ (nếu có). BusBooking có quyền thay đổi giá vé bất cứ lúc nào mà không cần thông báo trước, tuy nhiên giá vé đã thanh toán sẽ không bị thay đổi.</p>
            
            <h3>4.3. Thanh toán</h3>
            <p>Bạn có thể thanh toán bằng các phương thức được BusBooking hỗ trợ. Việc thanh toán phải được hoàn tất trong thời gian quy định, nếu không vé sẽ tự động bị hủy.</p>
            
            <div class="terms-warning">
                <p><strong>Lưu ý:</strong> Sau khi thanh toán thành công, bạn không thể hủy giao dịch trực tiếp. Việc hủy vé phải tuân theo chính sách hủy vé của nhà xe.</p>
            </div>
        </div>

        <!-- 5. Hủy và đổi vé -->
        <div class="terms-section">
            <h2>5. Hủy và đổi vé</h2>
            <p>Chính sách hủy và đổi vé được quy định chi tiết trong <a href="<?php echo appUrl('user/info/policies.php'); ?>" style="color: #1E90FF; font-weight: 600;">Chính sách & Quy định</a>. Tuy nhiên, một số điểm quan trọng:</p>
            <ul>
                <li>Bạn có thể hủy vé trước giờ khởi hành tối thiểu 24 giờ</li>
                <li>Phí hủy vé và thời gian hoàn tiền tùy thuộc vào thời điểm hủy</li>
                <li>Một số nhà xe có chính sách riêng, bạn cần xem chi tiết trước khi đặt</li>
                <li>Vé không thể hủy sau khi xe đã khởi hành (trừ trường hợp đặc biệt)</li>
            </ul>
        </div>

        <!-- 6. Trách nhiệm -->
        <div class="terms-section">
            <h2>6. Trách nhiệm và giới hạn trách nhiệm</h2>
            
            <h3>6.1. Trách nhiệm của BusBooking</h3>
            <p>BusBooking cam kết:</p>
            <ul>
                <li>Cung cấp nền tảng đặt vé ổn định và an toàn</li>
                <li>Xử lý thanh toán một cách an toàn và chính xác</li>
                <li>Hỗ trợ khách hàng trong quá trình đặt vé và sử dụng dịch vụ</li>
                <li>Bảo vệ thông tin cá nhân của khách hàng</li>
            </ul>
            
            <h3>6.2. Trách nhiệm của Nhà xe</h3>
            <p>Nhà xe chịu trách nhiệm:</p>
            <ul>
                <li>Cung cấp dịch vụ vận chuyển đúng như đã cam kết</li>
                <li>Đảm bảo an toàn cho hành khách</li>
                <li>Thông báo kịp thời về các thay đổi lịch trình</li>
                <li>Xử lý các khiếu nại liên quan đến dịch vụ vận chuyển</li>
            </ul>
            
            <h3>6.3. Giới hạn trách nhiệm</h3>
            <p>BusBooking không chịu trách nhiệm về:</p>
            <ul>
                <li>Chất lượng dịch vụ vận chuyển của nhà xe</li>
                <li>Thay đổi lịch trình do nhà xe quyết định</li>
                <li>Thiệt hại phát sinh do hành khách trễ chuyến</li>
                <li>Thiệt hại về tài sản hoặc tính mạng do lỗi của nhà xe</li>
                <li>Gián đoạn dịch vụ do lỗi kỹ thuật hoặc lỗi từ bên thứ ba</li>
            </ul>
        </div>

        <!-- 7. Bảo mật thông tin -->
        <div class="terms-section">
            <h2>7. Bảo mật thông tin</h2>
            <p>BusBooking cam kết bảo vệ thông tin cá nhân của bạn theo <a href="<?php echo appUrl('user/info/policies.php'); ?>" style="color: #1E90FF; font-weight: 600;">Chính sách bảo mật</a>. Tuy nhiên:</p>
            <ul>
                <li>Bạn có trách nhiệm bảo mật thông tin đăng nhập của mình</li>
                <li>BusBooking không chịu trách nhiệm nếu bạn tiết lộ thông tin đăng nhập cho người khác</li>
                <li>Chúng tôi có thể chia sẻ thông tin với nhà xe để cung cấp dịch vụ</li>
                <li>Thông tin có thể được chia sẻ theo yêu cầu pháp luật</li>
            </ul>
        </div>

        <!-- 8. Sở hữu trí tuệ -->
        <div class="terms-section">
            <h2>8. Sở hữu trí tuệ</h2>
            <p>Tất cả nội dung trên website BusBooking, bao gồm nhưng không giới hạn: logo, văn bản, hình ảnh, đồ họa, phần mềm, đều thuộc quyền sở hữu của BusBooking hoặc các bên cấp phép.</p>
            <p>Bạn không được phép:</p>
            <ul>
                <li>Sao chép, sửa đổi, phân phối hoặc sử dụng nội dung mà không có sự cho phép</li>
                <li>Sử dụng logo hoặc thương hiệu của BusBooking cho mục đích thương mại</li>
                <li>Reverse engineer hoặc decompile phần mềm của BusBooking</li>
            </ul>
        </div>

        <!-- 9. Chấm dứt sử dụng -->
        <div class="terms-section">
            <h2>9. Chấm dứt sử dụng dịch vụ</h2>
            <p>BusBooking có quyền chấm dứt hoặc tạm ngưng quyền truy cập của bạn nếu:</p>
            <ul>
                <li>Bạn vi phạm các điều khoản sử dụng</li>
                <li>Bạn sử dụng dịch vụ cho mục đích bất hợp pháp</li>
                <li>Bạn có hành vi gian lận hoặc lừa đảo</li>
                <li>Theo yêu cầu của cơ quan pháp luật</li>
            </ul>
            <p>Khi tài khoản bị chấm dứt, bạn sẽ không thể truy cập dịch vụ và các vé đã đặt có thể bị hủy.</p>
        </div>

        <!-- 10. Thay đổi điều khoản -->
        <div class="terms-section">
            <h2>10. Thay đổi điều khoản</h2>
            <p>BusBooking có quyền thay đổi, sửa đổi hoặc cập nhật các điều khoản này bất cứ lúc nào. Các thay đổi sẽ có hiệu lực ngay sau khi được đăng tải trên website.</p>
            <p>Bạn có trách nhiệm thường xuyên kiểm tra các điều khoản để cập nhật các thay đổi. Việc bạn tiếp tục sử dụng dịch vụ sau khi điều khoản được cập nhật được coi là bạn đã chấp nhận các thay đổi đó.</p>
        </div>

        <!-- 11. Luật áp dụng -->
        <div class="terms-section">
            <h2>11. Luật áp dụng và giải quyết tranh chấp</h2>
            <p>Các điều khoản này được điều chỉnh bởi pháp luật Việt Nam. Mọi tranh chấp phát sinh sẽ được giải quyết thông qua:</p>
            <ol>
                <li><strong>Thương lượng:</strong> Các bên sẽ cố gắng giải quyết tranh chấp thông qua thương lượng hòa bình.</li>
                <li><strong>Hòa giải:</strong> Nếu thương lượng không thành công, các bên có thể yêu cầu hòa giải.</li>
                <li><strong>Tòa án:</strong> Nếu hòa giải không thành công, tranh chấp sẽ được giải quyết tại Tòa án có thẩm quyền tại Việt Nam.</li>
            </ol>
        </div>

        <!-- 12. Liên hệ -->
        <div class="terms-section">
            <h2>12. Liên hệ</h2>
            <p>Nếu bạn có bất kỳ câu hỏi nào về các điều khoản này, vui lòng liên hệ với chúng tôi:</p>
            <ul>
                <li><strong>Hotline:</strong> 1900 123 456</li>
                <li><strong>Email:</strong> support@busbooking.com</li>
                <li><strong>Địa chỉ:</strong> 123 Đường ABC, Phường XYZ, Quận 1, TP. Hồ Chí Minh</li>
            </ul>
        </div>

        <!-- Acceptance -->
        <div class="terms-highlight" style="margin-top: 48px;">
            <p style="text-align: center; font-size: 18px;">
                Bằng việc sử dụng dịch vụ BusBooking, bạn xác nhận rằng bạn đã đọc, hiểu và đồng ý tuân thủ tất cả các điều khoản và điều kiện được nêu trong tài liệu này.
            </p>
        </div>
    </div>
</main>

<?php include '../../includes/footer_user.php'; ?>

