<?php
session_start();
include "db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../frontend/teacher_login.html");
    exit();
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$pass = $_POST['password'] ?? '';

$stmt = mysqli_prepare($conect, "SELECT id, name, password FROM teacher WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$teacher = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$email || !$teacher) {
    header("Location: ../frontend/teacher_login.html?error=invalid_login");
    exit();
}

if (!password_verify($pass, $teacher['password'])) {
    header("Location: ../frontend/teacher_login.html?error=wrong_password");
    exit();
}

session_regenerate_id(true);
$_SESSION['teacher_id'] = $teacher['id'];
$_SESSION['teacher_name'] = $teacher['name'];

header("Location: dashboard.php?login=success");
exit();
?>