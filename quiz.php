<?php
require_once 'includes/db.php';
require_once 'includes/analytics.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get quiz ID from URL
$quizId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Track page view
$analytics->trackPageView(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);

// Fetch quiz data
$quiz = $db->fetchOne("SELECT * FROM quizzes WHERE id = ?", [$quizId]);
if (!$quiz) {
    die('Quiz not found');
}

// Track quiz start
$analytics->trackQuizStart($quizId, isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);

// Fetch questions
$questions = $db->fetchAll("SELECT * FROM questions WHERE quiz_id = ?", [$quizId]);
if (empty($questions)) {
    die('No questions found for this quiz');
}

// Prepare quiz data for JavaScript
$quizData = [
    'quizId' => $quiz['id'],
    'questions' => $questions,
    'timeLimit' => $quiz['time_limit']
];

// Convert to JSON with proper escaping
$jsonData = json_encode($quizData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
if ($jsonData === false) {
    die('Failed to encode quiz data');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['title']); ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="assets/js/analytics.js"></script>
    <script src="assets/js/quiz.js"></script>
    <script>
    console.log("Quiz script loaded");
    </script>
</head>
<body>
    <div class="quiz-container">
        <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
        <p class="description"><?php echo htmlspecialchars($quiz['description']); ?></p>
        
        <div class="quiz-header">
            <div id="progress">Question 1 of <?php echo count($questions); ?></div>
            <div id="timer"><?php echo floor($quiz['time_limit'] / 60); ?>:<?php echo str_pad($quiz['time_limit'] % 60, 2, '0', STR_PAD_LEFT); ?></div>
        </div>
        
        <div class="quiz-body">
            <div id="question"></div>
            <div id="options"></div>
        </div>
        
        <div class="quiz-footer">
            <button id="prev-btn" disabled>Previous</button>
            <button id="next-btn">Next</button>
            <button id="submit-quiz">Submit Quiz</button>
        </div>
    </div>
    
    <script id="quiz-data" type="application/json">
        <?php echo $jsonData; ?>
    </script>
    <script>
        console.log("Quiz Data:", <?php echo $jsonData; ?>);
    </script>
</body>
</html>