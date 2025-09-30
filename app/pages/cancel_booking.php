<?php
// cancel_booking.php - 取消預約處理頁面
session_start();

// 引入必要文件
require_once dirname(__DIR__) . '/config/database.php';

// 確定使用者已登入
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 獲取預約ID和重定向URL
$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$redirectUrl = isset($_GET['redirect']) ? $_GET['redirect'] : 'my_bookings_new.php';

// 驗證預約ID
if (!$bookingId) {
    $_SESSION['error_message'] = '無效的預約ID';
    header("Location: $redirectUrl");
    exit;
}

try {
    $pdo = getDbConnection();

    // 首先檢查預約是否屬於當前用戶
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_ID = ? AND user_ID = ?");
    $stmt->execute([$bookingId, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        $_SESSION['error_message'] = '找不到預約或您沒有權限取消此預約';
        header("Location: $redirectUrl");
        exit;
    }

    // 檢查預約是否可以取消（例如，未來的預約）
    $startDatetime = new DateTime($booking['start_datetime']);
    $now = new DateTime();

    if ($startDatetime <= $now) {
        $_SESSION['error_message'] = '只能取消未來的預約';
        header("Location: $redirectUrl");
        exit;
    }

    // 更新預約狀態為已取消
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE booking_ID = ?");
    $result = $stmt->execute([$bookingId]);

    if ($result) {
        $_SESSION['success_message'] = '預約已成功取消';
    } else {
        $_SESSION['error_message'] = '無法取消預約，請稍後再試';
    }

} catch (PDOException $e) {
    // 記錄錯誤
    error_log("取消預約時出錯: " . $e->getMessage(), 0);
    $_SESSION['error_message'] = '處理取消請求時發生錯誤，請稍後再試';
}

// 重定向回預約列表頁面
header("Location: $redirectUrl");
exit;
?>
