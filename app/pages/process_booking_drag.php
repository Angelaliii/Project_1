<?php
// process_booking_drag.php - 處理教室預約（依新規格修正版）
session_start();

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/UserModel.php';
// CSRF protection
require_once dirname(__DIR__) . '/helpers/security.php';
if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
    redirect_with_errors('', ['無效的請求 (CSRF 驗證失敗)']);
}

// 建議設時區，與 DB 保持一致
date_default_timezone_set('Asia/Taipei');

// 小工具：導向
function redirect_with_errors(string $bookingDate, array $errors) {
    $_SESSION['booking_errors'] = $errors;
    $q = $bookingDate !== '' ? ('?booking_date=' . urlencode($bookingDate)) : '';
    header("Location: booking.php{$q}");
    exit;
}
function redirect_success(string $bookingDate, string $msg) {
    $_SESSION['booking_success'] = $msg;
    header("Location: booking.php?booking_date=" . urlencode($bookingDate) . "&success=1");
    exit;
}

// 需登入
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$sessionRole = isset($_SESSION['role']) ? strtolower(trim((string)$_SESSION['role'])) : '';
if ($sessionRole === '' || !in_array($sessionRole, ['student','teacher'], true)) {
    redirect_with_errors('', ['找不到有效的使用者角色，請重新登入']);
}

// 僅允許 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: booking.php");
    exit;
}

// 讀取輸入
$bookingDate = isset($_POST['booking_date']) ? trim((string)$_POST['booking_date']) : '';
$selectedSlotsJson = $_POST['selected_slots'] ?? '';
$purposeRaw = isset($_POST['purpose']) ? (string)$_POST['purpose'] : '';

// 驗證日期（Y-m-d）
$tz = new DateTimeZone('Asia/Taipei');
$dtCheck = DateTime::createFromFormat('Y-m-d', $bookingDate, $tz);
$validDate = $dtCheck && $dtCheck->format('Y-m-d') === $bookingDate;

// 增加詳細診斷信息
if (!$validDate) {
    $errorMsg = '日期格式不正確（需為 YYYY-MM-DD）';
    // 添加更多診斷信息
    $errorMsg .= "，收到的值為: '" . $bookingDate . "'";
    if (!$dtCheck) {
        $errorMsg .= "，日期解析失敗";
    } elseif ($dtCheck->format('Y-m-d') !== $bookingDate) {
        $errorMsg .= "，格式化後不匹配: " . $dtCheck->format('Y-m-d');
    }
    
    redirect_with_errors($bookingDate, [$errorMsg]);
}

// 處理 purpose（純文字、≤ 100）
$purpose = trim(strip_tags($purposeRaw));
if ($purpose === '') {
    redirect_with_errors($bookingDate, ['用途為必填']);
}
if (mb_strlen($purpose) > 100) {
    redirect_with_errors($bookingDate, ['用途文字過長（最多 100 字）']);
}

// 解析/驗證 selected_slots
$selected = json_decode($selectedSlotsJson, true);
if (!is_array($selected)) {
    redirect_with_errors($bookingDate, ['選擇的時段資料格式錯誤（非有效 JSON）']);
}

