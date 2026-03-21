-- Add missing columns to maintenance_requests table
ALTER TABLE `maintenance_requests` 
ADD COLUMN `issue_description` TEXT NULL AFTER `approved_by_id`,
ADD COLUMN `issue_photos` JSON NULL AFTER `issue_description`,
ADD COLUMN `manager_notes` TEXT NULL AFTER `issue_photos`,
ADD COLUMN `completed_at` TIMESTAMP NULL AFTER `manager_notes`;

-- Update status enum to match Flutter app
ALTER TABLE `maintenance_requests` 
MODIFY COLUMN `status` ENUM('pending', 'in_progress', 'completed', 'rejected') 
DEFAULT 'pending';

-- Make mechanic_id nullable
ALTER TABLE `maintenance_requests` 
MODIFY COLUMN `mechanic_id` BIGINT UNSIGNED NULL;
