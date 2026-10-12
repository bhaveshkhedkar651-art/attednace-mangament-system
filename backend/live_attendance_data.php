<?php
session_start();

if (!isset($_SESSION['teacher_id'])) {
    http_response_code(401);
    exit('<tr><td colspan="5" style="text-align:center; padding:20px; color:#991b1b;">Unauthorized</td></tr>');
}

require_once "db.php";

$teacher_id = (int) $_SESSION['teacher_id'];
$subject_id = max(0, (int) ($_GET['subject_id'] ?? 0));


if ($subject_id > 0) {
    $own_stmt = mysqli_prepare($conect, "SELECT id FROM subjects WHERE id = ? AND teacher_id = ? LIMIT 1");
    mysqli_stmt_bind_param($own_stmt, "ii", $subject_id, $teacher_id);
    mysqli_stmt_execute($own_stmt);
    $own_res = mysqli_stmt_get_result($own_stmt);
    $owns = mysqli_num_rows($own_res) === 1;
    mysqli_stmt_close($own_stmt);

    if (!$owns) {
        exit('<tr><td colspan="5" style="text-align:center; padding:20px; color:#991b1b;">Subject not found.</td></tr>');
    }
}

$today_start = date("Y-m-d 00:00:00");
$today_end   = date("Y-m-d 23:59:59");
$cols        = $subject_id === 0 ? 5 : 4;

if ($subject_id > 0) {
    $stmt = mysqli_prepare($conect,
        "SELECT subjects.subject_name, students.roll_no, students.name, attendance.time, attendance.status
         FROM attendance
         JOIN students ON students.id = attendance.student_id
         JOIN subjects ON subjects.id = attendance.subject_id
         WHERE subjects.teacher_id = ? AND attendance.subject_id = ? AND attendance.date BETWEEN ? AND ?
         ORDER BY attendance.id DESC");
    mysqli_stmt_bind_param($stmt, "iiss", $teacher_id, $subject_id, $today_start, $today_end);
} else {
    $stmt = mysqli_prepare($conect,
        "SELECT subjects.subject_name, students.roll_no, students.name, attendance.time, attendance.status
         FROM attendance
         JOIN students ON students.id = attendance.student_id
         JOIN subjects ON subjects.id = attendance.subject_id
         WHERE subjects.teacher_id = ? AND attendance.date BETWEEN ? AND ?
         ORDER BY attendance.id DESC");
    mysqli_stmt_bind_param($stmt, "iss", $teacher_id, $today_start, $today_end);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo '<tr><td colspan="' . $cols . '" style="text-align:center; padding:40px; color:#94a3b8;">'
       . '<i class="fas fa-inbox" style="font-size:40px; display:block; margin-bottom:10px;"></i>'
       . 'No attendance recorded today.</td></tr>';
    mysqli_stmt_close($stmt);
    exit;
}

while ($row = mysqli_fetch_assoc($result)) {
    echo '<tr>';
    if ($subject_id === 0) {
        echo '<td>' . htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8') . '</td>';
    }
    echo '<td>' . htmlspecialchars($row['roll_no'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($row['time'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td> <span class="status-pill">' . htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') . '</span></td>';
    echo '</tr>';
}

mysqli_stmt_close($stmt);