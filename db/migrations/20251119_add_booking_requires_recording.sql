-- Migration: Add requires_recording to bookings

ALTER TABLE bookings
ADD COLUMN requires_recording BOOLEAN NOT NULL DEFAULT FALSE COMMENT '使用者在預約時是否要求錄播服務';

-- 如果需要可為既有紀錄設定預設值（此處已預設為 FALSE）

-- 使用方式：在執行此 migration 後，請確保應用端改為在 INSERT/UPDATE bookings 時包含此欄位。
