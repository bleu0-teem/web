<?php

// -------------------------------------------------------------------
// csrf_utils.php - CSRF Token Management Utilities
// -------------------------------------------------------------------
// used for creating a token for SECURITY :3

/**
 * Generate a new CSRF token
 * @return string The generated token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // Handle error (e.g., log it)
            return null; // or handle as appropriate
        }
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token
 * @param string $token The token to validate
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenerate CSRF token (for security after successful operations)
 * @return string The new token
 */
function regenerateCSRFToken() {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        // Handle error (e.g., log it)
        return null; // or handle as appropriate
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF token for forms
 * @return string The current token
 */
function getCSRFToken() {
    return generateCSRFToken();
}
