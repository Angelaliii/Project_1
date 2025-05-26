<?php
/**
 * 預約控制器 - 處理預約相關請求
 */
class BookingController extends ApiController {
    /**
     * 顯示用戶的預約列表
     */
    public function index() {
        // 確保用戶已登入
        $this->requireLogin();
        
        // 分頁參數
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = 10; // 每頁顯示的預約數量
        $offset = ($page - 1) * $pageSize;
        
        // 篩選條件
        $status = isset($_GET['status']) ? sanitize($_GET['status']) : null;
        
        // 載入預約數據
        $bookingModel = $this->loadModel('Booking');
        $userId = $_SESSION['user_id'];
        
        if ($status) {
            $bookings = $bookingModel->getUserBookings($userId, $status, $pageSize, $offset);
            $totalBookings = $bookingModel->count($userId, $status);
        } else {
            $bookings = $bookingModel->getUserBookings($userId, null, $pageSize, $offset);
            $totalBookings = $bookingModel->count($userId);
        }
        
        // 計算總頁數
        $totalPages = ceil($totalBookings / $pageSize);
        
        // 準備數據
        $data = [
            'bookings' => $bookings,
            'status' => $status,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalBookings' => $totalBookings,
            'totalPages' => $totalPages
        ];
        
        // 載入視圖
        $this->view('booking/index', $data);
    }
    
    /**
     * 顯示預約詳情
     */
    public function viewDetail($id = null) {
        // 確保用戶已登入
        $this->requireLogin();
        
        // 確保提供了預約 ID
        if (!$id) {
            // 如果沒有提供 ID，從 GET 參數中獲取
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $this->redirect('/booking');
                return;
            }
        }
        
        // 載入預約數據
        $bookingModel = $this->loadModel('Booking');
        $booking = $bookingModel->getById($id);
        
        if (!$booking) {
            // 預約不存在，重定向到預約列表
            setFlash('booking_error', '預約不存在', 'danger');
            $this->redirect('/booking');
            return;
        }
        
        // 檢查是否為該用戶的預約或具有管理權限
        $userId = $_SESSION['user_id'];
        if ($booking['user_ID'] != $userId && !isTeacher() && !isAdmin()) {
            // 沒有權限查看其他用戶的預約
            setFlash('booking_error', '您沒有權限查看此預約', 'danger');
            $this->redirect('/booking');
            return;
        }
        
        // 準備數據
        $data = [
            'booking' => $booking
        ];
        
