<?php
require_once 'db.php';
require_once 'analytics.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) throw new Exception('Invalid JSON data');

    error_log("Received analytics data: " . print_r($data, true));

    $analytics = new Analytics();
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    switch ($data['event_category']) {
        case 'page':
            if ($data['event_action'] === 'view') {
                $analytics->trackPageView($userId);
            }
            break;
            
        case 'quiz':
            switch ($data['event_action']) {
                case 'start':
                    $analytics->trackQuizStart($data['event_value'], $userId);
                    break;
                case 'complete':
                    $analytics->trackQuizComplete(
                        isset($data['quiz_id']) ? $data['quiz_id'] : $data['event_value'], // Prefer explicit quiz_id
                        $data['event_label'], 
                        $data['event_value'], // score
                        isset($data['total_questions']) ? (int)$data['total_questions'] : 1,
                        isset($data['time_taken']) ? (int)$data['time_taken'] : 0,
                        $userId
                    );
                    break;
                case 'timer_expired':
                    $analytics->trackEvent('quiz_timer_expired', [
                        'quiz_id' => $data['event_value'],
                        'quiz_title' => $data['event_label']
                    ], $userId);
                    break;
            }
            break;
            
        case 'question':
            $isCorrect = ($data['event_action'] === 'correct');
            $analytics->trackQuestionAnswer(
                $data['event_value'],
                $data['event_label'],
                $isCorrect,
                isset($data['time_taken']) ? (int)$data['time_taken'] : 0,
                $userId
            );
            break;
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleQuestionEvent($analytics, $data, $userId) {
    $isCorrect = ($data['event_action'] === 'correct');
    $quizId = $data['event_value'] ?? null;
    $questionId = $data['event_label'] ?? null;
    
    if (empty($quizId) || empty($questionId)) {
        throw new Exception('Missing quiz or question ID');
    }
    
    $analytics->trackQuestionAnswer(
        $quizId,
        $questionId,
        $isCorrect,
        max(0, (int)($data['time_taken'] ?? 0)),
        $userId
    );
}
?>