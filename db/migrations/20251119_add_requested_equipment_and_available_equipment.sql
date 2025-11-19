-- Migration: Add requested_equipment to bookings and available_equipment to classrooms

ALTER TABLE bookings
ADD COLUMN requested_equipment TEXT NULL COMMENT '使用者在預約時要求的設備，逗號分隔';

ALTER TABLE classrooms
ADD COLUMN available_equipment TEXT NULL COMMENT '教室可提供的設備清單，逗號分隔（例如: projector,microphone）';

-- 說明：
-- 1) 執行此檔會在 bookings 增加 requested_equipment 欄位；前端可傳入多項設備並以逗號存放。
-- 2) 在 classrooms 增加 available_equipment，可由管理端在教室編輯頁填入教室可提供設備。
