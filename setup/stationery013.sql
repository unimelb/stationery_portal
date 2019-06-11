-- stationery13.sql
-- add is_active column
ALTER TABLE category
ADD COLUMN is_active VARCHAR(3) DEFAULT 'no'
AFTER description;
UPDATE category SET is_active = 'yes' WHERE category_id IN (1, 2, 3, 4);