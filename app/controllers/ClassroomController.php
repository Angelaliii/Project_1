<?php
/**
 * 教室控制器 - 處理教室相關請求
 */
class ClassroomController extends ApiController {
    /**
     * 顯示教室列表
     */
    public function index() {
        // 確保用戶已登入
        $this->requireLogin();
        
        // 分頁參數
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = 8; // 每頁顯示的教室數量
        $offset = ($page - 1) * $pageSize;
        
        // 篩選條件
        $building = isset($_GET['building']) ? sanitize($_GET['building']) : null;
        
        // 載入教室數據
        $classroomModel = $this->loadModel('Classroom');
        $classrooms = $classroomModel->getAll($building, $pageSize, $offset);
        $totalClassrooms = $classroomModel->count($building);
        $buildings = $classroomModel->getBuildings();
        
        // 計算總頁數
        $totalPages = ceil($totalClassrooms / $pageSize);
        
        // 準備數據
        $data = [
            'classrooms' => $classrooms,
            'buildings' => $buildings,
            'building' => $building,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalClassrooms' => $totalClassrooms,
            'totalPages' => $totalPages
        ];
        
        // 載入視圖
        $this->view('classroom/index', $data);
    }
    
    /**
     * 顯示教室詳情
     */
    public function viewDetails($id = null) {
        // 確保用戶已登入
        $this->requireLogin();
        
        // 確保提供了教室 ID
        if (!$id) {
            // 如果沒有提供 ID，從 GET 參數中獲取
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $this->redirect('/classroom');
                return;
            }
        }
        
        // 載入教室數據
        $classroomModel = $this->loadModel('Classroom');
        $classroom = $classroomModel->getById($id);
        
        if (!$classroom) {
            // 教室不存在，重定向到教室列表
            setFlash('classroom_error', '教室不存在', 'danger');
            $this->redirect('/classroom');
            return;
        }
        
        // 載入預約數據
        $bookingModel = $this->loadModel('Booking');
        $today = date('Y-m-d');
        $nextWeek = date('Y-m-d', strtotime('+7 days'));
        
        // 獲取未來一周的預約
        $bookings = $bookingModel->getClassroomBookings(
            $id,
            $today,
            $nextWeek,
            ['booked', 'in_use']
        );
        
        // 準備數據
        $data = [
            'classroom' => $classroom,
            'bookings' => $bookings
        ];
        
