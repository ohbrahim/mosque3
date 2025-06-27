-- إنشاء الجداول الصحيحة
CREATE TABLE IF NOT EXISTS polls_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    question TEXT NOT NULL,
    poll_type ENUM('single_choice', 'multiple_choice', 'rating', 'text') DEFAULT 'single_choice',
    target_audience ENUM('all', 'members', 'students', 'teachers') DEFAULT 'all',
    start_date DATE,
    end_date DATE,
    max_votes_per_user INT DEFAULT 1,
    allow_anonymous BOOLEAN DEFAULT TRUE,
    show_results BOOLEAN DEFAULT TRUE,
    results_after_vote BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    status ENUM('draft', 'active', 'closed', 'archived') DEFAULT 'draft',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
);

CREATE TABLE IF NOT EXISTS poll_options_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT NOT NULL,
    option_text VARCHAR(200) NOT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (poll_id) REFERENCES polls_new(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS poll_votes_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT NOT NULL,
    option_id INT,
    user_id INT NULL,
    voter_ip VARCHAR(45),
    voter_email VARCHAR(100),
    vote_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (poll_id) REFERENCES polls_new(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES poll_options_new(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- نقل البيانات من الجداول القديمة
INSERT INTO polls_new (id, title, question, status, created_by, created_at, updated_at)
SELECT id, 
       COALESCE(title, SUBSTRING(question, 1, 100)) as title,
       question, 
       CASE 
           WHEN status = 'active' THEN 'active'
           WHEN status = 'inactive' THEN 'draft'
           ELSE 'draft'
       END as status,
       created_by, 
       created_at, 
       updated_at
FROM polls
WHERE id IS NOT NULL;

-- إعادة تسمية الجداول
DROP TABLE IF EXISTS polls_old;
DROP TABLE IF EXISTS poll_options_old;
DROP TABLE IF EXISTS poll_votes_old;

RENAME TABLE polls TO polls_old;
RENAME TABLE poll_options TO poll_options_old;
RENAME TABLE poll_votes TO poll_votes_old;

RENAME TABLE polls_new TO polls;
RENAME TABLE poll_options_new TO poll_options;
RENAME TABLE poll_votes_new TO poll_votes;