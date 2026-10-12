<?php
require_once 'db.php';
require_once 'mailer.php';
$config = require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../frontend/forget_pass.html");
    exit();
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    exit("Please enter a valid email. <a href='../frontend/forget_pass.html'>Try again</a>");
}

$genericMessage = "If this email is registered, a password reset link has been sent.";
$stmt = mysqli_prepare($conect, "SELECT id, name, reset_requested_at FROM students WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$student) {
    sleep(1);
    exit($genericMessage . " <a href='../frontend/student_login_check.html'>Back to login</a>");
}

if ($student['reset_requested_at'] && time() - strtotime($student['reset_requested_at']) < 60) {
    exit($genericMessage . " <a href='../frontend/student_login_check.html'>Back to login</a>");
}

$student_id = (int) $student['id'];
$token = bin2hex(random_bytes(32));
$token_hash = hash('sha256', $token);
$now = date('Y-m-d H:i:s');
$expires_at = date('Y-m-d H:i:s', time() + 600);

$reset_stmt = mysqli_prepare($conect, "
    INSERT INTO student_password_resets (student_id, token_hash, expires_at, created_at)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), expires_at = VALUES(expires_at), created_at = VALUES(created_at)
");
mysqli_stmt_bind_param($reset_stmt, "isss", $student_id, $token_hash, $expires_at, $now);
$reset_saved = mysqli_stmt_execute($reset_stmt);
mysqli_stmt_close($reset_stmt);

if (!$reset_saved) {
    error_log("Unable to save password reset token for student ID " . $student_id);
    http_response_code(500);
    exit("Unable to process the request. Please try again later.");
}

$rate_stmt = mysqli_prepare($conect, "UPDATE students SET reset_requested_at = ? WHERE id = ?");
mysqli_stmt_bind_param($rate_stmt, "si", $now, $student_id);
mysqli_stmt_execute($rate_stmt);
mysqli_stmt_close($rate_stmt);

$base_url = $config['app_url'];
if (!$base_url) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\');
    $base_url = $scheme . '://' . $host . $dir;
}
$reset_url = rtrim($base_url, '/') . '/backend/reset_password.php?token=' . urlencode($token);

$safe_name = htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8');
$safe_url = htmlspecialchars($reset_url, ENT_QUOTES, 'UTF-8');
$body = "
    <h2>Password Reset</h2>
    <p>Hello {$safe_name},</p>
    <p>Use the link below to choose a new password. This link expires in 10 minutes and can only be used once.</p>
    <p><a href='{$safe_url}'>Reset your password</a></p>
    <p>If you did not request this, please ignore this email.</p>
";

if (!sendMail($email, $student['name'], 'Password Reset Link', $body)) {
    $delete_stmt = mysqli_prepare($conect, "DELETE FROM student_password_resets WHERE student_id = ?");
    mysqli_stmt_bind_param($delete_stmt, "i", $student_id);
    mysqli_stmt_execute($delete_stmt);
    mysqli_stmt_close($delete_stmt);
    http_response_code(500);
    exit("Unable to send the reset link. Please try again later.");
}

exit($genericMessage . " <a href='../frontend/student_login_check.html'>Back to login</a>");