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
require_once("db.php");

if (!isset($_SESSION["student_id"])) {
    die("Please login first. <a href='../frontend/student_login_check.html'>Login here</a>");
}

if (!isset($_GET["token"])) {
    die("Invalid QR code. Token missing.");
}

$student_id = (int) $_SESSION["student_id"];
$token = trim($_GET["token"]);

if (!preg_match('/^[a-f0-9]{32}$/i', $token)) {
    http_response_code(400);
    die("Invalid QR code.");
}


$stmt = mysqli_prepare($conect, "SELECT id, subject_id, expires_at FROM qr_session WHERE qr_token = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$qr_result = mysqli_stmt_get_result($stmt);
$qr = mysqli_fetch_assoc($qr_result);
mysqli_stmt_close($stmt);

if (!$qr) {
    die("Invalid QR code. Please scan again.");
}

if (strtotime($qr["expires_at"]) <= time()) {
    die("QR code has expired. Please ask your teacher to generate a new one.");
}

$qr_session_id = (int) $qr["id"];
$subject_id    = (int) $qr["subject_id"];


$student_stmt = mysqli_prepare($conect, "SELECT name, roll_no FROM students WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($student_stmt, "i", $student_id);
mysqli_stmt_execute($student_stmt);
$student_result = mysqli_stmt_get_result($student_stmt);
$student = mysqli_fetch_assoc($student_result);
mysqli_stmt_close($student_stmt);

if (!$student) {
    die("Student not found in database.");
}


$subject_stmt = mysqli_prepare($conect, "SELECT subject_name FROM subjects WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($subject_stmt, "i", $subject_id);
mysqli_stmt_execute($subject_stmt);
$subject_result = mysqli_stmt_get_result($subject_stmt);
$subject_row = mysqli_fetch_assoc($subject_result);
mysqli_stmt_close($subject_stmt);

$subject_name = $subject_row['subject_name'] ?? 'Unknown Subject';


$today_start = date("Y-m-d 00:00:00");
$today_end   = date("Y-m-d 23:59:59");
$date        = date("Y-m-d H:i:s");
$time        = date("H:i:s");
$display_date= date("Y-m-d");
$status      = "PRESENT";


$check_stmt = mysqli_prepare($conect,
    "SELECT id, time FROM attendance
     WHERE student_id = ? AND subject_id = ? AND date BETWEEN ? AND ?
     LIMIT 1");
mysqli_stmt_bind_param($check_stmt, "iiss", $student_id, $subject_id, $today_start, $today_end);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$already_marked = mysqli_fetch_assoc($check_result);
mysqli_stmt_close($check_stmt);


if ($already_marked) {
    unset($_SESSION["attendance_token"]);
    header('Location: student_dashboard.php?attendance=already_marked');
    exit();
}


$insert_stmt = mysqli_prepare($conect,
    "INSERT INTO attendance (student_id, subject_id, qr_session_id, date, time, status)
     VALUES (?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($insert_stmt, "iiisss", $student_id, $subject_id, $qr_session_id, $date, $time, $status);

if (!mysqli_stmt_execute($insert_stmt)) {
    mysqli_stmt_close($insert_stmt);
    error_log("Attendance insert failed: " . mysqli_error($conect));
    http_response_code(500);
    die("Unable to mark attendance. Please try again.");
}
mysqli_stmt_close($insert_stmt);

unset($_SESSION["attendance_token"]);
?>
<!doctype html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../frontend/atten_css.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Marked</title>
</head>
<body>
<div class="attendance-box">
    <h2 class="success-title">✅ Attendance Marked!</h2>
    <p><b>Name:</b> <?php echo htmlspecialchars($student["name"], ENT_QUOTES, 'UTF-8'); ?></p>
    <p><b>Roll Number:</b> <?php echo htmlspecialchars($student["roll_no"], ENT_QUOTES, 'UTF-8'); ?></p>
    <p><b>Subject:</b> <?php echo htmlspecialchars($subject_name, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><b>Date:</b> <?php echo htmlspecialchars($display_date, ENT_QUOTES, 'UTF-8'); ?></p>
    <p><b>Time:</b> <?php echo htmlspecialchars($time, ENT_QUOTES, 'UTF-8'); ?></p>
    <a class="home-link" href="student_dashboard.php">Go to Dashboard</a>
</div>
</body>
</html>