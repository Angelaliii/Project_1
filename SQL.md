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
area VARCHAR(255) NOT NULL COMMENT '場域或樓宇，例如：德芳外語大樓',
classroom_code VARCHAR(255) NOT NULL COMMENT '教室代碼或房號，例如：FG202',
classroom_type VARCHAR(50) COMMENT '教室種類，如：一般教室、電腦教室、實驗室',
capacity INT NULL,
features TEXT NULL,
recording_system BOOLEAN NOT NULL DEFAULT FALSE,
classroom_photo VARCHAR(255) NULL,
classroom_notes TEXT NULL,
available_times TEXT NULL,
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

---

-- bookings（主檔；區間）

---

-- ===== 新增/修改：教室欄位與種子資料 =====
-- 說明：以下為對 `classrooms` 的欄位調整（刪除 building/room，新增 area/code 等），
-- 並插入一組範例教室資料；接著調整 `classroom_permissions` 並插入權限資料。

-- 請注意：若您是重新建立資料庫（本檔前段有 DROP/CREATE），
-- 這些 ALTER/INSERT 針對已存在表格執行；若資料庫剛建立可改直接使用 CREATE + INSERT。

-- Alter `classrooms`：刪除舊欄位，新增新欄位
ALTER TABLE classrooms
DROP COLUMN building,
DROP COLUMN room,
ADD COLUMN area VARCHAR(255) NOT NULL,
ADD COLUMN classroom_code VARCHAR(255) NOT NULL,
ADD COLUMN classroom_photo VARCHAR(255) NULL,
ADD COLUMN classroom_notes TEXT NULL,
ADD COLUMN features TEXT NULL,
ADD COLUMN capacity INT NULL,
ADD COLUMN available_times TEXT NULL,
ADD COLUMN recording_system BOOLEAN NOT NULL DEFAULT FALSE;

-- 插入教室種子資料（若表中已有相同 classroom_code 或其他唯一性需求請先檢查）
INSERT INTO classrooms (area, classroom_code, capacity, recording_system, features)
VALUES
('德芳外語大樓', 'FG202', 70, TRUE, '教室'),
('德芳外語大樓', 'FG204', 56, TRUE, '教室'),
('德芳外語大樓', 'FG208', 40, TRUE, '教室'),
('德芳外語大樓', 'FG302', 56, TRUE, '教室'),
('德芳外語大樓', 'FG303', 48, TRUE, '教室'),
('外語學院', 'LA202', 68, TRUE, '教室'),
('外語學院', 'LA204', 34, TRUE, '教室'),
('外語學院', 'LA206', 34, TRUE, '教室'),
('外語學院', 'LA302', 45, TRUE, '教室'),
('外語學院', 'LC302', 20, FALSE, '教室'),
('聖言樓', 'SF130', 54, TRUE, '教室'),
('聖言樓', 'SF131', 112, TRUE, '教室'),
('聖言樓', 'SF901', 56, TRUE, '教室'),
('濟時樓', '語言 A', 63, TRUE, '教室'),
('濟時樓', '語言 B', 63, TRUE, '教室'),
('進修部大樓', 'ES618', 48, FALSE, '教室'),
('德芳大樓', '多功能影音資源展示教室', 30, FALSE, '其他'),
('濟時樓', '語言檢測教室', 45, TRUE, '其他'),
('濟時樓', 'eClassroom A', 50, TRUE, '其他'),
('濟時樓', 'eClassroom B', 24, TRUE, '其他'),
('濟時樓', 'eClassroom A+B', 90, TRUE, '其他'),
('濟時樓', 'eSchool 學習討論室\_A', 7, FALSE, '其他'),
('濟時樓', 'eSchool 學習討論室\_B', 7, FALSE, '其他'),
('濟時樓', 'eSchool 學習討論室\_C', 7, FALSE, '其他'),
('濟時樓', 'eSchool 學習討論室\_D', 7, FALSE, '其他'),
('濟時樓', 'eMeeting', 24, FALSE, '其他'),
('聖言樓', '視聽討論室 SF909', 12, TRUE, '其他'),
('聖言樓', '攝影棚', NULL, FALSE, '其他'),
('聖言樓', '錄音室', NULL, FALSE, '其他');

-- Alter `classroom_permissions`：移除時間戳欄位（created_at, updated_at）
ALTER TABLE classroom_permissions
DROP COLUMN created_at,
DROP COLUMN updated_at;

-- 插入教室權限資料（classroom_id 對應上面或現有教室的 ID）
INSERT INTO classroom_permissions (classroom_id, allowed_roles)
VALUES
(13, 'Teacher, Department'),
(14, 'Teacher, Department'),
(15, 'Teacher, Department'),
(16, 'Teacher, Department'),
(17, 'Teacher, Department'),
(18, 'Teacher, Department'),
(19, 'Teacher, Department'),
(20, 'Teacher, Department'),
(21, 'Teacher, Department'),
(22, 'Teacher, Department'),
(23, 'Teacher, Department'),
(24, 'Teacher, Department'),
(25, 'Teacher, Department'),
(26, 'Teacher, Department'),
(27, 'Teacher, Department'),
(28, 'Teacher, Department'),
(29, 'Teacher, Department'),
(30, 'Teacher, Department'),
(31, 'Teacher, Department'),
(32, 'Teacher, Department'),
(33, 'Teacher, Department'),
(34, 'Teacher, Department, Student'),
(35, 'Teacher, Department, Student'),
(36, 'Teacher, Department, Student'),
(37, 'Teacher, Department, Student'),
(38, 'Teacher, Department'),
(39, 'Teacher, Department'),
(40, 'Teacher, Department'),
(41, 'Teacher, Department');

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
