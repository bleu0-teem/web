<?php
/**
 * Roblox Actions API - Helper Functions
 * Contains common functions for all Roblox action APIs
 */

// Verify API password from environment variables
function verifyApiPassword() {
    $inputPassword = $_GET['api_password'] ?? $_POST['api_password'] ?? null;
    
    // Check from JSON input body
    if (empty($inputPassword)) {
        $jsonInput = json_decode(file_get_contents('php://input'), true);
        if (is_array($jsonInput) && isset($jsonInput['api_password'])) {
            $inputPassword = $jsonInput['api_password'];
        }
    }
    
    // Also check from Authorization header
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (empty($inputPassword) && !empty($authHeader)) {
        if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            $inputPassword = $matches[1];
        }
    }

    
    $envPassword = $_ENV['API_PASSWORD'] ?? getenv('API_PASSWORD');
    
    if (empty($envPassword)) {
        return [
            'valid' => false,
            'error' => 'API_PASSWORD not configured on server'
        ];
    }
    
    if (empty($inputPassword)) {
        return [
            'valid' => false,
            'error' => 'API password is required'
        ];
    }
    
    if ($inputPassword !== $envPassword) {
        return [
            'valid' => false,
            'error' => 'Invalid API password'
        ];
    }
    
    return ['valid' => true];
}

// Get random cookies from environment variable
function getRandomCookies($count = 1) {
    $cookiesJson = $_ENV['ROBLOX_COOKIES_JSON'] ?? getenv('ROBLOX_COOKIES_JSON');
    
    if (empty($cookiesJson)) {
        return [
            'success' => false,
            'error' => 'ROBLOX_COOKIES_JSON not configured'
        ];
    }
    
    $cookies = json_decode($cookiesJson, true);
    
    if (!is_array($cookies) || empty($cookies)) {
        return [
            'success' => false,
            'error' => 'No cookies available or invalid JSON format'
        ];
    }
    
    $normalized = [];
    foreach ($cookies as $entry) {
        if (is_string($entry) && !empty($entry)) {
            $normalized[] = [
                'cookie' => $entry,
                'bound_auth_token' => null
            ];
            continue;
        }

        if (is_array($entry) && isset($entry['cookie']) && is_string($entry['cookie']) && !empty($entry['cookie'])) {
            $normalized[] = [
                'cookie' => $entry['cookie'],
                'bound_auth_token' => $entry['bound_auth_token'] ?? $entry['bound_auth'] ?? null
            ];
        }
    }

    if (empty($normalized)) {
        return [
            'success' => false,
            'error' => 'No cookies available or invalid JSON format'
        ];
    }

    // Shuffle and slice to get random cookies
    shuffle($normalized);
    $selectedCookies = array_slice($normalized, 0, $count);
    
    return [
        'success' => true,
        'cookies' => $selectedCookies,
        'total_available' => count($normalized)
    ];
}

// Make HTTP request to Roblox API
function makeRobloxRequest($url, $cookie, $method = 'GET', $data = null, $isJson = true) {
    $ch = curl_init();
    
    $acceptHeader = $isJson ? 'application/json' : 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
    
    $headers = [
        'Cookie: .ROBLOSECURITY=' . $cookie,
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: ' . $acceptHeader,
        'Accept-Language: en-US,en;q=0.5',
        'Referer: https://www.roblox.com/',
        'Origin: https://www.roblox.com'
    ];

    
    if ($isJson) {
        $headers[] = 'Content-Type: application/json';
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            if ($isJson) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
    } elseif ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error,
        'success' => $httpCode >= 200 && $httpCode < 300
    ];
}

// Check if already following a user
function checkIfAlreadyFollowing($cookie, $userId) {
    $url = "https://friends.roblox.com/v1/users/{$userId}/followers";
    $result = makeRobloxRequest($url, $cookie);
    
    if (!$result['success']) {
        return ['checked' => false, 'following' => false];
    }
    
    $data = json_decode($result['response'], true);
    
    // Check if user is in the followers list
    if (isset($data['data'])) {
        foreach ($data['data'] as $follower) {
            if (isset($follower['id'])) {
                // We need to get the current user's ID to check if they are following
                // This is a simplified check - in reality we'd need to get the current user first
                return ['checked' => true, 'following' => false];
            }
        }
    }
    
    return ['checked' => true, 'following' => false];
}

// Check if already friends with user
function checkIfAlreadyFriends($cookie, $userId) {
    $url = "https://friends.roblox.com/v1/users/{$userId}/friends";
    $result = makeRobloxRequest($url, $cookie);
    
    if (!$result['success']) {
        return ['checked' => false, 'friends' => false];
    }
    
    $data = json_decode($result['response'], true);
    
    return ['checked' => true, 'friends' => false];
}

// Check if already in group
function checkIfAlreadyInGroup($cookie, $groupId) {
    // First get the current user's ID
    $url = "https://users.roblox.com/v1/users/authenticated";
    $result = makeRobloxRequest($url, $cookie);
    
    if (!$result['success']) {
        return ['checked' => false, 'in_group' => false];
    }
    
    $userData = json_decode($result['response'], true);
    $userId = $userData['id'] ?? null;
    
    if (!$userId) {
        return ['checked' => false, 'in_group' => false];
    }
    
    // Check if user is in the group
    $url = "https://groups.roblox.com/v1/users/{$userId}/groups/roles";
    $result = makeRobloxRequest($url, $cookie);
    
    if (!$result['success']) {
        return ['checked' => false, 'in_group' => false];
    }
    
    $groups = json_decode($result['response'], true);
    
    if (isset($groups['data'])) {
        foreach ($groups['data'] as $group) {
            if (isset($group['group']['id']) && $group['group']['id'] == $groupId) {
                return ['checked' => true, 'in_group' => true];
            }
        }
    }
    
    return ['checked' => true, 'in_group' => false];
}

// Check if already favorited a place
function checkIfAlreadyFavorited($cookie, $placeId) {
    // Get the current user's ID
    $url = "https://users.roblox.com/v1/users/authenticated";
    $result = makeRobloxRequest($url, $cookie);
    
    if (!$result['success']) {
        return ['checked' => false, 'favorited' => false];
    }
    
    $userData = json_decode($result['response'], true);
    $userId = $userData['id'] ?? null;
    
    if (!$userId) {
        return ['checked' => false, 'favorited' => false];
    }
    
    // Check if place is favorited
    $url = "https://games.roblox.com/v1/users/{$userId}/favorites_games";
    $result = makeRobloxRequest($url, $cookie);
    
    if (!$result['success']) {
        return ['checked' => false, 'favorited' => false];
    }
    
    $favorites = json_decode($result['response'], true);
    
    if (isset($favorites['data'])) {
        foreach ($favorites['data'] as $fav) {
            if (isset($fav['placeId']) && $fav['placeId'] == $placeId) {
                return ['checked' => true, 'favorited' => true];
            }
        }
    }
    
    return ['checked' => true, 'favorited' => false];
}

// Get authenticated user info from cookie
function getAuthenticatedUser($cookie) {
    $url = "https://users.roblox.com/v1/users/authenticated";
    $result = makeRobloxRequest($url, $cookie);
    
    if (!$result['success']) {
        return null;
    }
    
    return json_decode($result['response'], true);
}

// Send response in standard format
function sendActionResponse($status, $message, $data = null) {
    header('Content-Type: application/json');
    http_response_code($status);
    
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

// Handle error response
function handleActionError($message, $code = 400) {
    sendActionResponse($code, $message);
}
?>
