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

if (!isset($_SESSION['student_id'])) {
    http_response_code(401);
    exit();
}

require_once 'db.php';

$student_id = (int) $_SESSION['student_id'];

$stmt = mysqli_prepare($conect, 'SELECT photo FROM students WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row || empty($row['photo'])) {
    header('Content-Type: image/svg+xml');
    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="#cbd5e1"/><text x="50" y="65" font-size="50" text-anchor="middle" fill="#fff">?</text></svg>';
    exit();
}

$photo_path = __DIR__ . '/../uploads/students/' . $row['photo'];
if (!file_exists($photo_path)) {
    http_response_code(404);
    exit();
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $photo_path);
finfo_close($finfo);

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($photo_path));
header('Cache-Control: private, max-age=3600');
readfile($photo_path);
exit();
