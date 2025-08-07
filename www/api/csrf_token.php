<?php
// -------------------------------------------------------------------
// csrf_token.php - Returns current CSRF token as JSON
// -------------------------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/security_config.php';
require_once __DIR__ . '/csrf_utils.php';

// Security headers and CORS for dev
setSecurityHeaders();
validateOrigin();

// Handle preflight
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    header('Access-Control-Max-Age: 86400');
    header('Content-Type: application/json');
    exit;
}

header('Content-Type: application/json');

$token = getCSRFToken();

// Also set a CSRF cookie for double-submit strategy (readable by JS)
setcookie('XSRF-TOKEN', $token, [
    'expires' => time() + 3600,
    'path' => '/',
    'secure' => false,
    'httponly' => false,
    'samesite' => 'Lax'
]);

echo json_encode(['csrf_token' => $token]);
exit;
?>


