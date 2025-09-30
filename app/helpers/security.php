<?php
// app/helpers/security.php
// CSRF and session helper functions

// Ensure session is started. Caller may configure cookie params before including this file.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Ensure a CSRF token exists in session and return it.
 * @return string
 */
function ensure_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden input field for CSRF token
 */
function csrf_field(): string {
    $token = ensure_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify provided CSRF token against session value
 */
function verify_csrf(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

?>
