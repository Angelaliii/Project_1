<?php
// api/classrooms.php - 教室管理API

require_once 'config.php';

// 路由處理
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

switch ($action) {
    case 'list':
        listClassrooms();
        break;
    case 'get':
        getClassroom();
        break;
    case 'create':
        createClassroom();
        break;
    case 'update':
        updateClassroom();
        break;
    case 'delete':
        deleteClassroom();
        break;
    default:
        sendResponse(400, '無效的操作');
}

// 獲取教室列表
function listClassrooms() {
    // 驗證用戶身份
    $userId = authenticateAPI();
    
    try {
        $pdo = connectDB();
        
        // 獲取查詢參數
        $search = isset($_GET['search']) ? $_GET['search'] : null;
        $building = isset($_GET['building']) ? $_GET['building'] : null;
        
        // 構建SQL查詢
        $sql = "SELECT * FROM classrooms WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (classroom_name LIKE ? OR building LIKE ? OR room LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if ($building) {
            $sql .= " AND building = ?";
            $params[] = $building;
        }
        
        $sql .= " ORDER BY building, room";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $classrooms = $stmt->fetchAll();
        
        // 不返回 BLOB 數據
        foreach ($classrooms as &$classroom) {
            if (isset($classroom['picture'])) {
                $classroom['has_picture'] = !empty($classroom['picture']);
                unset($classroom['picture']);
            }
        }
        
        sendResponse(200, '獲取教室列表成功', $classrooms);
    } catch (PDOException $e) {
        sendResponse(500, '獲取教室列表失敗: ' . $e->getMessage());
    }
}

// 獲取單個教室
function getClassroom() {
    // 驗證用戶身份
    $userId = authenticateAPI();
    
    // 獲取要查詢的教室ID
    $classroomId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($classroomId <= 0) {
        sendResponse(400, '無效的教室ID');
    }
    
    try {
        $pdo = connectDB();
        
        $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE classroom_ID = ?");
        $stmt->execute([$classroomId]);
        $classroom = $stmt->fetch();
        
        if (!$classroom) {
            sendResponse(404, '找不到該教室');
        }
        
        // 獲取該教室的預約情況
        $includePicture = isset($_GET['include_picture']) && $_GET['include_picture'] === 'true';
        
        if (!$includePicture && isset($classroom['picture'])) {
            $classroom['has_picture'] = !empty($classroom['picture']);
            unset($classroom['picture']);
        } else if (isset($classroom['picture']) && !empty($classroom['picture'])) {
            // 轉換為 base64 格式
            $classroom['picture'] = base64_encode($classroom['picture']);
        }
        
        sendResponse(200, '獲取教室信息成功', $classroom);
    } catch (PDOException $e) {
        sendResponse(500, '獲取教室信息失敗: ' . $e->getMessage());
    }
}

// 創建教室
function createClassroom() {
    // 需要教師權限
    $userId = authenticateAPI();
    if (!isTeacher()) {
        sendResponse(403, '沒有權限執行此操作');
    }
    
    // 獲取POST數據
    $data = getRequestData();
    
    // 驗證必填字段
    if (empty($data['classroom_name'])) {
        sendResponse(400, '缺少必填字段');
    }
    
    try {
        $pdo = connectDB();
        
        // 準備插入數據
        $fields = ['classroom_name'];
        $placeholders = ['?'];
        $params = [$data['classroom_name']];
        
        // 可選字段
        if (isset($data['building'])) {
            $fields[] = 'building';
            $placeholders[] = '?';
            $params[] = $data['building'];
        }
        
        if (isset($data['room'])) {
            $fields[] = 'room';
            $placeholders[] = '?';
            $params[] = $data['room'];
        }
        
        // 圖片處理
        if (!empty($data['picture']) && is_string($data['picture'])) {
            // 假設圖片是 base64 編碼的
            $imageData = base64_decode($data['picture']);
            $fields[] = 'picture';
            $placeholders[] = '?';
            $params[] = $imageData;
        }
        
        // 構建SQL
        $sql = "INSERT INTO classrooms (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);
        
        if ($success) {
            $newClassroomId = $pdo->lastInsertId();
            sendResponse(201, '教室創建成功', ['classroom_ID' => $newClassroomId]);
        } else {
            sendResponse(500, '教室創建失敗');
        }
    } catch (PDOException $e) {
        sendResponse(500, '教室創建失敗: ' . $e->getMessage());
    }
}

