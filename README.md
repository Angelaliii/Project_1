# 教室租借系統

這是一個基於 PHP MVC 架構的教室租借系統，用於學校或機構管理教室預約。此專案使用 PHP 開發，遵循 MVC 設計模式，配合 MySQL 資料庫運行。

### 作者資訊

- 👋 My name is Angela.
- 📖 Major : Department of Information Management

## 檔案結構

```
Project_1/
├── app/                      # 應用程式核心
│   ├── config/               # 設定檔案
│   ├── controllers/          # 控制器
│   ├── core/                 # 框架核心檔案
│   ├── models/               # 資料模型
│   └── views/                # 視圖檔案
│       ├── admin/            # 管理員視圖
│       ├── auth/             # 認證視圖
│       ├── booking/          # 預約視圖
│       ├── classroom/        # 教室視圖
│       ├── components/       # 共用元件
│       ├── home/             # 首頁視圖
│       ├── layouts/          # 佈局模板
│       └── user/             # 用戶視圖
├── public/                   # 公開訪問的檔案
│   ├── css/                  # CSS檔案
│   ├── js/                   # JavaScript檔案
│   ├── img/                  # 圖片檔案
│   ├── assets/               # 其他資源
│   ├── .htaccess             # URL重寫規則
│   └── index.php             # 入口檔案
└── README.md                 # 專案說明
```

## 系統功能

1. **用戶管理**

   - 註冊、登入和登出功能
   - 用戶角色區分：學生、教師和管理員
   - 個人資料管理

2. **教室管理**

   - 教室的新增、查詢、修改和刪除
   - 教室詳細資訊與圖片

3. **預約管理**

   - 教室預約的申請、查詢、修改和取消
   - 即時預約確認（無需審核流程）
   - 預約時段選擇

4. **儀表板**
   - 用戶儀表板：個人預約記錄、統計信息

## 安裝說明

1. **環境要求**

   - PHP 7.4 或更高版本
   - MySQL 5.7 或更高版本
   - Apache Web Server 搭配 mod_rewrite 模組

2. **安裝步驟**

   - 將專案檔案複製到您的 Web 服務器根目錄（如 xampp/htdocs/）
   - 確保 Apache 的 mod_rewrite 模組已啟用
   - 創建一個名為 `rent_classroom` 的資料庫
   - 訪問網站首頁，系統會自動初始化資料庫
   - 首次使用，請先註冊一個帳號

3. **配置設定**
   - 配置文件位於 `app/config/config.php`
   - 根據需要修改資料庫連接資訊及其他設定

## 使用技術

- PHP 7.4 (原生，無框架)
- MySQL 資料庫
- HTML5 / CSS3
- JavaScript / jQuery
- Font Awesome 圖標
- 響應式設計
  - 管理員儀表板：系統狀態、用戶管理、預約管理

## 系統需求

- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- Web 伺服器（Apache 或 Nginx）
- 啟用 mod_rewrite 模組（用於 URL 重寫）

## 安裝步驟

1. 將專案複製到 Web 伺服器目錄

2. 配置資料庫連接：
   編輯 `app/config/config.php` 檔案，設定資料庫連接參數：

   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'rent_classroom');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

3. 設定虛擬主機（可選但建議）：
   在 Apache 中，創建一個虛擬主機配置，指向 `public` 目錄：

   ```apache
   <VirtualHost *:80>
       ServerName your-domain.com
       DocumentRoot "/path/to/Project_1/public"
       <Directory "/path/to/Project_1/public">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

4. 確保 `.htaccess` 正確配置：
   檢查 `public/.htaccess` 檔案，確保 URL 重寫規則正確。

5. 訪問網站，系統將自動創建並初始化資料庫。

## 檔案結構

```
Project_1/
  ├── app/                    # 應用程序核心
  │   ├── config/             # 配置文件
  │   ├── controllers/        # 控制器
  │   ├── core/               # 核心類別 (Router, Controller, Database等)
  │   ├── models/             # 資料模型
  │   └── views/              # 視圖
  │       ├── admin/          # 管理員視圖
  │       ├── auth/           # 認證相關視圖
  │       ├── booking/        # 預約相關視圖
  │       ├── classroom/      # 教室相關視圖
  │       ├── components/     # 共用元件
  │       ├── home/           # 首頁相關視圖
  │       ├── layouts/        # 佈局模板
  │       └── user/           # 用戶相關視圖
  ├── public/                 # 公開訪問目錄
  │   ├── assets/             # 靜態資源
  │   │   └── images/         # 圖片
  │   ├── css/                # 樣式表
  │   ├── js/                 # JavaScript檔案
  │   ├── .htaccess           # URL重寫規則
  │   └── index.php           # 入口文件
  └── index.php               # 重定向到公開目錄
```
