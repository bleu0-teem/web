<?php
// -------------------------------------------------------------------
// error_handler.php - Centralized Error Handling
// -------------------------------------------------------------------

/**
 * Send JSON error response
 * @param int $statusCode HTTP status code
 * @param string $message Error message
 * @param array $additionalData Additional data to include
 */
function sendErrorResponse($statusCode, $message, $additionalData = []) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    
    $response = [
        'error' => $message,
        'timestamp' => date('c'),
        'status' => $statusCode
    ];
    
    if (!empty($additionalData)) {
        $response = array_merge($response, $additionalData);
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Send JSON success response
 * @param string $message Success message
 * @param array $data Additional data to include
 * @param int $statusCode HTTP status code (default: 200)
 */
function sendSuccessResponse($message, $data = [], $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    
    $response = [
        'message' => $message,
        'timestamp' => date('c'),
        'status' => $statusCode
    ];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Log error with context
 * @param string $message Error message
 * @param array $context Additional context
 * @param string $level Log level (error, warning, info)
 */
function logError($message, $context = [], $level = 'error') {
    $logData = [
        'timestamp' => date('c'),
        'level' => $level,
        'message' => $message,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'context' => $context
    ];
    
    error_log(json_encode($logData));
}

/**
 * Validate required POST fields
 * @param array $requiredFields Array of required field names
 * @return array Array of validated fields or false if validation fails
 */
function validateRequiredFields($requiredFields) {
    $validated = [];
    $missing = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $missing[] = $field;
        } else {
            $validated[$field] = trim($_POST[$field]);
        }
    }
    
    if (!empty($missing)) {
        sendErrorResponse(400, 'Missing required fields: ' . implode(', ', $missing));
    }
    
    return $validated;
}

/**
 * Check if request method is allowed
 * @param array $allowedMethods Array of allowed HTTP methods
 */
function validateRequestMethod($allowedMethods) {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if (!in_array($method, $allowedMethods)) {
        sendErrorResponse(405, 'Method not allowed. Allowed methods: ' . implode(', ', $allowedMethods));
    }
} 