<?php
header('Content-Type: application/json');
http_response_code($_GET['code'] ?? 500);

$error_messages = [
    404 => 'API endpoint not found',
    500 => 'Internal server error',
    403 => 'Access forbidden',
    401 => 'Unauthorized access'
];

$code = (int)($_GET['code'] ?? 500);
$message = $error_messages[$code] ?? 'Unknown error';

echo json_encode([
    'success' => false,
    'error' => [
        'code' => $code,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]
]);
?>