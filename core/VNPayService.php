<?php
/**
 * VNPay Payment Service
 * Service xử lý thanh toán qua VNPay
 */

class VNPayService {
    
    /**
     * Tạo URL thanh toán VNPay
     */
    public static function createPaymentUrl($bookingId, $amount, $orderInfo, $ipAddress = null) {
        // Load config
        if (!defined('VNPAY_TMN_CODE')) {
            require_once __DIR__ . '/../config/vnpay.php';
        }
        
        // Get IP address
        if ($ipAddress === null) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
        
        // Set timezone for VNPay (REQUIRED!)
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        
        // Tạo mã giao dịch unique
        $txnRef = $bookingId . '_' . time();
        
        // Thời gian tạo (YmdHis) - Format: 20251209181500
        $createDate = date('YmdHis');
        
        // Thời gian hết hạn (1 giờ để dễ test)
        $expireDate = date('YmdHis', strtotime('+1 hour'));
        
        // Build input data
        $inputData = [
            "vnp_Version" => VNPAY_VERSION,
            "vnp_Command" => VNPAY_COMMAND,
            "vnp_TmnCode" => VNPAY_TMN_CODE,
            "vnp_Amount" => $amount * 100, // VNPay yêu cầu số tiền * 100
            "vnp_CreateDate" => $createDate,
            "vnp_CurrCode" => VNPAY_CURRENCY_CODE,
            "vnp_IpAddr" => $ipAddress,
            "vnp_Locale" => VNPAY_LOCALE,
            "vnp_OrderInfo" => $orderInfo,
            "vnp_OrderType" => 'other', // Loại hàng hóa
            "vnp_ReturnUrl" => VNPAY_RETURN_URL,
            "vnp_TxnRef" => $txnRef,
            "vnp_ExpireDate" => $expireDate
        ];
        
        // Optional: Bank code (nếu muốn chọn ngân hàng trước)
        // $inputData['vnp_BankCode'] = 'NCB';
        
        // Sort data theo alphabet
        ksort($inputData);
        
        // Tạo query string
        $query = "";
        $i = 0;
        $hashdata = "";
        
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }
        
        // Tạo secure hash
        $vnpSecureHash = hash_hmac('sha512', $hashdata, VNPAY_HASH_SECRET);
        $query .= 'vnp_SecureHash=' . $vnpSecureHash;
        
        // Return payment URL
        return VNPAY_URL . "?" . $query;
    }
    
    /**
     * Xác thực response từ VNPay
     */
    public static function validateResponse($inputData) {
        // Load config
        if (!defined('VNPAY_TMN_CODE')) {
            require_once __DIR__ . '/../config/vnpay.php';
        }
        
        // Lấy secure hash từ VNPay
        $vnpSecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHashType']);
        
        // Sort data
        ksort($inputData);
        
        // Tạo hash để so sánh
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
        
        $secureHash = hash_hmac('sha512', $hashdata, VNPAY_HASH_SECRET);
        
        // So sánh hash
        return $secureHash === $vnpSecureHash;
    }
    
    /**
     * Parse response data
     */
    public static function parseResponse($inputData) {
        return [
            'txn_ref' => $inputData['vnp_TxnRef'] ?? '',
            'amount' => isset($inputData['vnp_Amount']) ? ($inputData['vnp_Amount'] / 100) : 0,
            'bank_code' => $inputData['vnp_BankCode'] ?? '',
            'card_type' => $inputData['vnp_CardType'] ?? '',
            'order_info' => $inputData['vnp_OrderInfo'] ?? '',
            'pay_date' => $inputData['vnp_PayDate'] ?? '',
            'response_code' => $inputData['vnp_ResponseCode'] ?? '',
            'transaction_no' => $inputData['vnp_TransactionNo'] ?? '',
            'transaction_status' => $inputData['vnp_TransactionStatus'] ?? '',
            'tmn_code' => $inputData['vnp_TmnCode'] ?? ''
        ];
    }
    
    /**
     * Get response message
     */
    public static function getResponseMessage($responseCode) {
        if (!defined('VNPAY_RESPONSE_CODES')) {
            require_once __DIR__ . '/../config/vnpay.php';
        }
        
        return VNPAY_RESPONSE_CODES[$responseCode] ?? 'Lỗi không xác định';
    }
    
    /**
     * Check if payment success
     */
    public static function isPaymentSuccess($responseCode) {
        return $responseCode === '00';
    }
}