// 依教室分組 + 驗證
$groupedByClassroom = [];
$allHoursFlat = []; // 用來做「今天是否已過時」的檢查（聚合全部小時）
foreach ($selected as $i => $slot) {
    if (!is_array($slot) || !array_key_exists('classroomId', $slot) || !array_key_exists('hour', $slot)) {
        redirect_with_errors($bookingDate, ["第 " . ($i + 1) . " 個時段資料缺少必要欄位（classroomId/hour）"]);
    }
    $classroomId = filter_var($slot['classroomId'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $hour = filter_var($slot['hour'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 23]]);
    if ($classroomId === false || $hour === false) {
        redirect_with_errors($bookingDate, ["第 " . ($i + 1) . " 個時段的 classroomId/hour 超出允許範圍"]);
    }
    // 營運時段：允許的起始小時 8–20（20→到 21:00）
    if ($hour < 8 || $hour > 20) {
        redirect_with_errors($bookingDate, ["第 " . ($i + 1) . " 個時段不在營運時段（允許 08–21，起始小時僅能 08–20）"]);
    }

    $groupedByClassroom[$classroomId][] = (int)$hour;
    $allHoursFlat[] = (int)$hour;
}

if (empty($groupedByClassroom)) {
    redirect_with_errors($bookingDate, ['所有欄位都是必填的']);
}

// 不允許過去的日期，也不允許當天已過的時段
$now = new DateTime('now', $tz);
$baseDay = DateTime::createFromFormat('Y-m-d H:i:s', $bookingDate . ' 00:00:00', $tz);
if (!$baseDay) {
    redirect_with_errors($bookingDate, ['日期解析錯誤']);
}

// 檢查是否是過去的日期
$today = new DateTime('today', $tz);
if ($baseDay < $today) {
    redirect_with_errors($bookingDate, ['不能預約過去的日期']);
}

$pastHours = [];
if ($baseDay->format('Y-m-d') === $now->format('Y-m-d')) {
    $currentHour = (int)$now->format('H');
    $currentMinute = (int)$now->format('i');
    
    foreach (array_unique($allHoursFlat) as $h) {
        // 檢查是否為已過時間
        $isPastHour = 
            $h < $currentHour || 
            ($h === $currentHour && $currentMinute >= 30 && $h >= 13) || // 13:30以後的時段，過了30分就不能預約
            ($h === $currentHour && $h < 13); // 上午時段，當前小時就不能預約
            
        if ($isPastHour) {
            $pastHours[] = $h;
        }
    }
}
if (!empty($pastHours)) {
    sort($pastHours);
    $txt = implode(', ', array_map(fn($h) => sprintf('%02d:00', $h), $pastHours));
    redirect_with_errors($bookingDate, ["以下時段已經過去，無法預約：{$txt}"]);
}

// 檢查預約月份的租借限制
try {
    // 初始化UserModel
    $userModel = new UserModel();
    
    // 從預約日期中獲取年月
    $yearMonth = substr($bookingDate, 0, 7); // 格式 YYYY-MM
    
    // 檢查預約月份的租借次數
    $monthBookingCount = $userModel->getMonthBookingCount($_SESSION['user_id'], $yearMonth);
    
    // 如果該月預約已經達到或超過3個，則禁止再預約
    if ($monthBookingCount >= 3) {
        // 顯示年月
        $dateObj = DateTime::createFromFormat('Y-m-d', $bookingDate);
        $formattedMonth = $dateObj->format('Y年m月');
        redirect_with_errors($bookingDate, ["您在{$formattedMonth}的預約已達到上限（每月最多3個），請選擇其他月份或取消一些該月的預約"]);
    }
} catch (Exception $e) {
    error_log('檢查預約限制時出錯: ' . $e->getMessage(), 0);
    redirect_with_errors($bookingDate, ['檢查預約限制時發生錯誤，請稍後再試']);
}

// 執行交易
try {
    $pdo = getDbConnection();

    $pdo->beginTransaction();
    $bookingCount = 0;

    foreach ($groupedByClassroom as $classroomId => $hours) {
        // 去重 + 排序
        $hours = array_values(array_unique(array_map('intval', $hours)));
        sort($hours);

        // 取教室與權限
        $stmt = $pdo->prepare("
            SELECT c.classroom_ID, c.classroom_name,
                   COALESCE(cp.allowed_roles, 'student,teacher') AS allowed_roles
            FROM classrooms c
            LEFT JOIN classroom_permissions cp
              ON c.classroom_ID = cp.classroom_id
            WHERE c.classroom_ID = ?
            LIMIT 1
        ");
        $stmt->execute([$classroomId]);
        $classroom = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$classroom) {
            $pdo->rollBack();
            redirect_with_errors($bookingDate, ['某些選擇的教室不存在']);
        }

        // 權限：teacher 無條件通過；student 必須被允許
        if ($sessionRole !== 'teacher') {
            $allowedRoles = array_map(fn($r) => strtolower(trim($r)), explode(',', (string)$classroom['allowed_roles']));
            if (!in_array('student', $allowedRoles, true)) {
                $pdo->rollBack();
                redirect_with_errors($bookingDate, ["您沒有權限預約教室「{$classroom['classroom_name']}」"]);
            }
        }

        // 檢查是否衝突（以小時檢查）
        $placeholders = implode(',', array_fill(0, count($hours), '?'));
        $params = array_merge([$classroomId, $bookingDate], $hours);
        $stmt = $pdo->prepare("
            SELECT bs.hour
            FROM booking_slots bs
            WHERE bs.classroom_ID = ?
              AND bs.`date` = ?
              AND bs.hour IN ($placeholders)
        ");
        $stmt->execute($params);
        $bookedHours = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        if (!empty($bookedHours)) {
            sort($bookedHours);
            $bookedStr = implode(', ', array_map(fn($h) => sprintf('%02d:00', $h), $bookedHours));
            $pdo->rollBack();
            redirect_with_errors($bookingDate, ["教室「{$classroom['classroom_name']}」的時段 {$bookedStr} 已被預約"]);
        }

        // 把連續時段分組（例如 8,9,10 → 一組）
        $timeGroups = [];
        $current = [$hours[0]];
        for ($i = 1; $i < count($hours); $i++) {
            if ($hours[$i] === $hours[$i - 1] + 1) {
                $current[] = $hours[$i];
            } else {
                $timeGroups[] = $current;
                $current = [$hours[$i]];
            }
        }
        $timeGroups[] = $current;

        // 逐組建立 booking + slots
        foreach ($timeGroups as $group) {
            $startHour = $group[0];
            $endHour = end($group) + 1; // 結束為下一小時（最多 21）

            // 用 DateTime 組起訖，避免 24:00 問題
            $startDt = (clone $baseDay)->modify("+{$startHour} hours")->format('Y-m-d H:i:s');
            $endDt   = (clone $baseDay)->modify("+{$endHour} hours")->format('Y-m-d H:i:s');

            // 再保險：不得超過 21:00
            $maxEnd = (clone $baseDay)->modify('+21 hours'); // 當日 21:00
            if (new DateTime($endDt, $tz) > $maxEnd) {
                $pdo->rollBack();
                redirect_with_errors($bookingDate, ['超出營運時段（最晚至 21:00）']);
            }

            // 處理是否要求錄播（來自表單，會影響 bookings.requires_recording）
            $requiresRecording = isset($_POST['requires_recording']) && ($_POST['requires_recording'] == '1' || $_POST['requires_recording'] === 1) ? 1 : 0;

            // 處理要求設備（可為多選）
            $requestedEquipment = '';
            if (isset($_POST['equipment']) && is_array($_POST['equipment'])) {
                $allowedEquipment = array_map('strval', $_POST['equipment']);
                // 過濾掉可能的惡意字元並使用逗號分隔儲存
                $clean = array_map(function($v) { return preg_replace('/[^a-zA-Z0-9_\- ]/', '', trim($v)); }, $allowedEquipment);
                $clean = array_filter($clean, fn($x) => $x !== '');
                if (!empty($clean)) {
                    $requestedEquipment = implode(',', $clean);
                }
            }

            // 建立 bookings（包含 requires_recording 與 requested_equipment 欄位）
            $stmt = $pdo->prepare("\
                INSERT INTO bookings (classroom_ID, user_ID, status, start_datetime, end_datetime, purpose, requires_recording, requested_equipment)\
                VALUES (?, ?, 'booked', ?, ?, ?, ?, ?)\
            ");
            $stmt->execute([$classroomId, $_SESSION['user_id'], $startDt, $endDt, $purpose, $requiresRecording, $requestedEquipment]);
            $bookingId = $pdo->lastInsertId();

            // 建立每小時 slots
            $ins = $pdo->prepare("
                INSERT INTO booking_slots (booking_ID, classroom_ID, `date`, hour)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($group as $h) {
                try {
                    $ins->execute([$bookingId, $classroomId, $bookingDate, (int)$h]);
                } catch (PDOException $ex) {
                    // 1062 duplicate key → 被同時搶先
                    if ((int)$ex->errorInfo[1] === 1062) {
                        $pdo->rollBack();
                        redirect_with_errors($bookingDate, ['有時段被同時搶先預約了，請重新選擇']);
                    }
                    $pdo->rollBack();
                    error_log('建立時段失敗: ' . $ex->getMessage(), 0);
                    redirect_with_errors($bookingDate, ['預約時段創建失敗，請稍後再試']);
                }
            }

            $bookingCount++;
        }
    }

    $pdo->commit();

    if ($bookingCount > 1) {
        redirect_success($bookingDate, "預約成功！已創建 {$bookingCount} 筆預約，您可以在「我的預約」中查看詳情。");
    } else {
        redirect_success($bookingDate, "預約成功！您可以在「我的預約」中查看詳情。");
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('處理預約時出錯: ' . $e->getMessage(), 0);
    redirect_with_errors($bookingDate, ['處理預約時發生錯誤，請稍後再試']);
}
