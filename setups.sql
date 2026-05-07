CREATE DATABASE IF NOT EXISTS news_portal;
USE news_portal;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin','editor') NOT NULL DEFAULT 'user'
);

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS news_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  summary TEXT,
  category_id INT,
  category VARCHAR(50),
  media_path VARCHAR(255),
  media_type ENUM('image','video') NULL,
  author_name VARCHAR(120),
  created_by INT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_news_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_news_category
    FOREIGN KEY (category_id) REFERENCES categories(id)
    ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS saved_news (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  news_id INT NOT NULL,
  saved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_saved_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_saved_news
    FOREIGN KEY (news_id) REFERENCES news_posts(id)
    ON DELETE CASCADE,
  UNIQUE KEY unique_saved (user_id, news_id)
);

INSERT INTO categories (name, description) VALUES
('Politics', 'Political news and updates'),
('Sports', 'Sports news and events'),
('Technology', 'Technology and innovation news'),
('Entertainment', 'Entertainment and celebrity news')
ON DUPLICATE KEY UPDATE name = VALUES(name);

--  for forgot password flow
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reset_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
);

INSERT INTO users (username, email, password, role)
VALUES (
  'admin',
  'admin@local',
  '$2y$12$7D8JiSshjijCL968l80Tq.VoGeJ/tiJVzF0s3AfIqSXp/hFR7t3PC',
  'admin'
)
ON DUPLICATE KEY UPDATE role = 'admin';

-- Temporary admin password: Admin@12345
-- Change it right after first login.
