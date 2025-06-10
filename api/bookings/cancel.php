<?php
// api/bookings/cancel.php - 取消預約的API端點

session_start();
header('Content-Type: application/json');

// 確定用戶已登入
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => '用戶未登入'
    ]);
    exit;
}

// 解析請求數據
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['booking_id']) || !is_numeric($data['booking_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => '缺少有效的預約ID'
    ]);
    exit;
}

$bookingId = (int) $data['booking_id'];

// 引入必要文件
require_once dirname(dirname(__DIR__)) . '/app/config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 檢查預約是否屬於當前用戶
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_ID = ? AND user_ID = ?");
    $stmt->execute([$bookingId, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode([
            'status' => 'error',
            'message' => '無效的預約ID或您無權取消此預約'
        ]);
        exit;
    }
    
    // 檢查是否可以取消（只能取消尚未開始的預約）
    if (strtotime($booking['start_datetime']) <= time()) {
        echo json_encode([
            'status' => 'error',
            'message' => '無法取消已開始或已結束的預約'
        ]);
        exit;
    }
    
    // 更新預約狀態為已取消
    $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_ID = ?");
    $updateStmt->execute([$bookingId]);
    
    // 返回成功響應
    echo json_encode([
        'status' => 'success',
        'message' => '預約已成功取消'
    ]);
    
} catch (PDOException $e) {
    // 記錄錯誤
    error_log("取消預約時出錯: " . $e->getMessage(), 0);
    
    // 返回錯誤響應
    echo json_encode([
        'status' => 'error',
        'message' => '取消預約時發生錯誤，請稍後再試'
    ]);
}
