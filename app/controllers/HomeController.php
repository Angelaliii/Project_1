<?php
/**
 * 首頁控制器 - 處理首頁相關請求
 */
class HomeController extends Controller {
    /**
     * 顯示首頁
     */
    public function index() {
        // 如果用戶已登入，重定向到儀表板
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
            return;
        }
        
        // 載入首頁視圖
        $this->view('home/index');
    }
    
    /**
     * 顯示儀表板
     */
    public function dashboard() {
        // 確保用戶已登入
        $this->requireLogin();
        
        // 載入用戶數據
        $userId = $_SESSION['user_id'];
        $userModel = $this->loadModel('User');
        $user = $userModel->getById($userId);
        
        // 載入預約數據
        $bookingModel = $this->loadModel('Booking');
        $upcomingBookings = $bookingModel->getUserBookings(
            $userId, 
            ['booked', 'in_use'],
            3
        );
        
        // 如果是教師或管理員，載入更多數據
        $recentClassrooms = [];
        $classroomStats = [];
        $bookingStats = [];
        
        if (isset($_SESSION['role']) && ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin')) {
            $classroomModel = $this->loadModel('Classroom');
            $recentClassrooms = $classroomModel->getAll(null, 5);
            
            // 取得教室和預約統計
            $classroomStats['total'] = $classroomModel->count();
            $bookingStats['month_total'] = $bookingModel->countThisMonth();
            $bookingStats['today_total'] = $bookingModel->countToday();
        }
        
        // 載入最近活動
        $recentActivities = [];
        
        // 準備視圖數據
        $data = [
            'title' => '儀表板',
            'styles' => ['dashboard.css'],
            'user' => $user,
            'upcomingBookings' => $upcomingBookings,
            'recentClassrooms' => $recentClassrooms,
            'classroomStats' => $classroomStats,
            'bookingStats' => $bookingStats,
            'recentActivities' => $recentActivities
        ];
        
        // 載入視圖
        $this->view('home/dashboard', $data);
    }
    
    /**
     * 顯示關於頁面
     */
    public function about() {
        $this->view('home/about');
    }
    
    /**
     * 顯示聯絡頁面
     */
    public function contact() {
        $errors = [];
        $success = false;
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = sanitize($_POST['name'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $message = sanitize($_POST['message'] ?? '');
            
            // 表單驗證
            if (empty($name)) {
                $errors['name'] = '請輸入您的姓名';
            }
            
            if (empty($email)) {
                $errors['email'] = '請輸入您的電子郵件';
            } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = '請輸入有效的電子郵件地址';
            }
            
            if (empty($message)) {
                $errors['message'] = '請輸入訊息內容';
            }
            
            // 如果沒有錯誤，處理聯絡表單
            if (empty($errors)) {
                // 在實際應用中，這裡可以發送電子郵件或儲存到資料庫
                // 這裡只是模擬成功
                $success = true;
                
                // 清空表單數據，避免重複提交
                $name = $email = $message = '';
            }
        }
        
        // 準備數據
        $data = [
            'name' => $name ?? '',
            'email' => $email ?? '',
            'message' => $message ?? '',
            'errors' => $errors,
            'success' => $success
        ];
        
        // 載入視圖
        $this->view('home/contact', $data);
    }
}
