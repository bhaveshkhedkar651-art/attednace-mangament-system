<?php
session_start();

if (empty($_SESSION['teacher_id'])) {
    header('Location: ../frontend/teacher_login.html');
    exit();
}

require_once 'db.php';

$teacher_id = (int) $_SESSION['teacher_id'];
$teacher_stmt = mysqli_prepare($conect, 'SELECT id, name, email, password FROM teacher WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($teacher_stmt, 'i', $teacher_id);
mysqli_stmt_execute($teacher_stmt);
$teacher_result = mysqli_stmt_get_result($teacher_stmt);
$teacher = mysqli_fetch_assoc($teacher_result);
mysqli_stmt_close($teacher_stmt);

if (!$teacher) {
    session_destroy();
    header('Location: ../frontend/teacher_login.html');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!is_string($csrf_token) || !hash_equals($_SESSION['teacher_profile_csrf'] ?? '', $csrf_token)) {
        $_SESSION['teacher_profile_flash'] = ['type' => 'error', 'message' => 'Your form expired. Please try again.'];
        header('Location: teacher_profile.php');
        exit();
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

        if ($name === '' || strlen($name) > 100 || !$email || strlen($email) > 100) {
            $_SESSION['teacher_profile_flash'] = ['type' => 'error', 'message' => 'Enter a name and a valid email address (maximum 100 characters each).'];
        } else {
            $duplicate_stmt = mysqli_prepare($conect, 'SELECT id FROM teacher WHERE email = ? AND id <> ? LIMIT 1');
            mysqli_stmt_bind_param($duplicate_stmt, 'si', $email, $teacher_id);
            mysqli_stmt_execute($duplicate_stmt);
            $duplicate_result = mysqli_stmt_get_result($duplicate_stmt);
            $email_in_use = mysqli_fetch_assoc($duplicate_result);
            mysqli_stmt_close($duplicate_stmt);

            if ($email_in_use) {
                $_SESSION['teacher_profile_flash'] = ['type' => 'error', 'message' => 'That email address is already in use.'];
            } else {
                $update_stmt = mysqli_prepare($conect, 'UPDATE teacher SET name = ?, email = ? WHERE id = ?');
                mysqli_stmt_bind_param($update_stmt, 'ssi', $name, $email, $teacher_id);
                $updated = mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);

                if ($updated) {
                    $_SESSION['teacher_name'] = $name;
                    $_SESSION['teacher_profile_flash'] = ['type' => 'success', 'message' => 'Profile details updated.'];
                } else {
                    $_SESSION['teacher_profile_flash'] = ['type' => 'error', 'message' => 'Unable to update profile details. Please try again.'];
                }
            }
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!password_verify($current_password, $teacher['password'])) {
            $_SESSION['teacher_profile_flash'] = ['type' => 'error', 'message' => 'Current password is incorrect.'];
        } elseif (strlen($new_password) < 8) {
            $_SESSION['teacher_profile_flash'] = ['type' => 'error', 'message' => 'New password must be at least 8 characters.'];
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['teacher_profile_flash'] = ['type' => 'error', 'message' => 'The new passwords do not match.'];
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $password_stmt = mysqli_prepare($conect, 'UPDATE teacher SET password = ? WHERE id = ?');
            mysqli_stmt_bind_param($password_stmt, 'si', $password_hash, $teacher_id);
            $password_updated = mysqli_stmt_execute($password_stmt);
            mysqli_stmt_close($password_stmt);

            if ($password_updated) {
                session_regenerate_id(true);
                $_SESSION['teacher_profile_flash'] = ['type' => 'success', 'message' => 'Password changed successfully.'];
            } else {
                $_SESSION['teacher_profile_flash'] = ['type' => 'error', 'message' => 'Unable to change password. Please try again.'];
            }
        }
    } else {
        $_SESSION['teacher_profile_flash'] = ['type' => 'error', 'message' => 'Unknown profile action.'];
    }

    header('Location: teacher_profile.php');
    exit();
}

