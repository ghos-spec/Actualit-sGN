USE news_db;
ALTER TABLE articles ADD COLUMN video_path VARCHAR(255) NULL AFTER video_url; 