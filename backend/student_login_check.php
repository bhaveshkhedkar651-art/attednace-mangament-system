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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../frontend/student_login_check.html");
    exit();
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$pass  = $_POST['password'] ?? '';

if (!$email || $pass === '') {
    header("Location: ../frontend/student_login_check.html?error=invalid");
    exit();
}


$attendance_token = $_SESSION['attendance_token'] ?? null;

$stmt = mysqli_prepare($conect, "SELECT id, name, password FROM students WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$student) {
    header("Location: ../frontend/student_login_check.html?error=credentials");
    exit();
}

if (!password_verify($pass, $student['password'])) {
    header("Location: ../frontend/student_login_check.html?error=wrong_password");
    exit();
}


$_SESSION['student_id']   = (int) $student['id'];
$_SESSION['student_name'] = $student['name'];


if ($attendance_token) {
    $_SESSION['attendance_token'] = $attendance_token;
    header("Location: attendance_marked.php?token=" . urlencode($attendance_token));
    exit();
}

header('Location: ../backend/student_dashboard.php?login=success');
exit();