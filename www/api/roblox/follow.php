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
$input = getCachedJsonRequestBody();
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

foreach ($cookies as $index => $cookieEntry) {
    $cookie = is_array($cookieEntry) ? ($cookieEntry['cookie'] ?? null) : $cookieEntry;
    $boundAuthToken = is_array($cookieEntry) ? ($cookieEntry['bound_auth_token'] ?? null) : null;

    $result = [
        'cookie_index' => $index,
        'success' => false,
        'error' => null
    ];

    if (empty($cookie)) {
        $result['error'] = 'Invalid cookie entry';
        $errorCount++;
        $results[] = $result;
        continue;
    }
    
    // Check if already following
    $checkResult = checkIfAlreadyFollowing($cookie, $userId);
    
    if ($checkResult['checked'] && $checkResult['following']) {
        $result['message'] = 'Already following this user';
        $alreadyCompletedCount++;
        $results[] = $result;
        continue;
    }
    
    $followUrl = "https://friends.roblox.com/v1/users/{$userId}/follow";
    $csrfToken = null;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $followUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    $headers = [
        'Cookie: .ROBLOSECURITY=' . $cookie,
        'Accept: application/json, text/plain, */*',
        'Accept-Language: en-US,en;q=0.9',
        'Accept-Encoding: gzip, deflate, br',
        'Referer: https://www.roblox.com/',
        'Origin: https://www.roblox.com',
        'Cache-Control: no-cache',
        'Pragma: no-cache',
        'Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120"',
        'Sec-Ch-Ua-Mobile: ?0',
        'Sec-Ch-Ua-Platform: "Windows"',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: same-site'
    ];

    if (!empty($boundAuthToken)) {
        $headers[] = 'x-bound-auth-token: ' . $boundAuthToken;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $firstResponse = curl_exec($ch);
    $firstHeaderSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $firstHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $firstHeadersStr = $firstResponse !== false ? substr($firstResponse, 0, $firstHeaderSize) : '';
    if (preg_match('/x-csrf-token:\s*([^\r\n]+)/i', $firstHeadersStr, $matches)) {
        $csrfToken = trim($matches[1]);
    }

    $result['debug_bound_auth'] = !empty($boundAuthToken);
    $result['debug_csrf'] = $csrfToken ? substr($csrfToken, 0, 10) . '...' : 'NOT FOUND';

    // If the first call was a 403 and we got a token, retry once with token.
    $response = $firstResponse;
    $httpCode = $firstHttpCode;

    if ($httpCode === 403 && $csrfToken) {
        $retryHeaders = [
            'Cookie: .ROBLOSECURITY=' . $cookie,
            'Accept: application/json, text/plain, */*',
            'Accept-Language: en-US,en;q=0.9',
            'Accept-Encoding: gzip, deflate, br',
            'Referer: https://www.roblox.com/',
            'Origin: https://www.roblox.com',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120"',
            'Sec-Ch-Ua-Mobile: ?0',
            'Sec-Ch-Ua-Platform: "Windows"',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-site',
            'x-csrf-token: ' . $csrfToken
        ];

        if (!empty($boundAuthToken)) {
            $retryHeaders[] = 'x-bound-auth-token: ' . $boundAuthToken;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $retryHeaders);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    }

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    
    // Debug: Log response
    $result['debug_http_code'] = $httpCode;
    if ($response !== false) {
        $body = substr($response, $headerSize);
        $result['debug_response'] = substr($body, 0, 200);
    } else {
        $result['debug_response'] = '';
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $result['success'] = true;
        $result['message'] = 'Successfully followed user';
        $successCount++;
    } else {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['errorMessage'] ?? $errorData['message'] ?? 'Failed to follow user (HTTP ' . $httpCode . ')';
        
        // Check for challenge requirement specifically
        if (strpos($errorMessage, 'Challenge is required') !== false) {
            $errorMessage = 'Challenge required - Roblox anti-bot protection detected. Try again later or use different cookies.';
        }
        
        $result['error'] = $errorMessage;
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
