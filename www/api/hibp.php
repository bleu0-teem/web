
<?php
// -------------------------------------------------------------------
// hibp.php
//
// Provides a function to check if a password has been found in a breach
// using the "Have I Been Pwned" API (k-anonymity model).
// -------------------------------------------------------------------

/**
 * Checks if a password has been found in a breach using the HIBP API.
 *
 * @param string $password The plain-text password to check.
 * @return bool True if the password is found in a breach, false otherwise.
 */
function isPwnedPassword(string $password): bool
{
    // Compute uppercase SHA-1 of the plain password
    $sha1 = strtoupper(sha1($password));
    $prefix = substr($sha1, 0, 5);
    $suffix = substr($sha1, 5);

    // Prepare an HTTP context with a User-Agent header
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => "User-Agent: HIBP-PHP/1.0\r\n",
            'timeout' => 10,
        ]
    ]);

    $url = "https://api.pwnedpasswords.com/range/$prefix";
    $body = @file_get_contents($url, false, $ctx);

    // If request failed or returned empty, skip the breach check
    if ($body === false) {
        error_log("HIBP check failed or timed out for prefix $prefix.");
        return false;
    }

    // Inspect HTTP response code from $http_response_header
    $httpCode = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        if (preg_match('#^HTTP/\d+\.\d+\s+(\d{3})#', $http_response_header[0], $m)) {
            $httpCode = intval($m[1]);
        }
    }
    if ($httpCode !== 200) {
        error_log("HIBP returned HTTP $httpCode for prefix $prefix. Skipping breach check.");
        return false;
    }

    // Parse each line "HASHTAIL:COUNT"
    $lines = explode("\r\n", $body);
    foreach ($lines as $line) {
        if (strpos($line, ':') === false) {
            continue;
        }
        [$hashTail, ] = explode(':', $line, 2);
        if ($hashTail === $suffix) {
            return true;
        }
    }
    return false;
}