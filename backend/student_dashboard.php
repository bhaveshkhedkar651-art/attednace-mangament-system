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
    header('Location: ../frontend/student_login_check.html');
    exit();
}

require_once 'db.php';

$student_id = (int) $_SESSION['student_id'];

$student_stmt = mysqli_prepare($conect, "SELECT id, name, email, roll_no, class, photo FROM students WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($student_stmt, 'i', $student_id);
mysqli_stmt_execute($student_stmt);
$student_result = mysqli_stmt_get_result($student_stmt);
$student = mysqli_fetch_assoc($student_result);
mysqli_stmt_close($student_stmt);

if (!$student) {
    session_destroy();
    header('Location: ../frontend/student_login_check.html');
    exit();
}

$stats_stmt = mysqli_prepare($conect,
    "SELECT COUNT(*) AS total_records,
            SUM(CASE WHEN status = 'PRESENT' THEN 1 ELSE 0 END) AS total_present,
            SUM(CASE WHEN status = 'ABSENT' THEN 1 ELSE 0 END) AS total_absent
     FROM attendance WHERE student_id = ?"
);
mysqli_stmt_bind_param($stats_stmt, 'i', $student_id);
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$stats = mysqli_fetch_assoc($stats_result);
mysqli_stmt_close($stats_stmt);

$total_present = (int) ($stats['total_present'] ?? 0);
$total_absent = (int) ($stats['total_absent'] ?? 0);
$total_days = $total_present + $total_absent;
$attendance_percent = $total_days > 0 ? round(($total_present / $total_days) * 100, 1) : 0;

$subject_stmt = mysqli_prepare($conect,
    "SELECT s.id AS subject_id,
            s.subject_name,
            COUNT(a.id) AS present_count,
            SUM(CASE WHEN a.status = 'PRESENT' THEN 1 ELSE 0 END) AS present_total,
            COUNT(DISTINCT a.date) AS total_sessions
     FROM subjects s
     LEFT JOIN attendance a ON a.subject_id = s.id AND a.student_id = ?
     GROUP BY s.id, s.subject_name
     ORDER BY s.subject_name"
);
mysqli_stmt_bind_param($subject_stmt, 'i', $student_id);
mysqli_stmt_execute($subject_stmt);
$subject_result = mysqli_stmt_get_result($subject_stmt);
$subjects_data = [];
while ($row = mysqli_fetch_assoc($subject_result)) {
    $subjects_data[] = $row;
}
mysqli_stmt_close($subject_stmt);

$recent_stmt = mysqli_prepare($conect,
    "SELECT s.subject_name, a.date, a.time, a.status
     FROM attendance a
     JOIN subjects s ON s.id = a.subject_id
     WHERE a.student_id = ?
     ORDER BY a.id DESC
     LIMIT 10"
);
mysqli_stmt_bind_param($recent_stmt, 'i', $student_id);
mysqli_stmt_execute($recent_stmt);
$recent_result = mysqli_stmt_get_result($recent_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../frontend/student_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-graduation-cap"></i> AMS</h2>
            <p>Student Portal</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="student_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="student_scan.php"><i class="fas fa-qrcode"></i> Scan QR</a></li>
            <li><a href="student_attendance.php"><i class="fas fa-calendar-check"></i> My Attendance</a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="../frontend/change_password.html"><i class="fas fa-lock"></i> Change Password</a></li>
            <li><a href="student_logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button class="menu-btn" onclick="toggleSidebar()" type="button"><i class="fas fa-bars"></i></button>
                <h2>Dashboard</h2>
            </div>
            <div class="header-right">
                <div class="student-info">
                    <div class="avatar">
                        <img src="student_photo.php" alt="Student photo">
                    </div>
                    <div>
                        <div class="student-name"><?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="student-id">Roll: <?php echo htmlspecialchars($student['roll_no'], ENT_QUOTES, 'UTF-8'); ?> | <?php echo htmlspecialchars($student['class'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_days; ?></h3>
                    <p>Total Days</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-user-check"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_present; ?></h3>
                    <p>Present</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-user-times"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_absent; ?></h3>
                    <p>Absent</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-percentage"></i></div>
                <div class="stat-info">
                    <h3><?php echo $attendance_percent; ?>%</h3>
                    <p>Overall Attendance</p>
                </div>
            </div>
        </section>

        <section class="content-section">
            <div class="section-header">
                <h2><i class="fas fa-book"></i> Subject-wise Attendance</h2>
            </div>
            <div class="subjects-grid">
                <?php if (!empty($subjects_data)) : ?>
                    <?php foreach ($subjects_data as $sub) : ?>
                        <?php
                        $present = (int) ($sub['present_total'] ?? 0);
                        $total_sessions = max(1, (int) ($sub['total_sessions'] ?? 0));
                        $percent = $total_sessions > 0 ? round(($present / $total_sessions) * 100, 1) : 0;
                        $class = $percent >= 75 ? '' : ($percent >= 50 ? 'warning' : 'danger');
                        ?>
                        <div class="subject-card">
                            <h3><?php echo htmlspecialchars($sub['subject_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="subject-stat">
                                <span><?php echo $present; ?> present</span>
                                <span><?php echo $percent; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill <?php echo $class; ?>" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="empty-block">No subject records found yet.</div>
                <?php endif; ?>
            </div>
        </section>

        <section class="content-section">
            <div class="section-header">
                <h2><i class="fas fa-history"></i> Recent Attendance</h2>
                <a href="student_attendance.php" class="btn-view">View All</a>
            </div>
            <div class="table-wrap">
                <table class="data-table" id="attendance-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="attendance-body">
                        <?php if (mysqli_num_rows($recent_result) > 0) : ?>
                            <?php while ($row = mysqli_fetch_assoc($recent_result)) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(date('H:i:s', strtotime($row['time'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="status-pill"><?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr><td colspan="4" class="empty">No attendance records yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div id="toast" class="toast" role="status" aria-live="polite"></div>
    <script src="../frontend/student_dashboard.js"></script>
</body>
</html>
<?php mysqli_stmt_close($recent_stmt); ?>
