<?php
/**
 * 預約 API 控制器
 */
class BookingsApiController extends ApiController {
    
    private $bookingModel;
    private $classroomModel;
    
    public function __construct() {
        parent::__construct();
        $this->bookingModel = $this->loadModel('Booking');
        $this->classroomModel = $this->loadModel('Classroom');
    }
    
    /**
     * 獲取預約列表或特定預約
     */
    public function index() {
        if ($this->requestMethod !== 'GET') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        $bookingId = $_GET['id'] ?? null;
        
        if ($bookingId) {
            $this->getBooking($bookingId);
        } else {
            $this->listBookings();
        }
    }
    
    /**
     * 獲取預約列表
     */
    private function listBookings() {
        try {
            // 分頁參數
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            // 篩選參數
            $userId = $_GET['user_id'] ?? null;
            $classroomId = $_GET['classroom_id'] ?? null;
            $status = $_GET['status'] ?? '';
            $date = $_GET['date'] ?? '';
            
            $bookings = $this->bookingModel->getBookings($limit, $offset, $userId, $classroomId, $status, $date);
            $total = $this->bookingModel->getBookingsCount($userId, $classroomId, $status, $date);
            
            $this->sendSuccess([
                'bookings' => $bookings,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_records' => $total,
                    'per_page' => $limit
                ]
            ]);
        } catch (Exception $e) {
            $this->sendError('獲取預約列表失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 獲取特定預約
     */
    private function getBooking($bookingId) {
        try {
            $booking = $this->bookingModel->getBookingById($bookingId);
            if (!$booking) {
                $this->sendError('預約不存在', 404);
            }
            
            $this->sendSuccess($booking);
        } catch (Exception $e) {
            $this->sendError('獲取預約失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 創建新預約
     */
    public function create() {
        if ($this->requestMethod !== 'POST') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        $this->validateRequired(['classroom_id', 'booking_date', 'start_time', 'end_time', 'purpose']);
        
        try {
            // 檢查是否已登入
            session_start();
            if (!isset($_SESSION['user_id'])) {
                $this->sendError('需要登入才能創建預約', 401);
            }
            
            $classroomId = $this->requestData['classroom_id'];
            $bookingDate = $this->requestData['booking_date'];
            $startTime = $this->requestData['start_time'];
            $endTime = $this->requestData['end_time'];
            
            // 檢查教室是否存在
            $classroom = $this->classroomModel->getClassroomById($classroomId);
            if (!$classroom) {
                $this->sendError('教室不存在', 404);
            }
            
            // 檢查教室是否可用
            if (!$classroom['is_available']) {
                $this->sendError('教室目前不可用', 400);
            }
            
            // 檢查時間衝突
            if ($this->bookingModel->hasTimeConflict($classroomId, $bookingDate, $startTime, $endTime)) {
                $this->sendError('該時段已被預約', 400);
            }
            
            // 驗證預約時間
            $bookingDateTime = DateTime::createFromFormat('Y-m-d H:i', $bookingDate . ' ' . $startTime);
            $now = new DateTime();
            
            if ($bookingDateTime <= $now) {
                $this->sendError('不能預約過去的時間', 400);
            }
            
            // 創建預約
            $bookingData = [
                'user_id' => $_SESSION['user_id'],
                'classroom_id' => $classroomId,
                'booking_date' => $bookingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'purpose' => $this->requestData['purpose'],
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $bookingId = $this->bookingModel->createBooking($bookingData);
            
            if ($bookingId) {
                $this->sendSuccess(['booking_id' => $bookingId], '預約創建成功');
            } else {
                $this->sendError('創建預約失敗', 500);
            }
        } catch (Exception $e) {
            $this->sendError('創建預約失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 更新預約狀態
     */
    public function updateStatus() {
        if ($this->requestMethod !== 'PUT') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        $bookingId = $_GET['id'] ?? null;
        if (!$bookingId) {
            $this->sendError('需要提供預約ID', 400);
        }
        
        $this->validateRequired(['status']);
        
        try {
            // 檢查預約是否存在
            $booking = $this->bookingModel->getBookingById($bookingId);
            if (!$booking) {
                $this->sendError('預約不存在', 404);
            }
            
            $newStatus = $this->requestData['status'];
            $allowedStatuses = ['pending', 'approved', 'rejected', 'cancelled'];
            
            if (!in_array($newStatus, $allowedStatuses)) {
                $this->sendError('無效的狀態值', 400);
            }
            
            // 檢查權限
            session_start();
            if (!isset($_SESSION['user_id'])) {
                $this->sendError('需要登入', 401);
            }
            
            // 只有管理員或預約者本人可以更新狀態
            if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $booking['user_id']) {
                $this->sendError('沒有權限更新此預約', 403);
            }
            
            // 學生只能取消自己的預約
            if ($_SESSION['role'] === 'student' && $newStatus !== 'cancelled') {
                $this->sendError('學生只能取消預約', 403);
            }
            
            $updateData = [
                'status' => $newStatus,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->bookingModel->updateBooking($bookingId, $updateData);
            
            if ($result) {
                $this->sendSuccess(null, '預約狀態更新成功');
            } else {
                $this->sendError('更新預約狀態失敗', 500);
            }
        } catch (Exception $e) {
            $this->sendError('更新預約狀態失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 刪除預約
     */
    public function delete() {
        if ($this->requestMethod !== 'DELETE') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        $bookingId = $_GET['id'] ?? null;
        if (!$bookingId) {
            $this->sendError('需要提供預約ID', 400);
        }
        
        try {
            // 檢查預約是否存在
            $booking = $this->bookingModel->getBookingById($bookingId);
            if (!$booking) {
                $this->sendError('預約不存在', 404);
            }
            
            // 檢查權限
            session_start();
            if (!isset($_SESSION['user_id'])) {
                $this->sendError('需要登入', 401);
            }
            
            // 只有管理員或預約者本人可以刪除
            if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $booking['user_id']) {
                $this->sendError('沒有權限刪除此預約', 403);
            }
            
            $result = $this->bookingModel->deleteBooking($bookingId);
            
            if ($result) {
                $this->sendSuccess(null, '預約刪除成功');
            } else {
                $this->sendError('刪除預約失敗', 500);
            }
        } catch (Exception $e) {
            $this->sendError('刪除預約失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 獲取可用時段
     */
    public function slots() {
        if ($this->requestMethod !== 'GET') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        $classroomId = $_GET['classroom_id'] ?? null;
        $date = $_GET['date'] ?? date('Y-m-d');
        
        if (!$classroomId) {
            $this->sendError('需要提供教室ID', 400);
        }
        
        try {
            // 檢查教室是否存在
            $classroom = $this->classroomModel->getClassroomById($classroomId);
            if (!$classroom) {
                $this->sendError('教室不存在', 404);
            }
            
            $slots = $this->bookingModel->getAvailableSlots($classroomId, $date);
            
            $this->sendSuccess([
                'classroom' => $classroom,
                'date' => $date,
                'available_slots' => $slots
            ]);
        } catch (Exception $e) {
            $this->sendError('獲取可用時段失敗: ' . $e->getMessage(), 500);
        }
    }
}
