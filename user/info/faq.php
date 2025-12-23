<?php
/**
 * Trang Câu hỏi thường gặp (FAQ)
 */

require_once '../../config/session.php';
require_once '../../config/constants.php';
require_once '../../core/helpers.php';
require_once '../../core/csrf.php';
require_once '../../core/auth.php';

$pageTitle = 'Câu hỏi thường gặp - BusBooking';
$pageDescription = 'Tổng hợp các câu hỏi thường gặp về đặt vé, thanh toán, hủy vé và các dịch vụ khác';

include '../../includes/header_user.php';
?>

<style>
/* FAQ Page Styles */
.faq-page {
    background: #fff;
    min-height: 100vh;
}

.faq-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 20px 80px;
}

.faq-breadcrumb {
    font-size: 14px;
    color: #2196F3;
    margin-bottom: 16px;
}

.faq-breadcrumb a {
    color: #2196F3;
    text-decoration: none;
}

.faq-breadcrumb a:hover {
    text-decoration: underline;
}

.faq-breadcrumb span {
    color: #666;
}

.faq-header {
    text-align: center;
    margin-bottom: 48px;
}

.faq-header h1 {
    font-size: 42px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 16px;
}

.faq-header p {
    font-size: 18px;
    color: #666;
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.6;
}

.faq-search {
    max-width: 600px;
    margin: 0 auto 48px;
    position: relative;
}

