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
        $_SESSION['booking_errors'] = ["所有欄位都是必填的"];
        header("Location: booking.php?classroom_id=$classroomId&date=$bookingDate");
        exit;
    }
    
    // 對選擇的時間排序
    sort($selectedHours);
    
    // 找出連續的時間段分組
    $timeGroups = [];
    $currentGroup = [$selectedHours[0]];
    
    for ($i = 1; $i < count($selectedHours); $i++) {
        if ($selectedHours[$i] == $selectedHours[$i-1] + 1) {
            // 時間連續，添加到當前組
            $currentGroup[] = $selectedHours[$i];
        } else {
            // 時間不連續，結束當前組，開始新的一組
            $timeGroups[] = $currentGroup;
            $currentGroup = [$selectedHours[$i]];
        }
    }
    
    // 添加最後一組
    $timeGroups[] = $currentGroup;
    
    // 驗證時間是否在未來
    $bookingTimestamp = strtotime($bookingDate);
    if ($bookingTimestamp <= time()) {
        $_SESSION['booking_errors'] = ["只能預約未來的時間段"];
        header("Location: booking.php?classroom_id=$classroomId&date=$bookingDate");
        exit;
    }
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 檢查教室是否存在並獲取權限信息
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   COALESCE(cp.allowed_roles, 'student,teacher') AS allowed_roles 
            FROM classrooms c
            LEFT JOIN classroom_permissions cp ON c.classroom_ID = cp.classroom_id
            WHERE c.classroom_ID = ?
        ");
        $stmt->execute([$classroomId]);
        $classroom = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$classroom) {
            $_SESSION['booking_errors'] = ["所選教室不存在"];
            header("Location: booking.php?date=$bookingDate");
            exit;
        }
        
        // 檢查用戶權限 - 教師永遠有權限
        if ($_SESSION['role'] !== 'teacher') {
            $allowedRoles = explode(',', $classroom['allowed_roles']);
            if (!in_array($_SESSION['role'], $allowedRoles)) {
                $_SESSION['booking_errors'] = ["您沒有權限預約此教室"];
                header("Location: booking.php?classroom_id=$classroomId&date=$bookingDate");
                exit;
            }
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
            $_SESSION['booking_errors'] = ["部分所選時段已被預約，請重新選擇"];
            header("Location: booking.php?classroom_id=$classroomId&date=$bookingDate");
            exit;
        }
        
        // 開始交易
        $pdo->beginTransaction();
        
        // 處理每個連續時間段為一個預約
        $bookingCount = 0;
        
        foreach ($timeGroups as $group) {
            // 計算開始和結束時間
            $startHour = reset($group);
            $endHour = end($group) + 1; // 結束時間為最後一個小時 + 1
            
            $startDatetime = $bookingDate . ' ' . sprintf('%02d:00:00', $startHour);
            $endDatetime = $bookingDate . ' ' . sprintf('%02d:00:00', $endHour);
            
            // 創建新預約
            $stmt = $pdo->prepare("
                INSERT INTO bookings (classroom_ID, user_ID, status, start_datetime, end_datetime, purpose, created_at, updated_at) 
                VALUES (?, ?, 'booked', ?, ?, ?, NOW(), NOW())
            ");
            $result = $stmt->execute([$classroomId, $_SESSION['user_id'], $startDatetime, $endDatetime, $purpose]);
            
            if (!$result) {
                $pdo->rollBack();
                $_SESSION['booking_errors'] = ["預約失敗，請稍後再試"];
                header("Location: booking.php?classroom_id=$classroomId&date=$bookingDate");
                exit;
            }
            
            // 獲取新創建的預約ID
            $bookingId = $pdo->lastInsertId();
            
            // 創建每個小時的時段記錄
            $insertSlotStmt = $pdo->prepare("
                INSERT INTO booking_slots (booking_ID, date, hour, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            
            foreach ($group as $hour) {
                $result = $insertSlotStmt->execute([$bookingId, $bookingDate, $hour]);
                
                if (!$result) {
                    $pdo->rollBack();
                    $_SESSION['booking_errors'] = ["預約時段創建失敗，請稍後再試"];
                    header("Location: booking.php?classroom_id=$classroomId&date=$bookingDate");
                    exit;
                }
            }
            
            $bookingCount++;
        }
        
        // 提交交易
        $pdo->commit();
        
        // 設置成功訊息並重定向回預約頁面
        if ($bookingCount > 1) {
            $_SESSION['booking_success'] = "預約成功！已創建 {$bookingCount} 筆預約，您可以在「我的預約」中查看詳情。";
        } else {
            $_SESSION['booking_success'] = "預約成功！您可以在「我的預約」中查看詳情。";
        }
        header("Location: booking.php?classroom_id=$classroomId&date=$bookingDate&success=1");
        exit;
        
    } catch (PDOException $e) {
        // 如果有交易正在進行，回滾
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // 記錄錯誤
        error_log("處理預約時出錯: " . $e->getMessage(), 0);
        $_SESSION['booking_errors'] = ["處理預約時發生錯誤，請稍後再試"];
        header("Location: booking.php?classroom_id=$classroomId&date=$bookingDate");
        exit;
    }
} else {
    // 非POST請求，重定向到預約頁面
    header("Location: booking.php");
    exit;
}
