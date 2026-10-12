<?php
session_start();

if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../frontend/teacher_login.html");
    exit();
}

require_once "db.php";
$config = require __DIR__ . '/config.php';

$teacher_id   = (int) $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';

if (!isset($_GET['subject_id'])) {
    die("Subject not selected.");
}

$subject_id = (int) $_GET['subject_id'];


$check_stmt = mysqli_prepare($conect, "SELECT id, subject_name FROM subjects WHERE id = ? AND teacher_id = ? LIMIT 1");
mysqli_stmt_bind_param($check_stmt, "ii", $subject_id, $teacher_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$subject = mysqli_fetch_assoc($check_result);
mysqli_stmt_close($check_stmt);

if (!$subject) {
    die("Subject not found or unauthorized access.");
}

$valid_seconds = $config['qr_valid_seconds'];


$base_url = $config['app_url'];
if (!$base_url) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host_parts = parse_url('http://' . $host);
    $request_host = trim(strtolower($host_parts['host'] ?? ''), '[]');
    if (in_array($request_host, ['localhost', '127.0.0.1', '::1'], true)) {
        $lan_ip = gethostbyname(gethostname());
        if (filter_var($lan_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $port = isset($host_parts['port']) ? ':' . (int) $host_parts['port'] : '';
            $host = $lan_ip . $port;
        }
    }
    $dir    = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\');
    $base_url = $scheme . '://' . $host . $dir;
}
$base_url = rtrim($base_url, '/');


$token_key   = 'current_qr_token_'   . $subject_id;
$expires_key = 'current_qr_expires_' . $subject_id;


if (isset($_SESSION[$expires_key]) && time() >= $_SESSION[$expires_key]) {
    unset($_SESSION[$token_key], $_SESSION[$expires_key]);
}


if (!isset($_SESSION[$token_key], $_SESSION[$expires_key])) {
    $qr_token   = bin2hex(random_bytes(16));
    $created_at = date("Y-m-d H:i:s");
    $expires_at = date("Y-m-d H:i:s", strtotime("+$valid_seconds seconds"));

    $insert_stmt = mysqli_prepare($conect,
        "INSERT INTO qr_session (teacher_id, subject_id, qr_token, created_at, expires_at)
         VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($insert_stmt, "iisss", $teacher_id, $subject_id, $qr_token, $created_at, $expires_at);
    $result = mysqli_stmt_execute($insert_stmt);
    mysqli_stmt_close($insert_stmt);

    if ($result) {
        $_SESSION[$token_key]   = $qr_token;
        $_SESSION[$expires_key] = time() + $valid_seconds;
    } else {
        error_log("QR session insert failed: " . mysqli_error($conect));
        http_response_code(500);
        exit("Unable to create a QR session. Please try again.");
    }
} else {
    $qr_token = $_SESSION[$token_key];
}

$time_left = max(0, $_SESSION[$expires_key] - time());

$scan_url  = $base_url . "/backend/scan.php?token=" . urlencode($qr_token);


$qr_api_url = 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
    'size'   => '300x300',
    'data'   => $scan_url,
    'margin' => 10,
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate QR</title>
    <link rel="stylesheet" href="../frontend/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../frontend/qr.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-graduation-cap"></i> AMS</h2>
        <p>Attendance System</p>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="teacher_subjects.php"><i class="fas fa-book"></i> My Subjects</a></li>
        <li><a href="teacher_subjects.php" class="active"><i class="fas fa-qrcode"></i> Generate QR</a></li>
        <li><a href="live_attendance.php"><i class="fas fa-users"></i> Live Attendance</a></li>
        <li><a href="teacher_profile.php"><i class="fas fa-user"></i> Profile</a></li>
        <li><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <header class="top-header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <h2><i class="fas fa-qrcode"></i> Generate QR</h2>
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

    <div style="text-align:center; background:white; padding:30px; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom:15px; color:#1e3a8a;">
            📖 <?php echo htmlspecialchars($subject['subject_name']); ?>
        </h3>

        <h2>Scan this QR Code</h2>

        <div class="qr-container">
            <img src="<?php echo htmlspecialchars($qr_api_url, ENT_QUOTES, 'UTF-8'); ?>"
                 width="300" height="300" alt="Attendance QR Code">
        </div>

        <p class="link-box">
            <b>Link:</b>
            <a href="<?php echo htmlspecialchars($scan_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                <?php echo htmlspecialchars($scan_url, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </p>

        <p class="timer">
            QR expires in <span id="countdown" data-time="<?php echo $time_left; ?>"><?php echo $time_left; ?></span> seconds
        </p>

        <iframe src="attendance_table.php?subject_id=<?php echo $subject_id; ?>"
                style="width:100%; height:300px; border:none; margin-top:20px; border-radius:8px;"></iframe>
    </div>
</div>

<script src="../frontend/dashboard.js"></script>
<script src="../frontend/qr.js"></script>
</body>
</html>