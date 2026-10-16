-- 005: Fixed cost snapshots for truthful historical gross-profit reporting.
-- Run only after migrations 001-004. Non-destructive and safe to repeat.
USE cas_hoa;
SET @db := DATABASE();
SET @sql := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='order_inventory_usage' AND column_name='unit_cost_snapshot')=0,
 'ALTER TABLE order_inventory_usage ADD COLUMN unit_cost_snapshot DECIMAL(12,2) NULL',
 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='order_inventory_usage' AND column_name='cost_amount_snapshot')=0,
 'ALTER TABLE order_inventory_usage ADD COLUMN cost_amount_snapshot DECIMAL(14,2) NULL',
 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
-- Existing usage remains NULL intentionally; never invent historical purchase prices.
