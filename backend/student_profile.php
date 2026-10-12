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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['profile_message'] = 'Photo upload failed. Please try again.';
        $_SESSION['profile_status'] = 'error';
        header('Location: student_profile.php');
        exit();
    }

    $photo = $_FILES['photo'];
    $max_size = 2 * 1024 * 1024;
    if ($photo['size'] > $max_size) {
        $_SESSION['profile_message'] = 'Photo must be under 2MB.';
        $_SESSION['profile_status'] = 'error';
        header('Location: student_profile.php');
        exit();
    }

    $allowed_mimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $photo['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed_mimes[$mime])) {
        $_SESSION['profile_message'] = 'Only JPG, PNG, or WEBP images are allowed.';
        $_SESSION['profile_status'] = 'error';
        header('Location: student_profile.php');
        exit();
    }

    $upload_dir = __DIR__ . '/../uploads/students/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $ext = $allowed_mimes[$mime];
    $new_photo_name = bin2hex(random_bytes(16)) . '.' . $ext;
    $target_path = $upload_dir . $new_photo_name;

    if (!move_uploaded_file($photo['tmp_name'], $target_path)) {
        $_SESSION['profile_message'] = 'Unable to save photo.';
        $_SESSION['profile_status'] = 'error';
        header('Location: student_profile.php');
        exit();
    }

    $old_photo = $student['photo'] ?? '';
    $update_stmt = mysqli_prepare($conect, 'UPDATE students SET photo = ? WHERE id = ?');
    mysqli_stmt_bind_param($update_stmt, 'si', $new_photo_name, $student_id);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);

    if ($old_photo && $old_photo !== $new_photo_name) {
        $old_path = $upload_dir . $old_photo;
        if (file_exists($old_path)) {
            @unlink($old_path);
        }
    }

    $_SESSION['profile_message'] = 'Profile photo updated successfully.';
    $_SESSION['profile_status'] = 'success';
    header('Location: student_profile.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="../frontend/student_dashboard.css">
    <link rel="stylesheet" href="../frontend/student_profile.css">
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
            <li><a href="student_profile.php" class="active"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="../frontend/change_password.html"><i class="fas fa-lock"></i> Change Password</a></li>
            <li><a href="student_logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button class="menu-btn" onclick="toggleSidebar()" type="button"><i class="fas fa-bars"></i></button>
                <h2>My Profile</h2>
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

        <div class="profile-layout">
            <div class="profile-card profile-info-card">
                <div class="profile-photo-wrap">
                    <img src="student_photo.php?ts=<?php echo time(); ?>" alt="Student photo" class="profile-photo">
                </div>
                <div class="profile-details">
                    <h3><?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <ul>
                        <li><strong>Email:</strong> <?php echo htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8'); ?></li>
                        <li><strong>Roll No:</strong> <?php echo htmlspecialchars($student['roll_no'], ENT_QUOTES, 'UTF-8'); ?></li>
                        <li><strong>Class:</strong> <?php echo htmlspecialchars($student['class'], ENT_QUOTES, 'UTF-8'); ?></li>
                    </ul>
                </div>
            </div>

            <div class="profile-card upload-card">
                <h3>Update Profile Photo</h3>
                <form action="student_profile.php" method="POST" enctype="multipart/form-data" class="upload-form">
                    <input type="file" name="photo" accept="image/*" required>
                    <button type="submit" class="primary-btn">Upload Photo</button>
                </form>
                <?php if (!empty($_SESSION['profile_message'])) : ?>
                    <div class="form-message <?php echo $_SESSION['profile_status'] ?? 'info'; ?>"><?php echo htmlspecialchars($_SESSION['profile_message'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php unset($_SESSION['profile_message'], $_SESSION['profile_status']); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../frontend/student_dashboard.js"></script>
</body>
</html>
