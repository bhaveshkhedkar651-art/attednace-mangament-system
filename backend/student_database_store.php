<?php
require_once("db.php");
require_once("mailer.php");

session_start();

$name     = trim($_POST["Username"] ?? "");
$password = $_POST["Password"] ?? "";
$email    = filter_var(trim($_POST["Email"] ?? ""), FILTER_VALIDATE_EMAIL);
$roll_no  = trim($_POST["Roll_no"] ?? "");
$photo    = $_FILES["photo"] ?? null;
$class    = $_POST["Class"] ?? "";


if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || $name === ''
    || $password === ''
    || !$email
    || $roll_no === ''
    || $class === ''
    || !$photo) {
    http_response_code(400);
    exit("All fields are required and email must be valid.");
}

if (strlen($password) < 6) {
    http_response_code(400);
    exit("Password must be at least 6 characters. <a href='../frontend/student_login.html'>Go back</a>");
}

$allowed_classes = ['FYBCA', 'SYBCA', 'TYBCA'];
if (!in_array($class, $allowed_classes, true)) {
    http_response_code(400);
    exit("Invalid class selected. <a href='../frontend/student_login.html'>Go back</a>");
}


if ($photo['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit("File upload failed. Please try again. <a href='../frontend/student_login.html'>Go back</a>");
}

$max_size = 2 * 1024 * 1024; 
if ($photo['size'] > $max_size) {
    http_response_code(400);
    exit("Photo must be under 2MB. <a href='../frontend/student_login.html'>Go back</a>");
}


$allowed_mimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $photo['tmp_name']);
finfo_close($finfo);

if (!isset($allowed_mimes[$mime])) {
    http_response_code(400);
    exit("Only JPG, PNG, WEBP images allowed. <a href='../frontend/student_login.html'>Go back</a>");
}

$ext = $allowed_mimes[$mime];


$upload_dir = __DIR__ . "/../uploads/students/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$photo_name = bin2hex(random_bytes(16)) . "." . $ext;
$photo_path = $upload_dir . $photo_name;

if (!move_uploaded_file($photo['tmp_name'], $photo_path)) {
    http_response_code(500);
    exit("Error: Failed to save photo. <a href='../frontend/student_login.html'>Go back</a>");
}


$password_hash = password_hash($password, PASSWORD_DEFAULT);


$stmt = mysqli_prepare($conect,
    "INSERT INTO students (name, password, email, roll_no, class, photo)
     VALUES (?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "ssssss", $name, $password_hash, $email, $roll_no, $class, $photo_name);
$result = mysqli_stmt_execute($stmt);
$student_id = $result ? mysqli_insert_id($conect) : 0;
mysqli_stmt_close($stmt);

if ($result) {
    session_regenerate_id(true);
    $_SESSION['student_id']   = $student_id;
    $_SESSION['student_name'] = $name;

    
    $subject = "Registration Successful";
    $body = "
        <h2>Welcome, " . htmlspecialchars($name) . "!</h2>
        <p>Your registration was successful.</p>
        <p>You can now log in using your email and password.</p>
        <p><b>Email:</b> " . htmlspecialchars($email) . "</p>
        <p>Thank you!</p>
    ";
    sendMail($email, $name, $subject, $body);

    
    if (isset($_SESSION["attendance_token"])) {
        $token = $_SESSION['attendance_token'];
        unset($_SESSION['attendance_token']);
        header("Location: attendance_marked.php?token=" . urlencode($token));
        exit();
    }

    header("Location: ../frontend/student_login_check.html?registered=1");
    exit();
}


if (file_exists($photo_path)) {
    @unlink($photo_path);
}

error_log("Student insert failed: " . mysqli_error($conect));
http_response_code(500);
echo "Unable to register student. Please try again.";