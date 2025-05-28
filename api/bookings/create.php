<?php
// api/bookings/create_booking.php - 專門處理創建預約的API請求

// 設置錯誤處理，避免 PHP 錯誤破壞 JSON 輸出
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(dirname(__DIR__)) . '/api_errors.log');
set_error_handler(function($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});

try {
    require_once dirname(__DIR__) . '/config.php';

    // 設置CORS頭
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');
    
    // 處理OPTIONS請求
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    // 只接受POST請求
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只接受POST請求');
    }

    // 獲取POST數據
    $data = json_decode(file_get_contents('php://input'), true);
    error_log('收到的預約數據: ' . json_encode($data));

    // 驗證必填字段
    if (!isset($data['classroom_ID']) || !isset($data['date']) || empty($data['slots'])) {
        throw new Exception('缺少必要欄位');
    }
    
    // 檢查用戶是否登入
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('未授權，請先登入');
    }

    // 開始處理預約
    $userId = $_SESSION['user_id'];
    $classroomId = $data['classroom_ID'];
    $date = $data['date'];
    $slots = $data['slots']; // 應為小時數陣列，例如 [9, 10, 11]
    
    // 取得使用目的欄位如果有的話
    $purpose = isset($data['purpose']) ? $data['purpose'] : null;

    // 驗證日期格式
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('日期格式無效');
    }

    // 連接資料庫
    $pdo = connectDB();
    
    // 檢查教室是否存在
    $stmt = $pdo->prepare("SELECT classroom_ID FROM classrooms WHERE classroom_ID = ?");
    $stmt->execute([$classroomId]);
    if (!$stmt->fetch()) {
        throw new Exception('教室不存在');
    }

    // 檢查時段是否已被預約
    $placeholders = implode(',', array_fill(0, count($slots), '?'));
    $stmt = $pdo->prepare("
        SELECT bs.hour 
        FROM booking_slots bs
        JOIN bookings b ON bs.booking_ID = b.booking_ID
        WHERE bs.date = ? 
        AND b.classroom_ID = ? 
        AND b.status IN ('booked', 'in_use')
        AND bs.hour IN ($placeholders)
    ");
    
    $params = [$date, $classroomId];
    foreach ($slots as $slot) {
        $params[] = $slot;
    }
    
    $stmt->execute($params);
    $bookedSlots = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($bookedSlots) > 0) {
        throw new Exception('所選時段已被預約: ' . implode(', ', $bookedSlots));
    }
    
    // 獲取日期時間範圍
    $startHour = min($slots);
    $endHour = max($slots) + 1;
    $startDatetime = "{$date} {$startHour}:00:00";
    $endDatetime = "{$date} {$endHour}:00:00";
    
    // 開始事務
    $pdo->beginTransaction();
    
    try {
        // 創建預約
        // 所有預約都直接設為 booked 狀態，不需要審核
        $status = 'booked';
        
        $stmt = $pdo->prepare("INSERT INTO bookings (classroom_ID, user_ID, status, start_datetime, end_datetime, purpose) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$classroomId, $userId, $status, $startDatetime, $endDatetime, $purpose]);
        
        $bookingId = $pdo->lastInsertId();
        
        // 插入預約時段
        $stmt = $pdo->prepare("INSERT INTO booking_slots (booking_ID, date, hour) VALUES (?, ?, ?)");
        
        foreach ($slots as $hour) {
            $stmt->execute([$bookingId, $date, $hour]);
        }
        
        // 提交事務
        $pdo->commit();
        
        // 返回成功響應
        echo json_encode([
            'status' => 'success',
            'message' => '預約創建成功',
            'booking_id' => $bookingId,
            'booking_status' => $status
        ]);
        
    } catch (Exception $e) {
        // 回滾事務
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('預約創建錯誤: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
