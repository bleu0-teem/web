<?php
require_once 'config.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(405, 'Method not allowed');
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate new CSRF token
$csrfToken = generateCSRFToken();

// Send response
sendResponse(200, 'CSRF token generated', [
    'csrf_token' => $csrfToken,
    'timestamp' => time()
]);
?>