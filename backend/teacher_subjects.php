<?php
session_start();

if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../frontend/teacher_login.html");
    exit();
}

include "db.php";

$teacher_id = (int) $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
$subject_flash = $_SESSION['subject_flash'] ?? null;
unset($_SESSION['subject_flash']);

$subjects_stmt = mysqli_prepare($conect, "SELECT id, subject_name FROM subjects WHERE teacher_id = ? ORDER BY subject_name");
mysqli_stmt_bind_param($subjects_stmt, "i", $teacher_id);
mysqli_stmt_execute($subjects_stmt);
$result = mysqli_stmt_get_result($subjects_stmt);


$existing_subjects = [];
$subjects_for_grid = [];
while ($row = mysqli_fetch_assoc($result)) {
    $subjects_for_grid[] = $row;
    $existing_subjects[] = $row['subject_name'];
}
mysqli_stmt_close($subjects_stmt);


$common_subjects = ['DBMS', 'C#', 'Python', 'Java', 'PHP', 'C++', 'HTML', 'CSS', 'JavaScript', 'SQL', 'Networking', 'OS'];
$all_suggestions = array_unique(array_merge($common_subjects, $existing_subjects));
sort($all_suggestions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subjects</title>
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
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="teacher_subjects.php" class="active"><i class="fas fa-book"></i> My Subjects</a></li>
        <li><a href="teacher_subjects.php"><i class="fas fa-qrcode"></i> Generate QR</a></li>
        <li><a href="live_attendance.php"><i class="fas fa-users"></i> Live Attendance</a></li>
        <li><a href="teacher_profile.php"><i class="fas fa-user"></i> Profile</a></li>
        <li><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <header class="top-header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <h2><i class="fas fa-book"></i> My Subjects</h2>
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

    <div class="subject-card" style="margin-bottom:25px;">
        <h3 style="margin-bottom:15px; color:#1e3a8a;">➕ Add New Subject</h3>
        <form action="save_subject.php" method="POST" style="display:flex; gap:10px; flex-wrap:wrap;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

            <input
                type="text"
                name="subjects"
                list="subject-list"
                placeholder="Select or type a new subject (e.g. PHP, C++)"
                required
                maxlength="100"
                autocomplete="off"
                style="flex:1; min-width:220px; padding:11px; border-radius:8px; border:1px solid #cbd5e1; font-size:14px;"
            >
            <datalist id="subject-list">
                <?php foreach ($all_suggestions as $suggestion) { ?>
                    <option value="<?php echo htmlspecialchars($suggestion, ENT_QUOTES, 'UTF-8'); ?>">
                <?php } ?>
            </datalist>

            <button type="submit" class="btn-primary" style="flex:0 0 auto; padding:11px 25px; border:none; cursor:pointer;">
                <i class="fas fa-plus"></i> Add Subject
            </button>
        </form>
        <p style="margin-top:10px; font-size:12px; color:#64748b;">
            
        </p>
    </div>

    <h3 style="margin-bottom:15px; color:#1e3a8a;">📚 Your Subjects</h3>

    <?php if (count($subjects_for_grid) > 0) { ?>
        <div class="subject-grid">
            <?php foreach ($subjects_for_grid as $row) { ?>
                <div class="subject-card">
                    <div class="card-header">
                        <div class="subject-icon"><i class="fas fa-book"></i></div>
                        <span class="status-badge active">Active</span>
                    </div>
                    <h3><?php echo htmlspecialchars($row['subject_name']); ?></h3>
                    <p class="subject-meta">Subject ID: #<?php echo $row['id']; ?></p>
                    <div class="card-actions">
                        <a href="generate_qr.php?subject_id=<?php echo $row['id']; ?>" class="btn-primary">
                            <i class="fas fa-qrcode"></i> Generate QR
                        </a>
                        <a href="live_attendance.php?subject_id=<?php echo $row['id']; ?>" class="btn-secondary">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <form action="delete_subject.php" method="POST" class="remove-subject-form" onsubmit="return confirm('Are you sure you want to remove this subject? All related attendance and QR data will be deleted permanently.');">
                            <input type="hidden" name="subject_id" value="<?php echo (int) $row['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn-remove">
                                <i class="fas fa-trash-alt" aria-hidden="true"></i> Remove
                            </button>
                        </form>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>No Subjects Found</h3>
            <p>You haven't added any subjects yet. Add your first subject above.</p>
        </div>
    <?php } ?>
</div>

<div id="toast" class="toast" role="status" aria-live="polite"
    data-message="<?php echo htmlspecialchars($subject_flash['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
    data-type="<?php echo htmlspecialchars($subject_flash['type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
<script src="../frontend/dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>