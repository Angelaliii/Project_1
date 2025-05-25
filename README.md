# 教室租借系統

這是一個基於 PHP 和 JavaScript 的教室租借系統，採用前後端分離的架構設計。

### 作者資訊

- 👋 My name is Angela.
- 📖 Major : Department of Information Management

## 主要功能

1. **用戶管理**

   - 註冊、登錄和登出功能
   - 用戶角色區分：學生、教師和管理員
   - 個人資料管理

2. **教室管理**

   - 教室的新增、查詢、修改和刪除
   - 教室詳細資訊與圖片

3. **預約管理**

   - 教室預約的申請、查詢、修改和取消
   - 拖曳式時段選擇（Grid 介面）
   - 即時預約確認（無需審核流程）
   - 使用月預約上限（4 次）控制

4. **儀表板**
   - 用戶儀表板：個人預約記錄、統計信息
   - 管理員儀表板：系統狀態、用戶管理、預約管理

## 技術架構

### 前端

- HTML5、CSS3、JavaScript (ES6+)
- 原生 JavaScript 實現的 AJAX 請求和 DOM 操作
- 拖曳式互動界面（無需複雜框架）
- SweetAlert2 提供美化提示框
- 響應式設計，適配不同設備

### 後端

- PHP 7+ 作為服務端語言
- MySQL 資料庫
- RESTful API 設計
- 分層架構：路由 -> 控制器 -> 服務 -> 資料庫

### 資料庫設計

- users: 儲存用戶資訊
- classrooms: 儲存教室資訊
- bookings: 儲存預約資訊
- booking_slots: 儲存預約時段的細節

## API 端點

### 認證相關

- POST /api/auth/login - 用戶登入
- POST /api/auth/register - 用戶註冊
- POST /api/auth/logout - 用戶登出

### 用戶相關

- GET /api/users - 獲取所有用戶（管理員專用）
- GET /api/users?id=:id - 獲取特定用戶
- POST /api/users - 創建用戶（管理員專用）
- PUT /api/users?id=:id - 更新用戶
- DELETE /api/users?id=:id - 刪除用戶（管理員專用）
- GET /api/users/profile - 獲取當前登入用戶的資料
- PUT /api/users/profile - 更新當前登入用戶的資料

### 教室相關

- GET /api/classrooms - 獲取所有教室
- GET /api/classrooms?id=:id - 獲取特定教室
- POST /api/classrooms - 創建教室（管理員專用）
- PUT /api/classrooms?id=:id - 更新教室（管理員專用）
- DELETE /api/classrooms?id=:id - 刪除教室（管理員專用）

### 預約相關

- GET /api/bookings - 獲取所有預約
- GET /api/bookings?id=:id - 獲取特定預約
- POST /api/bookings - 創建預約
- PUT /api/bookings?id=:id - 更新預約
- DELETE /api/bookings?id=:id - 取消預約
- GET /api/bookings/slots?date=:date&classroom_id=:id - 獲取指定日期和教室的可用時段
