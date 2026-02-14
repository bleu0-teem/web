<?php
/**
 * Roblox Join Group API
 * Joins a group using random Roblox cookies
 * 
 * Parameters:
 * - group_id: The ID of the group to join
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
$groupId = $input['group_id'] ?? null;
$count = isset($input['count']) ? intval($input['count']) : 1;

if (!$groupId) {
    handleActionError('group_id is required');
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

// Perform join group action for each cookie
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
    
    // Check if already in group
    $checkResult = checkIfAlreadyInGroup($cookie, $groupId);
    
    if ($checkResult['checked'] && $checkResult['in_group']) {
        $result['message'] = 'Already in this group';
        $alreadyCompletedCount++;
        $results[] = $result;
        continue;
    }
    
    // Get group info to verify the group exists
    $groupUrl = "https://groups.roblox.com/v1/groups/{$groupId}";
    $groupCheck = makeRobloxRequest($groupUrl, $cookie);
    
    if (!$groupCheck['success'] || !json_decode($groupCheck['response'], true)) {
        $result['error'] = 'Group not found or invalid group ID';
        $errorCount++;
        $results[] = $result;
        continue;
    }
    
    // Create a temporary cookie file for this session
    $cookieFile = tempnam(sys_get_temp_dir(), 'roblox_cookie_');
    file_put_contents($cookieFile, ".ROBLOSECURITY\tTRUE\t/\tFALSE\t0\t.ROBLOSECURITY\t" . $cookie . "\n");
    
    // Get CSRF token from Roblox home page HTML
    $csrfUrl = "https://www.roblox.com/home";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $csrfUrl);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    
    $csrfResponse = curl_exec($ch);
    curl_close($ch);
    
    // Extract CSRF token from HTML body
    $csrfToken = null;
    if (preg_match("/Roblox\.XsrfToken\.setToken\('([^']+)'\);/", $csrfResponse, $matches)) {
        $csrfToken = $matches[1];
    }
    
    // Make join group request using same session
    $joinUrl = "https://groups.roblox.com/v1/groups/{$groupId}/join";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $joinUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json, text/plain, */*',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: gzip, deflate, br',
        'Referer: https://www.roblox.com/',
        'Origin: https://www.roblox.com',
        'Connection: keep-alive',
        'x-csrf-token: ' . ($csrfToken ?: '')
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Clean up cookie file
    @unlink($cookieFile);


    
    if ($httpCode >= 200 && $httpCode < 300) {
        $result['success'] = true;
        $result['message'] = 'Successfully joined group';
        $successCount++;
    } else {
        $errorData = json_decode($response, true);
        $result['error'] = $errorData['errorMessage'] ?? $errorData['message'] ?? 'Failed to join group (HTTP ' . $httpCode . ')';
        $errorCount++;
    }
    
    $results[] = $result;
}

// Send response
sendActionResponse(200, 'Join group action completed', [
    'total_accounts_used' => count($cookies),
    'success_count' => $successCount,
    'error_count' => $errorCount,
    'already_completed' => $alreadyCompletedCount,
    'total_available_cookies' => $cookiesResult['total_available'],
    'results' => $results
]);
?>
