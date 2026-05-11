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

-- Assuming categories IDs: 1=Politics, 2=Sports, 3=Technology, 4=Entertainment
-- Adjust IDs if needed after checking your DB

-- Run after your schema + categories + admin user
INSERT INTO news_posts (
  title, summary, category_id, category, media_path, media_type,
  author_name, created_by, status, created_at
) VALUES

-- 1. Politics
('Trump Calls Iran’s Response to Peace Proposal ‘Totally Unacceptable’',
 'President Trump rejected Iran’s latest response to U.S. ceasefire proposals amid ongoing tensions in the Strait of Hormuz.',
 1, 'Politics',
 'https://media.gettyimages.com/id/2150000000/photo/donald-trump-speaks-at-a-rally.jpg',  -- Replace with actual recent Trump image if needed
 'image', 'AP/Reuters', NULL, 'approved', NOW()),

-- 2. Politics
('Virginia Supreme Court Tosses Voter-Approved Redistricting Map in Major Blow to Democrats',
 'The court ruling overturns a map that could have shifted multiple House seats ahead of midterms.',
 1, 'Politics',
 'https://media.gettyimages.com/id/2180000000/photo/us-supreme-court-building.jpg',
 'image', 'NYT Staff', NULL, 'approved', NOW()),

-- 3. Sports - Thunder vs Lakers
('Thunder Dominate Lakers 131-108, Take 3-0 Series Lead in Western Semifinals',
 'Oklahoma City blew out Los Angeles in Game 3 behind Shai Gilgeous-Alexander and Ajay Mitchell.',
 2, 'Sports',
 'https://picsum.photos/id/1015/800/450',   -- Reliable placeholder (or use real NBA Getty)
 'image', 'NBA/USA Today', NULL, 'approved', NOW()),

-- 4. Sports - Arsenal
('Arsenal Advances to Champions League Final After Beating Atlético Madrid',
 'The Gunners secured a historic spot in the final with a strong performance.',
 2, 'Sports',
 'https://picsum.photos/id/201/800/450',
 'image', 'Arsenal/CNN Sport', NULL, 'approved', NOW()),

-- 5. Technology - Nintendo
('Nintendo Shares Slump as Price Hikes and Games Shortfall Spook Investors',
 'Despite strong Switch 2 performance, Nintendo warned of profit drops.',
 3, 'Technology',
 'https://www.nintendolife.com/images/news/switch2_official.jpg',
 'image', 'Reuters', NULL, 'approved', NOW()),

-- 6. Technology - SpaceX
('Elon Musk’s SpaceX Plans $55 Billion Investment in AI Chip Factory',
 'The company aims to boost AI capabilities with the new Terafab semiconductor facility.',
 3, 'Technology',
 'https://picsum.photos/id/1074/800/450',   -- Tech/factory style
 'image', 'NYT Tech', NULL, 'approved', NOW()),

-- 7. Entertainment
('Taylor Swift and Travis Kelce Enjoy Romantic Date Night in London',
 'The couple was spotted at dinner and events during their UK getaway.',
 4, 'Entertainment',
 'https://picsum.photos/id/1005/800/450',   -- Celebrity style
 'image', 'Page Six/TMZ', NULL, 'approved', NOW());
