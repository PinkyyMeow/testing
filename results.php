<?php
require_once 'includes/db.php';
require_once 'includes/analytics.php';

session_start();

// Track page view
$analytics->trackPageView(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);

// Get results data
$quizId = intval($_GET['quiz_id'] ?? 0);
$score = intval($_GET['score'] ?? 0);
$totalQuestions = intval($_GET['total'] ?? 1);
$timeTaken = intval($_GET['time'] ?? 0);

// Fetch quiz title FIRST
$quiz = $db->fetchOne("SELECT title FROM quizzes WHERE id = ?", [$quizId]);
$quizTitle = $quiz ? $quiz['title'] : 'Unknown Quiz';

// Track completion (now with correct order and defined quizTitle)
$analytics->trackQuizComplete(
    $quizId,
    $quizTitle,
    $score,
    $totalQuestions,
    $timeTaken,
    isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null
);

// Calculate percentage
$percentage = round(($score / $totalQuestions) * 100);

// Save result to database if user is logged in
if (isset($_SESSION['user_id'])) {
    $db->insert(
        "INSERT INTO results (user_id, quiz_id, score, total_questions, time_taken) 
         VALUES (?, ?, ?, ?, ?)",
        [$_SESSION['user_id'], $quizId, $score, $totalQuestions, $timeTaken]
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results: <?php echo htmlspecialchars($quizTitle); ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="assets/js/analytics.js"></script>
</head>
<body>
    <div class="results-container">
        <h1>Quiz Results: <?php echo htmlspecialchars($quizTitle); ?></h1>
        
        <div class="result-card">
            <div class="result-score">
                <div class="percentage"><?php echo $percentage; ?>%</div>
                <div class="fraction"><?php echo $score; ?> out of <?php echo $totalQuestions; ?> correct</div>
            </div>
            
            <div class="result-details">
                <div class="detail">
                    <span class="label">Time Taken:</span>
                    <span class="value"><?php echo floor($timeTaken / 60); ?>m <?php echo $timeTaken % 60; ?>s</span>
                </div>
                <div class="detail">
                    <span class="label">Completion Date:</span>
                    <span class="value"><?php echo date('F j, Y g:i a'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="result-actions">
            <a href="quiz.php?id=<?php echo $quizId; ?>" class="btn">Retake Quiz</a>
            <a href="index.php" class="btn">Back to Home</a>
        </div>
    </div>
</body>
</html>