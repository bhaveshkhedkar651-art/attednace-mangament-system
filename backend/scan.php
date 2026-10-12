<?php

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
include "db.php";

if (!isset($_GET["token"])) {
    http_response_code(400);
    exit("Invalid QR code. Token is missing.");
}

$token = trim($_GET['token']);

if (!preg_match('/^[a-f0-9]{32}$/i', $token)) {
    http_response_code(400);
    exit("Invalid QR code.");
}

$stmt = mysqli_prepare($conect, "SELECT expires_at FROM qr_session WHERE qr_token = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$qr = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$qr) {
    http_response_code(404);
    exit("Invalid QR code.");
}

if (strtotime($qr["expires_at"]) <= time()) {
    http_response_code(410);
    exit("QR code expired. Please rescan a current code.");
}

if (!isset($_SESSION['student_id'])) {
    $_SESSION['attendance_token'] = $token;
    header("Location: ../frontend/student_login_check.html");
    exit();
}

header("Location: attendance_marked.php?token=" . urlencode($token));
exit();