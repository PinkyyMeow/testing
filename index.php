<?php
// index.php
require_once 'includes/db.php';
require_once 'includes/analytics.php';

// Track page view
$analytics->trackPageView(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);

// Fetch all quizzes
$quizzes = $db->fetchAll("SELECT * FROM quizzes");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Quiz Platform</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>PolyU Quizzes</h1>
        
        <?php if (empty($quizzes)): ?>
            <p>No quizzes available yet.</p>
        <?php else: ?>
            <div class="quiz-list">
                <?php foreach ($quizzes as $quiz): ?>
                <div class="quiz-card">
                    <h2><?php echo htmlspecialchars($quiz['title']); ?></h2>
                    <p><?php echo htmlspecialchars($quiz['description']); ?></p>
                    <p>Time limit: <?php echo floor($quiz['time_limit'] / 60); ?> minutes</p>
                    <a href="quiz.php?id=<?php echo $quiz['id']; ?>" class="btn">Start Quiz</a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>