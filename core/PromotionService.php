<?php

class PromotionService
{
    /**
     * Lấy thông tin khuyến mãi theo mã.
     */
    public static function getByCode(mysqli $conn, string $code): ?array
    {
        $stmt = $conn->prepare("SELECT * FROM promotions WHERE code = ? LIMIT 1");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $promo = $stmt->get_result()->fetch_assoc();
        return $promo ?: null;
    }

    /**
     * Tính mức giảm giá dựa trên khuyến mãi và giá trị đơn hàng.
     */
    public static function calculateDiscount(array $promo, float $orderAmount): float
    {
        $discount = 0.0;
        if ($promo['discount_type'] === 'percentage') {
            $discount = $orderAmount * ((float)$promo['discount_value'] / 100);
            if ($promo['max_discount_amount'] !== null) {
                $discount = min($discount, (float)$promo['max_discount_amount']);
            }
        } else {
            $discount = (float)$promo['discount_value'];
        }

        return min($discount, $orderAmount);
    }

    /**
     * Kiểm tra hợp lệ và trả về thông tin khuyến mãi + số tiền giảm.
     *
     * @throws Exception khi không hợp lệ.
     */
    public static function applyPromotion(mysqli $conn, string $code, float $orderAmount): array
    {
        $promo = self::getByCode($conn, $code);
        if (!$promo) {
            throw new Exception('Mã khuyến mãi không tồn tại.');
        }

        $now = date('Y-m-d H:i:s');
        $usageLimitOk = ($promo['usage_limit'] === null || $promo['usage_limit'] === '' || $promo['used_count'] < $promo['usage_limit']);

        if ($promo['status'] !== 'active') {
            throw new Exception('Mã khuyến mãi không hoạt động.');
        } elseif ($now < $promo['start_date'] || $now > $promo['end_date']) {
            throw new Exception('Mã khuyến mãi đã hết hạn hoặc chưa bắt đầu.');
        } elseif (!$usageLimitOk) {
            throw new Exception('Mã khuyến mãi đã hết lượt sử dụng.');
        } elseif ($orderAmount < (float)$promo['min_order_amount']) {
            throw new Exception('Đơn hàng chưa đạt giá trị tối thiểu.');
        }

        $discount = self::calculateDiscount($promo, $orderAmount);
        $final = max(0, $orderAmount - $discount);

        return [
            'promotion' => $promo,
            'discount' => $discount,
            'final' => $final,
        ];
    }

    /**
     * Cộng lượt dùng khuyến mãi + ghi log sử dụng (gọi trong transaction).
     *
     * @throws Exception khi vượt giới hạn hoặc khuyến mãi không còn hợp lệ.
     */
    public static function incrementUsage(mysqli $conn, int $promotionId, int $bookingId, ?int $userId, float $discountAmount): void
    {
        // Khóa dòng để tránh race condition.
        $stmt = $conn->prepare("
            SELECT promotion_id, status, start_date, end_date, usage_limit, used_count
            FROM promotions
            WHERE promotion_id = ?
            FOR UPDATE
        ");
        $stmt->bind_param("i", $promotionId);
        $stmt->execute();
        $promo = $stmt->get_result()->fetch_assoc();

        if (!$promo) {
            throw new Exception('Không tìm thấy khuyến mãi.');
        }

        $now = date('Y-m-d H:i:s');
        $usageLimitOk = ($promo['usage_limit'] === null || $promo['usage_limit'] === '' || $promo['used_count'] < $promo['usage_limit']);

        if ($promo['status'] !== 'active') {
            throw new Exception('Khuyến mãi đã tắt.');
        } elseif ($now < $promo['start_date'] || $now > $promo['end_date']) {
            throw new Exception('Khuyến mãi đã hết hạn hoặc chưa bắt đầu.');
        } elseif (!$usageLimitOk) {
            throw new Exception('Khuyến mãi đã hết lượt sử dụng.');
        }

        // Cộng lượt dùng
        $stmt = $conn->prepare("UPDATE promotions SET used_count = used_count + 1 WHERE promotion_id = ?");
        $stmt->bind_param("i", $promotionId);
        $stmt->execute();

        // Ghi nhật ký sử dụng (bỏ qua lỗi nếu bảng chưa sẵn sàng)
        $uid = $userId ?: 0;
        try {
            $stmt = $conn->prepare("
                INSERT INTO promotion_usage (promotion_id, user_id, booking_id, discount_amount, used_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("iiid", $promotionId, $uid, $bookingId, $discountAmount);
            $stmt->execute();
        } catch (Exception $e) {
            // Log nhẹ nhàng, không phá giao dịch chính
            error_log('promotion_usage insert failed: ' . $e->getMessage());
        }
    }
}

