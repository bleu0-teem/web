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
    $userUrl = "https://friends.roblox.com/v1/users/{$userId}/follow";
    $userCheck = makeRobloxRequest($userUrl, $cookie);

    if (!$userCheck['success']) {
        $result['error'] = 'Request failed';
        $result['response'] = $userCheck['response']; // show raw response
        $errorCount++;
        $results[] = $result;
        continue;
    }

    $result['response'] = $userCheck['response']; // always return API response
    $results[] = $result;

    
    // Get CSRF token from Roblox home page HTML
    $csrfUrl = "https://www.roblox.com/home";
    
    $csrfHeaders = [
        'Cookie: .ROBLOSECURITY=' . $cookie,
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Referer: https://www.roblox.com/',
        'Origin: https://www.roblox.com'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $csrfUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $csrfHeaders);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $csrfResponse = curl_exec($ch);
    curl_close($ch);
    
    // Extract CSRF token from HTML body
    $csrfToken = null;
    if (preg_match("/Roblox\.XsrfToken\.setToken\('([^']+)'\);/", $csrfResponse, $matches)) {
        $csrfToken = $matches[1];
    }

    
    // Make follow request
    $followUrl = "https://friends.roblox.com/v1/users/{$userId}/follow";
    
    $headers = [
        'Cookie: .ROBLOSECURITY=' . $cookie,
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0',
        'Accept: application/json, text/plain, */*',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: gzip, deflate, br',
        'Referer: https://www.roblox.com/',
        'Origin: https://www.roblox.com',
        'Connection: keep-alive'
    ];
    
    if ($csrfToken) {
        $headers[] = 'x-csrf-token: ' . $csrfToken;
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $followUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    
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
