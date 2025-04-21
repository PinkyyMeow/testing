<?php
require_once 'includes/db.php';
require_once 'includes/analytics.php';

header('Content-Type: text/plain');

try {
    $db = new Database();
    $analytics = new Analytics();

    echo "=== Analytics Data Verification ===\n\n";
    
    // 1. Check database connection
    echo "Testing database connection... ";
    $conn = $db->getConnection();
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "OK\n";
    
    // 2. Check if analytics table exists
    echo "Checking analytics table... ";
    $tableExists = $db->fetchOne("
        SELECT COUNT(*) as count 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'analytics'
    ")['count'] > 0;
    
    echo ($tableExists ? "EXISTS" : "MISSING") . "\n";
    
    if (!$tableExists) {
        die("\nERROR: Analytics table doesn't exist. Please run setup.sql\n");
    }
    
    // 3. Count records
    $totalRecords = $db->fetchOne("SELECT COUNT(*) as count FROM analytics")['count'];
    echo "\nTotal analytics records: $totalRecords\n";
    
    // 4. Show recent records
    if ($totalRecords > 0) {
        echo "\nRecent 5 records:\n";
        $recentRecords = $db->fetchAll("
            SELECT 
                id, 
                event_type, 
                created_at,
                JSON_EXTRACT(event_data, '$.quiz_id') as quiz_id,
                JSON_EXTRACT(event_data, '$.score') as score
            FROM analytics 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        
        foreach ($recentRecords as $record) {
            echo sprintf(
                "[%d] %-15s %s (Quiz: %s, Score: %s)\n",
                $record['id'],
                $record['event_type'],
                $record['created_at'],
                $record['quiz_id'] ?? 'N/A',
                $record['score'] ?? 'N/A'
            );
        }
    }
    
    // 5. Test dashboard data
    echo "\nTesting dashboard data...\n";
    $dashboardData = $analytics->getAnalyticsData('7d');
    print_r($dashboardData);
    
    // 6. Test event tracking
    echo "\nTesting event tracking...\n";
    $testEventId = $analytics->trackEvent('test_event', ['test' => true]);
    echo "Test event recorded with ID: " . ($testEventId ?: 'FAILED') . "\n";
    
    echo "\n=== Verification Complete ===\n";
    if ($totalRecords == 0) {
        echo "WARNING: No analytics records found. Make sure:\n";
        echo "1. You've completed at least one quiz\n";
        echo "2. Tracking is enabled in quiz.php\n";
        echo "3. There are no errors in your PHP error log\n";
    }
    
} catch (Exception $e) {
    echo "\n!!! ERROR !!!\n" . $e->getMessage() . "\n";
    echo "Check your PHP error logs for more details.\n";
}