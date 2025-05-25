<?php
// api/bookings/index.php - 處理預約相關的API請求

// 設置錯誤處理，避免 PHP 錯誤破壞 JSON 輸出
error_reporting(E_ALL);
ini_set('display_errors', 0);
set_error_handler(function($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});

try {
    require_once dirname(__DIR__) . '/config.php';

    // 設置CORS頭
    setCorsHeaders();

// 確定HTTP方法
$method = $_SERVER['REQUEST_METHOD'];
// 獲取預約ID（如果在URL中指定）
$bookingId = isset($_GET['id']) ? intval($_GET['id']) : null;

switch ($method) {
    case 'GET':
        if ($bookingId) {
            getBooking($bookingId);
        } else {
            listBookings();
        }
        break;
    case 'POST':
        createBookingHandler();
        break;
    case 'PUT':
        if (!$bookingId) {
            sendError('更新預約需要預約ID', 400);
        }
        updateBooking($bookingId);
        break;
    case 'DELETE':
        if (!$bookingId) {
            sendError('刪除預約需要預約ID', 400);
        }
        deleteBooking($bookingId);
        break;
    default:
        sendError('不支持的HTTP方法', 405);
        break;
}

// 獲取所有預約
function listBookings() {
    try {
        $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
        $classroomId = isset($_GET['classroom_id']) ? intval($_GET['classroom_id']) : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $date = isset($_GET['date']) ? $_GET['date'] : null;
        
        // 驗證權限
        if (!isAdmin() && (!isset($_SESSION['user_id']) || ($userId && $_SESSION['user_id'] != $userId))) {
            sendError('未授權', 403);
        }
        
        $pdo = connectDB();
        
        $query = "SELECT b.booking_ID, b.classroom_ID, c.classroom_name, b.user_ID, u.user_name, 
                  b.status, b.start_datetime, b.end_datetime, b.created_at, b.updated_at 
                  FROM bookings b 
                  JOIN users u ON b.user_ID = u.user_id 
                  JOIN classrooms c ON b.classroom_ID = c.classroom_ID 
                  WHERE 1=1";
                  
        $params = [];
        
        if ($userId) {
            $query .= " AND b.user_ID = ?";
            $params[] = $userId;
        } elseif (!isAdmin() && isset($_SESSION['user_id'])) {
            // 非管理員只能查看自己的預約
            $query .= " AND b.user_ID = ?";
            $params[] = $_SESSION['user_id'];
        }
        
        if ($classroomId) {
            $query .= " AND b.classroom_ID = ?";
            $params[] = $classroomId;
        }
        
        if ($status) {
            $query .= " AND b.status = ?";
            $params[] = $status;
        }
        
        if ($date) {
            $query .= " AND DATE(b.start_datetime) = ?";
            $params[] = $date;
        }
        
        $query .= " ORDER BY b.start_datetime DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $bookings = $stmt->fetchAll();
        
        sendResponse(['bookings' => $bookings]);
    } catch (Exception $e) {
        sendError('獲取預約列表時發生錯誤: ' . $e->getMessage(), 500);
    }
}

// 獲取單個預約
function getBooking($bookingId) {
    try {
        $pdo = connectDB();
        
        $stmt = $pdo->prepare("SELECT b.*, c.classroom_name, u.user_name 
                              FROM bookings b 
                              JOIN users u ON b.user_ID = u.user_id 
                              JOIN classrooms c ON b.classroom_ID = c.classroom_ID 
                              WHERE b.booking_ID = ?");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            sendError('預約不存在', 404);
        }
        
        // 驗證權限（管理員或預約擁有者）
        if (!isAdmin() && (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $booking['user_ID'])) {
            sendError('未授權', 403);
        }
        
        // 獲取該預約的所有時段
        $stmt = $pdo->prepare("SELECT * FROM booking_slots WHERE booking_ID = ? ORDER BY date, hour");
        $stmt->execute([$bookingId]);
        $slots = $stmt->fetchAll();
        
        $booking['slots'] = $slots;
        
        sendResponse(['booking' => $booking]);
    } catch (Exception $e) {
        sendError('獲取預約時發生錯誤: ' . $e->getMessage(), 500);
    }
}

// 創建新預約
function createBooking() {
    try {
        $data = getJsonInput();
        
        // 驗證必填字段
        if (!isset($data['classroom_ID']) || !isset($data['start_datetime']) || !isset($data['end_datetime'])) {
            sendError('缺少必要欄位', 400);
        }
        
        // 驗證登入狀態
        if (!isset($_SESSION['user_id'])) {
            sendError('未授權，請先登入', 401);
        }
        
        $userId = $_SESSION['user_id'];
        $classroomId = $data['classroom_ID'];
        $startDatetime = $data['start_datetime'];
        $endDatetime = $data['end_datetime'];
        $status = isset($data['status']) && isAdmin() ? $data['status'] : 'pending';
        
        // 驗證時間格式
        if (!strtotime($startDatetime) || !strtotime($endDatetime)) {
            sendError('無效的時間格式', 400);
        }
        
        // 驗證開始時間小於結束時間
        if (strtotime($startDatetime) >= strtotime($endDatetime)) {
            sendError('開始時間必須早於結束時間', 400);
        }
        
        $pdo = connectDB();
        
        // 檢查教室是否存在
        $stmt = $pdo->prepare("SELECT classroom_ID FROM classrooms WHERE classroom_ID = ?");
        $stmt->execute([$classroomId]);
        if (!$stmt->fetch()) {
            sendError('教室不存在', 404);
        }
        
        // 檢查時段是否已被預約
        $stmt = $pdo->prepare("SELECT booking_ID FROM bookings 
                              WHERE classroom_ID = ? 
                              AND status IN ('booked', 'in_use', 'pending') 
                              AND NOT (end_datetime <= ? OR start_datetime >= ?)");
        $stmt->execute([$classroomId, $startDatetime, $endDatetime]);
        if ($stmt->fetch()) {
            sendError('所選時段已被預約', 409);
        }
        
        // 開始事務
        $pdo->beginTransaction();
        
        try {
            // 創建預約
            $stmt = $pdo->prepare("INSERT INTO bookings (classroom_ID, user_ID, status, start_datetime, end_datetime) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$classroomId, $userId, $status, $startDatetime, $endDatetime]);
            
            $bookingId = $pdo->lastInsertId();
            
            // 創建預約時段
            $start = new DateTime($startDatetime);
            $end = new DateTime($endDatetime);
            
            // 計算每個小時的時段並插入
            $interval = new DateInterval('PT1H'); // 1小時間隔
            $period = new DatePeriod(
                $start,
                $interval,
                $end
            );
            
            foreach ($period as $dt) {
                $date = $dt->format('Y-m-d');
                $hour = (int)$dt->format('H');
                
                $stmt = $pdo->prepare("INSERT INTO booking_slots (booking_ID, date, hour) VALUES (?, ?, ?)");
                $stmt->execute([$bookingId, $date, $hour]);
            }
            
            $pdo->commit();
            
            sendResponse(['message' => '預約創建成功', 'booking_ID' => $bookingId], 201);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        sendError('創建預約時發生錯誤: ' . $e->getMessage(), 500);
    }
}

// 更新預約
function updateBooking($bookingId) {
    try {
        $data = getJsonInput();
        
        $pdo = connectDB();
        
        // 檢查預約存在性並獲取當前信息
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_ID = ?");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            sendError('預約不存在', 404);
        }
        
        // 驗證權限
        // 管理員可以更改任何預約
        // 一般用戶只能更改自己的預約，且只能在 pending 或 booked 狀態下更改
        if (!isAdmin()) {
            if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $booking['user_ID']) {
                sendError('未授權', 403);
            }
            
            if (!in_array($booking['status'], ['pending', 'booked'])) {
                sendError('當前預約狀態無法修改', 400);
            }
        }
        
        // 準備更新語句
        $updates = [];
        $params = [];
        
        // 用戶可以更新的字段
        if (isset($data['status']) && isAdmin()) {
            $validStatus = ['available', 'booked', 'in_use', 'completed', 'cancelled'];
            if (!in_array($data['status'], $validStatus)) {
                sendError('無效的狀態', 400);
            }
            
            $updates[] = "status = ?";
            $params[] = $data['status'];
        }
        
        if (isset($data['start_datetime']) && isset($data['end_datetime'])) {
            // 驗證時間格式
            if (!strtotime($data['start_datetime']) || !strtotime($data['end_datetime'])) {
                sendError('無效的時間格式', 400);
            }
            
            // 驗證開始時間小於結束時間
            if (strtotime($data['start_datetime']) >= strtotime($data['end_datetime'])) {
                sendError('開始時間必須早於結束時間', 400);
            }
            
            // 檢查時段是否與其他預約衝突
            $stmt = $pdo->prepare("SELECT booking_ID FROM bookings 
                                  WHERE classroom_ID = ? 
                                  AND booking_ID != ?
                                  AND status IN ('booked', 'in_use', 'pending') 
                                  AND NOT (end_datetime <= ? OR start_datetime >= ?)");
            $stmt->execute([$booking['classroom_ID'], $bookingId, $data['start_datetime'], $data['end_datetime']]);
            if ($stmt->fetch()) {
                sendError('所選時段已被其他預約佔用', 409);
            }
            
            $updates[] = "start_datetime = ?";
            $params[] = $data['start_datetime'];
            
            $updates[] = "end_datetime = ?";
            $params[] = $data['end_datetime'];
        }
        
        if (empty($updates)) {
            sendError('沒有要更新的資料', 400);
        }
        
        // 開始事務
        $pdo->beginTransaction();
        
        try {
            // 添加預約ID到參數陣列
            $params[] = $bookingId;
            
            $sql = "UPDATE bookings SET " . implode(", ", $updates) . " WHERE booking_ID = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // 如果有更新時間，重新生成時段
            if (isset($data['start_datetime']) && isset($data['end_datetime'])) {
                // 删除舊時段
                $stmt = $pdo->prepare("DELETE FROM booking_slots WHERE booking_ID = ?");
                $stmt->execute([$bookingId]);
                
                // 創建新時段
                $start = new DateTime($data['start_datetime']);
                $end = new DateTime($data['end_datetime']);
                
                // 計算每個小時的時段並插入
                $interval = new DateInterval('PT1H'); // 1小時間隔
                $period = new DatePeriod(
                    $start,
                    $interval,
                    $end
                );
                
                foreach ($period as $dt) {
                    $date = $dt->format('Y-m-d');
                    $hour = (int)$dt->format('H');
                    
                    $stmt = $pdo->prepare("INSERT INTO booking_slots (booking_ID, date, hour) VALUES (?, ?, ?)");
                    $stmt->execute([$bookingId, $date, $hour]);
                }
            }
            
            $pdo->commit();
            
            sendResponse(['message' => '預約更新成功']);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        sendError('更新預約時發生錯誤: ' . $e->getMessage(), 500);
    }
}

// 刪除預約
function deleteBooking($bookingId) {
    try {
        $pdo = connectDB();
        
        // 檢查預約存在性並獲取當前信息
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_ID = ?");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            sendError('預約不存在', 404);
        }
        
        // 驗證權限（管理員或預約擁有者）
        if (!isAdmin() && (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $booking['user_ID'])) {
            sendError('未授權', 403);
        }
        
        // 非管理員只能刪除處於 pending 或 booked 狀態的預約
        if (!isAdmin() && !in_array($booking['status'], ['pending', 'booked'])) {
            sendError('當前預約狀態無法刪除', 400);
        }
        
        // 開始事務
        $pdo->beginTransaction();
        
        try {
            // 删除預約時段
            $stmt = $pdo->prepare("DELETE FROM booking_slots WHERE booking_ID = ?");
            $stmt->execute([$bookingId]);
            
            // 刪除預約
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE booking_ID = ?");
            $stmt->execute([$bookingId]);
            
            $pdo->commit();
            
            sendResponse(['message' => '預約已成功刪除']);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        sendError('刪除預約時發生錯誤: ' . $e->getMessage(), 500);
    }
}

// 捕獲任何其他錯誤
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => '處理請求時發生錯誤: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}
