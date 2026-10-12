<?php
session_start();

if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../frontend/teacher_login.html");
    exit();
}

include "db.php";

function respondToSubjectForm($success, $message, $status = 400) {
    $is_ajax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

    if ($is_ajax) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit();
    }

    $_SESSION['subject_flash'] = [
        'type' => $success ? 'success' : 'error',
        'message' => $message,
    ];
    header('Location: teacher_subjects.php');
    exit();
}

$subject_value = $_POST['subjects'] ?? '';
$subjects = is_string($subject_value) ? trim($subject_value) : '';
$teacher = (int) $_SESSION['teacher_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondToSubjectForm(false, 'Submit this form from the My Subjects page.', 400);
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!is_string($csrf_token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    respondToSubjectForm(false, 'Your security token is invalid or expired. Refresh My Subjects and try again.', 403);
}




if ($subjects === '' || strlen($subjects) > 100) {
    respondToSubjectForm(false, 'Please enter a subject name between 1 and 100 characters.', 400);
}


if (!preg_match('/^[A-Za-z0-9+#.\- ]+$/', $subjects)) {
    respondToSubjectForm(false, 'Subject names can use letters, numbers, spaces, and + # . - characters.', 400);
}



$subjects = ucwords(strtolower($subjects));


$dup_stmt = mysqli_prepare($conect, "SELECT id FROM subjects WHERE teacher_id = ? AND LOWER(subject_name) = LOWER(?) LIMIT 1");
mysqli_stmt_bind_param($dup_stmt, "is", $teacher, $subjects);
mysqli_stmt_execute($dup_stmt);
$dup_result = mysqli_stmt_get_result($dup_stmt);
$is_duplicate = mysqli_num_rows($dup_result) > 0;
mysqli_stmt_close($dup_stmt);

if ($is_duplicate) {
    respondToSubjectForm(false, 'This subject is already selected.', 409);
}


$stmt = mysqli_prepare($conect, "INSERT INTO subjects (subject_name, teacher_id) VALUES (?, ?)");
if (!$stmt) {
    error_log('Subject insert prepare failed: ' . mysqli_error($conect));
    respondToSubjectForm(false, 'Unable to save the subject. Please try again.', 500);
}
mysqli_stmt_bind_param($stmt, "si", $subjects, $teacher);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    respondToSubjectForm(true, 'Subject added successfully.', 200);
} else {
    error_log('Subject insert failed: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    respondToSubjectForm(false, 'Unable to save the subject. Please try again.', 500);
}
?>