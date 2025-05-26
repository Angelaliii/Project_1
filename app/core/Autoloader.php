<?php
/**
 * 自動載入類別的功能
 */
class Autoloader {
    /**
     * 註冊自動載入器
     */
    public function register() {
        spl_autoload_register([$this, 'loadClass']);
    }
    
    /**
     * 載入類別
     *
     * @param string $className 類別名稱
     */
    private function loadClass($className) {
        $paths = [
            ROOT_PATH . '/app/core/',
            ROOT_PATH . '/app/controllers/',
            ROOT_PATH . '/app/models/',
        ];
        
        // 遍歷所有路徑尋找類別檔案
        foreach ($paths as $path) {
            $file = $path . $className . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
}
