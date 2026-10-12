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
    http_response_code(401);
    exit();
}

require_once 'db.php';

$student_id = (int) $_SESSION['student_id'];
$limit = max(1, (int) ($_GET['limit'] ?? 10));

$stmt = mysqli_prepare($conect,
    "SELECT s.subject_name, a.date, a.time, a.status
     FROM attendance a
     JOIN subjects s ON s.id = a.subject_id
     WHERE a.student_id = ?
     ORDER BY a.id DESC
     LIMIT ?"
);
mysqli_stmt_bind_param($stmt, 'ii', $student_id, $limit);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo '<tr><td colspan="4" class="empty">No attendance records yet.</td></tr>';
    mysqli_stmt_close($stmt);
    exit();
}

while ($row = mysqli_fetch_assoc($result)) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($row['date'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($row['time'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td><span class="status-pill">' . htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') . '</span></td>';
    echo '</tr>';
}

mysqli_stmt_close($stmt);
