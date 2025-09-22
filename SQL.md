-- =========================================================
-- Rebuild rent_classroom (MySQL 8.x)
-- 重建 DB，含 per-slot 唯一性（classroom_ID, date, hour）
-- =========================================================

SET SQL_SAFE_UPDATES = 0;
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = '+00:00';
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

---

-- Drop & Create Database

---

DROP DATABASE IF EXISTS rent_classroom;
CREATE DATABASE rent_classroom
CHARACTER SET utf8mb4
COLLATE utf8mb4_0900_ai_ci;
USE rent_classroom;

---

-- users

---

CREATE TABLE users (
user_id INT AUTO_INCREMENT PRIMARY KEY,
user_name VARCHAR(255) NOT NULL UNIQUE,
mail VARCHAR(255) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL, -- 建議實際使用雜湊（bcrypt/argon2）
role ENUM('student','teacher') NOT NULL,
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

---

-- classrooms

---

CREATE TABLE classrooms (
classroom_ID INT AUTO_INCREMENT PRIMARY KEY,
classroom_name VARCHAR(255) NOT NULL,
building VARCHAR(255),
room VARCHAR(255),
classroom_type VARCHAR(50) COMMENT '教室種類，如：一般教室、電腦教室、實驗室',
picture BLOB,
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

---

-- bookings（主檔；區間）
-- 备注：
-- - 維持你現有的 status 集合（available/booked/...），
-- 但新增時間順序檢核 & booking_date 改為產生欄位（由 start_datetime 產生）

---

CREATE TABLE bookings (
booking_ID INT AUTO_INCREMENT PRIMARY KEY,
classroom_ID INT NOT NULL,
user_ID INT NOT NULL,
status ENUM('available','booked','in_use','completed','cancelled') NOT NULL DEFAULT 'available',
start_datetime DATETIME NOT NULL,
end_datetime DATETIME NOT NULL,
purpose TEXT COMMENT '預約目的',
booking_date DATE GENERATED ALWAYS AS (DATE(start_datetime)) STORED COMMENT '預約日期（由 start_datetime 產生）',
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

CONSTRAINT fk_bookings_classroom
FOREIGN KEY (classroom_ID) REFERENCES classrooms(classroom_ID)
ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT fk_bookings_user
FOREIGN KEY (user_ID) REFERENCES users(user_id)
ON DELETE CASCADE ON UPDATE CASCADE,

CONSTRAINT ck_time_order CHECK (end_datetime > start_datetime),

INDEX idx_classroom_start_end (classroom_ID, start_datetime, end_datetime)
) ENGINE=InnoDB;

---

-- booking_slots（逐小時切片；衍生索引表）
-- 重點：
-- - 含 classroom_ID（FK）以便 UNIQUE 防重複（同教室＋同日＋同小時只能一筆）

---

CREATE TABLE booking_slots (
slot_ID INT AUTO_INCREMENT PRIMARY KEY,
booking_ID INT NOT NULL,
classroom_ID INT NOT NULL,
date DATE NOT NULL,
hour TINYINT UNSIGNED NOT NULL,
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

CONSTRAINT fk_slots_booking
FOREIGN KEY (booking_ID) REFERENCES bookings(booking_ID)
ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT fk_slots_classroom
FOREIGN KEY (classroom_ID) REFERENCES classrooms(classroom_ID)
ON DELETE CASCADE ON UPDATE CASCADE,

CONSTRAINT ck_hour_range CHECK (hour BETWEEN 0 AND 23),

UNIQUE KEY uq_room_date_hour (classroom_ID, date, hour),
INDEX idx_booking_date_hour (booking_ID, date, hour),
INDEX idx_date_hour (date, hour)
) ENGINE=InnoDB;

---

-- classroom_permissions（教室租借權限）

---

CREATE TABLE classroom_permissions (
permission_id INT AUTO_INCREMENT PRIMARY KEY,
classroom_id INT NOT NULL,
allowed_roles VARCHAR(50) NOT NULL COMMENT '允許租借的角色，使用逗號分隔如 "student,teacher"',
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

CONSTRAINT fk_perm_classroom
FOREIGN KEY (classroom_id) REFERENCES classrooms(classroom_ID)
ON DELETE CASCADE ON UPDATE CASCADE,
UNIQUE KEY idx_classroom_id (classroom_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='教室租借權限表';

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- 完成：資料庫 rent_classroom 已重建
-- 提醒：
-- 1) 新增 bookings 後，請在同一個交易內把其區間「展開」插入 booking_slots，
-- 若撞 UNIQUE (classroom_ID,date,hour) 代表該時段被占用，應 ROLLBACK。
-- 2) booking_date 為產生欄位，不需人工寫入。
-- =========================================================
