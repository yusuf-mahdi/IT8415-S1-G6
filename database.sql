-- ============================================
-- IT8415 - Database Programming 2
-- Project: Book Review Platform
-- Prefix: dbProj_
-- ============================================

CREATE DATABASE IF NOT EXISTS dbProj_BookReview;
USE dbProj_BookReview;

-- ============================================
-- TABLE 1: Users (3 roles: admin, creator, viewer)
-- ============================================
CREATE TABLE dbProj_users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50) NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,         -- stored encrypted
    role        ENUM('admin', 'creator', 'viewer') DEFAULT 'viewer',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE 2: Categories / Genres
-- ============================================
CREATE TABLE dbProj_categories (
    category_id     INT AUTO_INCREMENT PRIMARY KEY,
    category_name   VARCHAR(100) NOT NULL,
    description     TEXT
);

-- ============================================
-- TABLE 3: Books
-- ============================================
CREATE TABLE dbProj_books (
    book_id         INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(200) NOT NULL,
    author          VARCHAR(150) NOT NULL,
    description     TEXT,
    cover_image     VARCHAR(255),
    category_id     INT,
    added_by        INT,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES dbProj_categories(category_id),
    FOREIGN KEY (added_by) REFERENCES dbProj_users(user_id),
    FULLTEXT INDEX ft_books (title, author, description)   -- for full-text search
);

-- ============================================
-- TABLE 4: Reviews (created by creators)
-- ============================================
CREATE TABLE dbProj_reviews (
    review_id           INT AUTO_INCREMENT PRIMARY KEY,
    book_id             INT NOT NULL,
    user_id             INT NOT NULL,
    review_title        VARCHAR(200) NOT NULL,
    review_content      TEXT NOT NULL,
    cover_image         VARCHAR(255),
    media_file          VARCHAR(255),          -- audio/video file (Task 1.2 requirement)
    downloadable_file   VARCHAR(255),          -- optional downloadable content (Task 1.2)
    status              ENUM('draft', 'published') DEFAULT 'draft',
    view_count          INT DEFAULT 0,
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES dbProj_books(book_id),
    FOREIGN KEY (user_id) REFERENCES dbProj_users(user_id),
    FULLTEXT INDEX ft_reviews (review_title, review_content)
);

-- ============================================
-- TABLE 5: Ratings (star rating 1-5)
-- ============================================
CREATE TABLE dbProj_ratings (
    rating_id   INT AUTO_INCREMENT PRIMARY KEY,
    review_id   INT NOT NULL,
    user_id     INT NOT NULL,
    rating      TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rating (review_id, user_id),         -- one rating per user per review
    FOREIGN KEY (review_id) REFERENCES dbProj_reviews(review_id),
    FOREIGN KEY (user_id) REFERENCES dbProj_users(user_id)
);

-- ============================================
-- TABLE 6: Comments
-- ============================================
CREATE TABLE dbProj_comments (
    comment_id      INT AUTO_INCREMENT PRIMARY KEY,
    review_id       INT NOT NULL,
    user_id         INT NOT NULL,
    comment_text    TEXT NOT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES dbProj_reviews(review_id),
    FOREIGN KEY (user_id) REFERENCES dbProj_users(user_id)
);

-- ============================================
-- STORED PROCEDURE 1: Get Most Popular Reviews
-- (used for Admin Report)
-- ============================================
DELIMITER //
CREATE PROCEDURE dbProj_GetPopularReviews(
    IN start_date DATE,
    IN end_date   DATE
)
BEGIN
    SELECT 
        r.review_id,
        r.review_title,
        u.username AS creator,
        b.title AS book_title,
        AVG(rt.rating) AS avg_rating,
        r.view_count,
        r.created_at
    FROM dbProj_reviews r
    JOIN dbProj_users u ON r.user_id = u.user_id
    JOIN dbProj_books b ON r.book_id = b.book_id
    LEFT JOIN dbProj_ratings rt ON r.review_id = rt.review_id
    WHERE r.created_at BETWEEN start_date AND end_date
      AND r.status = 'published'
    GROUP BY r.review_id
    ORDER BY avg_rating DESC, r.view_count DESC;
