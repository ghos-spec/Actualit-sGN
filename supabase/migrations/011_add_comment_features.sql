-- Ajout des colonnes pour les réponses et signalements
ALTER TABLE comments
ADD COLUMN parent_id INT DEFAULT NULL,
ADD COLUMN status ENUM('pending', 'approved', 'reported', 'rejected') DEFAULT 'pending',
ADD COLUMN report_count INT DEFAULT 0,
ADD COLUMN report_reason TEXT,
ADD COLUMN likes_count INT DEFAULT 0,
ADD COLUMN dislikes_count INT DEFAULT 0,
ADD FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE;

-- Table pour les votes sur les commentaires
CREATE TABLE comment_votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    comment_id INT NOT NULL,
    user_id INT NOT NULL,
    vote_type ENUM('like', 'dislike') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES journalists(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (comment_id, user_id)
);

-- Table pour les notifications
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('comment', 'reply', 'report', 'moderation') NOT NULL,
    content TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES journalists(id) ON DELETE CASCADE
);

-- Table pour les paramètres de modération
CREATE TABLE moderation_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    auto_approve_comments BOOLEAN DEFAULT TRUE,
    require_email_verification BOOLEAN DEFAULT FALSE,
    max_reports_before_hide INT DEFAULT 3,
    notify_on_reports BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertion des paramètres de modération par défaut
INSERT INTO moderation_settings (auto_approve_comments, require_email_verification, max_reports_before_hide, notify_on_reports)
VALUES (TRUE, FALSE, 3, TRUE); 