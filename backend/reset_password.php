<?php
require_once 'db.php';

header('Cache-Control: no-store, max-age=0');
header('Referrer-Policy: no-referrer');

$token = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? trim($_POST['token'] ?? '')
    : trim($_GET['token'] ?? '');

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    header("Location: ../frontend/forget_pass.html?error=invalid-link");
    exit();
}

$token_hash = hash('sha256', $token);
$is_post = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($is_post && !mysqli_begin_transaction($conect)) {
    http_response_code(500);
    exit("Unable to process the reset request. Please try again.");
}

$query = "SELECT student_id, expires_at FROM student_password_resets WHERE token_hash = ? LIMIT 1";
if ($is_post) {
    $query .= " FOR UPDATE";
}
$stmt = mysqli_prepare($conect, $query);
mysqli_stmt_bind_param($stmt, "s", $token_hash);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$reset = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$reset || strtotime($reset['expires_at']) <= time()) {
    if ($is_post) {
        mysqli_rollback($conect);
    }
    http_response_code(400);
    exit("This password reset link is invalid or expired. <a href='../frontend/forget_pass.html'>Request a new link</a>");
}

if (!$is_post) {
    $safe_token = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../frontend/login.css">
</head>
<body>
    <main class="box">
        <form action="reset_password.php" method="POST">
            <h2>Choose a new password</h2>
            <input type="hidden" name="token" value="{$safe_token}">
            <label for="password">New password</label>
            <input id="password" type="password" name="password" minlength="8" autocomplete="new-password" required>
            <label for="confirm-password">Confirm password</label>
            <input id="confirm-password" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>
            <button type="submit">Reset password</button>
        </form>
    </main>
</body>
</html>
HTML;
    exit();
}

$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';
if (strlen($password) < 8) {
    http_response_code(400);
    exit("Password must be at least 8 characters. <a href='reset_password.php?token=" . urlencode($token) . "'>Try again</a>");
}

if ($password !== $confirm) {
    http_response_code(400);
    exit("Passwords do not match. <a href='reset_password.php?token=" . urlencode($token) . "'>Try again</a>");
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

$update_stmt = mysqli_prepare($conect, "UPDATE students SET password = ?, reset_requested_at = NULL WHERE id = ?");
$student_id = (int) $reset['student_id'];
mysqli_stmt_bind_param($update_stmt, "si", $hashed, $student_id);
$updated = mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

$delete_stmt = mysqli_prepare($conect, "DELETE FROM student_password_resets WHERE student_id = ?");
mysqli_stmt_bind_param($delete_stmt, "i", $student_id);
$deleted = mysqli_stmt_execute($delete_stmt);
mysqli_stmt_close($delete_stmt);

if (!$updated || !$deleted) {
    mysqli_rollback($conect);
    http_response_code(500);
    exit("Unable to reset the password. Please try again.");
}

mysqli_commit($conect);
echo "Password reset successful. <a href='../frontend/student_login_check.html'>Log in</a>";