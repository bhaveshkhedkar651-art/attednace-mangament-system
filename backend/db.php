<?php
date_default_timezone_set('Asia/Kolkata');

function loadEnv($path) {
    if (!is_readable($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key === '' || getenv($key) !== false) continue;
        if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }
        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

loadEnv(__DIR__ . '/../.env');

$dbHost = $_ENV['DB_HOST'] ?? $_ENV['DB_PC'] ?? getenv('DB_HOST');
$dbUser = $_ENV['DB_USER'] ?? getenv('DB_USER');
$dbPass = $_ENV['DB_PASS'] ?? getenv('DB_PASS');
$dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME');

// These defaults are for local XAMPP development; server deployments should set DB_* environment variables.
if (!is_string($dbHost) || $dbHost === '') $dbHost = getenv('DB_PC') ?: '127.0.0.1';
if (!is_string($dbUser) || $dbUser === '') $dbUser = 'root';
if ($dbPass === false) $dbPass = '';
if (!is_string($dbName) || $dbName === '') $dbName = 'attendance_data';

if (!is_string($dbHost) || $dbHost === '' || !is_string($dbUser) || $dbUser === '' || $dbPass === false || !is_string($dbName) || $dbName === '') {
    error_log('Database configuration is incomplete. Set DB_HOST, DB_USER, DB_PASS, and DB_NAME in .env.');
    http_response_code(500);
    exit("Service temporarily unavailable. Please try again later.");
}

define('DB_PC', $dbHost);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_NAME', $dbName);

$conect = mysqli_connect(DB_PC, DB_USER, DB_PASS, DB_NAME);

if (!$conect) {
    error_log("DB Connection failed: " . mysqli_connect_error());
    http_response_code(500);
    exit("Service temporarily unavailable. Please try again later.");
}

if (!mysqli_set_charset($conect, 'utf8mb4')) {
    error_log("Unable to set database connection charset: " . mysqli_error($conect));
    http_response_code(500);
    exit("Service temporarily unavailable. Please try again later.");
}