<?php
session_start();

function show_delete_error($status, $message)
{
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Unable to Remove Subject</title>
        <style>
            body { margin: 0; padding: 24px; background: #f1f5f9; color: #1e293b; font: 16px/1.5 Arial, sans-serif; }
            main { max-width: 520px; margin: 10vh auto; padding: 28px; border: 1px solid #dbe3ec; border-radius: 8px; background: #fff; }
            h1 { margin-top: 0; color: #991b1b; font-size: 1.4rem; }
            a { color: #1d4ed8; font-weight: 600; }
        </style>
    </head>
    <body>
        <main>
            <h1>Subject was not removed</h1>
            <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
            <a href="../backend/teacher_subjects.php">Return to My Subjects</a>
        </main>
    </body>
    </html>
    <?php
    exit();
}

if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../frontend/teacher_login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    show_delete_error(405, 'Use the Remove button on the My Subjects page.');
}

$subject_value = $_POST['subject_id'] ?? null;
$subject_id = is_scalar($subject_value)
    ? filter_var($subject_value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;

if ($subject_id === false) {
    show_delete_error(400, 'A valid subject was not provided.');
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!is_string($csrf_token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    show_delete_error(403, 'Your security token is invalid or expired. Refresh My Subjects and try again.');
}

require_once 'db.php';

$teacher_id = (int) $_SESSION['teacher_id'];
$transaction_started = false;

try {
    if (!mysqli_begin_transaction($conect)) {
        throw new Exception('Unable to begin the deletion transaction.');
    }
    $transaction_started = true;

    $subject_stmt = mysqli_prepare($conect, 'SELECT id FROM subjects WHERE id = ? AND teacher_id = ? FOR UPDATE');
    if (!$subject_stmt) {
        throw new Exception('Unable to verify subject ownership.');
    }
    mysqli_stmt_bind_param($subject_stmt, 'ii', $subject_id, $teacher_id);
    if (!mysqli_stmt_execute($subject_stmt)) {
        mysqli_stmt_close($subject_stmt);
        throw new Exception('Unable to verify subject ownership.');
    }
    $subject_result = mysqli_stmt_get_result($subject_stmt);
    $subject = mysqli_fetch_assoc($subject_result);
    mysqli_stmt_close($subject_stmt);

    if (!$subject) {
        mysqli_rollback($conect);
        $transaction_started = false;
        show_delete_error(404, 'The subject was not found in your account.');
    }

    $attendance_stmt = mysqli_prepare($conect, 'DELETE FROM attendance WHERE subject_id = ?');
    if (!$attendance_stmt) {
        throw new Exception('Unable to prepare attendance deletion.');
    }
    mysqli_stmt_bind_param($attendance_stmt, 'i', $subject_id);
    if (!mysqli_stmt_execute($attendance_stmt)) {
        mysqli_stmt_close($attendance_stmt);
        throw new Exception('Unable to delete attendance records.');
    }
    mysqli_stmt_close($attendance_stmt);

    $qr_stmt = mysqli_prepare($conect, 'DELETE FROM qr_session WHERE subject_id = ?');
    if (!$qr_stmt) {
        throw new Exception('Unable to prepare QR session deletion.');
    }
    mysqli_stmt_bind_param($qr_stmt, 'i', $subject_id);
    if (!mysqli_stmt_execute($qr_stmt)) {
        mysqli_stmt_close($qr_stmt);
        throw new Exception('Unable to delete QR sessions.');
    }
    mysqli_stmt_close($qr_stmt);

    $delete_subject_stmt = mysqli_prepare($conect, 'DELETE FROM subjects WHERE id = ? AND teacher_id = ?');
    if (!$delete_subject_stmt) {
        throw new Exception('Unable to prepare subject deletion.');
    }
    mysqli_stmt_bind_param($delete_subject_stmt, 'ii', $subject_id, $teacher_id);
    if (!mysqli_stmt_execute($delete_subject_stmt) || mysqli_stmt_affected_rows($delete_subject_stmt) !== 1) {
        mysqli_stmt_close($delete_subject_stmt);
        throw new Exception('Unable to delete the subject.');
    }
    mysqli_stmt_close($delete_subject_stmt);

    if (!mysqli_commit($conect)) {
        throw new Exception('Unable to commit the deletion transaction.');
    }
    $transaction_started = false;

    header('Location: teacher_subjects.php?deleted=1');
    exit();
} catch (Exception $error) {
    if ($transaction_started) {
        mysqli_rollback($conect);
    }
    error_log('Subject deletion failed: ' . $error->getMessage());
    show_delete_error(500, 'The subject and its related data could not be removed. No changes were saved. Please try again.');
}
