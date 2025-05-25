<?php
// api/classrooms/index.php - 處理教室相關的API請求

// 設置錯誤處理，顯示詳細錯誤信息用於調試
error_reporting(E_ALL);
ini_set('display_errors', 1);  // 修改為顯示錯誤
// 禁用自訂錯誤處理器，讓錯誤直接顯示
/*
set_error_handler(function($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});
*/

try {
    require_once dirname(__DIR__) . '/config.php';

    // 設置CORS頭
    setCorsHeaders();

    // 確定HTTP方法
    $method = $_SERVER['REQUEST_METHOD'];
    // 獲取教室ID（如果在URL中指定）
    $classroomId = isset($_GET['id']) ? intval($_GET['id']) : null;

    // 添加調試信息
    // echo "<!-- 請求方法: $method, 教室ID: " . ($classroomId ? $classroomId : "無") . " -->\n";

    switch ($method) {
        case 'GET':
            if ($classroomId) {
                getClassroom($classroomId);
            } else {
                listClassrooms();
            }
            break;
        case 'POST':
            createClassroom();
            break;
        case 'PUT':
            if (!$classroomId) {
                sendError('更新教室需要教室ID', 400);
            }
            updateClassroom($classroomId);
            break;
        case 'DELETE':
            if (!$classroomId) {
                sendError('刪除教室需要教室ID', 400);
            }
            deleteClassroom($classroomId);
            break;
        default:
            sendError('不支持的HTTP方法', 405);
            break;
    }
} catch (Exception $e) {
    error_log("API 未捕獲的錯誤: " . $e->getMessage() . " 在文件 " . $e->getFile() . " 行 " . $e->getLine());
    // 顯示詳細錯誤信息給用戶
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}

// 獲取所有教室
function listClassrooms() {
    // 注意：移除了 HTML 註釋輸出以確保純 JSON 格式
    try {
        // 記錄嘗試連接
        error_log("API: 嘗試連接數據庫獲取教室列表");
        
        $pdo = connectDB();
        error_log("API: 成功連接數據庫");
        
        // 記錄準備查詢
        error_log("API: 準備查詢 classrooms 表");
        
        $stmt = $pdo->prepare("SELECT classroom_ID, classroom_name, building, room, created_at, updated_at FROM classrooms ORDER BY classroom_ID");
        $stmt->execute();
        $classrooms = $stmt->fetchAll();
        
        error_log("API: 成功獲取 " . count($classrooms) . " 筆教室記錄");
        
        sendResponse(['status' => 'success', 'classrooms' => $classrooms]);
    } catch (PDOException $e) {
        error_log("API 數據庫錯誤: " . $e->getMessage() . " 在文件 " . $e->getFile() . " 行 " . $e->getLine());
        sendError('數據庫錯誤: ' . $e->getMessage(), 500);
    } catch (Exception $e) {
        error_log("API 一般錯誤: " . $e->getMessage() . " 在文件 " . $e->getFile() . " 行 " . $e->getLine());
        sendError('獲取教室列表時發生錯誤: ' . $e->getMessage(), 500);
    }
}

// 獲取單個教室
function getClassroom($classroomId) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT classroom_ID, classroom_name, building, room, created_at, updated_at FROM classrooms WHERE classroom_ID = ?");
        $stmt->execute([$classroomId]);
        $classroom = $stmt->fetch();
        
        if (!$classroom) {
            sendError('教室不存在', 404);
        }
        
        sendResponse(['status' => 'success', 'classroom' => $classroom]);
    } catch (Exception $e) {
        sendError('獲取教室時發生錯誤: ' . $e->getMessage(), 500);
    }
}

