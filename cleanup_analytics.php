<?php
require_once 'includes/db.php';
require_once 'includes/analytics.php';

header('Content-Type: text/plain');

try {
    $db = new Database();
    
    echo "Starting analytics data cleanup...\n";
    echo "================================\n";
    
    // 1. Find and fix records where score > total_questions
    echo "Checking for invalid score records...\n";
    $badRecords = $db->fetchAll("
        SELECT id, event_data 
        FROM analytics 
        WHERE event_type = 'quiz_complete'
        AND JSON_VALID(event_data) = 1
        AND JSON_EXTRACT(event_data, '$.score') > JSON_EXTRACT(event_data, '$.total_questions')
    ");
    
    if (empty($badRecords)) {
        echo "No invalid score records found.\n";
    } else {
        echo "Found " . count($badRecords) . " invalid records. Fixing...\n";
        
        foreach ($badRecords as $record) {
            $data = json_decode($record['event_data'], true);
            if (!$data) {
                echo "Skipping record ID: {$record['id']} (invalid JSON)\n";
                continue;
            }
            
            $originalScore = $data['score'];
            $data['score'] = min($data['score'], $data['total_questions']);
            
            $db->query(
                "UPDATE analytics SET event_data = ? WHERE id = ?",
                [json_encode($data), $record['id']]
            );
            
            echo "Fixed record ID: {$record['id']} (score adjusted from $originalScore to {$data['score']})\n";
        }
    }
    
    // 2. Find and fix records with invalid JSON
    echo "\nChecking for invalid JSON records...\n";
    $invalidJsonRecords = $db->fetchAll("
        SELECT id, event_data 
        FROM analytics 
        WHERE JSON_VALID(event_data) = 0
        LIMIT 100
    ");
    
    if (empty($invalidJsonRecords)) {
        echo "No invalid JSON records found.\n";
    } else {
        echo "Found " . count($invalidJsonRecords) . " invalid JSON records. These need manual review.\n";
        foreach ($invalidJsonRecords as $record) {
            echo "Record ID: {$record['id']} - Data: " . substr($record['event_data'], 0, 50) . "...\n";
        }
    }
    
    echo "\nCleanup completed successfully.\n";
    echo "================================\n";
    echo "Summary:\n";
    echo "- Checked and fixed " . count($badRecords) . " invalid score records\n";
    echo "- Found " . count($invalidJsonRecords) . " invalid JSON records (need manual review)\n";
    
} catch (Exception $e) {
    echo "\n!!! Cleanup failed !!!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Check your PHP error logs for more details.\n";
}
?>