<?php
/**
 * Roblox Devforum Like API
 * Likes a Devforum post using random Roblox cookies
 * 
 * Parameters:
 * - post_id: The ID of the Devforum post to like
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
$postId = $input['post_id'] ?? null;
$count = isset($input['count']) ? intval($input['count']) : 1;

if (!$postId) {
    handleActionError('post_id is required');
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

// Perform like action for each cookie
$results = [];
$successCount = 0;
$errorCount = 0;
$alreadyCompletedCount = 0;

foreach ($cookies as $index => $cookieEntry) {
    $cookie = is_array($cookieEntry) ? ($cookieEntry['cookie'] ?? null) : $cookieEntry;

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
    
    // Get authenticated user first to check if already liked
    $userInfo = getAuthenticatedUser($cookie);
    $userId = $userInfo['id'] ?? null;
    
    // Check if already liked (by checking the post's likes)
    $checkUrl = "https://devforum.roblox.com/posts/{$postId}/likes.json";
    $checkResponse = makeRobloxRequest($checkUrl, $cookie);
    
    $alreadyLiked = false;
    if ($checkResponse['success'] && $userId) {
        $likeData = json_decode($checkResponse['response'], true);
        if (isset($likeData['post']['like_ids']) && in_array($userId, $likeData['post']['like_ids'])) {
            $alreadyLiked = true;
        }
    }
    
    if ($alreadyLiked) {
        $result['message'] = 'Already liked this post';
        $alreadyCompletedCount++;
        $results[] = $result;
        continue;
    }
    
    // Get CSRF token from Devforum
    $csrfUrl = "https://devforum.roblox.com/session/csrf";
    $csrfResponse = makeRobloxRequest($csrfUrl, $cookie, 'GET', null, false);
    
    $csrfToken = null;
    if ($csrfResponse['success']) {
        // Devforum might return CSRF in different ways
        if (preg_match('/data-token="([^"]+)"/', $csrfResponse['response'], $matches)) {
            $csrfToken = $matches[1];
        }
    }
    
    if (!$csrfToken) {
        $csrfToken = $_COOKIE['csrf'] ?? null;
    }
    
    // Make like request
    $likeUrl = "https://devforum.roblox.com/posts/{$postId}/likes.json";
    
    $headers = [
        'Cookie: .ROBLOSECURITY=' . $cookie,
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
        'X-Requested-With: XMLHttpRequest',
        'Referer: https://devforum.roblox.com/t/' . $postId
    ];
    
    if ($csrfToken) {
        $headers[] = 'X-CSRF-TOKEN: ' . $csrfToken;
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $likeUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $result['success'] = true;
        $result['message'] = 'Successfully liked Devforum post';
        $successCount++;
    } else {
        $errorData = json_decode($response, true);
        $result['error'] = $errorData['errors'][0]['message'] ?? $errorData['error'] ?? 'Failed to like post (HTTP ' . $httpCode . ')';
        $errorCount++;
    }
    
    $results[] = $result;
}

// Send response
sendActionResponse(200, 'Devforum like action completed', [
    'total_accounts_used' => count($cookies),
    'success_count' => $successCount,
    'error_count' => $errorCount,
    'already_completed' => $alreadyCompletedCount,
    'total_available_cookies' => $cookiesResult['total_available'],
    'results' => $results
]);
?>
