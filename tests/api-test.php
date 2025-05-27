<?php
require_once '../includes/config.php';

// ทดสอบ API endpoints
$endpoints = [
    'dashboard-stats.php',
    'system-health.php', 
    'quick-stats.php?action=recent_patients',
    'global-search.php',
    'notification-counts.php'
];

echo "<h2>API Endpoint Tests</h2>\n";

foreach ($endpoints as $endpoint) {
    $url = "http://localhost/your-project/api/$endpoint";
    
    echo "<h3>Testing: $endpoint</h3>\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-CSRF-Token: test-token'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "Status: $httpCode<br>\n";
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data) {
            echo "Response: " . (isset($data['success']) ? ($data['success'] ? '✅ Success' : '❌ Failed') : '⚠️ Unknown') . "<br>\n";
            if (isset($data['message'])) {
                echo "Message: " . htmlspecialchars($data['message']) . "<br>\n";
            }
        } else {
            echo "Invalid JSON response<br>\n";
        }
    } else {
        echo "No response<br>\n";
    }
    
    curl_close($ch);
    echo "<hr>\n";
}
?>
