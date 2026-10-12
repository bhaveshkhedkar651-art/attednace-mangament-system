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
$filter_subject = max(0, (int) ($_GET['subject_id'] ?? 0));
$filter_from = isset($_GET['from']) && $_GET['from'] !== '' ? trim($_GET['from']) : '';
$filter_to = isset($_GET['to']) && $_GET['to'] !== '' ? trim($_GET['to']) : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$subject_stmt = mysqli_prepare($conect,
    "SELECT DISTINCT s.id, s.subject_name
     FROM attendance a
     JOIN subjects s ON s.id = a.subject_id
     WHERE a.student_id = ?
     ORDER BY s.subject_name"
);
mysqli_stmt_bind_param($subject_stmt, 'i', $student_id);
mysqli_stmt_execute($subject_stmt);
$subject_result = mysqli_stmt_get_result($subject_stmt);
$subject_options = [];
while ($row = mysqli_fetch_assoc($subject_result)) {
    $subject_options[] = $row;
}
mysqli_stmt_close($subject_stmt);

$filter_sql = "SELECT a.id, s.subject_name, a.date, a.time, a.status
               FROM attendance a
               JOIN subjects s ON s.id = a.subject_id
               WHERE a.student_id = ?";
$params = [$student_id];
$types = 'i';

if ($filter_subject > 0) {
    $filter_sql .= ' AND a.subject_id = ?';
    $params[] = $filter_subject;
    $types .= 'i';
}

if ($filter_from !== '' && $filter_to !== '') {
    $filter_sql .= ' AND a.date BETWEEN ? AND ?';
    $params[] = $filter_from;
    $params[] = $filter_to;
    $types .= 'ss';
}

$count_sql = $filter_sql . ' ORDER BY a.date DESC, a.time DESC';
$count_stmt = mysqli_prepare($conect, $count_sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_records = mysqli_num_rows($count_result);
mysqli_stmt_close($count_stmt);

$filter_sql .= ' ORDER BY a.date DESC, a.time DESC LIMIT ? OFFSET ?';
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$records_stmt = mysqli_prepare($conect, $filter_sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($records_stmt, $types, ...$params);
}
mysqli_stmt_execute($records_stmt);
$records_result = mysqli_stmt_get_result($records_stmt);
$total_pages = max(1, (int) ceil($total_records / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance</title>
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
            <li><a href="student_attendance.php" class="active"><i class="fas fa-calendar-check"></i> My Attendance</a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="../frontend/change_password.html"><i class="fas fa-lock"></i> Change Password</a></li>
            <li><a href="student_logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button class="menu-btn" onclick="toggleSidebar()" type="button"><i class="fas fa-bars"></i></button>
                <h2>My Attendance</h2>
            </div>
            <div class="header-right">
                <div class="student-info">
                    <div class="avatar">
                        <img src="student_photo.php" alt="Student photo">
                    </div>
                    <div>
                        <div class="student-name"><?php echo htmlspecialchars($_SESSION['student_name'] ?? 'Student', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="student-id">Attendance History</div>
                    </div>
                </div>
            </div>
        </header>

        <section class="content-section">
            <div class="filter-box">
                <form method="GET" action="student_attendance.php" class="filter-form">
                    <div class="filter-grid">
                        <div>
                            <label for="subject_id">Subject</label>
                            <select id="subject_id" name="subject_id">
                                <option value="0">All Subjects</option>
                                <?php foreach ($subject_options as $subject) : ?>
                                    <option value="<?php echo (int) $subject['id']; ?>" <?php echo $filter_subject === (int) $subject['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['subject_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="from">From</label>
                            <input id="from" type="date" name="from" value="<?php echo htmlspecialchars($filter_from, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div>
                            <label for="to">To</label>
                            <input id="to" type="date" name="to" value="<?php echo htmlspecialchars($filter_to, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div>
                            <button type="submit" class="primary-btn">Apply</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($records_result) > 0) : ?>
                            <?php while ($row = mysqli_fetch_assoc($records_result)) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['time'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="status-pill"><?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr><td colspan="4" class="empty">No attendance records match your filter.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_records > $per_page) : ?>
                <div class="pagination">
                    <?php if ($page > 1) : ?>
                        <a href="student_attendance.php?subject_id=<?php echo (int) $filter_subject; ?>&from=<?php echo urlencode($filter_from); ?>&to=<?php echo urlencode($filter_to); ?>&page=<?php echo max(1, $page - 1); ?>">Previous</a>
                    <?php endif; ?>
                    <span>Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                    <?php if ($page < $total_pages) : ?>
                        <a href="student_attendance.php?subject_id=<?php echo (int) $filter_subject; ?>&from=<?php echo urlencode($filter_from); ?>&to=<?php echo urlencode($filter_to); ?>&page=<?php echo min($total_pages, $page + 1); ?>">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script src="../frontend/student_dashboard.js"></script>
</body>
</html>
<?php mysqli_stmt_close($records_stmt); ?>
