-- Migration: 2025-11-19
-- Drop `classroom_name`, add unique constraint (area,classroom_code)
-- Remove feature token `x06jo4` from `features` text
-- Upsert classroom rows from provided list

START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;

-- 1) Drop classroom_name if present
ALTER TABLE classrooms
DROP COLUMN IF EXISTS classroom_name;

-- 2) Ensure unique index on (area, classroom_code)
DROP INDEX IF EXISTS uq_area_code ON classrooms;
ALTER TABLE classrooms
ADD UNIQUE KEY uq_area_code (area, classroom_code);

-- 3) Remove feature token x06jo4 if present in features
UPDATE classrooms
SET features = TRIM(BOTH ',' FROM REPLACE(CONCAT(',',IFNULL(features,''),','), ',x06jo4,', ','))
WHERE features LIKE '%x06jo4%';

-- 4) Upsert classroom data (area,classroom_code) unique
INSERT INTO classrooms (area, classroom_code, capacity, recording_system, features, available_times)
VALUES
('德芳外語大樓','FG202',70,1,'教室,教師、系所','08:00-17:30'),
('德芳外語大樓','FG204',56,1,'教室,教師、系所','08:00-17:30'),
('德芳外語大樓','FG208',40,1,'教室,教師、系所','08:00-17:30'),
('德芳外語大樓','FG302',56,1,'教室,教師、系所','08:00-17:30'),
('德芳外語大樓','FG303',48,1,'教室,教師、系所','08:00-17:30'),
('外語學院','LA202',68,1,'教室,教師、系所','08:00-17:30'),
('外語學院','LA204',34,1,'教室,教師、系所','08:00-17:30'),
('外語學院','LA206',34,1,'教室,教師、系所','08:00-17:30'),
('外語學院','LA302',45,1,'教室,教師、系所','08:00-17:30'),
('外語學院','LC302',20,0,'教室,教師、系所','08:00-17:30'),
('聖言樓','SF130',54,1,'教室,教師、系所','08:00-17:30'),
('聖言樓','SF131',112,1,'教室,教師、系所','08:00-17:30'),
('聖言樓','SF901',56,1,'教室,教師、系所','08:00-17:30'),
('濟時樓','語言A',63,1,'教室,教師、系所','10:00-17:30'),
('濟時樓','語言B',63,1,'教室,教師、系所','10:00-17:30'),
('進修部大樓','ES618',48,0,'教室,教師、系所','08:00-17:30'),
('德芳大樓','多功能影音資源展示教室',30,0,'其他,教師、系所','10:00-17:30'),
('濟時樓','語言檢測教室',45,1,'其他,教師、系所','10:00-17:30'),
('濟時樓','eClassroom A',50,1,'其他,教師、系所','10:00-17:30'),
('濟時樓','eClassroom B',24,1,'其他,教師、系所','10:00-17:30'),
('濟時樓','eClassroom A+B',90,1,'其他,教師、系所','10:00-17:30'),
('濟時樓','eSchool_A',7,0,'其他,教師、系所,學生','10:00-17:30'),
('濟時樓','eSchool_B',7,0,'其他,教師、系所,學生','10:00-17:30'),
('濟時樓','eSchool_C',7,0,'其他,教師、系所,學生','10:00-17:30'),
('濟時樓','eSchool_D',7,0,'其他,教師、系所,學生','10:00-17:30'),
('濟時樓','eMeeting',24,0,'其他,教師、系所','10:00-17:30'),
('聖言樓','視聽討論室 SF909',12,1,'其他,教師、系所','08:00-16:30'),
('聖言樓','攝影棚',NULL,0,'其他,教師、系所','08:00-16:30'),
('聖言樓','錄音室',NULL,0,'其他,教師、系所','08:00-16:30')
ON DUPLICATE KEY UPDATE
capacity = VALUES(capacity),
recording_system = VALUES(recording_system),
features = VALUES(features),
available_times = VALUES(available_times);

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;

-- Notes:
-- 1) 建議先備份資料表：
--    mysqldump -u <user> -p rent_classroom classrooms > classrooms_backup.sql
-- 2) 若您在本地開發環境，建議先在測試 DB 執行並驗證再在生產環境執行。
-- 3) 若您希望 classroom_name 欄位的內容先暫存，可在 DROP 前執行 ALTER TABLE ADD COLUMN classroom_name_old ... 並填值。
