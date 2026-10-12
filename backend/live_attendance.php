<?php
session_start();

if (!isset($_SESSION['teacher_id'])) {
    http_response_code(401);
    exit('<tr><td colspan="5" style="text-align:center; padding:20px; color:#991b1b;">Unauthorized</td></tr>');
}

require_once "db.php";

$teacher_id = (int) $_SESSION['teacher_id'];
$subject_id = max(0, (int) ($_GET['subject_id'] ?? 0));


if ($subject_id > 0) {
    $own_stmt = mysqli_prepare($conect, "SELECT id FROM subjects WHERE id = ? AND teacher_id = ? LIMIT 1");
    mysqli_stmt_bind_param($own_stmt, "ii", $subject_id, $teacher_id);
    mysqli_stmt_execute($own_stmt);
    $own_res = mysqli_stmt_get_result($own_stmt);
    $owns = mysqli_num_rows($own_res) === 1;
    mysqli_stmt_close($own_stmt);

    if (!$owns) {
        exit('<tr><td colspan="5" style="text-align:center; padding:20px; color:#991b1b;">Subject not found.</td></tr>');
    }
}

$today_start = date("Y-m-d 00:00:00");
$today_end   = date("Y-m-d 23:59:59");
$cols        = $subject_id === 0 ? 5 : 4;

if ($subject_id > 0) {
    $stmt = mysqli_prepare($conect,
        "SELECT subjects.subject_name, students.roll_no, students.name, attendance.time, attendance.status
         FROM attendance
         JOIN students ON students.id = attendance.student_id
         JOIN subjects ON subjects.id = attendance.subject_id
         WHERE subjects.teacher_id = ? AND attendance.subject_id = ? AND attendance.date BETWEEN ? AND ?
         ORDER BY attendance.id DESC");
    mysqli_stmt_bind_param($stmt, "iiss", $teacher_id, $subject_id, $today_start, $today_end);
} else {
    $stmt = mysqli_prepare($conect,
        "SELECT subjects.subject_name, students.roll_no, students.name, attendance.time, attendance.status
         FROM attendance
         JOIN students ON students.id = attendance.student_id
         JOIN subjects ON subjects.id = attendance.subject_id
         WHERE subjects.teacher_id = ? AND attendance.date BETWEEN ? AND ?
         ORDER BY attendance.id DESC");
    mysqli_stmt_bind_param($stmt, "iss", $teacher_id, $today_start, $today_end);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Attendance</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f4f6;
            color: #111827;
        }
        .attendance-shell {
            max-width: 1100px;
            margin: 32px auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }
        .attendance-header {
            padding: 18px 24px;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #ffffff;
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f8fafc;
            color: #334155;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        td {
            font-size: 0.98rem;
            background: #ffffff;
        }
        tr:hover td {
            background: #f8fafc;
        }
        .empty-message {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
            font-size: 1rem;
        }
        .status-pill {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            min-width: 85px;
            text-align: center;
        }
        @media (max-width: 768px) {
            .attendance-shell {
                margin: 16px;
                border-radius: 12px;
            }
            th, td {
                padding: 10px 12px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="attendance-shell">
        <div class="attendance-header">Live Attendance</div>
        <table>
            <thead>
                <tr>';

    if ($subject_id === 0) {
        echo '<th>Subject</th>';
    }

    echo '<th>Roll No</th>
                <th>Student Name</th>
                <th>Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>';
}

if (mysqli_num_rows($result) === 0) {
    if ($is_ajax) {
        echo '<tr><td colspan="' . $cols . '" style="text-align:center; padding:40px; color:#94a3b8;">'
           . '<i class="fas fa-inbox" style="font-size:40px; display:block; margin-bottom:10px;"></i>'
           . 'No attendance recorded today.</td></tr>';
    } else {
        echo '<tr><td colspan="' . $cols . '" class="empty-message">No attendance recorded today.</td></tr>';
    }
    mysqli_stmt_close($stmt);
    if (!$is_ajax) {
        echo '</tbody></table></div></body></html>';
    }
    exit;
}

while ($row = mysqli_fetch_assoc($result)) {
    echo '<tr>';
    if ($subject_id === 0) {
        echo '<td>' . htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8') . '</td>';
    }
    echo '<td>' . htmlspecialchars($row['roll_no'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($row['time'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td><span class="status-pill">' . htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') . '</span></td>';
    echo '</tr>';
}

mysqli_stmt_close($stmt);

if (!$is_ajax) {
    echo '</tbody></table></div></body></html>';
}