// 創建新教室
function createClassroom() {
    try {
        // 驗證管理員權限
        if (!isAdmin() && !isTeacher()) {
            sendError('您沒有創建教室的權限', 403);
            return;
        }

        // 獲取和驗證數據
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['classroom_name']) || !isset($data['building']) || !isset($data['room'])) {
            sendError('教室數據不完整', 400);
            return;
        }

        $pdo = connectDB();
        $stmt = $pdo->prepare("INSERT INTO classrooms (classroom_name, building, room) VALUES (?, ?, ?)");
        $stmt->execute([
            $data['classroom_name'],
            $data['building'],
            $data['room']
        ]);

        $newId = $pdo->lastInsertId();
        sendResponse([
            'status' => 'success',
            'message' => '教室創建成功',
            'classroom_ID' => $newId
        ]);
    } catch (Exception $e) {
        sendError('創建教室時發生錯誤: ' . $e->getMessage(), 500);
    }
}

// 更新教室
function updateClassroom($classroomId) {
    try {
        // 驗證管理員權限
        if (!isAdmin() && !isTeacher()) {
            sendError('您沒有更新教室的權限', 403);
            return;
        }

        // 獲取和驗證數據
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            sendError('無效的數據格式', 400);
            return;
        }

        // 檢查教室是否存在
        $pdo = connectDB();
        $checkStmt = $pdo->prepare("SELECT classroom_ID FROM classrooms WHERE classroom_ID = ?");
        $checkStmt->execute([$classroomId]);
        if ($checkStmt->rowCount() === 0) {
            sendError('教室不存在', 404);
            return;
        }

        // 建立更新查詢
        $updateFields = [];
        $updateValues = [];

        if (isset($data['classroom_name'])) {
            $updateFields[] = "classroom_name = ?";
            $updateValues[] = $data['classroom_name'];
        }

        if (isset($data['building'])) {
            $updateFields[] = "building = ?";
            $updateValues[] = $data['building'];
        }

        if (isset($data['room'])) {
            $updateFields[] = "room = ?";
            $updateValues[] = $data['room'];
        }

        // 添加更新時間
        $updateFields[] = "updated_at = CURRENT_TIMESTAMP";

        // 如果沒有需要更新的欄位
        if (empty($updateFields)) {
            sendError('沒有提供可更新的數據', 400);
            return;
        }

        // 執行更新
        $updateValues[] = $classroomId;
        $sql = "UPDATE classrooms SET " . implode(", ", $updateFields) . " WHERE classroom_ID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateValues);

        sendResponse([
            'status' => 'success',
            'message' => '教室更新成功'
        ]);
    } catch (Exception $e) {
        sendError('更新教室時發生錯誤: ' . $e->getMessage(), 500);
    }
}

// 刪除教室
function deleteClassroom($classroomId) {
    try {
        // 驗證管理員權限
        if (!isAdmin()) {
            sendError('您沒有刪除教室的權限', 403);
            return;
        }

        // 檢查教室是否存在
        $pdo = connectDB();
        $checkStmt = $pdo->prepare("SELECT classroom_ID FROM classrooms WHERE classroom_ID = ?");
        $checkStmt->execute([$classroomId]);
        if ($checkStmt->rowCount() === 0) {
            sendError('教室不存在', 404);
            return;
        }

        // 檢查是否有相關預約
        $bookingCheck = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE classroom_ID = ?");
        $bookingCheck->execute([$classroomId]);
        $hasBookings = ($bookingCheck->fetchColumn() > 0);

        if ($hasBookings) {
            sendError('無法刪除教室，已存在相關預約', 400);
            return;
        }

        // 刪除教室
        $stmt = $pdo->prepare("DELETE FROM classrooms WHERE classroom_ID = ?");
        $stmt->execute([$classroomId]);

        sendResponse([
            'status' => 'success',
            'message' => '教室刪除成功'
        ]);
    } catch (Exception $e) {
        sendError('刪除教室時發生錯誤: ' . $e->getMessage(), 500);
    }
}

// 不需要重複定義 sendResponse 和 sendError 函數，已在 api/config.php 中定義
?>