if (empty($_SESSION['teacher_profile_csrf'])) {
    $_SESSION['teacher_profile_csrf'] = bin2hex(random_bytes(32));
}

$flash = $_SESSION['teacher_profile_flash'] ?? null;
unset($_SESSION['teacher_profile_flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Profile</title>
    <link rel="stylesheet" href="../frontend/dashboard.css">
    <link rel="stylesheet" href="../frontend/teacher_profile.css">
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
        <li><a href="teacher_subjects.php"><i class="fas fa-qrcode"></i> Generate QR</a></li>
        <li><a href="live_attendance.php"><i class="fas fa-users"></i> Live Attendance</a></li>
        <li><a href="teacher_profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
        <li><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<main class="main-content">
    <header class="top-header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()" type="button" aria-label="Toggle navigation"><i class="fas fa-bars"></i></button>
            <h2>Teacher Profile</h2>
        </div>
        <div class="header-right">
            <div class="teacher-info">
                <div class="avatar"><?php echo htmlspecialchars((string) strtoupper(substr($teacher['name'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div>
                <div>
                    <div class="teacher-name"><?php echo htmlspecialchars((string) $teacher['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="teacher-id">ID: #<?php echo $teacher_id; ?></div>
                </div>
            </div>
        </div>
    </header>

    <?php if ($flash) : ?>
        <div class="profile-message <?php echo htmlspecialchars((string) $flash['type'], ENT_QUOTES, 'UTF-8'); ?>" role="<?php echo $flash['type'] === 'error' ? 'alert' : 'status'; ?>">
            <?php echo htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="profile-grid">
        <section class="profile-panel" aria-labelledby="account-heading">
            <div class="profile-panel-heading">
                <div class="profile-icon"><i class="fas fa-id-card"></i></div>
                <div>
                    <h3 id="account-heading">Account details</h3>
                    <p>Manage your teacher account information.</p>
                </div>
            </div>
            <form class="profile-form" method="POST" action="teacher_profile.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $_SESSION['teacher_profile_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="update_profile">
                <div class="profile-field">
                    <label for="teacher-id">Teacher ID</label>
                    <input id="teacher-id" type="text" value="#<?php echo $teacher_id; ?>" readonly>
                </div>
                <div class="profile-field">
                    <label for="teacher-name">Name</label>
                    <input id="teacher-name" type="text" name="name" value="<?php echo htmlspecialchars((string) $teacher['name'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="100" autocomplete="name" required>
                </div>
                <div class="profile-field">
                    <label for="teacher-email">Email</label>
                    <input id="teacher-email" type="email" name="email" value="<?php echo htmlspecialchars((string) $teacher['email'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="100" autocomplete="email" required>
                </div>
                <button class="profile-button" type="submit"><i class="fas fa-floppy-disk" aria-hidden="true"></i> Save details</button>
            </form>
        </section>

        <section class="profile-panel" aria-labelledby="password-heading">
            <div class="profile-panel-heading">
                <div class="profile-icon password-icon"><i class="fas fa-lock"></i></div>
                <div>
                    <h3 id="password-heading">Change password</h3>
                    <p>Confirm your current password to set a new one.</p>
                </div>
            </div>
            <form class="profile-form" method="POST" action="teacher_profile.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $_SESSION['teacher_profile_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="change_password">
                <div class="profile-field">
                    <label for="current-password">Current password</label>
                    <input id="current-password" type="password" name="current_password" autocomplete="current-password" required>
                </div>
                <div class="profile-field">
                    <label for="new-password">New password</label>
                    <input id="new-password" type="password" name="new_password" minlength="8" autocomplete="new-password" required>
                </div>
                <div class="profile-field">
                    <label for="confirm-password">Confirm new password</label>
                    <input id="confirm-password" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>
                </div>
                <button class="profile-button password-button" type="submit"><i class="fas fa-key" aria-hidden="true"></i> Update password</button>
            </form>
        </section>
    </div>
</main>
<script src="../frontend/dashboard.js"></script>
</body>
</html>
