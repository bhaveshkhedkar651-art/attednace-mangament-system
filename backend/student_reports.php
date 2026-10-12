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
$overall_percent = $total_days > 0 ? round(($total_present / $total_days) * 100, 1) : 0;

$subject_stmt = mysqli_prepare($conect,
    "SELECT s.subject_name,
            COUNT(a.id) AS total_sessions,
            SUM(CASE WHEN a.status = 'PRESENT' THEN 1 ELSE 0 END) AS present_count
     FROM subjects s
     LEFT JOIN attendance a ON a.subject_id = s.id AND a.student_id = ?
     GROUP BY s.id, s.subject_name
     ORDER BY s.subject_name"
);
mysqli_stmt_bind_param($subject_stmt, 'i', $student_id);
mysqli_stmt_execute($subject_stmt);
$subject_result = mysqli_stmt_get_result($subject_stmt);
$subject_rows = [];
while ($row = mysqli_fetch_assoc($subject_result)) {
    $subject_rows[] = $row;
}
mysqli_stmt_close($subject_stmt);

$month_stmt = mysqli_prepare($conect,
    "SELECT DATE_FORMAT(date, '%Y-%m') AS month,
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'PRESENT' THEN 1 ELSE 0 END) AS present
     FROM attendance
     WHERE student_id = ?
     GROUP BY DATE_FORMAT(date, '%Y-%m')
     ORDER BY month DESC"
);
mysqli_stmt_bind_param($month_stmt, 'i', $student_id);
mysqli_stmt_execute($month_stmt);
$month_result = mysqli_stmt_get_result($month_stmt);
$month_rows = [];
while ($row = mysqli_fetch_assoc($month_result)) {
    $month_rows[] = $row;
}
mysqli_stmt_close($month_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports</title>
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
            <li><a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
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
                <h2>Attendance Reports</h2>
            </div>
            <div class="header-right">
                <div class="student-info">
                    <div class="avatar">
                        <img src="student_photo.php" alt="Student photo">
                    </div>
                    <div>
                        <div class="student-name"><?php echo htmlspecialchars($_SESSION['student_name'] ?? 'Student', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="student-id">Overall Performance</div>
                    </div>
                </div>
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_days; ?></h3>
                    <p>Total Sessions</p>
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
                    <h3><?php echo $overall_percent; ?>%</h3>
                    <p>Overall</p>
                </div>
            </div>
        </section>

        <section class="content-section">
            <div class="section-header">
                <h2><i class="fas fa-book"></i> Subject-wise Breakdown</h2>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Present</th>
                            <th>Total</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($subject_rows)) : ?>
                            <?php foreach ($subject_rows as $row) : ?>
                                <?php $present = (int) ($row['present_count'] ?? 0); $total = max(1, (int) ($row['total_sessions'] ?? 0)); $percent = round(($present / $total) * 100, 1); ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo $present; ?></td>
                                    <td><?php echo $total; ?></td>
                                    <td><?php echo $percent; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="4" class="empty">No report data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="content-section">
            <div class="section-header">
                <h2><i class="fas fa-calendar-alt"></i> Monthly Summary</h2>
                <a href="student_export_csv.php" class="btn-view">Export CSV</a>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Total</th>
                            <th>Present</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($month_rows)) : ?>
                            <?php foreach ($month_rows as $row) : ?>
                                <?php $total = (int) ($row['total'] ?? 0); $present = (int) ($row['present'] ?? 0); $month_percent = $total > 0 ? round(($present / $total) * 100, 1) : 0; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['month'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo $total; ?></td>
                                    <td><?php echo $present; ?></td>
                                    <td><?php echo $month_percent; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="4" class="empty">No monthly data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script src="../frontend/student_dashboard.js"></script>
</body>
</html>
