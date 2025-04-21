<?php
require_once 'includes/db.php';
require_once 'includes/analytics.php';

header('Content-Type: text/plain');

try {
    $db = new Database();
    $analytics = new Analytics();

    // Clear old test data
    $db->query("DELETE FROM analytics WHERE user_id IS NULL");

    // Test events
    $analytics->trackPageView(null);
    $analytics->trackQuizStart(1, null);
    $analytics->trackQuestionAnswer(1, 101, true, 5, null);
    $analytics->trackQuestionAnswer(1, 102, false, 8, null);
    $analytics->trackQuizComplete(1, "Test Quiz", 3, 5, 120, null);
    $analytics->trackEvent('quiz_timer_expired', ['quiz_id' => 1, 'quiz_title' => 'Test Quiz'], null);

    // Verify results
    $results = $db->fetchAll("
        SELECT event_type, event_data, created_at 
        FROM analytics 
        ORDER BY created_at DESC
        LIMIT 10
    ");
    
    echo "=== Test Results ===\n";
    foreach ($results as $row) {
        echo "[{$row['created_at']}] {$row['event_type']}: {$row['event_data']}\n";
    }

    echo "\nTest completed successfully. Check your dashboard.php to verify data appears correctly.\n";

} catch (Exception $e) {
    echo "!!! Test Failed !!!\nError: " . $e->getMessage();
}