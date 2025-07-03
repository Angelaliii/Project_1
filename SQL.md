use rent_classroom;
CREATE TABLE users (
user_id INT AUTO_INCREMENT PRIMARY KEY,
user_name VARCHAR(255) NOT NULL UNIQUE,
mail VARCHAR(255) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL,
role ENUM('student', 'teacher', 'admin') NOT NULL,
created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

CREATE TABLE classrooms (
classroom_ID INT AUTO_INCREMENT PRIMARY KEY,
classroom_name VARCHAR(255) NOT NULL,
building VARCHAR(255),
room VARCHAR(255),
picture Blob,
created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE bookings (
booking_ID INT AUTO_INCREMENT PRIMARY KEY,
classroom_ID INT NOT NULL,
user_ID INT NOT NULL,
status ENUM('available', 'booked', 'in_use', 'completed', 'cancelled') NOT NULL DEFAULT 'available',
start_datetime DATETIME NOT NULL,
end_datetime DATETIME NOT NULL,
created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (classroom_ID) REFERENCES classrooms(classroom_ID) ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY (user_ID) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
INDEX idx_classroom_start_end (classroom_ID, start_datetime, end_datetime)
);

CREATE TABLE booking_slots (
slot_ID INT AUTO_INCREMENT PRIMARY KEY,
booking_ID INT NOT NULL,
date DATE NOT NULL,
hour TINYINT UNSIGNED NOT NULL,
created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (booking_ID) REFERENCES bookings(booking_ID) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `announcements` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`title` varchar(255) NOT NULL COMMENT '公告標題',
`content` text NOT NULL COMMENT '公告內容',
`publish_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '發佈日期',
`expiry_date` datetime DEFAULT NULL COMMENT '過期日期，若為 NULL 則不過期',
`status` enum('published','draft','archived') NOT NULL DEFAULT 'published' COMMENT '狀態: published(已發布), draft(草稿), archived(已封存)',
`created_by` int(11) NOT NULL COMMENT '創建者 ID',
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
`updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
PRIMARY KEY (`id`),
KEY `idx_publish_date` (`publish_date`),
KEY `idx_status` (`status`),
KEY `idx_created_by` (`created_by`),
CONSTRAINT `fk_announcement_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系統公告資料表';

-- 新增教室權限表
CREATE TABLE classroom_permissions (
permission_id INT AUTO_INCREMENT PRIMARY KEY,
classroom_id INT NOT NULL,
allowed_roles VARCHAR(50) NOT NULL COMMENT '允許租借的角色，使用逗號分隔如 "student,teacher,admin"',
created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
FOREIGN KEY (classroom_id) REFERENCES classrooms(classroom_ID) ON DELETE CASCADE ON UPDATE CASCADE,
UNIQUE KEY idx_classroom_id (classroom_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='教室租借權限表';
