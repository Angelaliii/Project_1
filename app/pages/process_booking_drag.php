<?php
// process_booking_drag.php - 處理教室預約
session_start();

// 引入必要文件
require_once dirname(__DIR__) . '/config/database.php';

// 確定使用者已登入
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 獲取表單數據
    $classroomId = isset($_POST['classroom_id']) ? (int) $_POST['classroom_id'] : 0;
    $bookingDate = isset($_POST['booking_date']) ? $_POST['booking_date'] : '';
    $selectedHoursJson = isset($_POST['selected_hours']) ? $_POST['selected_hours'] : '';
    $purpose = isset($_POST['purpose']) ? $_POST['purpose'] : '';
    
    // 解析選擇的小時
    $selectedHours = [];
    if (!empty($selectedHoursJson)) {
        $selectedHours = json_decode($selectedHoursJson);
    }
    
    // 基本驗證
    if (empty($classroomId) || empty($bookingDate) || empty($selectedHours) || empty($purpose)) {
        header("Location: booking_drag.php?error=所有欄位都是必填的&classroom_id=$classroomId&date=$bookingDate");
        exit;
    }
    
    // 確保選擇的時間有效（連續且有序）
    sort($selectedHours);
    for ($i = 1; $i < count($selectedHours); $i++) {
        if ($selectedHours[$i] !== $selectedHours[$i-1] + 1) {
            header("Location: booking_drag.php?error=請選擇連續的時間段&classroom_id=$classroomId&date=$bookingDate");
            exit;
        }
    }
    
    // 計算開始和結束時間
    $startHour = reset($selectedHours);
    $endHour = end($selectedHours) + 1; // 結束時間為最後一個小時 + 1
    
    $startDatetime = $bookingDate . ' ' . sprintf('%02d:00:00', $startHour);
    $endDatetime = $bookingDate . ' ' . sprintf('%02d:00:00', $endHour);
    
    // 驗證時間是否在未來
    $startTimestamp = strtotime($startDatetime);
    if ($startTimestamp <= time()) {
        header("Location: booking_drag.php?error=只能預約未來的時間段&classroom_id=$classroomId&date=$bookingDate");
        exit;
    }
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 檢查教室是否存在
        $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE classroom_ID = ?");
        $stmt->execute([$classroomId]);
        $classroom = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$classroom) {
            header("Location: booking_drag.php?error=所選教室不存在&date=$bookingDate");
            exit;
        }
        
        // 檢查所選時段是否已被預約
        $placeholders = implode(',', array_fill(0, count($selectedHours), '?'));
        $params = array_merge([$classroomId, $bookingDate], $selectedHours);
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as booked_count 
            FROM booking_slots bs 
            JOIN bookings b ON b.booking_ID = bs.booking_ID 
            WHERE b.classroom_ID = ? 
            AND bs.date = ? 
            AND bs.hour IN ($placeholders)
            AND b.status != 'cancelled'
        ");
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['booked_count'] > 0) {
            header("Location: booking_drag.php?error=部分所選時段已被預約，請重新選擇&classroom_id=$classroomId&date=$bookingDate");
            exit;
        }
        
        // 開始交易
        $pdo->beginTransaction();
        
        // 創建新預約
        $stmt = $pdo->prepare("
            INSERT INTO bookings (classroom_ID, user_ID, status, start_datetime, end_datetime, purpose, created_at, updated_at) 
            VALUES (?, ?, 'booked', ?, ?, ?, NOW(), NOW())
        ");
        $result = $stmt->execute([$classroomId, $_SESSION['user_id'], $startDatetime, $endDatetime, $purpose]);
        
        if (!$result) {
            $pdo->rollBack();
            header("Location: booking_drag.php?error=預約失敗，請稍後再試&classroom_id=$classroomId&date=$bookingDate");
            exit;
        }
        
        // 獲取新創建的預約ID
        $bookingId = $pdo->lastInsertId();
        
        // 創建每個小時的時段記錄
        $insertSlotStmt = $pdo->prepare("
            INSERT INTO booking_slots (booking_ID, date, hour, created_at, updated_at) 
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        
        foreach ($selectedHours as $hour) {
            $result = $insertSlotStmt->execute([$bookingId, $bookingDate, $hour]);
            
            if (!$result) {
                $pdo->rollBack();
                header("Location: booking_drag.php?error=預約時段創建失敗，請稍後再試&classroom_id=$classroomId&date=$bookingDate");
                exit;
            }
        }
        
        // 提交交易
        $pdo->commit();
        
        // 重定向到我的預約頁面
        header("Location: my_bookings.php?success=預約成功！");
        exit;
        
    } catch (PDOException $e) {
        // 如果有交易正在進行，回滾
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // 記錄錯誤
        error_log("處理預約時出錯: " . $e->getMessage(), 0);
        header("Location: booking_drag.php?error=處理預約時發生錯誤，請稍後再試&classroom_id=$classroomId&date=$bookingDate");
        exit;
    }
} else {
    // 非POST請求，重定向到預約頁面
    header("Location: booking_drag.php");
    exit;
}
?>
