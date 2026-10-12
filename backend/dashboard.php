<?php
session_start();

if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../frontend/teacher_login.html");
    exit();
}

include "db.php";

$teacher_id   = (int) $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$today        = date("Y-m-d");
$today_start  = $today . " 00:00:00";
$today_end    = $today . " 23:59:59";


$sub_stmt = mysqli_prepare($conect, "SELECT COUNT(*) AS total FROM subjects WHERE teacher_id = ?");
mysqli_stmt_bind_param($sub_stmt, "i", $teacher_id);
mysqli_stmt_execute($sub_stmt);
$sub_res = mysqli_stmt_get_result($sub_stmt);
$total_subjects = (int)(mysqli_fetch_assoc($sub_res)['total'] ?? 0);
mysqli_stmt_close($sub_stmt);


$stu_res = mysqli_query($conect, "SELECT COUNT(*) AS total FROM students");
$total_students = (int)(mysqli_fetch_assoc($stu_res)['total'] ?? 0);


$today_present_stmt = mysqli_prepare($conect,
    "SELECT COUNT(DISTINCT student_id) AS total
     FROM attendance
     WHERE date BETWEEN ? AND ?
     AND subject_id IN (SELECT id FROM subjects WHERE teacher_id = ?)");
mysqli_stmt_bind_param($today_present_stmt, "ssi", $today_start, $today_end, $teacher_id);
mysqli_stmt_execute($today_present_stmt);
$today_res = mysqli_stmt_get_result($today_present_stmt);
$today_present = (int)(mysqli_fetch_assoc($today_res)['total'] ?? 0);
mysqli_stmt_close($today_present_stmt);

$today_percent = $total_students > 0 ? round(($today_present / $total_students) * 100, 1) : 0;


$subjects_stmt = mysqli_prepare($conect,
    "SELECT s.id, s.subject_name,
            COALESCE(a.cnt, 0) AS present_today
     FROM subjects s
     LEFT JOIN (
         SELECT subject_id, COUNT(*) AS cnt
         FROM attendance
         WHERE date BETWEEN ? AND ?
         GROUP BY subject_id
     ) a ON a.subject_id = s.id
     WHERE s.teacher_id = ?
     ORDER BY s.subject_name");
mysqli_stmt_bind_param($subjects_stmt, "ssi", $today_start, $today_end, $teacher_id);
mysqli_stmt_execute($subjects_stmt);
$result = mysqli_stmt_get_result($subjects_stmt);

$subjects_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $subjects_data[] = $row;
}
mysqli_stmt_close($subjects_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="../frontend/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-graduation-cap"></i> AMS</h2>
        <p>Attendance System</p>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="teacher_subjects.php"><i class="fas fa-book"></i> My Subjects</a></li>
        <li><a href="teacher_subjects.php"><i class="fas fa-qrcode"></i> Generate QR</a></li>
        <li><a href="live_attendance.php"><i class="fas fa-users"></i> Live Attendance</a></li>
        <li><a href="teacher_profile.php"><i class="fas fa-user"></i> Profile</a></li>
        <li><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content" id="mainContent">
    <header class="top-header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <h2>Dashboard</h2>
        </div>
        <div class="header-right">
            <div class="teacher-info">
                <div class="avatar"><?php echo strtoupper(substr($teacher_name, 0, 1)); ?></div>
                <div>
                    <div class="teacher-name"><?php echo htmlspecialchars($teacher_name); ?></div>
                    <div class="teacher-id">ID: #<?php echo $teacher_id; ?></div>
                </div>
            </div>
        </div>
    </header>

    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-book"></i></div>
            <div class="stat-info"><h3><?php echo $total_subjects; ?></h3><p>Total Subjects</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-users"></i></div>
            <div class="stat-info"><h3><?php echo $total_students; ?></h3><p>Total Students</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-user-check"></i></div>
            <div class="stat-info"><h3><?php echo $today_present; ?></h3><p>Today's Present</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-percentage"></i></div>
            <div class="stat-info"><h3><?php echo $today_percent; ?>%</h3><p>Today's Attendance</p></div>
        </div>
    </section>

    <section class="subjects-section">
        <div class="section-header">
            <h2><i class="fas fa-book-open"></i> My Subjects</h2>
            <a href="teacher_subjects.php" class="btn-add"><i class="fas fa-plus"></i> Add Subject</a>
        </div>

        <?php if (count($subjects_data) > 0) { ?>
            <div class="subject-grid">
                <?php foreach ($subjects_data as $row) {
                    $sub_id = (int)$row['id'];
                    $present_today = (int)$row['present_today'];
                    $sub_percent = $total_students > 0 ? round(($present_today / $total_students) * 100, 1) : 0;
                ?>
                    <div class="subject-card">
                        <div class="card-header">
                            <div class="subject-icon"><i class="fas fa-book"></i></div>
                            <span class="status-badge <?php echo ($present_today > 0) ? 'active' : 'inactive'; ?>">
                                <?php echo ($present_today > 0) ? 'Active' : 'No Data'; ?>
                            </span>
                        </div>
                        <h3><?php echo htmlspecialchars($row['subject_name']); ?></h3>
                        <p class="subject-meta">Subject ID: #<?php echo $sub_id; ?></p>
                        <div class="subject-stats">
                            <div><span><?php echo $present_today; ?></span><small>Present Today</small></div>
                            <div><span><?php echo $sub_percent; ?>%</span><small>Attendance</small></div>
                        </div>
                        <div class="card-actions">
                            <a href="generate_qr.php?subject_id=<?php echo $sub_id; ?>" class="btn-primary">
                                <i class="fas fa-qrcode"></i> Generate QR
                            </a>
                            <a href="live_attendance.php?subject_id=<?php echo $sub_id; ?>" class="btn-secondary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No Subjects Found</h3>
                <p>You haven't added any subjects yet. Start by adding a subject.</p>
                <a href="teacher_subjects.php" class="btn-primary"><i class="fas fa-plus"></i> Add Subject</a>
            </div>
        <?php } ?>
    </section>
</div>

<div id="toast" class="toast"></div>
<script src="../frontend/dashboard.js"></script>
</body>
</html>