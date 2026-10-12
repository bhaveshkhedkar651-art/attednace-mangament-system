<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (!isset($_SESSION['student_id'])) {
    header('Location: ../frontend/student_login_check.html');
    exit();
}

require_once 'db.php';

$student_id = (int) $_SESSION['student_id'];
$stmt = mysqli_prepare($conect,
    'SELECT s.subject_name, a.date, a.time, a.status
     FROM attendance a
     JOIN subjects s ON s.id = a.subject_id
     WHERE a.student_id = ?
     ORDER BY a.date DESC, a.time DESC'
);

if (!$stmt) {
    error_log('Student CSV export prepare failed: ' . mysqli_error($conect));
    http_response_code(500);
    exit('Unable to export attendance right now. Please try again.');
}

mysqli_stmt_bind_param($stmt, 'i', $student_id);
if (!mysqli_stmt_execute($stmt)) {
    error_log('Student CSV export query failed: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    http_response_code(500);
    exit('Unable to export attendance right now. Please try again.');
}

$result = mysqli_stmt_get_result($stmt);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="my_attendance_' . date('Y-m-d') . '.csv"');
header('X-Content-Type-Options: nosniff');

$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ['Subject', 'Date', 'Time', 'Status'], ',', '"', '');

while ($row = mysqli_fetch_assoc($result)) {
    $values = array_map(static function ($value) {
        $value = (string) $value;
        return preg_match('/^[\s\x00-\x1F]*[=+\-@]/', $value) ? "'" . $value : $value;
    }, [$row['subject_name'], $row['date'], $row['time'], $row['status']]);
    fputcsv($output, $values, ',', '"', '');
}

fclose($output);
mysqli_stmt_close($stmt);
exit();