<?php
include "db.php";

$name = trim($_POST['name'] ?? '');
$password = $_POST['password'] ?? '';
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $name === '' || $password === '' || !$email) {
    http_response_code(400);
    exit("Enter your name, a valid email, and a password.");
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conect, "INSERT INTO teacher (name, password, email) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $name, $hashedPassword, $email);
$result = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($result) {
    header("Location: ../frontend/teacher_login.html?registered=success");
    exit();
}

http_response_code(500);
exit("Unable to register teacher. Please try again.");

?>