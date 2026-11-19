-- CÁ'S HOA | Migration 004 — security sessions and recipe-driven stock (idempotent).
-- Run after 003_admin_ops.sql. Existing customer and inventory data is retained.
USE cas_hoa;
SET @schema := DATABASE();
SET @sql := IF(
 (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@schema AND table_name='users' AND column_name='session_version')=0,
 'ALTER TABLE users ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 1',
 'DO 0'
);
PREPARE cas_stmt FROM @sql; EXECUTE cas_stmt; DEALLOCATE PREPARE cas_stmt;

CREATE TABLE IF NOT EXISTS product_recipes (
  product_id INT UNSIGNED NOT NULL,
  item_id INT UNSIGNED NOT NULL,
  quantity DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (product_id,item_id),
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES inventory_items(item_id),
  CONSTRAINT chk_recipe_qty_positive CHECK (quantity>0 AND quantity<=100000)
) ENGINE=InnoDB;

-- Snapshot of material quantities *actually consumed* per order, not current recipe.
-- UNIQUE(order_id,item_id) prevents duplicate deductions. restored_at makes returns idempotent.
CREATE TABLE IF NOT EXISTS order_inventory_usage (
  order_id INT UNSIGNED NOT NULL,
  item_id INT UNSIGNED NOT NULL,
  quantity DECIMAL(12,2) NOT NULL,
  consumed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  restored_at DATETIME NULL,
  actor_id INT UNSIGNED NULL,
  PRIMARY KEY (order_id,item_id),
  FOREIGN KEY (order_id) REFERENCES orders(order_id),
  FOREIGN KEY (item_id) REFERENCES inventory_items(item_id),
  FOREIGN KEY (actor_id) REFERENCES users(user_id),
  CONSTRAINT chk_usage_qty_positive CHECK (quantity>0)
) ENGINE=InnoDB;
