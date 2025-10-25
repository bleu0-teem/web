<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'blue0teem');
define('DB_USER', 'root');
define('DB_PASS', '');

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600); // 1 hour

// API settings
define('API_VERSION', '1.0');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// Password requirements
define('MIN_PASSWORD_LENGTH', 8);
define('REQUIRE_UPPERCASE', true);
define('REQUIRE_LOWERCASE', true);
define('REQUIRE_NUMBERS', true);
define('REQUIRE_SYMBOLS', false);

// Invite system
define('REQUIRE_INVITE_KEY', true);
define('INVITE_KEY_LENGTH', 16);

// Response helper function
function sendResponse($status, $message, $data = null) {
    header('Content-Type: application/json');
    http_response_code($status === 200 ? 200 : 400);
    
    $response = [
        'status' => $status,
        'message' => $message,
        'timestamp' => time()
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

// Error handler
function handleError($message, $code = 400) {
    sendResponse($code, $message);
}

// Database connection
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        handleError('Database connection failed: ' . $e->getMessage(), 500);
    }
}

// CSRF token functions
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION[CSRF_TOKEN_NAME] = $token;
    return $token;
}

function validateCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION[CSRF_TOKEN_NAME]) && 
           hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Password validation
function validatePassword($password) {
    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        return 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters long';
    }
    
    if (REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        return 'Password must contain at least one uppercase letter';
    }
    
    if (REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
        return 'Password must contain at least one lowercase letter';
    }
    
    if (REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one number';
    }
    
    if (REQUIRE_SYMBOLS && !preg_match('/[^A-Za-z0-9]/', $password)) {
        return 'Password must contain at least one special character';
    }
    
    return true;
}

// Rate limiting
function checkRateLimit($identifier, $maxAttempts = MAX_LOGIN_ATTEMPTS, $lockoutTime = LOCKOUT_TIME) {
    $file = sys_get_temp_dir() . '/rate_limit_' . md5($identifier);
    
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        $now = time();
        
        if ($data['count'] >= $maxAttempts && ($now - $data['last_attempt']) < $lockoutTime) {
            return false; // Rate limited
        }
        
        if (($now - $data['last_attempt']) >= $lockoutTime) {
            // Reset counter after lockout period
            $data = ['count' => 0, 'last_attempt' => $now];
        }
    } else {
        $data = ['count' => 0, 'last_attempt' => time()];
    }
    
    $data['count']++;
    $data['last_attempt'] = time();
    
    file_put_contents($file, json_encode($data));
    return true;
}

// Input sanitization
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Email validation
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Username validation
function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}
?>