.faq-search input {
    width: 100%;
    padding: 16px 50px 16px 20px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.faq-search input:focus {
    outline: none;
    border-color: #1E90FF;
    box-shadow: 0 0 0 3px rgba(30, 144, 255, 0.1);
}

.faq-search i {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    font-size: 20px;
}

.faq-categories {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 40px;
}

.faq-category-btn {
    padding: 10px 20px;
    background: #f8fafc;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    color: #666;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.faq-category-btn:hover,
.faq-category-btn.active {
    background: linear-gradient(135deg, #1E90FF 0%, #0d6efd 100%);
    border-color: #1E90FF;
    color: white;
}

.faq-section {
    margin-bottom: 48px;
}

.faq-section h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 3px solid #1E90FF;
}

.faq-item {
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.faq-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.faq-question {
    padding: 20px 24px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 16px;
    font-weight: 600;
    color: #1a1a2e;
    user-select: none;
}

.faq-question:hover {
    background: #f0f4f8;
}

.faq-question i {
    color: #1E90FF;
    transition: transform 0.3s ease;
    font-size: 14px;
}

.faq-item.active .faq-question i {
    transform: rotate(180deg);
}

.faq-answer {
    padding: 0 24px;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease, padding 0.3s ease;
    font-size: 15px;
    color: #666;
    line-height: 1.7;
}

.faq-item.active .faq-answer {
    max-height: 500px;
    padding: 0 24px 20px 24px;
}

.faq-answer p {
    margin-bottom: 12px;
}

.faq-answer ul {
    list-style: none;
    padding: 0;
    margin: 12px 0;
}

.faq-answer ul li {
    padding-left: 24px;
    margin-bottom: 8px;
    position: relative;
}

.faq-answer ul li::before {
    content: '•';
    position: absolute;
    left: 0;
    color: #1E90FF;
    font-weight: bold;
    font-size: 20px;
}

.faq-contact {
    background: linear-gradient(135deg, #1E90FF 0%, #0d6efd 100%);
    border-radius: 16px;
    padding: 48px 40px;
    color: white;
    text-align: center;
    margin-top: 48px;
}

.faq-contact h2 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 16px;
    border: none;
    padding: 0;
    color: white;
}

.faq-contact p {
    font-size: 18px;
    margin-bottom: 32px;
    opacity: 0.95;
}

.btn-contact {
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

.btn-contact:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    color: #1E90FF;
}

@media (max-width: 768px) {
    .faq-header h1 {
        font-size: 32px;
    }
    
    .faq-categories {
        justify-content: flex-start;
    }
}
</style>

<main class="faq-page">
    <div class="faq-container">
        <!-- Breadcrumb -->
        <div class="faq-breadcrumb">
            <a href="<?php echo appUrl(); ?>">Trang chủ</a> <span>/</span> Câu hỏi thường gặp
        </div>

        <!-- Header -->
        <div class="faq-header">
            <h1>Câu hỏi thường gặp</h1>
            <p>Tìm câu trả lời cho các câu hỏi phổ biến về đặt vé, thanh toán, hủy vé và các dịch vụ khác</p>
        </div>

        <!-- Search -->
        <div class="faq-search">
            <input type="text" id="faqSearch" placeholder="Tìm kiếm câu hỏi...">
            <i class="fas fa-search"></i>
        </div>

        <!-- Categories -->
        <div class="faq-categories">
            <button class="faq-category-btn active" data-category="all">Tất cả</button>
            <button class="faq-category-btn" data-category="booking">Đặt vé</button>
            <button class="faq-category-btn" data-category="payment">Thanh toán</button>
            <button class="faq-category-btn" data-category="cancel">Hủy/Đổi vé</button>
            <button class="faq-category-btn" data-category="ticket">Vé điện tử</button>
            <button class="faq-category-btn" data-category="other">Khác</button>
        </div>

        <!-- Đặt vé -->
        <div class="faq-section" data-category="booking">
            <h2>Đặt vé</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    <span>Tôi có cần đăng ký tài khoản để đặt vé không?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Có, bạn cần đăng ký và đăng nhập tài khoản để đặt vé. Tài khoản giúp bạn:</p>
                    <ul>
                        <li>Quản lý vé đã đặt dễ dàng</li>
                        <li>Nhận thông báo về chuyến xe</li>
                        <li>Hưởng các ưu đãi đặc biệt</li>
                        <li>Lưu thông tin để đặt vé nhanh hơn</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Tôi có thể đặt vé cho người khác không?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Có, bạn hoàn toàn có thể đặt vé cho người khác. Chỉ cần điền đúng thông tin của người sẽ đi xe vào form đặt vé. Lưu ý:</p>
                    <ul>
                        <li>Thông tin phải khớp với CMND/CCCD</li>
                        <li>Người đi xe cần mang theo giấy tờ tùy thân khi lên xe</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Tôi có thể đặt bao nhiêu vé một lần?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Bạn có thể đặt tối đa <strong>9 vé</strong> trong một lần đặt. Nếu cần đặt nhiều hơn, vui lòng thực hiện nhiều lần đặt hoặc liên hệ hotline để được hỗ trợ.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Làm sao để biết vé đã đặt thành công?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Sau khi thanh toán thành công, bạn sẽ nhận được:</p>
                    <ul>
                        <li>Email xác nhận kèm mã đặt vé</li>
                        <li>SMS thông báo (nếu có số điện thoại)</li>
                        <li>Vé điện tử trong mục "Vé của tôi" trên website</li>
                    </ul>
                    <p>Nếu không nhận được email, vui lòng kiểm tra thư mục Spam hoặc liên hệ hotline.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Tôi có thể chọn ghế ngồi không?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Có, bạn có thể chọn ghế ngồi trên sơ đồ ghế. Hệ thống sẽ hiển thị:</p>
                    <ul>
                        <li>Ghế trống (màu xanh lá) - Có thể chọn</li>
                        <li>Ghế đã đặt (màu xám) - Không thể chọn</li>
                        <li>Ghế bạn chọn (màu xanh dương) - Đang được chọn</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Thanh toán -->
        <div class="faq-section" data-category="payment">
            <h2>Thanh toán</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    <span>BusBooking hỗ trợ những phương thức thanh toán nào?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>BusBooking hỗ trợ các phương thức thanh toán sau:</p>
                    <ul>
                        <li><strong>MoMo:</strong> Thanh toán qua ví điện tử MoMo</li>
                        <li><strong>VNPay:</strong> Thanh toán qua cổng VNPay (thẻ ngân hàng, Internet Banking)</li>
                        <li><strong>ZaloPay:</strong> Thanh toán qua ví ZaloPay</li>
                        <li><strong>Thanh toán khi lên xe:</strong> Thanh toán bằng tiền mặt khi lên xe</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Thanh toán có an toàn không?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Rất an toàn. BusBooking sử dụng:</p>
                    <ul>
                        <li>Công nghệ mã hóa SSL/TLS để bảo vệ thông tin</li>
                        <li>Các cổng thanh toán uy tín được Ngân hàng Nhà nước cấp phép</li>
                        <li>Không lưu trữ thông tin thẻ ngân hàng của khách hàng</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Tôi đã thanh toán nhưng chưa nhận được vé, phải làm sao?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Trong một số trường hợp, hệ thống cần vài phút để xử lý. Nếu sau <strong>15 phút</strong> bạn vẫn chưa nhận được vé:</p>
                    <ul>
                        <li>Kiểm tra email (kể cả thư mục Spam)</li>
                        <li>Kiểm tra mục "Vé của tôi" trên website</li>
                        <li>Liên hệ hotline <strong>1900 123 456</strong> với mã đặt vé hoặc số điện thoại đã dùng để đặt vé</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Tôi có thể hủy thanh toán không?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Sau khi thanh toán thành công, bạn không thể hủy giao dịch trực tiếp. Tuy nhiên, bạn có thể:</p>
                    <ul>
                        <li>Hủy vé theo chính sách hủy vé của nhà xe</li>
                        <li>Được hoàn tiền theo quy định (xem <a href="<?php echo appUrl('user/info/policies.php'); ?>" style="color: #1E90FF;">Chính sách hủy vé</a>)</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Hủy/Đổi vé -->
        <div class="faq-section" data-category="cancel">
            <h2>Hủy/Đổi vé</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    <span>Tôi có thể hủy vé không?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Có, bạn có thể hủy vé trước giờ khởi hành tối thiểu <strong>24 giờ</strong>. Phí hủy vé:</p>
                    <ul>
                        <li>Hủy trước 48 giờ: Hoàn tiền 100% (trừ phí dịch vụ 5%)</li>
                        <li>Hủy trước 24-48 giờ: Hoàn tiền 80% (trừ phí dịch vụ 5%)</li>
                        <li>Hủy trong vòng 24 giờ: Hoàn tiền 50% (trừ phí dịch vụ 5%)</li>
                        <li>Hủy sau khi xe khởi hành: Không được hoàn tiền</li>
                    </ul>
                    <p><strong>Lưu ý:</strong> Một số nhà xe có chính sách riêng, vui lòng xem chi tiết trước khi đặt.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Tôi có thể đổi vé không?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Có, bạn có thể đổi vé với các điều kiện:</p>
                    <ul>
                        <li>Đổi vé trước giờ khởi hành tối thiểu 24 giờ</li>
                        <li>Chuyến xe mới còn ghế trống</li>
                        <li>Chỉ được đổi 1 lần cho mỗi vé</li>
                        <li>Phí đổi vé: 50.000 VNĐ/vé</li>
                    </ul>
                    <p>Để đổi vé, vui lòng liên hệ hotline hoặc vào mục "Vé của tôi" trên website.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Thời gian hoàn tiền là bao lâu?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Thời gian hoàn tiền tùy thuộc vào phương thức thanh toán:</p>
                    <ul>
                        <li>MoMo / ZaloPay: 3-5 ngày làm việc</li>
                        <li>VNPay (Thẻ ngân hàng): 5-7 ngày làm việc</li>
                        <li>Internet Banking: 5-7 ngày làm việc</li>
                    </ul>
                    <p>Tiền sẽ được chuyển về tài khoản/ngân hàng bạn đã sử dụng để thanh toán.</p>
                </div>
            </div>
        </div>

        <!-- Vé điện tử -->
        <div class="faq-section" data-category="ticket">
            <h2>Vé điện tử</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    <span>Tôi cần mang gì khi lên xe?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Khi lên xe, bạn cần mang theo:</p>
                    <ul>
                        <li><strong>Mã đặt vé</strong> hoặc vé điện tử (hiển thị trên điện thoại)</li>
                        <li><strong>CMND/CCCD/Hộ chiếu</strong> để đối chiếu thông tin</li>
                    </ul>
                    <p>Bạn không cần in vé, chỉ cần hiển thị mã QR code hoặc mã đặt vé trên điện thoại.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Tôi có thể xem vé ở đâu?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Bạn có thể xem vé tại:</p>
                    <ul>
                        <li>Email xác nhận đặt vé</li>
                        <li>Mục "Vé của tôi" trên website (sau khi đăng nhập)</li>
                        <li>Ứng dụng BusBooking (nếu có)</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Tôi quên mã đặt vé, phải làm sao?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Bạn có thể:</p>
                    <ul>
                        <li>Kiểm tra email đã dùng để đặt vé</li>
                        <li>Đăng nhập vào tài khoản và xem trong mục "Vé của tôi"</li>
                        <li>Liên hệ hotline <strong>1900 123 456</strong> với số điện thoại đã đăng ký để được hỗ trợ</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Khác -->
        <div class="faq-section" data-category="other">
            <h2>Khác</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    <span>Trẻ em có được miễn phí không?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Chính sách giá vé cho trẻ em:</p>
                    <ul>
                        <li>Dưới <strong>5 tuổi</strong>: Miễn phí (không có ghế riêng, ngồi cùng người lớn)</li>
                        <li>Từ <strong>5-12 tuổi</strong>: Giá vé 75% (có ghế riêng)</li>
                        <li>Từ <strong>12 tuổi trở lên</strong>: Giá vé người lớn</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Tôi nên đến bến xe trước bao lâu?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Bạn nên đến bến xe trước giờ khởi hành <strong>ít nhất 30 phút</strong> để:</p>
                    <ul>
                        <li>Làm thủ tục lên xe</li>
                        <li>Gửi hành lý ký gửi (nếu có)</li>
                        <li>Tránh trễ chuyến</li>
                    </ul>
                    <p>Nếu bạn trễ, vé sẽ không được hoàn tiền.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Tôi có thể mang bao nhiêu hành lý?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Quy định hành lý:</p>
                    <ul>
                        <li><strong>Hành lý xách tay:</strong> Tối đa 7kg, kích thước không quá 40cm x 30cm x 20cm</li>
                        <li><strong>Hành lý ký gửi:</strong> Tối đa 20kg miễn phí, vượt quá tính phí 10.000 VNĐ/kg</li>
                    </ul>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Nhà xe hủy chuyến thì sao?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Nếu nhà xe hủy chuyến, bạn sẽ:</p>
                    <ul>
                        <li>Được hoàn tiền 100%</li>
                        <li>Nhận thông báo qua email và SMS</li>
                        <li>Được hỗ trợ đặt chuyến thay thế (nếu có)</li>
                    </ul>
                    <p>Vui lòng liên hệ hotline để được hỗ trợ tốt nhất.</p>
                </div>
            </div>
        </div>

        <!-- Contact -->
        <div class="faq-contact">
            <h2>Không tìm thấy câu trả lời?</h2>
            <p>Liên hệ với chúng tôi để được hỗ trợ tốt nhất</p>
            <a href="<?php echo appUrl('user/info/contact.php'); ?>" class="btn-contact">
                <i class="fas fa-envelope"></i> Liên hệ ngay
            </a>
        </div>
    </div>
</main>

<script>
// FAQ Toggle
document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', () => {
        const item = question.parentElement;
        const isActive = item.classList.contains('active');
        
        // Close all items
        document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
        
        // Toggle current item
        if (!isActive) {
            item.classList.add('active');
        }
    });
});

// FAQ Search
document.getElementById('faqSearch').addEventListener('input', (e) => {
    const searchTerm = e.target.value.toLowerCase();
    document.querySelectorAll('.faq-item').forEach(item => {
        const question = item.querySelector('.faq-question').textContent.toLowerCase();
        const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
        
        if (question.includes(searchTerm) || answer.includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});

// FAQ Category Filter
document.querySelectorAll('.faq-category-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.faq-category-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        const category = btn.dataset.category;
        
        document.querySelectorAll('.faq-section').forEach(section => {
            if (category === 'all' || section.dataset.category === category) {
                section.style.display = '';
            } else {
                section.style.display = 'none';
            }
        });
    });
});
</script>

<?php include '../../includes/footer_user.php'; ?>

