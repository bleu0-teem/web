<?php
/**
 * Roblox Follow User API
 * Follows a user using random Roblox cookies
 * 
 * Parameters:
 * - user_id: The ID of the user to follow
 * - count: Number of accounts to use (optional, default 1)
 * - api_password: API password for authentication
 */

require_once __DIR__ . '/roblox_helper.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleActionError('Method not allowed', 405);
}

// Verify API password
$passwordCheck = verifyApiPassword();
if (!$passwordCheck['valid']) {
    handleActionError($passwordCheck['error'], 401);
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// Validate required fields
$userId = $input['user_id'] ?? null;
$count = isset($input['count']) ? intval($input['count']) : 1;

if (!$userId) {
    handleActionError('user_id is required');
}

if ($count < 1) {
    $count = 1;
}

// Get random cookies
$cookiesResult = getRandomCookies($count);
if (!$cookiesResult['success']) {
    handleActionError($cookiesResult['error']);
}

$cookies = $cookiesResult['cookies'];

// Perform follow action for each cookie
$results = [];
$successCount = 0;
$errorCount = 0;
$alreadyCompletedCount = 0;

foreach ($cookies as $index => $cookie) {
    $result = [
        'cookie_index' => $index,
        'success' => false,
        'error' => null
    ];
    
    // Check if already following
    $checkResult = checkIfAlreadyFollowing($cookie, $userId);
    
    if ($checkResult['checked'] && $checkResult['following']) {
        $result['message'] = 'Already following this user';
        $alreadyCompletedCount++;
        $results[] = $result;
        continue;
    }
    
    // Get user info to verify the user exists
    $userUrl = "https://users.roblox.com/v1/users/{$userId}";
    $userCheck = makeRobloxRequest($userUrl, $cookie);
    
    if (!$userCheck['success'] || !json_decode($userCheck['response'], true)) {
        $result['error'] = 'User not found or invalid user ID';
        $errorCount++;
        $results[] = $result;
        continue;
    }
    
    // Get CSRF token
    $csrfUrl = "https://www.roblox.com/api/csrf";
    $csrfResponse = makeRobloxRequest($csrfUrl, $cookie, 'POST', ['cookie' => $cookie], false);
    
    $csrfToken = null;
    if ($csrfResponse['success']) {
        $csrfData = json_decode($csrfResponse['response'], true);
        $csrfToken = $csrfData['token'] ?? null;
    }
    
    if (!$csrfToken) {
        // Try alternative CSRF method
        $csrfToken = $_COOKIE['csrf'] ?? null;
    }
    
    // Make follow request
    $followUrl = "https://friends.roblox.com/v1/users/{$userId}/follow";
    
    $headers = [
        'Cookie: .ROBLOSECURITY=' . $cookie,
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Content-Type: application/json',
        'Referer: https://www.roblox.com/user'
    ];
    
    if ($csrfToken) {
        $headers[] = 'X-CSRF-TOKEN: ' . $csrfToken;
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $followUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $result['success'] = true;
        $result['message'] = 'Successfully followed user';
        $successCount++;
    } else {
        $errorData = json_decode($response, true);
        $result['error'] = $errorData['errorMessage'] ?? $errorData['message'] ?? 'Failed to follow user (HTTP ' . $httpCode . ')';
        $errorCount++;
    }
    
    $results[] = $result;
}

// Send response
sendActionResponse(200, 'Follow action completed', [
    'total_accounts_used' => count($cookies),
    'success_count' => $successCount,
    'error_count' => $errorCount,
    'already_completed' => $alreadyCompletedCount,
    'total_available_cookies' => $cookiesResult['total_available'],
    'results' => $results
]);
?>
