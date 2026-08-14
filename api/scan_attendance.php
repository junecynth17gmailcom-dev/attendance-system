<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';


// ======================================================
// TEACHER LOGIN
// ======================================================

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'teacher'
) {

    echo json_encode([
        'success' => false,
        'message' => 'Teacher login required.'
    ]);

    exit;
}


// ======================================================
// PHILIPPINES TIME
// ======================================================

date_default_timezone_set('Asia/Manila');


// ======================================================
// TEACHER
// ======================================================

$teacherId = current_user_id();


// ======================================================
// INPUT
// ======================================================

$qrToken = trim(
    $_POST['qr_token'] ?? ''
);

$subjectId = (int)(
    $_POST['subject_id'] ?? 0
);

$course = trim(
    $_POST['course'] ?? ''
);

$yearLevel = trim(
    $_POST['year_level'] ?? ''
);

$section = trim(
    $_POST['section'] ?? ''
);


// ======================================================
// VALIDATE INPUT
// ======================================================

if ($qrToken === '') {

    echo json_encode([
        'success' => false,
        'message' => 'QR code is empty.'
    ]);

    exit;
}


if ($subjectId <= 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Please select a subject.'
    ]);

    exit;
}


if (
    $course === '' ||
    $yearLevel === '' ||
    $section === ''
) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Please select Course, Year Level and Section.'
    ]);

    exit;
}


// ======================================================
// GET STUDENT FROM QR TOKEN
// ======================================================

$stmt = $pdo->prepare("

    SELECT

        s.id,
        s.student_number,
        s.year_level,
        s.course,
        s.section,
        s.status,

        u.full_name

    FROM students s

    INNER JOIN users u
        ON u.id = s.user_id

    WHERE s.qr_token = ?

    LIMIT 1

");

$stmt->execute([
    $qrToken
]);

$student = $stmt->fetch();


// ======================================================
// INVALID QR
// ======================================================

if (!$student) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Invalid QR code. Student was not found.'
    ]);

    exit;
}


// ======================================================
// CHECK STUDENT STATUS
// ======================================================

if ($student['status'] !== 'active') {

    echo json_encode([
        'success' => false,
        'message' =>
            'This student account is not active.'
    ]);

    exit;
}


// ======================================================
// CHECK COURSE
// ======================================================

if (
    $student['course'] !== $course
) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Invalid QR code for the selected course. ' .
            'Student belongs to ' .
            $student['course'] . '.'
    ]);

    exit;
}


// ======================================================
// CHECK YEAR LEVEL
// ======================================================

if (
    $student['year_level'] !== $yearLevel
) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Invalid QR code for the selected year level. ' .
            'Student belongs to ' .
            $student['year_level'] . '.'
    ]);

    exit;
}


// ======================================================
// CHECK SECTION
// ======================================================

if (
    $student['section'] !== $section
) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Invalid QR code for the selected section. ' .
            'Student belongs to ' .
            $student['section'] . '.'
    ]);

    exit;
}


// ======================================================
// CHECK SUBJECT BELONGS TO TEACHER
// ======================================================

$stmt = $pdo->prepare("

    SELECT

        id,
        subject_code,
        subject_name

    FROM subjects

    WHERE id = ?
    AND teacher_id = ?

    LIMIT 1

");

$stmt->execute([
    $subjectId,
    $teacherId
]);

$subject = $stmt->fetch();


if (!$subject) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Invalid subject. This subject does not belong to your teacher account.'
    ]);

    exit;
}


// ======================================================
// CURRENT PHILIPPINES DATE AND TIME
// ======================================================

$attendanceDate = date('Y-m-d');

$timeIn = date('H:i:s');


// ======================================================
// CHECK DUPLICATE
//
// Same student + teacher + subject + date
// ======================================================

$stmt = $pdo->prepare("

    SELECT id

    FROM attendance

    WHERE student_id = ?
    AND teacher_id = ?
    AND subject_id = ?
    AND attendance_date = ?

    LIMIT 1

");

$stmt->execute([

    (int)$student['id'],

    $teacherId,

    $subjectId,

    $attendanceDate

]);

$existing = $stmt->fetch();


if ($existing) {

    echo json_encode([

        'success' => false,

        'duplicate' => true,

        'message' =>
            'Attendance already recorded for this student, subject and teacher today.'

    ]);

    exit;
}


// ======================================================
// INSERT ATTENDANCE
// ======================================================

try {

    $stmt = $pdo->prepare("

        INSERT INTO attendance

        (
            student_id,
            teacher_id,
            subject_id,
            attendance_date,
            time_in
        )

        VALUES

        (?, ?, ?, ?, ?)

    ");


    $stmt->execute([

        (int)$student['id'],

        $teacherId,

        $subjectId,

        $attendanceDate,

        $timeIn

    ]);


    // ==================================================
    // SUCCESS
    // ==================================================

    echo json_encode([

        'success' => true,

        'message' =>
            'Attendance recorded successfully.',

        'student' => [

            'name' =>
                $student['full_name'],

            'student_number' =>
                $student['student_number'],

            'course' =>
                $student['course'],

            'year_level' =>
                $student['year_level'],

            'section' =>
                $student['section'],

            'subject' =>
                $subject['subject_code'] .
                ' - ' .
                $subject['subject_name'],

            'teacher' =>
                $_SESSION['full_name'] ?? 'Teacher',

            'time_in' =>
                date(
                    'h:i A',
                    strtotime($timeIn)
                ),

            'attendance_date' =>
                date(
                    'F d, Y',
                    strtotime($attendanceDate)
                )

        ]

    ]);

} catch (PDOException $e) {

    // Duplicate database constraint
    if ($e->getCode() === '23000') {

        echo json_encode([

            'success' => false,

            'duplicate' => true,

            'message' =>
                'Attendance has already been recorded today.'

        ]);

        exit;
    }


    echo json_encode([

        'success' => false,

        'message' =>
            'Database error: ' .
            $e->getMessage()

    ]);

}