        // 載入視圖
        $this->view('booking/detail', $data);
    }
    
    /**
     * 創建新預約
     */
    public function create($classroomId = null) {
        // 確保用戶已登入
        $this->requireLogin();
        
        // 確保提供了教室 ID
        if (!$classroomId) {
            // 如果沒有提供 ID，從 GET 參數中獲取
            $classroomId = $_GET['classroom_id'] ?? null;
            
            if (!$classroomId) {
                $this->redirect('/classroom');
                return;
            }
        }
        
        // 載入教室數據
        $classroomModel = $this->loadModel('Classroom');
        $classroom = $classroomModel->getById($classroomId);
        
        if (!$classroom) {
            // 教室不存在，重定向到教室列表
            setFlash('booking_error', '教室不存在', 'danger');
            $this->redirect('/classroom');
            return;
        }
        
        $errors = [];
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $startDate = sanitize($_POST['start_date'] ?? '');
            $startTime = sanitize($_POST['start_time'] ?? '');
            $endDate = sanitize($_POST['end_date'] ?? '');
            $endTime = sanitize($_POST['end_time'] ?? '');
            $purpose = sanitize($_POST['purpose'] ?? '');
            
            // 表單驗證
            if (empty($startDate) || empty($startTime)) {
                $errors['start_datetime'] = '請選擇開始日期和時間';
            }
            
            if (empty($endDate) || empty($endTime)) {
                $errors['end_datetime'] = '請選擇結束日期和時間';
            }
            
            // 組合日期和時間
            $startDatetime = $startDate . ' ' . $startTime;
            $endDatetime = $endDate . ' ' . $endTime;
            
            // 驗證日期時間格式
            if (!strtotime($startDatetime)) {
                $errors['start_datetime'] = '無效的開始日期或時間格式';
            }
            
            if (!strtotime($endDatetime)) {
                $errors['end_datetime'] = '無效的結束日期或時間格式';
            }
            
            // 確保開始時間早於結束時間
            if (strtotime($startDatetime) >= strtotime($endDatetime)) {
                $errors['datetime'] = '開始時間必須早於結束時間';
            }
            
            // 如果沒有錯誤，創建預約
            if (empty($errors)) {
                $bookingModel = $this->loadModel('Booking');
                
                $bookingData = [
                    'classroom_ID' => $classroomId,
                    'user_ID' => $_SESSION['user_id'],
                    'status' => 'booked',
                    'start_datetime' => $startDatetime,
                    'end_datetime' => $endDatetime,
                    'purpose' => $purpose
                ];
                
                $result = $bookingModel->create($bookingData);
                
                if (isset($result['error'])) {
                    $errors['create'] = $result['error'];
                } else {
                    // 設置成功訊息
                    setFlash('booking_success', '預約創建成功', 'success');
                    
                    // 重定向到預約列表
                    $this->redirect('/booking');
                    return;
                }
            }
        } else {
            // 默認值，預約一小時
            $startDate = date('Y-m-d');
            $startTime = date('H:00', strtotime('+1 hour'));
            $endDate = date('Y-m-d');
            $endTime = date('H:00', strtotime('+2 hours'));
        }
        
        // 準備數據
        $data = [
            'classroom' => $classroom,
            'start_date' => $startDate ?? '',
            'start_time' => $startTime ?? '',
            'end_date' => $endDate ?? '',
            'end_time' => $endTime ?? '',
            'purpose' => $purpose ?? '',
            'errors' => $errors
        ];
        
        // 載入視圖
        $this->view('booking/create', $data);
    }
    
    /**
     * 取消預約
     */
    public function cancel($id = null) {
        // 確保用戶已登入
        $this->requireLogin();
        
        // 確保提供了預約 ID
        if (!$id) {
            // 如果沒有提供 ID，從 GET 參數中獲取
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $this->redirect('/booking');
                return;
            }
        }
        
        // 載入預約數據
        $bookingModel = $this->loadModel('Booking');
        $booking = $bookingModel->getById($id);
        
        if (!$booking) {
            // 預約不存在，重定向到預約列表
            setFlash('booking_error', '預約不存在', 'danger');
            $this->redirect('/booking');
            return;
        }
        
        // 檢查是否為該用戶的預約或具有管理權限
        $userId = $_SESSION['user_id'];
        if ($booking['user_ID'] != $userId && !isTeacher() && !isAdmin()) {
            // 沒有權限取消其他用戶的預約
            setFlash('booking_error', '您沒有權限取消此預約', 'danger');
            $this->redirect('/booking');
            return;
        }
        
        // 檢查預約是否已經取消或已完成
        if ($booking['status'] === 'cancelled' || $booking['status'] === 'completed') {
            setFlash('booking_error', '此預約已經取消或已完成，無法再次取消', 'danger');
            $this->redirect('/booking/viewDetail/' . $id);
            return;
        }
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // 取消預約
            $result = $bookingModel->cancel($id);
            
            if (isset($result['error'])) {
                setFlash('booking_error', $result['error'], 'danger');
            } else {
                // 設置成功訊息
                setFlash('booking_success', '預約已成功取消', 'success');
            }
            
            // 重定向到預約列表
            $this->redirect('/booking');
            return;
        }
        
        // 準備數據
        $data = [
            'booking' => $booking
        ];
        
        // 載入視圖
        $this->view('booking/cancel', $data);
    }
    
    /**
     * 顯示教師的預約管理頁面
     */
    public function manage() {
        // 確保用戶已登入並具有教師或管理員權限
        $this->requireLogin();
        if (!isTeacher() && !isAdmin()) {
            $this->redirect('/booking');
            return;
        }
        
        // 分頁參數
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = 10; // 每頁顯示的預約數量
        $offset = ($page - 1) * $pageSize;
        
        // 篩選條件
        $status = isset($_GET['status']) ? sanitize($_GET['status']) : null;
        
        // 載入預約數據
        $bookingModel = $this->loadModel('Booking');
        
        if ($status) {
            $bookings = $bookingModel->getAll($status, null, null, $pageSize, $offset);
            $totalBookings = $bookingModel->count(null, $status);
        } else {
            $bookings = $bookingModel->getAll(null, null, null, $pageSize, $offset);
            $totalBookings = $bookingModel->count();
        }
        
        // 計算總頁數
        $totalPages = ceil($totalBookings / $pageSize);
        
        // 準備數據
        $data = [
            'bookings' => $bookings,
            'status' => $status,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalBookings' => $totalBookings,
            'totalPages' => $totalPages
        ];
        
        // 載入視圖
        $this->view('booking/manage', $data);
    }
    
    /**
     * 更改預約狀態（僅限教師和管理員）
     */
    public function changeStatus($id = null) {
        // 確保用戶已登入並具有教師或管理員權限
        $this->requireLogin();
        if (!isTeacher() && !isAdmin()) {
            $this->redirect('/booking');
            return;
        }
        
        // 確保提供了預約 ID
        if (!$id) {
            // 如果沒有提供 ID，從 GET 參數中獲取
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $this->redirect('/booking/manage');
                return;
            }
        }
        
        // 載入預約數據
        $bookingModel = $this->loadModel('Booking');
        $booking = $bookingModel->getById($id);
        
        if (!$booking) {
            // 預約不存在，重定向到預約管理頁面
            setFlash('booking_error', '預約不存在', 'danger');
            $this->redirect('/booking/manage');
            return;
        }
        
        $errors = [];
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $status = sanitize($_POST['status'] ?? '');
            
            // 表單驗證
            if (empty($status)) {
                $errors['status'] = '請選擇狀態';
            } else if (!in_array($status, ['available', 'booked', 'in_use', 'completed', 'cancelled'])) {
                $errors['status'] = '無效的狀態';
            }
            
            // 如果沒有錯誤，更新預約狀態
            if (empty($errors)) {
                $result = $bookingModel->update($id, ['status' => $status]);
                
                if (isset($result['error'])) {
                    $errors['update'] = $result['error'];
                } else {
                    // 設置成功訊息
                    setFlash('booking_success', '預約狀態已更新', 'success');
                    
                    // 重定向到預約管理頁面
                    $this->redirect('/booking/manage');
                    return;
                }
            }
        }
        
        // 準備數據
        $data = [
            'booking' => $booking,
            'errors' => $errors
        ];
        
        // 載入視圖
        $this->view('booking/change_status', $data);
    }

    /**
     * API: 獲取預約列表
     */
    public function apiList() {
        $this->setCorsHeaders();
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->sendError('只允許GET請求', 405);
        }
        
        $this->requireApiAuth();
        
        try {
            $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
            $classroomId = isset($_GET['classroom_id']) ? intval($_GET['classroom_id']) : null;
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            $date = isset($_GET['date']) ? $_GET['date'] : null;
            
            // 驗證權限 - 只能查看自己的預約
            if (!isset($_SESSION['user_id']) || ($userId && $_SESSION['user_id'] != $userId)) {
                $this->sendError('未授權', 403);
            }
            
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
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
            } elseif (isset($_SESSION['user_id'])) {
                // 只能查看自己的預約
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
            
            $this->sendResponse(['bookings' => $bookings]);
        } catch (Exception $e) {
            $this->sendError('獲取預約列表時發生錯誤: ' . $e->getMessage(), 500);
        }
    }

    /**
     * API: 獲取單個預約詳情
     */
    public function apiGet($id) {
        $this->setCorsHeaders();
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->sendError('只允許GET請求', 405);
        }
        
        $this->requireApiAuth();
        
        if (!$id) {
            $this->sendError('缺少預約ID', 400);
        }
        
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            $stmt = $pdo->prepare("SELECT b.*, c.classroom_name, u.user_name 
                                  FROM bookings b 
                                  JOIN users u ON b.user_ID = u.user_id 
                                  JOIN classrooms c ON b.classroom_ID = c.classroom_ID 
                                  WHERE b.booking_ID = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                $this->sendError('預約不存在', 404);
            }
            
            // 驗證權限（只有預約擁有者可以查看）
            if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $booking['user_ID']) {
                $this->sendError('未授權', 403);
            }
            
            // 獲取該預約的所有時段
            $stmt = $pdo->prepare("SELECT * FROM booking_slots WHERE booking_ID = ? ORDER BY date, hour");
            $stmt->execute([$id]);
            $slots = $stmt->fetchAll();
            
            $booking['slots'] = $slots;
            
            $this->sendResponse(['booking' => $booking]);
        } catch (Exception $e) {
            $this->sendError('獲取預約時發生錯誤: ' . $e->getMessage(), 500);
        }
    }

    /**
     * API: 創建新預約
     */
    public function apiCreate() {
        $this->setCorsHeaders();
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('只允許POST請求', 405);
        }
        
        $this->requireApiAuth();
        
        try {
            $data = $this->getJsonInput();
            
            // 驗證必填字段
            if (!isset($data['classroom_ID']) || !isset($data['start_datetime']) || !isset($data['end_datetime'])) {
                $this->sendError('缺少必要欄位', 400);
            }
            
            $userId = $_SESSION['user_id'];
            $classroomId = $data['classroom_ID'];
            $startDatetime = $data['start_datetime'];
            $endDatetime = $data['end_datetime'];
            $purpose = isset($data['purpose']) ? $data['purpose'] : '';
            $status = 'booked'; // 直接設為已預約，無需核可
            
            // 驗證時間格式
            if (!strtotime($startDatetime) || !strtotime($endDatetime)) {
                $this->sendError('無效的時間格式', 400);
            }
            
            // 驗證開始時間小於結束時間
            if (strtotime($startDatetime) >= strtotime($endDatetime)) {
                $this->sendError('開始時間必須早於結束時間', 400);
            }
            
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // 檢查教室是否存在
            $stmt = $pdo->prepare("SELECT classroom_ID FROM classrooms WHERE classroom_ID = ?");
            $stmt->execute([$classroomId]);
            if (!$stmt->fetch()) {
                $this->sendError('教室不存在', 404);
            }
            
            // 檢查時段是否已被預約
            $stmt = $pdo->prepare("SELECT booking_ID FROM bookings 
                                  WHERE classroom_ID = ? 
                                  AND status IN ('booked', 'in_use') 
                                  AND NOT (end_datetime <= ? OR start_datetime >= ?)");
            $stmt->execute([$classroomId, $startDatetime, $endDatetime]);
            if ($stmt->fetch()) {
                $this->sendError('所選時段已被預約', 409);
            }
            
            // 開始事務
            $pdo->beginTransaction();
            
            try {
                // 創建預約
                $stmt = $pdo->prepare("INSERT INTO bookings (classroom_ID, user_ID, status, start_datetime, end_datetime, purpose) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$classroomId, $userId, $status, $startDatetime, $endDatetime, $purpose]);
                
                $bookingId = $pdo->lastInsertId();
                
                // 創建預約時段
                $start = new DateTime($startDatetime);
                $end = new DateTime($endDatetime);
                
                // 計算每個小時的時段並插入
                $interval = new DateInterval('PT1H'); // 1小時間隔
                $period = new DatePeriod($start, $interval, $end);
                
                foreach ($period as $dt) {
                    $date = $dt->format('Y-m-d');
                    $hour = (int)$dt->format('H');
                    
                    $stmt = $pdo->prepare("INSERT INTO booking_slots (booking_ID, date, hour) VALUES (?, ?, ?)");
                    $stmt->execute([$bookingId, $date, $hour]);
                }
                
                $pdo->commit();
                
                $this->sendResponse(['message' => '預約創建成功', 'booking_ID' => $bookingId], 201);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            $this->sendError('創建預約時發生錯誤: ' . $e->getMessage(), 500);
        }
    }

    /**
     * API: 更新預約
     */
    public function apiUpdate($id) {
        $this->setCorsHeaders();
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->sendError('只允許PUT請求', 405);
        }
        
        $this->requireApiAuth();
        
        if (!$id) {
            $this->sendError('缺少預約ID', 400);
        }
        
        try {
            $data = $this->getJsonInput();
            
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // 檢查預約存在性並獲取當前信息
            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_ID = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                $this->sendError('預約不存在', 404);
            }
            
            // 驗證權限
            // 一般用戶只能更改自己的預約，且只能在 booked 狀態下更改
            if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $booking['user_ID']) {
                $this->sendError('未授權', 403);
            }
            
            if (!in_array($booking['status'], ['booked'])) {
                $this->sendError('當前預約狀態無法修改', 400);
            }
            
            // 準備更新語句
            $updates = [];
            $params = [];
            
            // 用戶可以更新的字段
            if (isset($data['status'])) {
                $validStatus = ['booked', 'in_use', 'completed', 'cancelled'];
                if (!in_array($data['status'], $validStatus)) {
                    $this->sendError('無效的狀態', 400);
                }
                
                $updates[] = "status = ?";
                $params[] = $data['status'];
            }
            
            if (isset($data['purpose'])) {
                $updates[] = "purpose = ?";
                $params[] = $data['purpose'];
            }
            
            if (isset($data['start_datetime']) && isset($data['end_datetime'])) {
                // 驗證時間格式
                if (!strtotime($data['start_datetime']) || !strtotime($data['end_datetime'])) {
                    $this->sendError('無效的時間格式', 400);
                }
                
                // 驗證開始時間小於結束時間
                if (strtotime($data['start_datetime']) >= strtotime($data['end_datetime'])) {
                    $this->sendError('開始時間必須早於結束時間', 400);
                }
                
                // 檢查時段是否與其他預約衝突
                $stmt = $pdo->prepare("SELECT booking_ID FROM bookings 
                                      WHERE classroom_ID = ? 
                                      AND booking_ID != ?
                                      AND status IN ('booked', 'in_use') 
                                      AND NOT (end_datetime <= ? OR start_datetime >= ?)");
                $stmt->execute([$booking['classroom_ID'], $id, $data['start_datetime'], $data['end_datetime']]);
                if ($stmt->fetch()) {
                    $this->sendError('所選時段已被其他預約佔用', 409);
                }
                
                $updates[] = "start_datetime = ?";
                $params[] = $data['start_datetime'];
                
                $updates[] = "end_datetime = ?";
                $params[] = $data['end_datetime'];
            }
            
            if (empty($updates)) {
                $this->sendError('沒有要更新的資料', 400);
            }
            
            // 開始事務
            $pdo->beginTransaction();
            
            try {
                // 添加預約ID到參數陣列
                $params[] = $id;
                
                $sql = "UPDATE bookings SET " . implode(", ", $updates) . " WHERE booking_ID = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                // 如果有更新時間，重新生成時段
                if (isset($data['start_datetime']) && isset($data['end_datetime'])) {
                    // 删除舊時段
                    $stmt = $pdo->prepare("DELETE FROM booking_slots WHERE booking_ID = ?");
                    $stmt->execute([$id]);
                    
                    // 創建新時段
                    $start = new DateTime($data['start_datetime']);
                    $end = new DateTime($data['end_datetime']);
                    
                    // 計算每個小時的時段並插入
                    $interval = new DateInterval('PT1H'); // 1小時間隔
                    $period = new DatePeriod($start, $interval, $end);
                    
                    foreach ($period as $dt) {
                        $date = $dt->format('Y-m-d');
                        $hour = (int)$dt->format('H');
                        
                        $stmt = $pdo->prepare("INSERT INTO booking_slots (booking_ID, date, hour) VALUES (?, ?, ?)");
                        $stmt->execute([$id, $date, $hour]);
                    }
                }
                
                $pdo->commit();
                
                $this->sendResponse(['message' => '預約更新成功']);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            $this->sendError('更新預約時發生錯誤: ' . $e->getMessage(), 500);
        }
    }

    /**
     * API: 刪除預約
     */
    public function apiDelete($id) {
        $this->setCorsHeaders();
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->sendError('只允許DELETE請求', 405);
        }
        
        $this->requireApiAuth();
        
        if (!$id) {
            $this->sendError('缺少預約ID', 400);
        }
        
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // 檢查預約存在性並獲取當前信息
            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_ID = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                $this->sendError('預約不存在', 404);
            }
            
            // 驗證權限（只有預約擁有者可以刪除）
            if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $booking['user_ID']) {
                $this->sendError('未授權', 403);
            }
            
            // 只能刪除處於 booked 狀態的預約
            if (!in_array($booking['status'], ['booked'])) {
                $this->sendError('當前預約狀態無法刪除', 400);
            }
            
            // 開始事務
            $pdo->beginTransaction();
            
            try {
                // 删除預約時段
                $stmt = $pdo->prepare("DELETE FROM booking_slots WHERE booking_ID = ?");
                $stmt->execute([$id]);
                
                // 刪除預約
                $stmt = $pdo->prepare("DELETE FROM bookings WHERE booking_ID = ?");
                $stmt->execute([$id]);
                
                $pdo->commit();
                
                $this->sendResponse(['message' => '預約刪除成功']);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            $this->sendError('刪除預約時發生錯誤: ' . $e->getMessage(), 500);
        }
    }

    /**
     * API: 創建預約 (支援時段模式)
     */
    public function apiCreateSlots() {
        $this->setCorsHeaders();
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('只允許POST請求', 405);
        }
        
        $this->requireApiAuth();
        
        try {
            $data = $this->getJsonInput();
            
            // 驗證必填字段
            if (!isset($data['classroom_ID']) || !isset($data['date']) || empty($data['slots'])) {
                $this->sendError('缺少必要欄位', 400);
            }
            
            $userId = $_SESSION['user_id'];
            $classroomId = $data['classroom_ID'];
            $date = $data['date'];
            $slots = $data['slots'];
            $purpose = isset($data['purpose']) ? $data['purpose'] : '';
            
            // 驗證日期格式
            if (!strtotime($date)) {
                $this->sendError('無效的日期格式', 400);
            }
            
            // 驗證時段數組
            if (!is_array($slots) || empty($slots)) {
                $this->sendError('時段不能為空', 400);
            }
            
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // 檢查教室是否存在
            $stmt = $pdo->prepare("SELECT classroom_ID FROM classrooms WHERE classroom_ID = ?");
            $stmt->execute([$classroomId]);
            if (!$stmt->fetch()) {
                $this->sendError('教室不存在', 404);
            }
            
            // 檢查時段是否已被預約
            foreach ($slots as $hour) {
                $stmt = $pdo->prepare("SELECT bs.booking_ID FROM booking_slots bs 
                                      JOIN bookings b ON bs.booking_ID = b.booking_ID 
                                      WHERE b.classroom_ID = ? 
                                      AND bs.date = ? 
                                      AND bs.hour = ? 
                                      AND b.status IN ('booked', 'in_use')");
                $stmt->execute([$classroomId, $date, $hour]);
                if ($stmt->fetch()) {
                    $this->sendError("時段 {$hour}:00 已被預約", 409);
                }
            }
            
            // 計算開始和結束時間
            sort($slots);
            $startHour = min($slots);
            $endHour = max($slots) + 1;
            
            $startDatetime = $date . ' ' . sprintf('%02d:00:00', $startHour);
            $endDatetime = $date . ' ' . sprintf('%02d:00:00', $endHour);
            
            // 開始事務
            $pdo->beginTransaction();
            
            try {
                // 創建預約
                $stmt = $pdo->prepare("INSERT INTO bookings (classroom_ID, user_ID, status, start_datetime, end_datetime, purpose) 
                                      VALUES (?, ?, 'booked', ?, ?, ?)");
                $stmt->execute([$classroomId, $userId, $startDatetime, $endDatetime, $purpose]);
                
                $bookingId = $pdo->lastInsertId();
                
                // 創建預約時段
                foreach ($slots as $hour) {
                    $stmt = $pdo->prepare("INSERT INTO booking_slots (booking_ID, date, hour) VALUES (?, ?, ?)");
                    $stmt->execute([$bookingId, $date, $hour]);
                }
                
                $pdo->commit();
                
                $this->sendResponse(['message' => '預約創建成功', 'booking_ID' => $bookingId], 201);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            $this->sendError('創建預約時發生錯誤: ' . $e->getMessage(), 500);
        }
    }

    /**
     * API: 取消預約
     */
    public function apiCancel($id) {
        $this->setCorsHeaders();
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('只允許POST請求', 405);
        }
        
        $this->requireApiAuth();
        
        if (!$id) {
            $this->sendError('缺少預約ID', 400);
        }
        
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // 檢查預約存在性並獲取當前信息
            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_ID = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();
            
            if (!$booking) {
                $this->sendError('預約不存在', 404);
            }
            
            // 驗證權限（只有預約擁有者可以取消）
            if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $booking['user_ID']) {
                $this->sendError('未授權', 403);
            }
            
            // 只能取消處於 booked 狀態的預約
            if ($booking['status'] !== 'booked') {
                $this->sendError('當前預約狀態無法取消', 400);
            }
            
            // 更新預約狀態為取消
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_ID = ?");
            $stmt->execute([$id]);
            
            $this->sendResponse(['message' => '預約已取消']);
        } catch (Exception $e) {
            $this->sendError('取消預約時發生錯誤: ' . $e->getMessage(), 500);
        }
    }

    /**
     * API: 獲取可用預約時段
     */
    public function apiGetSlots() {
        $this->setCorsHeaders();
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->sendError('只允許GET請求', 405);
        }
        
        try {
            $date = isset($_GET['date']) ? $_GET['date'] : null;
            $classroomId = isset($_GET['classroom_id']) ? intval($_GET['classroom_id']) : null;
            
            if (!$date || !$classroomId) {
                $this->sendError('請提供日期和教室ID', 400);
            }
            
            // 驗證日期格式
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $this->sendError('日期格式不正確，應為 YYYY-MM-DD', 400);
            }
            
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // 檢查教室是否存在
            $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE classroom_ID = ?");
            $stmt->execute([$classroomId]);
            $classroom = $stmt->fetch();
            
            if (!$classroom) {
                $this->sendError("教室不存在 (ID: $classroomId)", 404);
            }
            
            // 獲取當天已被預約的時段
            $stmt = $pdo->prepare("
                SELECT DISTINCT bs.hour 
                FROM booking_slots bs 
                JOIN bookings b ON bs.booking_ID = b.booking_ID 
                WHERE bs.date = ? AND b.classroom_ID = ? AND b.status IN ('booked', 'in_use')
            ");
            $stmt->execute([$date, $classroomId]);
            $bookedSlots = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // 生成9-17點的時段 (共9個小時)
            $slots = [];
            for ($hour = 9; $hour <= 17; $hour++) {
                $slots[] = [
                    'hour' => $hour,
                    'time' => sprintf('%02d:00-%02d:00', $hour, $hour + 1),
                    'available' => !in_array($hour, $bookedSlots)
                ];
            }
            
            $this->sendResponse([
                'status' => 'success',
                'date' => $date,
                'classroom_id' => $classroomId,
                'slots' => $slots,
                'classroom' => $classroom
            ]);
            
        } catch (Exception $e) {
            $this->sendError('獲取可用時段時發生錯誤: ' . $e->getMessage(), 500);
        }
    }
}
