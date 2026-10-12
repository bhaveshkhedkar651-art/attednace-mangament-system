<?php
session_start();

if (!isset($_SESSION['teacher_id'])) {
    http_response_code(401);
    exit("Please sign in as a teacher.");
}

require_once "db.php";

$teacher_id = (int) $_SESSION['teacher_id'];
$subject_id = (int) ($_GET['subject_id'] ?? 0);

if ($subject_id < 1) {
    http_response_code(400);
    exit("A valid subject is required.");
}


$subject_stmt = mysqli_prepare($conect, "SELECT subject_name FROM subjects WHERE id = ? AND teacher_id = ? LIMIT 1");
mysqli_stmt_bind_param($subject_stmt, "ii", $subject_id, $teacher_id);
mysqli_stmt_execute($subject_stmt);
$subject_result = mysqli_stmt_get_result($subject_stmt);
$subject = mysqli_fetch_assoc($subject_result);
mysqli_stmt_close($subject_stmt);

if (!$subject) {
    http_response_code(404);
    exit("Subject not found.");
}


$today_start = date("Y-m-d 00:00:00");
$today_end   = date("Y-m-d 23:59:59");

$attendance_stmt = mysqli_prepare($conect,
    "SELECT students.roll_no, students.name, attendance.time, attendance.status
     FROM attendance
     JOIN students ON students.id = attendance.student_id
     WHERE attendance.subject_id = ? AND attendance.date BETWEEN ? AND ?
     ORDER BY attendance.id DESC");
mysqli_stmt_bind_param($attendance_stmt, "iss", $subject_id, $today_start, $today_end);
mysqli_stmt_execute($attendance_stmt);
$attendance_result = mysqli_stmt_get_result($attendance_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="5">
    <title>Today's Attendance</title>
    <link rel="stylesheet" href="../frontend/attendance_table.css">
</head>
<body class="attendance-table-page">
    <div class="attendance-table-wrap">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>Roll No</th>
                    <th>Student Name</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($attendance_result && mysqli_num_rows($attendance_result) > 0) { ?>
                    <?php while ($row = mysqli_fetch_assoc($attendance_result)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['roll_no'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['time'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="status-pill"><?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr><td colspan="4" class="empty-message">No attendance recorded today for <?php echo htmlspecialchars($subject['subject_name'], ENT_QUOTES, 'UTF-8'); ?>.</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php mysqli_stmt_close($attendance_stmt); ?>