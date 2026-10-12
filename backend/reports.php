<?php
session_start();

if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../frontend/teacher_login.html");
    exit();
}

include "db.php";

$teacher_id = (int) $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$filter_subject = max(0, (int) ($_GET['subject_id'] ?? 0));
$default_from = date("Y-m-d", strtotime("-30 days"));
$default_to = date("Y-m-d");
$filter_from = is_string($_GET['from'] ?? null) ? $_GET['from'] : $default_from;
$filter_to = is_string($_GET['to'] ?? null) ? $_GET['to'] : $default_to;
$filter_error = '';

foreach (['filter_from' => $default_from, 'filter_to' => $default_to] as $variable => $fallback) {
    $date_value = $$variable;
    $date_object = DateTime::createFromFormat('!Y-m-d', $date_value);
    if (!$date_object || $date_object->format('Y-m-d') !== $date_value) {
        $$variable = $fallback;
    }
}

if ($filter_from > $filter_to) {
    $filter_error = 'The start date must be on or before the end date.';
}

$subjects_stmt = mysqli_prepare($conect, "SELECT id, subject_name FROM subjects WHERE teacher_id = ? ORDER BY subject_name");
mysqli_stmt_bind_param($subjects_stmt, "i", $teacher_id);
mysqli_stmt_execute($subjects_stmt);
$subjects_result = mysqli_stmt_get_result($subjects_stmt);
$subjects = [];
$subject_is_valid = $filter_subject === 0;

while ($subject_row = mysqli_fetch_assoc($subjects_result)) {
    $subjects[] = $subject_row;
    if ((int) $subject_row['id'] === $filter_subject) {
        $subject_is_valid = true;
    }
}
mysqli_stmt_close($subjects_stmt);

if (!$subject_is_valid) {
    $filter_error = 'Select a subject from your account.';
}

$report_res = false;
$total_records = 0;
$present_count = 0;
$absent_count = 0;

