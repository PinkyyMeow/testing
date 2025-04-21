<?php
require_once 'db.php';

class Analytics {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }

    public function trackEvent($eventType, $eventData = [], $userId = null) {
        try {
            $conn = $this->db->getConnection();
            
            $pageUrl = $_SERVER['REQUEST_URI'] ?? 'unknown';
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $sql = "INSERT INTO analytics (event_type, event_data, page_url, user_id, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?)";
        
            $eventDataJson = json_encode($eventData);
            if ($eventDataJson === false) {
                throw new Exception("Failed to encode event data: " . json_last_error_msg());
            }
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $userIdParam = !empty($userId) ? $userId : null;
            
            if (!$stmt->bind_param("sssiss", $eventType, $eventDataJson, $pageUrl, $userIdParam, $ipAddress, $userAgent)) {
                throw new Exception("Bind failed: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            return $stmt->insert_id;
        } catch (Exception $e) {
            error_log("Analytics Error [" . date('Y-m-d H:i:s') . "]: " . $e->getMessage());
            error_log("Event Data: " . print_r($eventData, true));
            return false;
        }
    }

    public function trackPageView($userId = null) {
        return $this->trackEvent('page_view', [
            'page_title' => basename($_SERVER['REQUEST_URI']),
            'timestamp' => time()
        ], $userId);
    }

    public function trackQuizStart($quizId, $userId = null) {
        return $this->trackEvent('quiz_start', [
            'quiz_id' => $quizId,
            'timestamp' => time()
        ], $userId);
    }

    public function trackQuizComplete($quizId, $quizTitle, $score, $totalQuestions, $timeTaken, $userId = null) {
        // Validate inputs
        $score = max(0, min($score, $totalQuestions)); // Ensure score is between 0 and total
        if ($totalQuestions <= 0) {
            error_log("Invalid total questions: $totalQuestions");
            return false;
        }
        
        // Log if score was adjusted
        if ($score != $score) {
            error_log("Score adjusted from $score to $score (total questions: $totalQuestions)");
        }
        
        return $this->trackEvent('quiz_complete', [
            'quiz_id' => $quizId,
            'quiz_title' => $quizTitle,
            'score' => $score,
            'total_questions' => $totalQuestions,
            'time_taken' => $timeTaken,
            'timestamp' => time()
        ], $userId);
    }

    public function trackQuestionAnswer($quizId, $questionId, $isCorrect, $timeTaken, $userId = null) {
        return $this->trackEvent('question_answer', [
            'quiz_id' => $quizId,
            'question_id' => $questionId,
            'is_correct' => $isCorrect,
            'time_taken' => $timeTaken,
            'timestamp' => time()
        ], $userId);
    }

    public function getAnalyticsData($timePeriod = '7d') {
        $conn = $this->db->getConnection();
        
        $interval = match($timePeriod) {
            '1d' => 'INTERVAL 1 DAY',
            '30d' => 'INTERVAL 30 DAY',
            default => 'INTERVAL 7 DAY'
        };
    
        try {
            // Basic counts
            $counts = $this->db->fetchOne("
                SELECT 
                    SUM(CASE WHEN event_type = 'page_view' THEN 1 ELSE 0 END) as page_views,
                    SUM(CASE WHEN event_type = 'quiz_start' THEN 1 ELSE 0 END) as quiz_starts,
                    SUM(CASE WHEN event_type = 'quiz_complete' THEN 1 ELSE 0 END) as quiz_completions
                FROM analytics 
                WHERE created_at >= NOW() - $interval
            ");
    
            // Improved average score calculation with validation
            $scoreData = $this->db->fetchOne("
                SELECT 
                    SUM(CAST(JSON_EXTRACT(event_data, '$.score') AS DECIMAL(10,2))) as total_score,
                    SUM(CAST(JSON_EXTRACT(event_data, '$.total_questions') AS DECIMAL(10,2))) as total_questions,
                    COUNT(*) as attempts
                FROM analytics 
                WHERE event_type = 'quiz_complete'
                AND created_at >= NOW() - $interval
                AND JSON_VALID(event_data) = 1
                AND JSON_EXTRACT(event_data, '$.score') <= JSON_EXTRACT(event_data, '$.total_questions')
            ");
    
            $avgScore = 0;
            if ($scoreData['attempts'] > 0 && $scoreData['total_questions'] > 0) {
                $avgScore = ($scoreData['total_score'] / $scoreData['total_questions']) * 100;
                $avgScore = min(100, max(0, round($avgScore, 2))); // Ensure 0-100 range
            }
    
            $completionRate = 0;
            if (($counts['quiz_starts'] ?? 0) > 0) {
                $completionRate = min(100, round(($counts['quiz_completions'] / $counts['quiz_starts']) * 100, 2));
            }

            $popularQuizzes = $this->db->fetchAll("
                SELECT 
                    JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.quiz_id')) as quiz_id,
                    JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.quiz_title')) as quiz_title,
                    COUNT(*) as attempts,
                    AVG(
                        CASE 
                            WHEN JSON_EXTRACT(event_data, '$.total_questions') > 0 
                            AND JSON_EXTRACT(event_data, '$.score') <= JSON_EXTRACT(event_data, '$.total_questions')
                            THEN (JSON_EXTRACT(event_data, '$.score') * 100.0 / JSON_EXTRACT(event_data, '$.total_questions'))
                            ELSE 0
                        END
                    ) as avg_score_percentage
                FROM analytics 
                WHERE event_type = 'quiz_complete'
                AND created_at >= NOW() - $interval
                AND JSON_VALID(event_data) = 1
                GROUP BY quiz_id, quiz_title
                HAVING attempts > 0 AND avg_score_percentage BETWEEN 0 AND 100
                ORDER BY attempts DESC
                LIMIT 5
            ");

            $difficultQuestions = $this->db->fetchAll("
                SELECT 
                    JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.question_id')) as question_id,
                    COUNT(*) as attempts,
                    AVG(JSON_EXTRACT(event_data, '$.is_correct')) * 100 as correct_percentage
                FROM analytics 
                WHERE event_type = 'question_answer'
                AND created_at >= NOW() - $interval
                AND JSON_VALID(event_data) = 1
                GROUP BY question_id
                HAVING attempts > 0
                ORDER BY correct_percentage ASC
                LIMIT 5
            ");

            return [
                'page_views' => (int)($counts['page_views'] ?? 0),
                'quiz_starts' => (int)($counts['quiz_starts'] ?? 0),
                'quiz_completions' => (int)($counts['quiz_completions'] ?? 0),
                'completion_rate' => $completionRate,
                'avg_score' => $avgScore,
                'popular_quizzes' => $popularQuizzes ?? [],
                'difficult_questions' => $difficultQuestions ?? []
            ];
        } catch (Exception $e) {
            error_log("Dashboard Error: " . $e->getMessage());
            return [
                'page_views' => 0,
                'quiz_starts' => 0,
                'quiz_completions' => 0,
                'completion_rate' => 0,
                'avg_score' => 0,
                'popular_quizzes' => [],
                'difficult_questions' => []
            ];
        }
    }
}

$analytics = new Analytics();
?>