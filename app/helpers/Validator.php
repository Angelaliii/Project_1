<?php
/**
 * Validator.php - 處理數據驗證的輔助類
 */
class Validator {
    /**
     * 存儲驗證錯誤
     */
    private $errors = [];
    
    /**
     * 檢查值是否為空
     * 
     * @param mixed $value 要檢查的值
     * @param string $field 欄位名稱（用於錯誤訊息）
     * @return bool 是否通過驗證
     */
    public function required($value, $field) {
        if (empty($value) && $value !== '0') {
            $this->errors[$field] = "{$field}不能為空";
            return false;
        }
        return true;
    }
    
    /**
     * 驗證電子郵件格式
     * 
     * @param string $email 要驗證的電子郵件
     * @param string $field 欄位名稱（用於錯誤訊息）
     * @return bool 是否通過驗證
     */
    public function email($email, $field = 'email') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "請提供有效的電子郵件地址";
            return false;
        }
        return true;
    }
    
    /**
     * 驗證兩個值是否相同（例如密碼和確認密碼）
     * 
     * @param mixed $value1 第一個值
     * @param mixed $value2 第二個值
     * @param string $field 欄位名稱（用於錯誤訊息）
     * @return bool 是否通過驗證
     */
    public function matches($value1, $value2, $field) {
        if ($value1 !== $value2) {
            $this->errors[$field] = "兩次輸入的{$field}不一致";
            return false;
        }
        return true;
    }
    
    /**
     * 驗證字符串最小長度
     * 
     * @param string $value 要驗證的字符串
     * @param int $min 最小長度
     * @param string $field 欄位名稱（用於錯誤訊息）
     * @return bool 是否通過驗證
     */
    public function minLength($value, $min, $field) {
        if (strlen($value) < $min) {
            $this->errors[$field] = "{$field}長度至少需要{$min}個字符";
            return false;
        }
        return true;
    }
    
    /**
     * 驗證字符串最大長度
     * 
     * @param string $value 要驗證的字符串
     * @param int $max 最大長度
     * @param string $field 欄位名稱（用於錯誤訊息）
     * @return bool 是否通過驗證
     */
    public function maxLength($value, $max, $field) {
        if (strlen($value) > $max) {
            $this->errors[$field] = "{$field}長度不能超過{$max}個字符";
            return false;
        }
        return true;
    }
    
    /**
     * 驗證密碼複雜度（至少包含一個大寫字母、一個小寫字母和一個數字）
     * 
     * @param string $password 要驗證的密碼
     * @param string $field 欄位名稱（用於錯誤訊息）
     * @return bool 是否通過驗證
     */
    public function passwordStrength($password, $field = 'password') {
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password)) {
            $this->errors[$field] = "密碼必須包含至少一個大寫字母、一個小寫字母和一個數字";
            return false;
        }
        return true;
    }
    
    /**
     * 檢查值是否在允許的值列表中
     * 
     * @param mixed $value 要檢查的值
     * @param array $allowedValues 允許的值列表
     * @param string $field 欄位名稱（用於錯誤訊息）
     * @return bool 是否通過驗證
     */
    public function inArray($value, $allowedValues, $field) {
        if (!in_array($value, $allowedValues)) {
            $this->errors[$field] = "提供的{$field}值無效";
            return false;
        }
        return true;
    }
    
    /**
     * 檢查是否有任何驗證錯誤
     * 
     * @return bool 是否有錯誤
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * 獲取所有驗證錯誤
     * 
     * @return array 錯誤數組
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * 獲取第一個錯誤訊息
     * 
     * @return string|null 第一個錯誤訊息或 null
     */
    public function getFirstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}