        // 載入視圖
        $this->view('classroom/view', $data);
    }
    
    /**
     * 創建教室（僅限教師和管理員）
     */
    public function create() {
        // 確保用戶已登入並具有教師或管理員權限
        $this->requireLogin();
        if (!isTeacher() && !isAdmin()) {
            $this->redirect('/classroom');
            return;
        }
        
        $errors = [];
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = sanitize($_POST['classroom_name'] ?? '');
            $building = sanitize($_POST['building'] ?? '');
            $room = sanitize($_POST['room'] ?? '');
            $capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : null;
            $description = sanitize($_POST['description'] ?? '');
            
            // 表單驗證
            if (empty($name)) {
                $errors['classroom_name'] = '請輸入教室名稱';
            }
            
            // 處理上傳的圖片
            $picture = null;
            if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['picture'];
                
                // 檢查文件類型
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($file['type'], $allowedTypes)) {
                    $errors['picture'] = '只允許 JPG, PNG 或 GIF 格式的圖片';
                } else {
                    // 讀取圖片數據
                    $picture = file_get_contents($file['tmp_name']);
                }
            }
            
            // 如果沒有錯誤，創建教室
            if (empty($errors)) {
                $classroomModel = $this->loadModel('Classroom');
                
                $classroomData = [
                    'classroom_name' => $name,
                    'building' => $building,
                    'room' => $room,
                    'capacity' => $capacity,
                    'description' => $description
                ];
                
                if ($picture !== null) {
                    $classroomData['picture'] = $picture;
                }
                
                $classroomId = $classroomModel->create($classroomData);
                
                if ($classroomId) {
                    // 設置成功訊息
                    setFlash('classroom_success', '教室創建成功', 'success');
                    
                    // 重定向到教室列表
                    $this->redirect('/classroom');
                    return;
                } else {
                    $errors['create'] = '創建教室時發生錯誤';
                }
            }
        }
        
        // 準備數據
        $data = [
            'classroom_name' => $name ?? '',
            'building' => $building ?? '',
            'room' => $room ?? '',
            'capacity' => $capacity ?? '',
            'description' => $description ?? '',
            'errors' => $errors
        ];
        
        // 載入視圖
        $this->view('classroom/create', $data);
    }
    
    /**
     * 編輯教室（僅限教師和管理員）
     */
    public function edit($id = null) {
        // 確保用戶已登入並具有教師或管理員權限
        $this->requireLogin();
        if (!isTeacher() && !isAdmin()) {
            $this->redirect('/classroom');
            return;
        }
        
        // 確保提供了教室 ID
        if (!$id) {
            // 如果沒有提供 ID，從 GET 參數中獲取
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $this->redirect('/classroom');
                return;
            }
        }
        
        // 載入教室數據
        $classroomModel = $this->loadModel('Classroom');
        $classroom = $classroomModel->getById($id);
        
        if (!$classroom) {
            // 教室不存在，重定向到教室列表
            setFlash('classroom_error', '教室不存在', 'danger');
            $this->redirect('/classroom');
            return;
        }
        
        $errors = [];
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = sanitize($_POST['classroom_name'] ?? '');
            $building = sanitize($_POST['building'] ?? '');
            $room = sanitize($_POST['room'] ?? '');
            $capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : null;
            $description = sanitize($_POST['description'] ?? '');
            
            // 表單驗證
            if (empty($name)) {
                $errors['classroom_name'] = '請輸入教室名稱';
            }
            
            // 準備更新數據
            $classroomData = [
                'classroom_name' => $name,
                'building' => $building,
                'room' => $room,
                'capacity' => $capacity,
                'description' => $description
            ];
            
            // 處理上傳的圖片
            if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['picture'];
                
                // 檢查文件類型
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($file['type'], $allowedTypes)) {
                    $errors['picture'] = '只允許 JPG, PNG 或 GIF 格式的圖片';
                } else {
                    // 讀取圖片數據
                    $classroomData['picture'] = file_get_contents($file['tmp_name']);
                }
            }
            
            // 如果沒有錯誤，更新教室
            if (empty($errors)) {
                $result = $classroomModel->update($id, $classroomData);
                
                if ($result) {
                    // 設置成功訊息
                    setFlash('classroom_success', '教室更新成功', 'success');
                    
                    // 重定向到教室詳情
                    $this->redirect('/classroom/viewDetails/' . $id);
                    return;
                } else {
                    $errors['update'] = '更新教室時發生錯誤';
                }
            }
        } else {
            // 從資料庫中獲取教室數據，填充表單
            $name = $classroom['classroom_name'];
            $building = $classroom['building'];
            $room = $classroom['room'];
            $capacity = $classroom['capacity'];
            $description = $classroom['description'];
        }
        
        // 準備數據
        $data = [
            'classroom' => $classroom,
            'classroom_name' => $name,
            'building' => $building,
            'room' => $room,
            'capacity' => $capacity,
            'description' => $description,
            'errors' => $errors
        ];
        
        // 載入視圖
        $this->view('classroom/edit', $data);
    }
    
    /**
     * 刪除教室（僅限教師和管理員）
     */
    public function delete($id = null) {
        // 確保用戶已登入並具有教師或管理員權限
        $this->requireLogin();
        if (!isTeacher() && !isAdmin()) {
            $this->redirect('/classroom');
            return;
        }
        
        // 確保提供了教室 ID
        if (!$id) {
            // 如果沒有提供 ID，從 GET 參數中獲取
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $this->redirect('/classroom');
                return;
            }
        }
        
        // 載入教室數據
        $classroomModel = $this->loadModel('Classroom');
        $classroom = $classroomModel->getById($id);
        
        if (!$classroom) {
            // 教室不存在，重定向到教室列表
            setFlash('classroom_error', '教室不存在', 'danger');
            $this->redirect('/classroom');
            return;
        }
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // 刪除教室
            $result = $classroomModel->delete($id);
            
            if ($result) {
                // 設置成功訊息
                setFlash('classroom_success', '教室刪除成功', 'success');
            } else {
                // 設置錯誤訊息
                setFlash('classroom_error', '刪除教室時發生錯誤', 'danger');
            }
            
            // 重定向到教室列表
            $this->redirect('/classroom');
            return;
        }
        
        // 準備數據
        $data = [
            'classroom' => $classroom
        ];
        
        // 載入視圖
        $this->view('classroom/delete', $data);
    }
    
    /**
     * API: 獲取教室列表
     */
    public function apiList() {
        $this->setCorsHeaders();
        
        try {
            $classroomModel = $this->loadModel('Classroom');
            $classrooms = $classroomModel->getAll();
            
            $this->sendResponse([
                'status' => 'success',
                'classrooms' => $classrooms
            ]);
        } catch (Exception $e) {
            error_log("API 獲取教室列表錯誤: " . $e->getMessage());
            $this->sendError('獲取教室列表時發生錯誤: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * API: 獲取單個教室
     */
    public function apiGet($id) {
        $this->setCorsHeaders();
        
        try {
            $classroomModel = $this->loadModel('Classroom');
            $classroom = $classroomModel->getById($id);
            
            if (!$classroom) {
                $this->sendError('教室不存在', 404);
                return;
            }
            
            $this->sendResponse([
                'status' => 'success',
                'classroom' => $classroom
            ]);
        } catch (Exception $e) {
            error_log("API 獲取教室錯誤: " . $e->getMessage());
            $this->sendError('獲取教室時發生錯誤: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * API: 創建教室
     */
    public function apiCreate() {
        $this->setCorsHeaders();
        
        // 要求教師權限
        if (!$this->requireTeacherAuth()) {
            return;
        }
        
        try {
            $data = $this->getRequestData();
            
            // 驗證必要字段
            if (empty($data['classroom_name'])) {
                $this->sendError('教室名稱為必填項目', 400);
                return;
            }
            
            $classroomModel = $this->loadModel('Classroom');
            $result = $classroomModel->create($data);
            
            if ($result) {
                $this->sendResponse([
                    'status' => 'success',
                    'message' => '教室創建成功',
                    'classroom_id' => $result
                ]);
            } else {
                $this->sendError('創建教室失敗', 500);
            }
        } catch (Exception $e) {
            error_log("API 創建教室錯誤: " . $e->getMessage());
            $this->sendError('創建教室時發生錯誤: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * API: 更新教室
     */
    public function apiUpdate($id) {
        $this->setCorsHeaders();
        
        // 要求教師權限
        if (!$this->requireTeacherAuth()) {
            return;
        }
        
        try {
            $data = $this->getRequestData();
            
            $classroomModel = $this->loadModel('Classroom');
            
            // 檢查教室是否存在
            if (!$classroomModel->getById($id)) {
                $this->sendError('教室不存在', 404);
                return;
            }
            
            $result = $classroomModel->update($id, $data);
            
            if ($result) {
                $this->sendResponse([
                    'status' => 'success',
                    'message' => '教室更新成功'
                ]);
            } else {
                $this->sendError('更新教室失敗', 500);
            }
        } catch (Exception $e) {
            error_log("API 更新教室錯誤: " . $e->getMessage());
            $this->sendError('更新教室時發生錯誤: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * API: 刪除教室
     */
    public function apiDelete($id) {
        $this->setCorsHeaders();
        
        // 要求教師權限
        if (!$this->requireTeacherAuth()) {
            return;
        }
        
        try {
            $classroomModel = $this->loadModel('Classroom');
            
            // 檢查教室是否存在
            if (!$classroomModel->getById($id)) {
                $this->sendError('教室不存在', 404);
                return;
            }
            
            $result = $classroomModel->delete($id);
            
            if ($result) {
                $this->sendResponse([
                    'status' => 'success',
                    'message' => '教室刪除成功'
                ]);
            } else {
                $this->sendError('刪除教室失敗', 500);
            }
        } catch (Exception $e) {
            error_log("API 刪除教室錯誤: " . $e->getMessage());
            $this->sendError('刪除教室時發生錯誤: ' . $e->getMessage(), 500);
        }
    }
}
