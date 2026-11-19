-- Chạy sau schema.sql. Thực thi nhiều lần không gây mất dữ liệu.
USE cas_hoa;
INSERT IGNORE INTO roles(role_id,name,description) VALUES (4,'owner','Chủ sở hữu');
CREATE TABLE IF NOT EXISTS password_resets(
 reset_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 token_hash CHAR(64) NOT NULL UNIQUE,
 expires_at DATETIME NOT NULL,
 consumed_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE CASCADE,
 INDEX idx_reset_user(user_id)
) ENGINE=InnoDB;
