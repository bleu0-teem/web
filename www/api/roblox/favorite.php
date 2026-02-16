<?php
/**
 * Roblox Favorite Place API
 * Favorites a Roblox place using random Roblox cookies
 * 
 * Parameters:
 * - place_id: The ID of the place to favorite
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
$placeId = $input['place_id'] ?? null;
$count = isset($input['count']) ? intval($input['count']) : 1;

if (!$placeId) {
    handleActionError('place_id is required');
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

// Perform favorite action for each cookie
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
    
    // Check if already favorited
    $checkResult = checkIfAlreadyFavorited($cookie, $placeId);
    
    if ($checkResult['checked'] && $checkResult['favorited']) {
        $result['message'] = 'Already favorited this place';
        $alreadyCompletedCount++;
        $results[] = $result;
        continue;
    }
    
    // Get place info to verify the place exists
    $placeUrl = "https://games.roblox.com/v1/games/{$placeId}";
    $placeCheck = makeRobloxRequest($placeUrl, $cookie);
    
    if (!$placeCheck['success'] || !json_decode($placeCheck['response'], true)) {
        $result['error'] = 'Place not found or invalid place ID';
        $errorCount++;
        $results[] = $result;
        continue;
    }
    
    // Create a temporary cookie file for this session
    $cookieFile = tempnam(sys_get_temp_dir(), 'roblox_cookie_');
    file_put_contents($cookieFile, ".ROBLOSECURITY\tTRUE\t/\tFALSE\t0\t.ROBLOSECURITY\t" . $cookie . "\n");
    
    // Get CSRF token by making a POST request and extracting from response headers
    $csrfUrl = "https://friends.roblox.com/v1/users/1/request-friendship";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $csrfUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    
    $csrfResponse = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    
    // Extract CSRF token from response headers
    $csrfToken = null;
    $headersStr = substr($csrfResponse, 0, $headerSize);
    if (preg_match('/x-csrf-token:\s*([^\r\n]+)/i', $headersStr, $matches)) {
        $csrfToken = trim($matches[1]);
    }

    
    // Make favorite request using same session
    $favoriteUrl = "https://games.roblox.com/v1/games/{$placeId}/favorite";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $favoriteUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json, text/plain, */*',
        'Accept-Language: ru,en-US;q=0.9,en;q=0.8',
        'Referer: https://www.roblox.com/',
        'Origin: https://www.roblox.com',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: same-site',
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
        $result['message'] = 'Successfully favorited place';
        $successCount++;
    } else {
        $errorData = json_decode($response, true);
        $result['error'] = $errorData['errorMessage'] ?? $errorData['message'] ?? 'Failed to favorite place (HTTP ' . $httpCode . ')';
        $errorCount++;
    }
    
    $results[] = $result;
}

// Send response
sendActionResponse(200, 'Favorite action completed', [
    'total_accounts_used' => count($cookies),
    'success_count' => $successCount,
    'error_count' => $errorCount,
    'already_completed' => $alreadyCompletedCount,
    'total_available_cookies' => $cookiesResult['total_available'],
    'results' => $results
]);
?>
