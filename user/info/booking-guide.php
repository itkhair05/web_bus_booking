<?php
/**
 * Trang Hướng dẫn đặt vé
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/auth.php';

$pageTitle = 'Hướng dẫn đặt vé - BusBooking';
$pageDescription = 'Hướng dẫn chi tiết cách đặt vé xe khách trực tuyến trên BusBooking';

include '../../includes/header_user.php';
?>

<style>
/* Booking Guide Page Styles */
.guide-page {
    background: #fff;
    min-height: 100vh;
}

.guide-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 20px 80px;
}

.guide-breadcrumb {
    font-size: 14px;
    color: #2196F3;
    margin-bottom: 16px;
}

.guide-breadcrumb a {
    color: #2196F3;
    text-decoration: none;
}

.guide-breadcrumb a:hover {
    text-decoration: underline;
}

.guide-breadcrumb span {
    color: #666;
}

.guide-header {
    text-align: center;
    margin-bottom: 48px;
}

.guide-header h1 {
    font-size: 42px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 16px;
}

.guide-header p {
    font-size: 18px;
    color: #666;
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.6;
}

.guide-section {
    margin-bottom: 48px;
}

.guide-section h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 3px solid #1E90FF;
}

.guide-section p {
    font-size: 16px;
    line-height: 1.8;
    color: #333;
    margin-bottom: 16px;
}

.guide-steps {
    margin: 32px 0;
}

.guide-step {
    display: flex;
    gap: 24px;
    margin-bottom: 32px;
    align-items: flex-start;
    padding: 24px;
    background: #f8fafc;
    border-radius: 12px;
    border-left: 4px solid #1E90FF;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.guide-step:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.guide-step:last-child {
    margin-bottom: 0;
}

.guide-step-number {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #1E90FF 0%, #0d6efd 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    font-weight: 700;
    flex-shrink: 0;
}

.guide-step-content h3 {
    font-size: 20px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 12px;
}

.guide-step-content p {
    font-size: 15px;
    color: #666;
    line-height: 1.7;
    margin-bottom: 12px;
}

.guide-step-content ul {
    list-style: none;
    padding: 0;
    margin: 12px 0 0 0;
}

.guide-step-content ul li {
    padding-left: 24px;
    margin-bottom: 8px;
    font-size: 14px;
    color: #666;
    position: relative;
    line-height: 1.6;
}

.guide-step-content ul li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: #1E90FF;
    font-weight: bold;
}

.guide-tips {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-left: 4px solid #1E90FF;
    padding: 24px;
    border-radius: 0 12px 12px 0;
    margin: 32px 0;
}

