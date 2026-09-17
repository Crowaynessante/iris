<?php
// ============================================
// Shared helpers for retired legacy pages. Laravel owns authentication now.
// ============================================

$db = require __DIR__ . '/../config/db.php';
$conn = mysqli_connect($db['host'], $db['user'], $db['password'], $db['database'], $db['port']);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');

function clean($value) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($value));
}

/**
 * Turns a rank value exactly as a ranking body writes it - a plain number
 * ("161"), a range ("801-1000", "1001–1100"), an ordinal ("2nd"), or a
 * non-numeric status ("Reporter Status", "Not listed") - into a single
 * integer IRIS can sort and plot on a chart.
 *
 * Ranges use their lower (better) bound, since that's what a reader means
 * when they say "we're in the 801-1000 band." Returns null when there is no
 * number to extract at all, so a chart can skip that point instead of
 * plotting a false zero for something like "Reporter Status".
 *
 * The original text is never discarded - it's always saved alongside this
 * value so the dashboard can still display "801-1000" or "Reporter Status"
 * to the reader; this number only drives sorting/plotting.
 */
function parse_rank_to_value($raw): ?int {
    $raw = trim((string) $raw);
    if ($raw === '') return null;
    $normalized = str_replace(['–', '—'], '-', $raw);
    if (preg_match('/\d+/', $normalized, $m)) {
        return (int) $m[0];
    }
    return null;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && $_SESSION['role'] === 'admin';
}

// Call at the top of any page only logged-in users may see
function require_login() {
    header('Location: /login', true, 302);
    exit();
}

// Call at the top of any admin-only page
function require_admin() {
    header('Location: /admin/dashboard', true, 302);
    exit();
}
