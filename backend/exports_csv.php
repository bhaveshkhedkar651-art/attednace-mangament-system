<?php
session_start();

if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../frontend/teacher_login.html");
    exit();
}

require_once "db.php";

$teacher_id = (int) $_SESSION['teacher_id'];
$subject_value = $_GET['subject_id'] ?? '0';
$filter_subject = is_scalar($subject_value) ? max(0, (int) $subject_value) : 0;
$default_from = date("Y-m-d", strtotime("-30 days"));
$default_to = date("Y-m-d");

function valid_report_date($value, $fallback)
{
    if (!is_string($value)) {
        return $fallback;
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : $fallback;
}

$filter_from = valid_report_date($_GET['from'] ?? null, $default_from);
$filter_to = valid_report_date($_GET['to'] ?? null, $default_to);

if ($filter_from > $filter_to) {
    http_response_code(400);
    exit("The start date must be on or before the end date.");
}

if ($filter_subject > 0) {
    $subject_stmt = mysqli_prepare($conect, "SELECT id FROM subjects WHERE id = ? AND teacher_id = ? LIMIT 1");
    mysqli_stmt_bind_param($subject_stmt, "ii", $filter_subject, $teacher_id);
    mysqli_stmt_execute($subject_stmt);
    $subject_result = mysqli_stmt_get_result($subject_stmt);
    $subject_exists = mysqli_num_rows($subject_result) === 1;
    mysqli_stmt_close($subject_stmt);

    if (!$subject_exists) {
        http_response_code(403);
        exit("That subject is not available in your account.");
    }
}

$sql = "SELECT students.roll_no, students.name AS student_name, students.class, students.email,
               subjects.subject_name, attendance.date, attendance.time, attendance.status
        FROM attendance
        JOIN students ON students.id = attendance.student_id
        JOIN subjects ON subjects.id = attendance.subject_id
        WHERE subjects.teacher_id = ? AND attendance.date BETWEEN ? AND ?";

if ($filter_subject > 0) {
    $sql .= " AND attendance.subject_id = ?";
}
$sql .= " ORDER BY attendance.date DESC, attendance.time DESC";

$stmt = mysqli_prepare($conect, $sql);
if ($filter_subject > 0) {
    mysqli_stmt_bind_param($stmt, "issi", $teacher_id, $filter_from, $filter_to, $filter_subject);
} else {
    mysqli_stmt_bind_param($stmt, "iss", $teacher_id, $filter_from, $filter_to);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

function csv_safe_value($value)
{
    $value = (string) $value;
    return preg_match('/^\s*[=+@-]/', $value) ? "'" . $value : $value;
}

$filename = "attendance_report_" . date("Y-m-d_H-i-s") . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');

$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ['Roll No', 'Student Name', 'Class', 'Email', 'Subject', 'Date', 'Time', 'Status'], ',', '"', '');

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, array_map('csv_safe_value', [
            $row['roll_no'],
            $row['student_name'],
            $row['class'],
            $row['email'],
            $row['subject_name'],
            $row['date'],
            $row['time'],
            $row['status']
        ]), ',', '"', '');
    }
} else {
    fputcsv($output, ['No records found'], ',', '"', '');
}

fclose($output);
mysqli_stmt_close($stmt);
exit();
?>