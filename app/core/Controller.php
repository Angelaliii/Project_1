<?php
/**
 * 基礎控制器類別
 */
class Controller {
    /**
     * 載入模型
     *
     * @param string $model 模型名稱
     * @return object 模型實例
     */
    protected function loadModel($model) {
        // 加載模型檔案
        require_once ROOT_PATH . '/app/models/' . $model . '.php';
        
        // 返回模型實例
        return new $model();
    }
    
    /**
     * 渲染視圖
     *
     * @param string $view 視圖路徑
     * @param array $data 要傳遞給視圖的資料
     * @param string $layout 佈局檔案名稱
     */
    protected function view($view, $data = [], $layout = 'main') {
        // 將資料轉換為變數
        extract($data);
        
        // 啟動輸出緩衝
        ob_start();
        
        // 包含視圖檔案
        $viewFile = ROOT_PATH . '/app/views/' . $view . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo "視圖 {$view} 不存在";
        }
        
        // 獲取視圖內容
        $content = ob_get_clean();
        
        // 載入佈局
        $layoutFile = ROOT_PATH . '/app/views/layouts/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            include $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * 重定向到另一個 URL
     *
     * @param string $url 目標 URL
     */
    protected function redirect($url) {
        // 處理相對URL (確保已定義BASE_URL常數)
        if (strpos($url, 'http') !== 0 && strpos($url, '/') === 0) {
            $url = '/dashboard/Project_1/public' . $url;
        }
        
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * 確保使用者已登入
     *
     * @param string $redirectUrl 未登入時重定向的地址
     * @return bool 是否已登入
     */
    protected function requireLogin($redirectUrl = '/auth/login') {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect($redirectUrl);
            return false;
        }
        return true;
    }
    
    /**
     * 檢查使用者角色
     *
     * @param string $role 需要的角色
     * @param string $redirectUrl 權限不足時重定向的地址
     * @return bool 是否有權限
     */
    protected function requireRole($role, $redirectUrl = '/') {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
            $this->redirect($redirectUrl);
            return false;
        }
        return true;
    }
}