if ($filter_error === '') {
    $report_sql = "SELECT students.roll_no, students.name AS student_name, students.class,
                          subjects.subject_name, attendance.date, attendance.time, attendance.status
                   FROM attendance
                   JOIN students ON students.id = attendance.student_id
                   JOIN subjects ON subjects.id = attendance.subject_id
                   WHERE subjects.teacher_id = ? AND attendance.date BETWEEN ? AND ?";

    if ($filter_subject > 0) {
        $report_sql .= " AND attendance.subject_id = ?";
    }
    $report_sql .= " ORDER BY attendance.date DESC, attendance.time DESC";

    $report_stmt = mysqli_prepare($conect, $report_sql);
    if ($filter_subject > 0) {
        mysqli_stmt_bind_param($report_stmt, "issi", $teacher_id, $filter_from, $filter_to, $filter_subject);
    } else {
        mysqli_stmt_bind_param($report_stmt, "iss", $teacher_id, $filter_from, $filter_to);
    }
    mysqli_stmt_execute($report_stmt);
    $report_res = mysqli_stmt_get_result($report_stmt);
    $total_records = mysqli_num_rows($report_res);

    while ($stat_row = mysqli_fetch_assoc($report_res)) {
        $status = strtolower($stat_row['status']);
        if ($status === 'present') {
            $present_count++;
        } elseif ($status === 'absent') {
            $absent_count++;
        }
    }
    mysqli_data_seek($report_res, 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports</title>
    <link rel="stylesheet" href="../frontend/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-box {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            align-items: end;
        }
        .filter-grid label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }
        .filter-grid input,
        .filter-grid select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            background: #f8fafc;
        }
        .filter-grid input:focus,
        .filter-grid select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
        }
        .btn {
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: 0.3s;
        }
        .btn-blue { background: #2563eb; color: white; }
        .btn-blue:hover { background: #1d4ed8; }
        .btn-green { background: #059669; color: white; }
        .btn-green:hover { background: #047857; }
        .btn-gray { background: #f1f5f9; color: #1e3a8a; }
        .btn-gray:hover { background: #e2e8f0; }

        .report-actions {
            display: flex;
            gap: 12px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .report-table {
            background: white;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .report-table table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
        }
        .filter-error {
            margin-top: 14px;
            color: #b42318;
            font-size: 14px;
        }
        .report-table th {
            background: #eff6ff;
            padding: 14px 12px;
            text-align: left;
            font-size: 13px;
            color: #1e3a8a;
            font-weight: 700;
        }
        .report-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }
        .report-table tr:hover td { background: #f8fafc; }

        .stats-mini {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .mini-card {
            background: white;
            padding: 18px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            text-align: center;
            border-left: 4px solid #2563eb;
        }
        .mini-card h3 { font-size: 24px; color: #1e3a8a; }
        .mini-card p { font-size: 12px; color: #64748b; margin-top: 4px; }
        .mini-card.green { border-left-color: #059669; }
        .mini-card.orange { border-left-color: #d97706; }

        @media print {
            .sidebar, .top-header, .filter-box, .report-actions, .no-print { display: none !important; }
            .main-content { margin: 0 !important; padding: 0 !important; }
            .report-table { box-shadow: none; }
        }
    </style>
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
        <li><a href="teacher_profile.php"><i class="fas fa-user"></i> Profile</a></li>
        <li><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content">

    <header class="top-header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <h2><i class="fas fa-chart-bar"></i> Attendance Reports</h2>
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

    <div class="filter-box no-print">
        <form method="GET" action="reports.php">
            <div class="filter-grid">
                <div>
                    <label for="subject_id">Subject</label>
                    <select id="subject_id" name="subject_id">
                        <option value="0">All Subjects</option>
                        <?php foreach ($subjects as $s) { ?>
                            <option value="<?php echo (int) $s['id']; ?>" <?php echo ($filter_subject === (int) $s['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['subject_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div>
                    <label for="from">From Date</label>
                    <input id="from" type="date" name="from" value="<?php echo htmlspecialchars($filter_from, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div>
                    <label for="to">To Date</label>
                    <input id="to" type="date" name="to" value="<?php echo htmlspecialchars($filter_to, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div>
                    <button type="submit" class="btn btn-blue" style="width:100%;">
                        <i class="fas fa-search"></i> Apply Filter
                    </button>
                </div>
            </div>
        </form>
        <?php if ($filter_error !== '') { ?>
            <p class="filter-error" role="alert"><?php echo htmlspecialchars($filter_error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } ?>
    </div>

    <div class="stats-mini">
        <div class="mini-card">
            <h3><?php echo $total_records; ?></h3>
            <p>Total Records</p>
        </div>
        <div class="mini-card green">
            <h3><?php echo $present_count; ?></h3>
            <p>Present</p>
        </div>
        <div class="mini-card orange">
            <h3><?php echo $absent_count; ?></h3>
            <p>Absent</p>
        </div>
    </div>

    <div class="report-actions no-print">
        <a href="exports_csv.php?<?php echo htmlspecialchars(http_build_query(['subject_id' => $filter_subject, 'from' => $filter_from, 'to' => $filter_to]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-green">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-gray">
            <i class="fas fa-print"></i> Print Report
        </button>
    </div>

    <div class="report-table">
        <table>
            <thead>
                <tr>
                    <th>Roll No</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($report_res && mysqli_num_rows($report_res) > 0) { 
                    mysqli_data_seek($report_res, 0);
                    while ($row = mysqli_fetch_assoc($report_res)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['roll_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['class']); ?></td>
                        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['date']); ?></td>
                        <td><?php echo htmlspecialchars($row['time']); ?></td>
                        <td><span class="status-badge active"><?php echo htmlspecialchars($row['status']); ?></span></td>
                    </tr>
                <?php } } else { ?>
                    <tr><td colspan="7" style="text-align:center; padding:40px; color:#94a3b8;">
                        <i class="fas fa-inbox" style="font-size:40px; display:block; margin-bottom:10px;"></i>
                        No records found for the selected filters.
                    </td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</div>

<script src="../frontend/dashboard.js"></script>
</body>
</html>