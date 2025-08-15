<?php

// -------------------------------------------------------------------
// api/login.php
//
// Expects POST fields:
//   • username_or_email
//   • password
//   • csrf_token
//
// Returns json reason and token (token if success) + HTTP status code:
//   • 200 OK   → "Login successful!"
//   • 400 Bad Request → missing fields or pwned password
//   • 401 Unauthorized → invalid credentials
//   • 403 Forbidden → CSRF token invalid
//   • 500 Internal Server Error → generic server error
// -------------------------------------------------------------------

// Disable error display to avoid breaking JSON responses
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set security headers
require_once 'security_config.php';
require_once 'error_handler.php';
setSecurityHeaders();
validateOrigin();

// Handle CORS preflight
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    header('Access-Control-Max-Age: 86400');
    header('Content-Type: application/json');
    exit;
}

// ------------------------------------------------------------
// DEV BYPASS (run as early as possible for local testing)
// ------------------------------------------------------------
if (function_exists('isLocalDevelopment') && isLocalDevelopment()) {
    require_once 'csrf_utils.php';
    $_SESSION['user_id']  = 1;
    $_SESSION['username'] = $_POST['username_or_email'] ?? 'devuser';
    session_regenerate_id(true);
    regenerateCSRFToken();
    sendSuccessResponse('Login successful! (dev bypass)', ['token' => 'dev-token']);
}

// Validate request method
validateRequestMethod(['POST']);

// ------------------------------------------------------------
// 1) CSRF PROTECTION (do this before any DB work)
// ------------------------------------------------------------
require_once 'csrf_utils.php';

// Check for CSRF token in POST or fallback to cookie
$csrf_token = $_POST['csrf_token'] ?? null;
if (!$csrf_token && isset($_COOKIE['XSRF-TOKEN'])) {
    $csrf_token = $_COOKIE['XSRF-TOKEN'];
}

if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    sendErrorResponse(403, 'Invalid CSRF token.');
}

// ------------------------------------------------------------
// DEV BYPASS (for local testing without DB)
// ------------------------------------------------------------
if ((($_ENV['APP_ENV'] ?? 'production') === 'development' || function_exists('isLocalDevelopment') && isLocalDevelopment()) && (($_ENV['AUTH_DEV_BYPASS'] ?? '1') === '1')) {
    $_SESSION['user_id']  = 1;
    $_SESSION['username'] = $_POST['username_or_email'] ?? 'devuser';
    session_regenerate_id(true);
    regenerateCSRFToken();
    sendSuccessResponse('Login successful! (dev bypass)', ['token' => 'dev-token']);
}

// ------------------------------------------------------------
// 2) DATABASE CONNECTION (PDO + SSL using environment variables)
// ------------------------------------------------------------
require_once 'db_connection.php';

// ------------------------------------------------------------
// 3) RATE LIMITING
// ------------------------------------------------------------
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limit_key = "login_attempts_$ip";

if (!checkRateLimit($rate_limit_key, 5, 900)) {
    sendErrorResponse(429, 'Too many login attempts. Please try again later.');
}

// ------------------------------------------------------------
// 4) FETCH & VALIDATE POST DATA
// ------------------------------------------------------------
$identifier = sanitizeInput($_POST['username_or_email'] ?? '', 'username');
$password   = $_POST['password'] ?? '';

// Validate input
if (!$identifier || empty($password)) {
    sendErrorResponse(400, 'Please fill in both fields with valid data.');
}

// ------------------------------------------------------------
// 5) SERVER-SIDE "Have I Been Pwned" CHECK
// ------------------------------------------------------------
require_once 'hibp.php';

if (isPwnedPassword($password)) {
    sendErrorResponse(400, 'Your password has been found in breaches. Please change it.');
}

// ------------------------------------------------------------
// 6) FETCH USER FROM DATABASE & VERIFY PASSWORD
// ------------------------------------------------------------
try {
    $stmt = $pdo->prepare("
        SELECT id, username, email, password_hash, token
        FROM users
        WHERE username = :ident OR email = :ident
        LIMIT 1
    ");
    $stmt->execute([':ident' => $identifier]);
    $userRow = $stmt->fetch();
} catch (PDOException $e) {
    logError("Database query failed: " . $e->getMessage(), ['identifier' => $identifier]);
    sendErrorResponse(500, 'Server error. Please try again later.');
}

if (!$userRow || !password_verify($password, $userRow['password_hash'])) {
    // Increment failed attempts
    incrementRateLimit($rate_limit_key);
    
    sendErrorResponse(401, 'Invalid username/password.');
}

// ------------------------------------------------------------
// 7) LOGIN SUCCESS: SET SESSION & RETURN
// ------------------------------------------------------------
// Clear rate limiting on successful login
clearRateLimit($rate_limit_key);

$_SESSION['user_id']  = $userRow['id'];
$_SESSION['username'] = $userRow['username'];

// Regenerate session ID for security
session_regenerate_id(true);

// Cookie flags are configured globally; avoid changing session ini at runtime

// Generate new CSRF token
regenerateCSRFToken();

// ---------------------------
// Generate and persist API token for this session/user (use helper)
// ---------------------------
$tokenToReturn = null;
try {
    // createApiToken returns token string or false
    $token = DatabaseUtils::createApiToken($userRow['id'], intval($_ENV['API_TOKEN_TTL_DAYS'] ?? 30));
    if ($token !== false) {
        $tokenToReturn = $token;
    } else {
        $tokenToReturn = $userRow['token'] ?? null;
    }
} catch (Exception $e) {
    error_log('createApiToken threw: ' . $e->getMessage());
    $tokenToReturn = $userRow['token'] ?? null;
}

sendSuccessResponse('Login successful!', ['token' => $tokenToReturn]);