.guide-tips h3 {
    font-size: 20px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.guide-tips h3 i {
    color: #1E90FF;
}

.guide-tips ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.guide-tips ul li {
    padding-left: 28px;
    margin-bottom: 12px;
    font-size: 15px;
    color: #333;
    line-height: 1.7;
    position: relative;
}

.guide-tips ul li::before {
    content: '💡';
    position: absolute;
    left: 0;
}

.guide-warning {
    background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
    border-left: 4px solid #ffc107;
    padding: 24px;
    border-radius: 0 12px 12px 0;
    margin: 32px 0;
}

.guide-warning h3 {
    font-size: 20px;
    font-weight: 600;
    color: #856404;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.guide-warning h3 i {
    color: #ffc107;
}

.guide-warning p {
    color: #856404;
    margin: 0;
}

.guide-cta {
    background: linear-gradient(135deg, #1E90FF 0%, #0d6efd 100%);
    border-radius: 16px;
    padding: 48px 40px;
    color: white;
    text-align: center;
    margin-top: 48px;
}

.guide-cta h2 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 16px;
}

.guide-cta p {
    font-size: 18px;
    margin-bottom: 32px;
    opacity: 0.95;
}

.btn-start-booking {
    background: white;
    color: #1E90FF;
    padding: 14px 32px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 18px;
    display: inline-block;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.btn-start-booking:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    color: #1E90FF;
}

@media (max-width: 768px) {
    .guide-header h1 {
        font-size: 32px;
    }
    
    .guide-step {
        flex-direction: column;
        text-align: center;
    }
    
    .guide-step-number {
        margin: 0 auto;
    }
}
</style>

<main class="guide-page">
    <div class="guide-container">
        <!-- Breadcrumb -->
        <div class="guide-breadcrumb">
            <a href="<?php echo appUrl(); ?>">Trang chủ</a> <span>/</span> Hướng dẫn đặt vé
        </div>

        <!-- Header -->
        <div class="guide-header">
            <h1>Hướng dẫn đặt vé</h1>
            <p>Hướng dẫn chi tiết từng bước để đặt vé xe khách trực tuyến trên BusBooking một cách nhanh chóng và dễ dàng.</p>
        </div>

        <!-- Giới thiệu -->
        <div class="guide-section">
            <h2>Tổng quan</h2>
            <p>Đặt vé xe khách trên BusBooking rất đơn giản và nhanh chóng. Chỉ với vài bước, bạn đã có thể sở hữu vé xe cho chuyến đi của mình. Quy trình đặt vé bao gồm: Tìm kiếm chuyến xe → Chọn ghế → Điền thông tin → Thanh toán → Nhận vé.</p>
        </div>

        <!-- Các bước đặt vé -->
        <div class="guide-section">
            <h2>Các bước đặt vé</h2>
            
            <div class="guide-steps">
                <div class="guide-step">
                    <div class="guide-step-number">1</div>
                    <div class="guide-step-content">
                        <h3>Tìm kiếm chuyến xe</h3>
                        <p>Trên trang chủ, nhập thông tin:</p>
                        <ul>
                            <li><strong>Điểm đi:</strong> Chọn điểm xuất phát của bạn</li>
                            <li><strong>Điểm đến:</strong> Chọn điểm đến của bạn</li>
                            <li><strong>Ngày đi:</strong> Chọn ngày bạn muốn khởi hành</li>
                            <li><strong>Số lượng khách:</strong> Chọn số lượng vé cần đặt</li>
                        </ul>
                        <p>Sau đó nhấn nút <strong>"Tìm chuyến"</strong> để xem danh sách các chuyến xe khả dụng.</p>
                    </div>
                </div>

                <div class="guide-step">
                    <div class="guide-step-number">2</div>
                    <div class="guide-step-content">
                        <h3>Chọn chuyến xe phù hợp</h3>
                        <p>Xem danh sách các chuyến xe và so sánh:</p>
                        <ul>
                            <li><strong>Giờ khởi hành:</strong> Chọn giờ phù hợp với lịch trình của bạn</li>
                            <li><strong>Giá vé:</strong> So sánh giá giữa các nhà xe</li>
                            <li><strong>Loại xe:</strong> Xe giường nằm, ghế ngồi, limousine...</li>
                            <li><strong>Tiện ích:</strong> WiFi, điều hòa, nước uống miễn phí...</li>
                            <li><strong>Đánh giá:</strong> Xem đánh giá từ hành khách đã đi</li>
                        </ul>
                        <p>Nhấn nút <strong>"Chọn chuyến"</strong> để tiếp tục.</p>
                    </div>
                </div>

                <div class="guide-step">
                    <div class="guide-step-number">3</div>
                    <div class="guide-step-content">
                        <h3>Chọn ghế ngồi</h3>
                        <p>Trên sơ đồ ghế, bạn sẽ thấy:</p>
                        <ul>
                            <li><strong>Ghế trống:</strong> Màu xanh lá - Có thể chọn</li>
                            <li><strong>Ghế đã đặt:</strong> Màu xám - Không thể chọn</li>
                            <li><strong>Ghế bạn chọn:</strong> Màu xanh dương - Đang được chọn</li>
                        </ul>
                        <p>Click vào các ghế bạn muốn đặt. Bạn có thể chọn nhiều ghế cùng lúc. Sau khi chọn xong, nhấn <strong>"Tiếp tục"</strong>.</p>
                    </div>
                </div>

                <div class="guide-step">
                    <div class="guide-step-number">4</div>
                    <div class="guide-step-content">
                        <h3>Chọn điểm đón/trả</h3>
                        <p>Nếu chuyến xe có nhiều điểm đón/trả, bạn sẽ được yêu cầu chọn:</p>
                        <ul>
                            <li><strong>Điểm đón:</strong> Nơi bạn muốn lên xe</li>
                            <li><strong>Điểm trả:</strong> Nơi bạn muốn xuống xe</li>
                        </ul>
                        <p>Giá vé có thể thay đổi tùy theo điểm đón/trả bạn chọn.</p>
                    </div>
                </div>

                <div class="guide-step">
                    <div class="guide-step-number">5</div>
                    <div class="guide-step-content">
                        <h3>Điền thông tin hành khách</h3>
                        <p>Nhập đầy đủ thông tin:</p>
                        <ul>
                            <li><strong>Họ và tên:</strong> Tên đầy đủ của hành khách (phải khớp với CMND/CCCD)</li>
                            <li><strong>Số điện thoại:</strong> Số điện thoại để nhận thông báo</li>
                            <li><strong>Email:</strong> Email để nhận vé điện tử</li>
                            <li><strong>Bảo hiểm:</strong> Tùy chọn mua bảo hiểm du lịch (nếu có)</li>
                        </ul>
                        <p><strong>Lưu ý:</strong> Nếu bạn chưa đăng nhập, hệ thống sẽ yêu cầu đăng nhập hoặc đăng ký tài khoản để tiếp tục.</p>
                    </div>
                </div>

                <div class="guide-step">
                    <div class="guide-step-number">6</div>
                    <div class="guide-step-content">
                        <h3>Chọn phương thức thanh toán</h3>
                        <p>BusBooking hỗ trợ nhiều phương thức thanh toán:</p>
                        <ul>
                            <li><strong>MoMo:</strong> Thanh toán qua ví điện tử MoMo</li>
                            <li><strong>VNPay:</strong> Thanh toán qua cổng VNPay (thẻ ngân hàng, Internet Banking)</li>
                            <li><strong>ZaloPay:</strong> Thanh toán qua ví ZaloPay</li>
                            <li><strong>Thanh toán khi lên xe:</strong> Thanh toán bằng tiền mặt khi lên xe</li>
                        </ul>
                        <p>Chọn phương thức thanh toán phù hợp và làm theo hướng dẫn trên màn hình.</p>
                    </div>
                </div>

                <div class="guide-step">
                    <div class="guide-step-number">7</div>
                    <div class="guide-step-content">
                        <h3>Xác nhận và nhận vé</h3>
                        <p>Sau khi thanh toán thành công:</p>
                        <ul>
                            <li>Bạn sẽ nhận được <strong>mã đặt vé</strong> qua email và SMS</li>
                            <li>Vé điện tử sẽ được gửi đến email của bạn</li>
                            <li>Bạn có thể xem vé trong mục <strong>"Vé của tôi"</strong> trên website</li>
                        </ul>
                        <p><strong>Lưu ý:</strong> Hãy lưu lại mã đặt vé và mang theo khi lên xe. Bạn có thể xuất vé điện tử hoặc hiển thị mã QR code trên điện thoại.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mẹo hữu ích -->
        <div class="guide-section">
            <h2>Mẹo hữu ích khi đặt vé</h2>
            
            <div class="guide-tips">
                <h3><i class="fas fa-lightbulb"></i> Mẹo đặt vé</h3>
                <ul>
                    <li><strong>Đặt vé sớm:</strong> Đặt vé trước 1-2 tuần thường có giá tốt hơn và nhiều lựa chọn ghế hơn.</li>
                    <li><strong>So sánh giá:</strong> Kiểm tra giá của nhiều nhà xe khác nhau để tìm được giá tốt nhất.</li>
                    <li><strong>Chọn giờ khởi hành:</strong> Chuyến sáng sớm hoặc đêm muộn thường có giá rẻ hơn.</li>
                    <li><strong>Đọc đánh giá:</strong> Xem đánh giá từ hành khách trước đó để chọn nhà xe uy tín.</li>
                    <li><strong>Kiểm tra tiện ích:</strong> Xem kỹ các tiện ích đi kèm để đảm bảo phù hợp với nhu cầu.</li>
                    <li><strong>Lưu thông tin:</strong> Lưu mã đặt vé và thông tin liên hệ nhà xe để tiện tra cứu sau này.</li>
                </ul>
            </div>
        </div>

        <!-- Lưu ý quan trọng -->
        <div class="guide-section">
            <h2>Lưu ý quan trọng</h2>
            
            <div class="guide-warning">
                <h3><i class="fas fa-exclamation-triangle"></i> Những điều cần lưu ý</h3>
                <p><strong>Thời gian đặt vé:</strong> Vé sẽ được giữ trong 15 phút sau khi bạn chọn ghế. Sau thời gian này, nếu chưa thanh toán, ghế sẽ được giải phóng.</p>
                <p><strong>Thông tin chính xác:</strong> Vui lòng điền đúng thông tin hành khách. Thông tin sai có thể gây khó khăn khi lên xe.</p>
                <p><strong>Thanh toán:</strong> Sau khi thanh toán, vui lòng đợi vài phút để hệ thống xử lý. Nếu có vấn đề, liên hệ hotline để được hỗ trợ.</p>
                <p><strong>Hủy/Đổi vé:</strong> Xem chính sách hủy và đổi vé của từng nhà xe trước khi đặt. Một số nhà xe có thể không cho phép hủy hoặc đổi vé.</p>
            </div>
        </div>

        <!-- Câu hỏi thường gặp -->
        <div class="guide-section">
            <h2>Câu hỏi thường gặp</h2>
            
            <div class="guide-steps">
                <div class="guide-step">
                    <div class="guide-step-content">
                        <h3>Tôi có cần đăng ký tài khoản để đặt vé không?</h3>
                        <p>Có, bạn cần đăng ký và đăng nhập tài khoản để đặt vé. Tài khoản giúp bạn quản lý vé dễ dàng hơn và nhận các ưu đãi đặc biệt.</p>
                    </div>
                </div>

                <div class="guide-step">
                    <div class="guide-step-content">
                        <h3>Tôi có thể đặt vé cho người khác không?</h3>
                        <p>Có, bạn có thể đặt vé cho người khác. Chỉ cần điền đúng thông tin của người sẽ đi xe vào form đặt vé.</p>
                    </div>
                </div>

                <div class="guide-step">
                    <div class="guide-step-content">
                        <h3>Làm sao để biết vé đã đặt thành công?</h3>
                        <p>Sau khi thanh toán thành công, bạn sẽ nhận được email xác nhận kèm mã đặt vé. Bạn cũng có thể kiểm tra trong mục "Vé của tôi" trên website.</p>
                    </div>
                </div>

                <div class="guide-step">
                    <div class="guide-step-content">
                        <h3>Tôi có thể hủy hoặc đổi vé không?</h3>
                        <p>Tùy thuộc vào chính sách của từng nhà xe. Một số nhà xe cho phép hủy/đổi vé trước giờ khởi hành 24 giờ. Vui lòng xem chi tiết trong <a href="<?php echo appUrl('user/info/policies.php'); ?>" style="color: #1E90FF; font-weight: 600;">Chính sách & Quy định</a>.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="guide-cta">
            <h2>Sẵn sàng đặt vé ngay?</h2>
            <p>Bắt đầu tìm kiếm chuyến xe phù hợp cho chuyến đi của bạn</p>
            <a href="<?php echo appUrl('user/search/index.php'); ?>" class="btn-start-booking">
                <i class="fas fa-search"></i> Tìm chuyến xe ngay
            </a>
        </div>
    </div>
</main>

<?php include '../../includes/footer_user.php'; ?>

