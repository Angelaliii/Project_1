<?php
// api/bookings/slots.php - 處理預約時段相關的API請求
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 記錄到特定的錯誤日誌文件
ini_set('log_errors', 1);
ini_set('error_log', dirname(dirname(__DIR__)) . '/api_errors.log');

error_log("=== slots.php 開始執行 ===");
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("GET 參數: " . print_r($_GET, true));

require_once dirname(__DIR__) . '/config.php';

// 設置CORS頭
setCorsHeaders();

// 確定HTTP方法
$method = $_SERVER['REQUEST_METHOD'];
// 獲取日期和教室ID
$date = isset($_GET['date']) ? $_GET['date'] : null;
$classroomId = isset($_GET['classroom_id']) ? intval($_GET['classroom_id']) : null;

error_log("Method: $method, 日期: $date, 教室ID: $classroomId");

switch ($method) {
    case 'GET':
        if ($date && $classroomId) {
            getAvailableSlots($date, $classroomId);
        } else {
            error_log("缺少參數: 日期=$date, 教室ID=$classroomId");
            sendError('請提供日期和教室ID', 400);
        }
        break;
    default:
        sendError('不支持的HTTP方法', 405);
        break;
}

/**
 * 獲取指定日期和教室的可用時段
 */
function getAvailableSlots($date, $classroomId) {
    error_log("getAvailableSlots 函數開始執行: date=$date, classroomId=$classroomId");
    
    try {
        // 確保日期格式正確
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            error_log("日期格式不正確: $date");
            sendError('日期格式不正確，應為 YYYY-MM-DD', 400);
            return;
        }
        
        $pdo = connectDB();
        error_log("數據庫連接成功");
        
        // 檢查教室是否存在，並獲取教室信息
        $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE classroom_ID = ?");
        $stmt->execute([$classroomId]);
        $classroom = $stmt->fetch();
        
        error_log("查詢教室結果: " . ($classroom ? json_encode($classroom) : "未找到"));
        
        if (!$classroom) {
            sendError("教室不存在 (ID: $classroomId)", 404);
            return;
        }
        
        // 獲取該日期的所有預約時段
        $stmt = $pdo->prepare("
            SELECT bs.hour 
            FROM booking_slots bs
            JOIN bookings b ON bs.booking_ID = b.booking_ID
            WHERE bs.date = ? 
            AND b.classroom_ID = ? 
            AND b.status IN ('booked', 'in_use')
        ");
        $stmt->execute([$date, $classroomId]);
        $bookedHours = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        error_log("已預訂的時間: " . implode(', ', $bookedHours));
        
        // 生成時間槽列表 (按新的時間段定義)
        $slots = [];
        
        // 上午時段：8:00-12:00，每小時一個時段
        for ($hour = 8; $hour <= 11; $hour++) {
            $slots[] = [
                'hour' => $hour,
                'time' => sprintf('%02d:00-%02d:00', $hour, $hour + 1),
                'available' => !in_array($hour, $bookedHours)
            ];
        }
        
        // 中午時段：12:00-13:30
        $slots[] = [
            'hour' => 12,
            'time' => '12:00-13:30',
            'available' => !in_array(12, $bookedHours)
        ];
        
        // 下午時段：13:30-20:30，每小時一個時段
        for ($hour = 13; $hour <= 20; $hour++) {
            $nextHour = $hour + 1;
            $slots[] = [
                'hour' => $hour,
                'time' => sprintf('%02d:30-%02d:30', $hour, $nextHour),
                'available' => !in_array($hour, $bookedHours)
            ];
        }
        
        error_log("生成的時間槽數量: " . count($slots));
        
        // 構造響應數據
        $response = [
            'status' => 'success',
            'date' => $date, 
            'classroom_id' => $classroomId, 
            'slots' => $slots,
            'classroom' => $classroom
        ];
        
        error_log("準備返回響應: " . json_encode($response));
        
        // 直接輸出JSON
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        error_log("發生錯誤: " . $e->getMessage());
        sendError('獲取可用時段時發生錯誤: ' . $e->getMessage(), 500);
    }
}