// 更新教室
function updateClassroom() {
    // 需要教師權限
    $userId = authenticateAPI();
    if (!isTeacher()) {
        sendResponse(403, '沒有權限執行此操作');
    }
    
    // 獲取數據
    $data = getRequestData();
    
    // 驗證必填字段
    if (!isset($data['classroom_ID']) || intval($data['classroom_ID']) <= 0) {
        sendResponse(400, '無效的教室ID');
    }
    
    $classroomId = intval($data['classroom_ID']);
    
    try {
        $pdo = connectDB();
        
        // 檢查教室是否存在
        $stmt = $pdo->prepare("SELECT classroom_ID FROM classrooms WHERE classroom_ID = ?");
        $stmt->execute([$classroomId]);
        if (!$stmt->fetch()) {
            sendResponse(404, '找不到該教室');
        }
        
        // 準備更新字段
        $updateFields = [];
        $params = [];
        
        // 可更新字段
        if (isset($data['classroom_name'])) {
            $updateFields[] = "classroom_name = ?";
            $params[] = $data['classroom_name'];
        }
        
        if (isset($data['building'])) {
            $updateFields[] = "building = ?";
            $params[] = $data['building'];
        }
        
        if (isset($data['room'])) {
            $updateFields[] = "room = ?";
            $params[] = $data['room'];
        }
        
        // 圖片處理
        if (isset($data['picture'])) {
            if (empty($data['picture'])) {
                // 清空圖片
                $updateFields[] = "picture = NULL";
            } else {
                // 更新圖片
                $imageData = base64_decode($data['picture']);
                $updateFields[] = "picture = ?";
                $params[] = $imageData;
            }
        }
        
        // 如果沒有要更新的字段
        if (empty($updateFields)) {
            sendResponse(400, '沒有提供要更新的字段');
        }
        
        // 添加教室ID到參數列表
        $params[] = $classroomId;
        
        // 執行更新
        $sql = "UPDATE classrooms SET " . implode(", ", $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE classroom_ID = ?";
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);
        
        if ($success) {
            sendResponse(200, '教室信息更新成功');
        } else {
            sendResponse(500, '教室信息更新失敗');
        }
    } catch (PDOException $e) {
        sendResponse(500, '教室信息更新失敗: ' . $e->getMessage());
    }
}

// 刪除教室
function deleteClassroom() {
    // 需要教師權限
    $userId = authenticateAPI();
    if (!isTeacher()) {
        sendResponse(403, '沒有權限執行此操作');
    }
    
    // 獲取要刪除的教室ID
    $classroomId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($classroomId <= 0) {
        sendResponse(400, '無效的教室ID');
    }
    
    try {
        $pdo = connectDB();
        
        // 檢查教室是否存在
        $stmt = $pdo->prepare("SELECT classroom_ID FROM classrooms WHERE classroom_ID = ?");
        $stmt->execute([$classroomId]);
        if (!$stmt->fetch()) {
            sendResponse(404, '找不到該教室');
        }
        
        // 檢查是否有相關預約
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE classroom_ID = ?");
        $stmt->execute([$classroomId]);
        $bookingCount = $stmt->fetchColumn();
        
        if ($bookingCount > 0) {
            sendResponse(409, '無法刪除，該教室已有相關預約記錄');
        }
        
        // 執行刪除
        $stmt = $pdo->prepare("DELETE FROM classrooms WHERE classroom_ID = ?");
        $success = $stmt->execute([$classroomId]);
        
        if ($success) {
            sendResponse(200, '教室刪除成功');
        } else {
            sendResponse(500, '教室刪除失敗');
        }
    } catch (PDOException $e) {
        sendResponse(500, '教室刪除失敗: ' . $e->getMessage());
    }
}
