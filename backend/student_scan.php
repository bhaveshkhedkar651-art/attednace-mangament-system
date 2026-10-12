<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header('Location: ../frontend/student_login_check.html');
    exit();
}

$student_name = htmlspecialchars($_SESSION['student_name'] ?? 'Student', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Attendance QR</title>
    <link rel="stylesheet" href="../frontend/student_dashboard.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-graduation-cap"></i> AMS</h2>
            <p>Student Portal</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="student_scan.php" class="active"><i class="fas fa-qrcode"></i> Scan QR</a></li>
            <li><a href="student_attendance.php"><i class="fas fa-calendar-check"></i> My Attendance</a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="../frontend/change_password.html"><i class="fas fa-lock"></i> Change Password</a></li>
            <li><a href="student_logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button class="menu-btn" onclick="toggleSidebar()" type="button" aria-label="Toggle navigation"><i class="fas fa-bars"></i></button>
                <h2>Scan QR</h2>
            </div>
            <div class="header-right">
                <div class="student-info">
                    <div class="avatar"><img src="student_photo.php" alt="Student photo"></div>
                    <div>
                        <div class="student-name"><?php echo $student_name; ?></div>
                        <div class="student-id">Student Portal</div>
                    </div>
                </div>
            </div>
        </header>

        <section class="content-section">
            <div class="section-header">
                <h2><i class="fas fa-qrcode"></i> Scan attendance QR</h2>
            </div>
            <div class="scanner-layout">
                <div id="qr-reader" class="qr-reader" aria-label="QR code camera view"></div>
                <p id="scanner-status" class="scanner-status" role="status" aria-live="polite">Start the camera and point it at your teacher's QR code.</p>
                <div class="scanner-actions">
                    <button id="start-camera" class="primary-btn" type="button"><i class="fas fa-camera"></i> Start camera</button>
                </div>
            </div>
        </section>
    </main>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="../frontend/student_dashboard.js"></script>
    <script src="../frontend/student_scan.js?v=3"></script>
</body>
</html>