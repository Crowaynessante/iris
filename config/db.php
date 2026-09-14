<?php
// ============================================
// Database connection (mysqli)
// Edit these to match your XAMPP/WAMP or live server credentials
// ============================================

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";          // XAMPP default is blank; your live host will give you one
$DB_NAME = "iris_db";

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Always safe with special characters in text (accented names, apostrophes, etc.)
mysqli_set_charset($conn, "utf8mb4");
