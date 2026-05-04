CREATE DATABASE IF NOT EXISTS news_portal;
USE news_portal;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin','editor') NOT NULL DEFAULT 'user'
);

CREATE TABLE IF NOT EXISTS news_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  summary TEXT,
  category VARCHAR(50) NOT NULL,
  media_path VARCHAR(255),
  media_type ENUM('image','video') NULL,
  author_name VARCHAR(120),
  created_by INT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_news_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS saved_news (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  news_id INT NOT NULL,
  saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_saved_news_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_saved_news_article
    FOREIGN KEY (news_id) REFERENCES news_posts(id)
    ON DELETE CASCADE,
  UNIQUE KEY unique_user_news (user_id, news_id)
);

CREATE TABLE IF NOT EXISTS news_votes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  news_id INT NOT NULL,
  vote_type ENUM('up','down') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_news_votes_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_news_votes_article
    FOREIGN KEY (news_id) REFERENCES news_posts(id)
    ON DELETE CASCADE,
  UNIQUE KEY unique_user_news_vote (user_id, news_id),
  KEY idx_news_votes_news_type (news_id, vote_type)
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
-- Change it right after fir5st login.
