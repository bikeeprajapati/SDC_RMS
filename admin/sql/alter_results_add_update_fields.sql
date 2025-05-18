ALTER TABLE results
ADD COLUMN updated_by INT NULL AFTER created_by,
ADD COLUMN updated_at DATETIME NULL AFTER created_at,
ADD FOREIGN KEY (updated_by) REFERENCES admin(id); 