CREATE DATABASE IF NOT EXISTS quiz_app;
USE quiz_app;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Quizzes table
CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    time_limit INT NOT NULL COMMENT 'Time limit in seconds',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Questions table
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_answer CHAR(1) NOT NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
);

-- Results table
CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    quiz_id INT NOT NULL,
    score INT NOT NULL,
    total_questions INT NOT NULL,
    time_taken INT NOT NULL COMMENT 'Time taken in seconds',
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Analytics table
CREATE TABLE analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    page_url VARCHAR(255) NOT NULL,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

/*
-- Insert sample quizzes
INSERT INTO quizzes (title, description, time_limit) VALUES 
('COMP3421 Basic Quiz', 'Test your knowledge of web development basics', 300),
('AMA4850 Math Quiz', 'Basic mathematics questions', 600),
('COMP4434 Advanced Quiz', 'Advanced computer science topics', 900);

-- Insert questions for COMP3421 Basic Quiz
INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
(1, 'What does HTML stand for?', 'Hyper Text Markup Language', 'Home Tool Markup Language', 'Hyperlinks and Text Markup Language', 'Hyper Tool Markup Language', 'A'),
(1, 'Which of these is a server-side language?', 'JavaScript', 'HTML', 'PHP', 'CSS', 'C'),
(1, 'What does CSS stand for?', 'Computer Style Sheets', 'Creative Style Sheets', 'Cascading Style Sheets', 'Colorful Style Sheets', 'C'),
(1, 'Which HTML tag is used for the largest heading?', '<heading>', '<h6>', '<h1>', '<head>', 'C'),
(1, 'How do you select an element with id "demo" in CSS?', '.demo', '#demo', '*demo', 'demo', 'B');

-- Insert questions for AMA4850 Math Quiz
INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
(2, 'What is 5 + 7?', '10', '11', '12', '13', 'C'),
(2, 'What is the square root of 64?', '6', '7', '8', '9', 'C'),
(2, 'Solve for x: 2x + 5 = 15', '5', '10', '7.5', '20', 'A'),
(2, 'What is the area of a circle with radius 3?', '6π', '9π', '12π', '15π', 'B'),
(2, 'What is 3 cubed?', '6', '9', '27', '81', 'C');

-- Insert questions for COMP4434 Advanced Quiz
INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
(3, 'Which data structure uses FIFO?', 'Stack', 'Queue', 'Array', 'Tree', 'B'),
(3, 'What is the time complexity of binary search?', 'O(1)', 'O(log n)', 'O(n)', 'O(n²)', 'B'),
(3, 'Which is NOT a NoSQL database?', 'MongoDB', 'Redis', 'PostgreSQL', 'Cassandra', 'C'),
(3, 'What does ACID stand for in databases?', 'Atomicity, Consistency, Isolation, Durability', 'Availability, Consistency, Integrity, Durability', 'Atomicity, Consistency, Integrity, Durability', 'Atomicity, Concurrency, Isolation, Durability', 'A'),
(3, 'Which sorting algorithm has worst-case O(n log n) time complexity?', 'QuickSort', 'MergeSort', 'BubbleSort', 'InsertionSort', 'B');

UPDATE quizzes SET title = 'Web Development Basic Quiz' WHERE title = 'COMP3421 Basic Quiz';
UPDATE quizzes SET title = 'Elementary Math Quiz' WHERE title = 'AMA4850 Math Quiz';
UPDATE quizzes SET title = 'Computer Science Fundamentals Quiz' WHERE title = 'COMP4434 Advanced Quiz';
-- Fix HTML tags in question options (for question ID 4)

UPDATE questions SET 
    option_a = '&lt;heading&gt;',
    option_b = '&lt;h6&gt;',
    option_c = '&lt;h1&gt;',
    option_d = '&lt;head&gt;'
WHERE id = 4;
*/