<?php
// -------------------------------------------------------------------
// security_config.php - Security Configuration and Headers
// -------------------------------------------------------------------

/**
 * Set security headers for all API endpoints
 */
function setSecurityHeaders() {
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Prevent referrer leakage
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com https://code.iconify.design; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'");
    
    // Prevent caching of sensitive pages
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

/**
 * Detect if running in local development (localhost or 127.0.0.1)
 * @return bool
 */
function isLocalDevelopment() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
    if (stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false) {
        return true;
    }
    if ($serverAddr === '127.0.0.1' || $serverAddr === '::1') {
        return true;
    }
    return false;
}

/**
 * Validate and sanitize input
 * @param string $input The input to sanitize
 * @param string $type The type of validation (email, username, etc.)
 * @return string|false Sanitized input or false if invalid
 */
function sanitizeInput($input, $type = 'general') {
    $input = trim($input);
    
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL) ? $input : false;
            
        case 'username':
            // Username: 3-20 characters, alphanumeric and underscore only
            if (strlen($input) >= 3 && strlen($input) <= 20 && preg_match('/^[a-zA-Z0-9_]+$/', $input)) {
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            }
            return false;
            
        case 'password':
            // Password: minimum 8 characters, at least one lowercase, one uppercase, one number
            if (strlen($input) >= 8 && preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $input)) {
                return $input; // Don't sanitize passwords
            }
            return false;
            
        default:
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Check if request is from allowed origin
 * @return bool True if allowed, false otherwise
 */
function validateOrigin() {
    $allowedOrigins = [
        'http://localhost',
        'https://localhost',
        'http://localhost:5500',
        'https://localhost:5500',
        'http://127.0.0.1',
        'https://127.0.0.1',
        'http://127.0.0.1:5500',
        'https://127.0.0.1:5500'
    ];
    
    // Add your production domain here
    if (isset($_ENV['ALLOWED_ORIGINS'])) {
        $allowedOrigins = array_merge($allowedOrigins, explode(',', $_ENV['ALLOWED_ORIGINS']));
    }
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;

    // Fallback: if Origin missing, infer from Referer
    if (!$origin) {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        if ($referer) {
            $scheme = parse_url($referer, PHP_URL_SCHEME) ?: 'http';
            $host   = parse_url($referer, PHP_URL_HOST) ?: '';
            $port   = parse_url($referer, PHP_URL_PORT);
            if ($host) {
                $origin = $scheme . '://' . $host . ($port ? (":" . $port) : '');
            }
        }
    }
    
    if ($origin && in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
        return true;
    }
    
    return false;
}

/**
 * Rate limiting check
 * @param string $key Unique identifier for rate limiting
 * @param int $maxAttempts Maximum attempts allowed
 * @param int $lockoutTime Lockout time in seconds
 * @return bool True if allowed, false if rate limited
 */
function checkRateLimit($key, $maxAttempts = 5, $lockoutTime = 900) {
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
    }
    
    $attempts = $_SESSION[$key];
    
    // Reset if lockout time has passed
    if ((time() - $attempts['time']) >= $lockoutTime) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
        return true;
    }
    
    // Check if rate limited
    if ($attempts['count'] >= $maxAttempts) {
        return false;
    }
    
    return true;
}

/**
 * Increment rate limit counter
 * @param string $key Unique identifier for rate limiting
 */
function incrementRateLimit($key) {
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'time' => time()];
    } else {
        $_SESSION[$key]['count']++;
        $_SESSION[$key]['time'] = time();
    }
}

/**
 * Clear rate limit for successful operations
 * @param string $key Unique identifier for rate limiting
 */
function clearRateLimit($key) {
    unset($_SESSION[$key]);
} 