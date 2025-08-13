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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        // Fallback to double-submit cookie pattern
        if (isset($_COOKIE['XSRF-TOKEN'])) {
            return hash_equals($_COOKIE['XSRF-TOKEN'], (string)$token);
        }
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Regenerate CSRF token (for security after successful operations)
 * @return string The new token
 */
function regenerateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return generateCSRFToken();
}
