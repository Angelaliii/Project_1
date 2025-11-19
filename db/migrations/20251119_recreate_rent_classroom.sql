-- Recreate rent_classroom database and seed classrooms (2025-11-19)
-- WARNING: This will DROP the database `rent_classroom` if it exists.
-- Run this only on a test environment or after taking a backup.

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS rent_classroom;
CREATE DATABASE rent_classroom
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE rent_classroom;

-- users
CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  user_name VARCHAR(255) NOT NULL UNIQUE,
  mail VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','teacher','admin') NOT NULL DEFAULT 'teacher',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- classrooms
-- Note: `classroom_name` removed. Use `classroom_code` as display name.
CREATE TABLE classrooms (
  classroom_ID INT AUTO_INCREMENT PRIMARY KEY,
  area VARCHAR(255) NOT NULL COMMENT '場域或樓宇，例如：德芳外語大樓',
  classroom_code VARCHAR(255) NOT NULL COMMENT '教室代碼或房號，例如：FG202',
  classroom_type VARCHAR(50) DEFAULT NULL COMMENT '教室種類，如：教室/其他',
  capacity INT DEFAULT NULL,
  recording_system BOOLEAN NOT NULL DEFAULT FALSE,
  available_equipment TEXT DEFAULT NULL,
  features TEXT DEFAULT NULL,
  available_times VARCHAR(255) DEFAULT NULL,
  classroom_photo VARCHAR(255) DEFAULT NULL,
  classroom_notes TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_area_code (area, classroom_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- bookings
CREATE TABLE bookings (
  booking_ID INT AUTO_INCREMENT PRIMARY KEY,
  classroom_ID INT NOT NULL,
  user_ID INT NOT NULL,
  status ENUM('available','booked','in_use','completed','cancelled') NOT NULL DEFAULT 'booked',
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  purpose TEXT NULL,
  requires_recording BOOLEAN NOT NULL DEFAULT FALSE,
  requested_equipment TEXT NULL,
  booking_date DATE GENERATED ALWAYS AS (DATE(start_datetime)) STORED,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_bookings_classroom FOREIGN KEY (classroom_ID) REFERENCES classrooms(classroom_ID) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_bookings_user FOREIGN KEY (user_ID) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT ck_time_order CHECK (end_datetime > start_datetime),
  INDEX idx_classroom_start_end (classroom_ID, start_datetime, end_datetime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- booking_slots (per-hour slices)
CREATE TABLE booking_slots (
  slot_ID INT AUTO_INCREMENT PRIMARY KEY,
  booking_ID INT NOT NULL,
  classroom_ID INT NOT NULL,
  date DATE NOT NULL,
  hour TINYINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_slots_booking FOREIGN KEY (booking_ID) REFERENCES bookings(booking_ID) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_slots_classroom FOREIGN KEY (classroom_ID) REFERENCES classrooms(classroom_ID) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT ck_hour_range CHECK (hour BETWEEN 0 AND 23),
  UNIQUE KEY uq_room_date_hour (classroom_ID, date, hour)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- classroom_permissions
CREATE TABLE classroom_permissions (
  permission_id INT AUTO_INCREMENT PRIMARY KEY,
  classroom_id INT NOT NULL,
  allowed_roles VARCHAR(255) NOT NULL COMMENT '逗號分隔，例如: teacher,admin,student',
  CONSTRAINT fk_perm_classroom FOREIGN KEY (classroom_id) REFERENCES classrooms(classroom_ID) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY idx_classroom_id (classroom_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed classrooms based on provided list
INSERT INTO classrooms (area, classroom_code, capacity, recording_system, features, classroom_type, available_times)
VALUES
('德芳外語大樓','FG202',70,1,'教師、系所','教室','08:00-17:30'),
('德芳外語大樓','FG204',56,1,'教師、系所','教室','08:00-17:30'),
('德芳外語大樓','FG208',40,1,'教師、系所','教室','08:00-17:30'),
('德芳外語大樓','FG302',56,1,'教師、系所','教室','08:00-17:30'),
('德芳外語大樓','FG303',48,1,'教師、系所','教室','08:00-17:30'),
('外語學院','LA202',68,1,'教師、系所','教室','08:00-17:30'),
('外語學院','LA204',34,1,'教師、系所','教室','08:00-17:30'),
('外語學院','LA206',34,1,'教師、系所','教室','08:00-17:30'),
('外語學院','LA302',45,1,'教師、系所','教室','08:00-17:30'),
('外語學院','LC302',20,0,'教師、系所','教室','08:00-17:30'),
('聖言樓','SF130',54,1,'教師、系所','教室','08:00-17:30'),
('聖言樓','SF131',112,1,'教師、系所','教室','08:00-17:30'),
('聖言樓','SF901',56,1,'教師、系所','教室','08:00-17:30'),
('濟時樓','語言A',63,1,'教師、系所','教室','10:00-17:30'),
('濟時樓','語言B',63,1,'教師、系所','教室','10:00-17:30'),
('進修部大樓','ES618',48,0,'教師、系所','教室','08:00-17:30'),
('德芳大樓','多功能影音資源展示教室',30,0,'教師、系所','其他','10:00-17:30'),
('濟時樓','語言檢測教室',45,1,'教師、系所','其他','10:00-17:30'),
('濟時樓','eClassroom A',50,1,'教師、系所','其他','10:00-17:30'),
('濟時樓','eClassroom B',24,1,'教師、系所','其他','10:00-17:30'),
('濟時樓','eClassroom A+B',90,1,'教師、系所','其他','10:00-17:30'),
('濟時樓','eSchool_A',7,0,'教師、系所,學生','其他','10:00-17:30'),
('濟時樓','eSchool_B',7,0,'教師、系所,學生','其他','10:00-17:30'),
('濟時樓','eSchool_C',7,0,'教師、系所,學生','其他','10:00-17:30'),
('濟時樓','eSchool_D',7,0,'教師、系所,學生','其他','10:00-17:30'),
('濟時樓','eMeeting',24,0,'教師、系所','其他','10:00-17:30'),
('聖言樓','視聽討論室 SF909',12,1,'教師、系所','其他','08:00-16:30'),
('聖言樓','攝影棚',NULL,0,'教師、系所','其他','08:00-16:30'),
('聖言樓','錄音室',NULL,0,'教師、系所','其他','08:00-16:30');

SET FOREIGN_KEY_CHECKS = 1;

-- Optional: seed an admin user (password placeholder - replace with hashed password in production)
INSERT INTO users (user_name, mail, password, role)
VALUES ('admin', 'admin@gmail.com', 'changeme', 'admin')
ON DUPLICATE KEY UPDATE user_name = user_name;

-- Done
