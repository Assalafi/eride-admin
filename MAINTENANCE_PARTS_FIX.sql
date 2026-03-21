-- Add missing cost columns to maintenance_request_parts pivot table
ALTER TABLE `maintenance_request_parts` 
ADD COLUMN `unit_cost` DECIMAL(10,2) DEFAULT 0 AFTER `quantity`,
ADD COLUMN `total_cost` DECIMAL(10,2) DEFAULT 0 AFTER `unit_cost`;

-- Update existing records to calculate costs based on parts.cost if available
-- This is optional if you have existing data
UPDATE maintenance_request_parts mrp
JOIN parts p ON mrp.part_id = p.id
SET 
    mrp.unit_cost = p.cost,
    mrp.total_cost = p.cost * mrp.quantity
WHERE mrp.unit_cost = 0;
