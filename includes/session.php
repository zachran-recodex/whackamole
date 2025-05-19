<?php
/**
 * Session Management
 *
 * Functions for handling user sessions.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirect if not logged in
 * 
 * Use this function to protect pages that require authentication
 */
function requireLogin() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        $_SESSION['error'] = 'You must be logged in to access that page';
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirect if already logged in
 * 
 * Use this function for login/register pages to redirect logged in users
 */
function redirectIfLoggedIn() {
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Set a flash message to be displayed once
 * 
 * @param string $type Message type (success, error, info, warning)
 * @param string $message The message to display
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * 
 * @return array|null Flash message or null if none exists
 */
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if a flash message exists
 * 
 * @return bool True if a flash message exists, false otherwise
 */
function hasFlashMessage() {
    return isset($_SESSION['flash']);
}

/**
 * Display flash message if one exists
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $type = $flash['type'];
        $message = $flash['message'];
        echo "<div class='alert alert-$type'>$message</div>";
    }
}

/**
 * Create CSRF token and store in session
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if token is valid, false otherwise
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenerate session ID to prevent session fixation
 */
function regenerateSession() {
    // Store old session data
    $oldSessionData = $_SESSION;
    
    // Clear session data
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    // Start a new session and regenerate ID
    session_start();
    session_regenerate_id(true);
    
    // Restore session data
    $_SESSION = $oldSessionData;
}