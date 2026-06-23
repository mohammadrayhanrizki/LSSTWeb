<?php
// =============================================================
// init.php — Centralized session & security bootstrap
// Include this at the TOP of every page instead of manual
// session_start() + require 'koneksi.php'
// =============================================================

// --- Secure Session Configuration (BEFORE session_start) ---
ini_set('session.cookie_httponly', 1);       // Block JavaScript access to PHPSESSID
ini_set('session.cookie_samesite', 'Strict'); // Block cross-site cookie sending (CSRF layer)
ini_set('session.use_strict_mode', 1);       // Reject uninitialized session IDs
// ini_set('session.cookie_secure', 1);      // Uncomment when you deploy with HTTPS

// --- Suppress error display in production ---
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/koneksi.php';

// =============================================================
// Activity Tracker
// =============================================================
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
}

// =============================================================
// CSRF Token Helpers
// =============================================================

/**
 * Generate or retrieve a CSRF token for the current session.
 * Use this inside <form> tags as a hidden input.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a ready-to-use hidden input field with the CSRF token.
 * Usage in HTML: <?= csrf_field(); ?>
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verify that the submitted CSRF token matches the session token.
 * Call this at the top of every POST handler.
 */
function verify_csrf(string $submitted_token): bool {
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $submitted_token);
}
