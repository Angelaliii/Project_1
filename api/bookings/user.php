<?php
// api/bookings/user.php - 獲取用戶預約列表的API端點

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

// 引入必要文件
require_once dirname(dirname(__DIR__)) . '/app/config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 構建查詢 - 獲取用戶的預約列表並包含教室相關信息
    $sql = "
        SELECT 
            b.booking_ID, 
            b.status, 
            b.start_datetime, 
            b.end_datetime,
            b.purpose,
            c.classroom_name, 
            c.building, 
            c.room,
            c.capacity
        FROM bookings b
        JOIN classrooms c ON b.classroom_ID = c.classroom_ID
        WHERE b.user_ID = ?
        ORDER BY b.start_datetime DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 處理每個預約的狀態文本
    foreach ($bookings as &$booking) {
        switch ($booking['status']) {
            case 'available': 
                $booking['status_text'] = '可預約'; 
                break;
            case 'booked': 
                $booking['status_text'] = '已預約'; 
                break;
            case 'in_use': 
                $booking['status_text'] = '使用中'; 
                break;
            case 'completed': 
                $booking['status_text'] = '已完成'; 
                break;
            case 'cancelled': 
                $booking['status_text'] = '已取消'; 
                break;
            case 'rejected': 
                $booking['status_text'] = '已拒絕'; 
                break;
            default:
                $booking['status_text'] = $booking['status'];
        }
    }
    
    // 返回成功響應
    echo json_encode([
        'status' => 'success',
        'bookings' => $bookings
    ]);

} catch (PDOException $e) {
    // 記錄錯誤
    error_log("獲取用戶預約列表時出錯: " . $e->getMessage(), 0);
    
    // 返回錯誤響應
    echo json_encode([
        'status' => 'error',
        'message' => '獲取預約列表時發生錯誤，請稍後再試'
    ]);
}