END //
DELIMITER ;

-- ============================================
-- STORED PROCEDURE 2: Get Reviews By User
-- (used for Admin Report)
-- ============================================
DELIMITER //
CREATE PROCEDURE dbProj_GetReviewsByUser(
    IN p_user_id INT
)
BEGIN
    SELECT 
        r.review_id,
        r.review_title,
        b.title AS book_title,
        r.status,
        r.view_count,
        r.created_at
    FROM dbProj_reviews r
    JOIN dbProj_books b ON r.book_id = b.book_id
    WHERE r.user_id = p_user_id
    ORDER BY r.created_at DESC;
END //
DELIMITER ;

-- ============================================
-- TABLE 7: Activity Log (MUST be before trigger!)
-- ============================================
CREATE TABLE dbProj_activity_log (
    log_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,
    action      VARCHAR(255),
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TRIGGER: Log activity when a rating is added
-- ============================================
DELIMITER //
CREATE TRIGGER dbProj_AfterRatingInsert
AFTER INSERT ON dbProj_ratings
FOR EACH ROW
BEGIN
    INSERT INTO dbProj_activity_log (user_id, action, created_at)
    VALUES (NEW.user_id, CONCAT('Rated review #', NEW.review_id), NOW());
END //
DELIMITER ;

-- ============================================
-- TEST DATA — Users
-- ============================================
INSERT INTO dbProj_users (username, email, password, role) VALUES
('admin',       'admin@bookreview.com',   MD5('admin123'),   'admin'),
('sara_reads',  'sara@email.com',         MD5('pass123'),    'creator'),
('ahmed_books', 'ahmed@email.com',        MD5('pass123'),    'creator'),
('fatima_lit',  'fatima@email.com',       MD5('pass123'),    'creator'),
('viewer1',     'viewer1@email.com',      MD5('pass123'),    'viewer'),
('viewer2',     'viewer2@email.com',      MD5('pass123'),    'viewer');

-- ============================================
-- TEST DATA — Categories (at least 5 unique)
-- ============================================
INSERT INTO dbProj_categories (category_name, description) VALUES
('Fiction',         'Novels and fictional stories'),
('Non-Fiction',     'Real events and factual writing'),
('Science Fiction', 'Futuristic and science-based stories'),
('Mystery',         'Suspense and detective stories'),
('Self-Help',       'Personal development and improvement'),
('Biography',       'Life stories of real people');

-- ============================================
-- TEST DATA — Books
-- ============================================
INSERT INTO dbProj_books (title, author, description, category_id, added_by) VALUES
('The Great Gatsby',        'F. Scott Fitzgerald', 'A story of wealth and obsession in 1920s America.',     1, 2),
('Dune',                    'Frank Herbert',       'An epic sci-fi saga set on a desert planet.',           3, 2),
('Atomic Habits',           'James Clear',         'A guide to building good habits and breaking bad ones.',5, 3),
('Gone Girl',               'Gillian Flynn',       'A psychological thriller about a missing woman.',       4, 3),
('Sapiens',                 'Yuval Noah Harari',   'A brief history of humankind.',                        2, 4),
('1984',                    'George Orwell',       'A dystopian novel about a totalitarian society.',       1, 2),
('Steve Jobs',              'Walter Isaacson',     'The biography of Apple co-founder Steve Jobs.',        6, 4),
('The Martian',             'Andy Weir',           'An astronaut stranded on Mars must survive alone.',     3, 3);

-- ============================================
-- TEST DATA — Reviews
-- ============================================
INSERT INTO dbProj_reviews (book_id, user_id, review_title, review_content, status, view_count, created_at) VALUES
(1, 2, 'A Timeless Classic',         'The Great Gatsby is a beautifully written novel that captures the emptiness of the American Dream. Fitzgerald uses vivid imagery and complex characters.', 'published', 120, '2026-01-10 10:00:00'),
(2, 2, 'Epic World Building',        'Dune is unlike anything I have ever read. The world Herbert created is incredibly detailed and the story is gripping from start to finish.',              'published', 95,  '2026-01-15 11:00:00'),
(3, 3, 'Changed My Daily Routine',   'Atomic Habits gave me practical tools to change my life. Clear explains habit formation in a simple and actionable way.',                              'published', 200, '2026-02-01 09:00:00'),
(4, 3, 'Could Not Put It Down',      'Gone Girl is a masterpiece of suspense. The twist completely shocked me and the writing kept me on edge throughout.',                                  'published', 150, '2026-02-10 14:00:00'),
(5, 4, 'Eye Opening History',        'Sapiens made me rethink everything I knew about human history. Harari writes complex ideas in a very accessible way.',                                 'published', 180, '2026-02-20 08:00:00'),
(6, 2, 'Scary and Relevant',         '1984 feels more relevant today than ever. Orwell predicted many aspects of modern surveillance society. A must-read.',                                 'published', 210, '2026-03-01 10:00:00'),
(7, 4, 'Inspiring Life Story',       'The Steve Jobs biography is fascinating. Isaacson paints an honest picture of both his genius and his flaws.',                                        'published', 90,  '2026-03-10 12:00:00'),
(8, 3, 'Science Made Fun',           'The Martian is both educational and entertaining. Weir clearly did his research and the humor makes the survival story very enjoyable.',               'published', 130, '2026-03-15 16:00:00'),
(1, 4, 'Overrated Classic',          'While beautifully written, I found the characters unlikeable and the plot thin. A good book but perhaps not deserving all the hype.',                 'published', 45,  '2026-04-01 09:00:00');

-- ============================================
-- TEST DATA — Ratings
-- ============================================
INSERT INTO dbProj_ratings (review_id, user_id, rating) VALUES
(1, 5, 5), (1, 6, 4),
(2, 5, 5), (2, 6, 5),
(3, 5, 5), (3, 6, 4),
(4, 5, 4), (4, 6, 5),
(5, 5, 5), (5, 6, 4),
(6, 5, 5), (6, 6, 5),
(7, 5, 4), (7, 6, 3),
(8, 5, 5), (8, 6, 4),
(9, 5, 3), (9, 6, 2);

-- ============================================
-- TEST DATA — Comments
-- ============================================
INSERT INTO dbProj_comments (review_id, user_id, comment_text) VALUES
(1, 5, 'Great review! I completely agree about the imagery.'),
(1, 6, 'I had to read this for school and loved it too.'),
(2, 5, 'Dune is my favourite book of all time!'),
(3, 6, 'This book changed my life as well. Highly recommend.'),
(4, 5, 'The twist got me completely! Did not see it coming.'),
(5, 6, 'Sapiens is brilliant. Harari is a great writer.'),
(6, 5, '1984 is terrifying in how accurate it feels today.'),
(8, 6, 'The Martian made me laugh so much. Great review!');

-- ============================================
-- VERIFY DATA
-- ============================================
SELECT 'Users'      AS table_name, COUNT(*) AS records FROM dbProj_users
UNION ALL
SELECT 'Categories',                COUNT(*) FROM dbProj_categories
UNION ALL
SELECT 'Books',                     COUNT(*) FROM dbProj_books
UNION ALL
SELECT 'Reviews',                   COUNT(*) FROM dbProj_reviews
UNION ALL
SELECT 'Ratings',                   COUNT(*) FROM dbProj_ratings
UNION ALL
SELECT 'Comments',                  COUNT(*) FROM dbProj_comments;
