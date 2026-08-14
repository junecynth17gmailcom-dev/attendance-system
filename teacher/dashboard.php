<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

require_login('teacher');

$teacherId = current_user_id();

$today = date('Y-m-d');


// ======================================================
// TEACHER INFORMATION
// ======================================================

$stmt = $pdo->prepare("
    SELECT
        id,
        username,
        full_name,
        email
    FROM users
    WHERE id = ?
    AND role = 'teacher'
    LIMIT 1
");

$stmt->execute([
    $teacherId
]);

$teacher = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$teacher) {

    exit('Teacher account not found.');

}


// ======================================================
// SELECT HISTORY DATE
// ======================================================

$historyDate = $_GET['history_date'] ?? $today;


// ======================================================
// VALIDATE DATE
// ======================================================

$dateObject = DateTime::createFromFormat(
    'Y-m-d',
    $historyDate
);

if (
    !$dateObject ||
    $dateObject->format('Y-m-d') !== $historyDate
) {

    $historyDate = $today;

}


// ======================================================
// TOTAL ACTIVE STUDENTS
// ======================================================

$totalStudents = (int)$pdo->query("
    SELECT COUNT(*)
    FROM students
    WHERE status = 'active'
")->fetchColumn();


// ======================================================
// PRESENT TODAY - THIS TEACHER ONLY
// ======================================================

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM attendance
    WHERE attendance_date = ?
    AND teacher_id = ?
");

$stmt->execute([
    $today,
    $teacherId
]);

$presentToday = (int)$stmt->fetchColumn();


// ======================================================
// ABSENT TODAY
// ======================================================

$absentToday = max(
    0,
    $totalStudents - $presentToday
);


// ======================================================
// TODAY'S ATTENDANCE
// ======================================================

$stmt = $pdo->prepare("
    SELECT

        a.id,
        a.attendance_date,
        a.time_in,

        u.full_name,

        s.student_number,
        s.year_level,
        s.course,
        s.section,

        sub.subject_code,
        sub.subject_name

    FROM attendance a

    INNER JOIN students s
        ON s.id = a.student_id

    INNER JOIN users u
        ON u.id = s.user_id

    LEFT JOIN subjects sub
        ON sub.id = a.subject_id

    WHERE a.attendance_date = ?
    AND a.teacher_id = ?

    ORDER BY
        s.course ASC,
        s.year_level ASC,
        s.section ASC,
        sub.subject_code ASC,
        u.full_name ASC,
        a.time_in ASC
");

$stmt->execute([
    $today,
    $teacherId
]);

$todayRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ======================================================
// GROUP TODAY'S ATTENDANCE
// COURSE + YEAR + SECTION + SUBJECT
// ======================================================

$attendanceGroups = [];

foreach ($todayRecords as $record) {

    $course = trim($record['course'] ?? '');
    $year = trim($record['year_level'] ?? '');
    $section = trim($record['section'] ?? '');
    $subjectCode = trim($record['subject_code'] ?? '');

    $groupKey =
        $course . '|' .
        $year . '|' .
        $section . '|' .
        ($subjectCode !== '' ? $subjectCode : 'NO-SUBJECT');


    if (!isset($attendanceGroups[$groupKey])) {

        $attendanceGroups[$groupKey] = [

            'course' =>
                $course,

            'year_level' =>
                $year,

            'section' =>
                $section,

            'subject_code' =>
                $subjectCode,

            'subject_name' =>
                trim($record['subject_name'] ?? ''),

            'records' => []

        ];

    }


    $attendanceGroups[$groupKey]['records'][] =
        $record;

}


// ======================================================
// ATTENDANCE HISTORY
// ======================================================

$stmt = $pdo->prepare("
    SELECT

        a.id,
        a.attendance_date,
        a.time_in,

        u.full_name,

        s.student_number,
        s.year_level,
        s.course,
        s.section,

        sub.subject_code,
        sub.subject_name

    FROM attendance a

    INNER JOIN students s
        ON s.id = a.student_id

    INNER JOIN users u
        ON u.id = s.user_id

    LEFT JOIN subjects sub
        ON sub.id = a.subject_id

    WHERE a.attendance_date = ?
    AND a.teacher_id = ?

    ORDER BY
        s.course ASC,
        s.year_level ASC,
        s.section ASC,
        sub.subject_code ASC,
        u.full_name ASC,
        a.time_in ASC
");

$stmt->execute([
    $historyDate,
    $teacherId
]);

$historyRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ======================================================
// GROUP HISTORY
// COURSE + YEAR + SECTION + SUBJECT
// ======================================================

$historyGroups = [];

foreach ($historyRecords as $record) {

    $course = trim($record['course'] ?? '');
    $year = trim($record['year_level'] ?? '');
    $section = trim($record['section'] ?? '');
    $subjectCode = trim($record['subject_code'] ?? '');

    $groupKey =
        $course . '|' .
        $year . '|' .
        $section . '|' .
        ($subjectCode !== '' ? $subjectCode : 'NO-SUBJECT');


    if (!isset($historyGroups[$groupKey])) {

        $historyGroups[$groupKey] = [

            'course' =>
                $course,

            'year_level' =>
                $year,

            'section' =>
                $section,

            'subject_code' =>
                $subjectCode,

            'subject_name' =>
                trim($record['subject_name'] ?? ''),

            'records' => []

        ];

    }


    $historyGroups[$groupKey]['records'][] =
        $record;

}


// ======================================================
// TOTAL HISTORY PRESENT
// ======================================================

$historyTotal = count($historyRecords);


// ======================================================
// REPORT SUMMARY
// ======================================================

$reportCourses = [];

foreach ($historyRecords as $record) {

    $course =
        trim($record['course'] ?? 'Unknown');

    $year =
        trim($record['year_level'] ?? 'Unknown');

    $section =
        trim($record['section'] ?? 'Unknown');


    if (!isset($reportCourses[$course])) {

        $reportCourses[$course] = [];

    }


    if (!isset($reportCourses[$course][$year])) {

        $reportCourses[$course][$year] = [];

    }


    if (!isset(
        $reportCourses[$course][$year][$section]
    )) {

        $reportCourses[$course][$year][$section] = 0;

    }


    $reportCourses[$course][$year][$section]++;

}


// ======================================================
// DATE DISPLAY
// ======================================================

$historyDateDisplay = date(
    'F d, Y',
    strtotime($historyDate)
);

?>

<!doctype html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>
Teacher Dashboard - QR Attendance
</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<link
    href="../assets/css/style.css"
    rel="stylesheet"
>


<style>

/* ======================================================
   GENERAL
====================================================== */

body {

    background: #f4f7fb;

}


/* ======================================================
   STAT CARDS
====================================================== */

.stat-card {

    background: #ffffff;

    border-radius: 15px;

    padding: 25px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.08);

    height: 100%;

}


.stat-card small {

    color: #6c757d;

    font-weight: 600;

}


.stat-card h2 {

    margin: 5px 0 0;

    font-weight: 700;

}


/* ======================================================
   ATTENDANCE CARD
====================================================== */

.attendance-card {

    border: 0;

    border-radius: 15px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.07);

    margin-bottom: 25px;

}


/* ======================================================
   GROUP TITLE
====================================================== */

.group-title {

    background: #f8f9fa;

    border-left: 5px solid #0d6efd;

    padding: 15px;

    border-radius: 8px;

    margin-bottom: 15px;

}


/* ======================================================
   HISTORY HEADER
====================================================== */

.history-header {

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #084298
        );

    color: white;

    border-radius: 15px;

    padding: 25px;

}


/* ======================================================
   REPORT CARD
====================================================== */

.report-card {

    border: none;

    border-radius: 15px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.07);

}


/* ======================================================
   REPORT COURSE
====================================================== */

.report-course {

    background: #0d6efd;

    color: white;

    padding: 15px;

    font-size: 18px;

    font-weight: 700;

}


/* ======================================================
   EMPTY BOX
====================================================== */

.empty-box {

    text-align: center;

    padding: 50px;

    color: #6c757d;

}


/* ======================================================
   PRINT RECEIPT BUTTON
====================================================== */

.print-receipt-btn {

    white-space: nowrap;

}


/* ======================================================
   PRINT AREA
====================================================== */

.print-receipt {

    display: none;

}


/* ======================================================
   PRINT
====================================================== */

@media print {

    body {

        background: white !important;

        margin: 0 !important;

        padding: 0 !important;

    }


    body * {

        visibility: hidden;

    }


    .print-receipt.print-active,
    .print-receipt.print-active * {

        visibility: visible;

    }


    .print-receipt.print-active {

        display: block !important;

        position: absolute;

        left: 0;

        top: 0;

        width: 100%;

        padding: 25px;

        background: white;

    }


    .no-print {

        display: none !important;

    }


    .print-receipt table {

        width: 100%;

        border-collapse: collapse;

        margin-top: 20px;

    }


    .print-receipt th,
    .print-receipt td {

        border: 1px solid #000;

        padding: 8px;

        font-size: 12px;

    }


    .print-receipt th {

        background: #eeeeee !important;

    }


    .print-header {

        text-align: center;

        margin-bottom: 20px;

    }


    .print-header h2 {

        margin-bottom: 5px;

    }


    .print-info {

        margin-bottom: 15px;

        line-height: 1.7;

    }


    .print-footer {

        margin-top: 50px;

        text-align: center;

    }


    .signature-line {

        display: inline-block;

        width: 250px;

        border-top: 1px solid #000;

        padding-top: 5px;

    }

}


/* ======================================================
   SCREEN ONLY
====================================================== */

@media screen {

    .print-receipt {

        display: none;

    }

}

</style>

</head>


<body class="app-bg">


<!-- ======================================================
     NAVBAR
====================================================== -->

<nav class="navbar navbar-dark app-nav">

<div class="container-fluid">


<a
    class="navbar-brand fw-bold"
    href="dashboard.php"
>

    📷 QR Attendance

</a>


<div class="text-white">

    Teacher:

    <strong>

        <?= htmlspecialchars(
            $teacher['full_name']
        ) ?>

    </strong>


    <a
        class="btn btn-sm btn-outline-light ms-2"
        href="../logout.php"
    >

        Logout

    </a>

</div>


</div>

</nav>



<!-- ======================================================
     MAIN
====================================================== -->

<div class="container py-4">


<!-- ======================================================
     PAGE HEADER
====================================================== -->

<div
    class="d-flex
    justify-content-between
    align-items-center
    mb-4"
>

<div>

<h2 class="fw-bold">

    Teacher Dashboard

</h2>

<p class="text-muted mb-0">

    <?= date('F d, Y') ?>

</p>

</div>


<a
    href="scanner.php"
    class="btn btn-success btn-lg"
>

    📷 Open QR Scanner

</a>

</div>



<!-- ======================================================
     STATISTICS
====================================================== -->

<div class="row g-3 mb-4">


<div class="col-md-4">

<div class="stat-card">

<small>
Active Students
</small>

<h2>

<?= $totalStudents ?>

</h2>

</div>

</div>


<div class="col-md-4">

<div class="stat-card">

<small>
My Present Today
</small>

<h2 class="text-success">

<?= $presentToday ?>

</h2>

</div>

</div>


<div class="col-md-4">

<div class="stat-card">

<small>
My Absent Today
</small>

<h2 class="text-danger">

<?= $absentToday ?>

</h2>

</div>

</div>


</div>



<!-- ======================================================
     TABS
====================================================== -->

<div class="card shadow-sm mb-4 no-print">

<div class="card-body">

<ul
    class="nav nav-pills nav-fill"
    id="teacherTabs"
    role="tablist"
>


<li class="nav-item">

<button
    class="nav-link active"
    data-bs-toggle="pill"
    data-bs-target="#todayTab"
    type="button"
>

    📋 Today's Attendance

</button>

</li>


<li class="nav-item">

<button
    class="nav-link"
    data-bs-toggle="pill"
    data-bs-target="#historyTab"
    type="button"
>

    🕐 Attendance History

</button>

</li>


<li class="nav-item">

<button
    class="nav-link"
    data-bs-toggle="pill"
    data-bs-target="#reportsTab"
    type="button"
>

    📊 Attendance Reports

</button>

</li>


</ul>

</div>

</div>



<div class="tab-content">



<!-- ======================================================
     TODAY
====================================================== -->

<div
    class="tab-pane fade show active"
    id="todayTab"
>


<div class="card shadow-sm">

<div class="card-body">


<div
    class="d-flex
    justify-content-between
    align-items-center
    mb-4"
>

<div>

<h4 class="fw-bold mb-1">

    Today's Attendance

</h4>

<p class="text-muted mb-0">

    Attendance records scanned by you today.

</p>

</div>


<span class="badge bg-success fs-6">

    <?= count($todayRecords) ?>

    Present

</span>


</div>



<?php if (!$attendanceGroups): ?>

<div class="alert alert-info text-center">

    📋 No attendance records for today.

</div>

<?php endif; ?>



<?php

$todayGroupNumber = 0;

foreach ($attendanceGroups as $group):

$todayGroupNumber++;

?>


<div class="card attendance-card">


<div class="card-body">


<!-- GROUP TITLE -->

<div class="group-title">


<div
    class="d-flex
    justify-content-between
    align-items-start"
>

<div>

<h5 class="fw-bold mb-1">

<?= htmlspecialchars(
    $group['course']
) ?>

-

<?= htmlspecialchars(
    $group['year_level']
) ?>

-

<?= htmlspecialchars(
    $group['section']
) ?>

</h5>


<?php if (
    $group['subject_code'] !== ''
): ?>

<div>

<strong>
Subject:
</strong>

<?= htmlspecialchars(
    $group['subject_code']
) ?>

-

<?= htmlspecialchars(
    $group['subject_name']
) ?>

</div>

<?php else: ?>

<div class="text-danger">

<strong>
Subject:
</strong>

No subject assigned

</div>

<?php endif; ?>

</div>


<button
    type="button"
    class="btn btn-outline-primary btn-sm no-print print-receipt-btn"
    onclick="printCategory(
        'todayReceipt<?= $todayGroupNumber ?>'
    )"
>

    🖨 Print Receipt

</button>


</div>


</div>



<!-- ATTENDANCE TABLE -->

<div class="table-responsive">

<table
    class="table
    table-hover
    align-middle"
>

<thead class="table-light">

<tr>

<th>
#
</th>

<th>
Student
</th>

<th>
Student No.
</th>

<th>
Course
</th>

<th>
Year
</th>

<th>
Section
</th>

<th>
Time In
</th>

<th>
Status
</th>

</tr>

</thead>


<tbody>


<?php foreach (
    $group['records']
    as $i => $record
): ?>

<tr>

<td>

<?= $i + 1 ?>

</td>


<td>

<strong>

<?= htmlspecialchars(
    $record['full_name']
) ?>

</strong>

</td>


<td>

<?= htmlspecialchars(
    $record['student_number']
) ?>

</td>


<td>

<?= htmlspecialchars(
    $record['course']
) ?>

</td>


<td>

<?= htmlspecialchars(
    $record['year_level']
) ?>

</td>


<td>

<?= htmlspecialchars(
    $record['section']
) ?>

</td>


<td>

<?= date(
    'h:i A',
    strtotime(
        $record['time_in']
    )
) ?>

</td>


<td>

<span class="badge bg-success">

Present

</span>

</td>


</tr>

<?php endforeach; ?>


</tbody>

</table>

</div>

</div>

</div>



<!-- ======================================================
     TODAY PRINT RECEIPT
====================================================== -->

<div
    id="todayReceipt<?= $todayGroupNumber ?>"
    class="print-receipt"
>


<div class="print-header">

<h2>
QR ATTENDANCE SYSTEM
</h2>

<h3>
ATTENDANCE RECEIPT
</h3>

<p>

Teacher Attendance Record

</p>

</div>


<div class="print-info">

<strong>Teacher:</strong>

<?= htmlspecialchars(
    $teacher['full_name']
) ?>

<br>


<strong>Date:</strong>

<?= date(
    'F d, Y'
) ?>

<br>


<strong>Course:</strong>

<?= htmlspecialchars(
    $group['course']
) ?>

<br>


<strong>Year Level:</strong>

<?= htmlspecialchars(
    $group['year_level']
) ?>

<br>


<strong>Section:</strong>

<?= htmlspecialchars(
    $group['section']
) ?>

<br>


<strong>Subject:</strong>

<?php if (
    $group['subject_code'] !== ''
): ?>

<?= htmlspecialchars(
    $group['subject_code']
) ?>

-

<?= htmlspecialchars(
    $group['subject_name']
) ?>

<?php else: ?>

No Subject

<?php endif; ?>

</div>


<table>

<thead>

<tr>

<th>
#
</th>

<th>
Student No.
</th>

<th>
Student Name
</th>

<th>
Time In
</th>

<th>
Status
</th>

</tr>

</thead>


<tbody>


<?php foreach (
    $group['records']
    as $i => $record
): ?>

<tr>

<td>

<?= $i + 1 ?>

</td>

<td>

<?= htmlspecialchars(
    $record['student_number']
) ?>

</td>

<td>

<?= htmlspecialchars(
    $record['full_name']
) ?>

</td>

<td>

<?= date(
    'h:i A',
    strtotime(
        $record['time_in']
    )
) ?>

</td>

<td>

PRESENT

</td>

</tr>

<?php endforeach; ?>


</tbody>

</table>


<p style="margin-top:20px;">

<strong>
Total Present:
</strong>

<?= count($group['records']) ?>

</p>


<div class="print-footer">

<div class="signature-line">

Teacher Signature

</div>

</div>


</div>


<?php endforeach; ?>


</div>

</div>


</div>



<!-- ======================================================
     ATTENDANCE HISTORY
====================================================== -->

<div
    class="tab-pane fade"
    id="historyTab"
>


<div class="history-header mb-4">


<div
    class="d-flex
    justify-content-between
    align-items-center"
>

<div>

<h3 class="fw-bold">

    🕐 Attendance History

</h3>

<p class="mb-0">

    View attendance records for a selected date.

</p>

</div>


<span class="badge bg-light text-primary fs-6">

    <?= $historyTotal ?>

    Present

</span>

</div>

</div>



<!-- DATE SELECTOR -->

<div class="card shadow-sm mb-4 no-print">

<div class="card-body">


<form
    method="GET"
    class="row g-3 align-items-end"
>


<div class="col-md-5">

<label class="form-label fw-bold">

    Select Attendance Date

</label>

<input
    type="date"
    name="history_date"
    class="form-control"
    value="<?= htmlspecialchars(
        $historyDate
    ) ?>"
    required
>

</div>


<div class="col-md-auto">

<button
    type="submit"
    class="btn btn-primary"
>

    🔍 View History

</button>

</div>


<div class="col-md-auto">

<a
    href="dashboard.php"
    class="btn btn-outline-secondary"
>

    Today

</a>

</div>


</form>

</div>

</div>



<!-- ======================================================
     HISTORY GROUPS
====================================================== -->

<?php if (!$historyGroups): ?>

<div class="card shadow-sm">

<div class="card-body empty-box">

<div style="font-size:50px;">
📋
</div>

<h4>
No Attendance Records
</h4>

<p>

There are no attendance records for

<strong>

<?= htmlspecialchars(
    $historyDateDisplay
) ?>

</strong>

</p>

</div>

</div>

<?php endif; ?>



<?php

$historyGroupNumber = 0;

foreach ($historyGroups as $group):

$historyGroupNumber++;

?>


<div class="card attendance-card">


<div class="card-body">


<!-- GROUP HEADER -->

<div class="group-title">


<div
    class="d-flex
    justify-content-between
    align-items-start"
>


<div>

<h5 class="fw-bold mb-1">

<?= htmlspecialchars(
    $group['course']
) ?>

-

<?= htmlspecialchars(
    $group['year_level']
) ?>

-

<?= htmlspecialchars(
    $group['section']
) ?>

</h5>


<?php if (
    $group['subject_code'] !== ''
): ?>

<div>

<strong>
Subject:
</strong>

<?= htmlspecialchars(
    $group['subject_code']
) ?>

-

<?= htmlspecialchars(
    $group['subject_name']
) ?>

</div>

<?php else: ?>

<div class="text-danger">

<strong>
Subject:
</strong>

No Subject

</div>

<?php endif; ?>


</div>


<button
    type="button"
    class="btn btn-primary btn-sm no-print print-receipt-btn"
    onclick="printCategory(
        'historyReceipt<?= $historyGroupNumber ?>'
    )"
>

    🖨 Print Receipt

</button>


</div>

</div>



<!-- HISTORY TABLE -->

<div class="table-responsive">

<table class="table table-hover align-middle">


<thead class="table-light">

<tr>

<th>
#
</th>

<th>
Student No.
</th>

<th>
Student Name
</th>

<th>
Course
</th>

<th>
Year Level
</th>

<th>
Section
</th>

<th>
Time In
</th>

<th>
Status
</th>

</tr>

</thead>


<tbody>


<?php foreach (
    $group['records']
    as $i => $record
): ?>


<tr>


<td>

<?= $i + 1 ?>

</td>


<td>

<strong>

<?= htmlspecialchars(
    $record['student_number']
) ?>

</strong>

</td>


<td>

<?= htmlspecialchars(
    $record['full_name']
) ?>

</td>


<td>

<?= htmlspecialchars(
    $record['course']
) ?>

</td>


<td>

<?= htmlspecialchars(
    $record['year_level']
) ?>

</td>


<td>

<?= htmlspecialchars(
    $record['section']
) ?>

</td>


<td>

<?= date(
    'h:i A',
    strtotime(
        $record['time_in']
    )
) ?>

</td>


<td>

<span class="badge bg-success">

Present

</span>

</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>

</div>

</div>

</div>



<!-- ======================================================
     HISTORY PRINT RECEIPT
====================================================== -->

<div
    id="historyReceipt<?= $historyGroupNumber ?>"
    class="print-receipt"
>


<div class="print-header">

<h2>
QR ATTENDANCE SYSTEM
</h2>

<h3>
ATTENDANCE RECEIPT
</h3>

<p>
Official Attendance Record
</p>

</div>


<div class="print-info">

<strong>Teacher:</strong>

<?= htmlspecialchars(
    $teacher['full_name']
) ?>

<br>


<strong>Date:</strong>

<?= htmlspecialchars(
    $historyDateDisplay
) ?>

<br>


<strong>Course:</strong>

<?= htmlspecialchars(
    $group['course']
) ?>

<br>


<strong>Year Level:</strong>

<?= htmlspecialchars(
    $group['year_level']
) ?>

<br>


<strong>Section:</strong>

<?= htmlspecialchars(
    $group['section']
) ?>

<br>


<strong>Subject:</strong>

<?php if (
    $group['subject_code'] !== ''
): ?>

<?= htmlspecialchars(
    $group['subject_code']
) ?>

-

<?= htmlspecialchars(
    $group['subject_name']
) ?>

<?php else: ?>

No Subject

<?php endif; ?>

</div>


<table>

<thead>

<tr>

<th>
#
</th>

<th>
Student No.
</th>

<th>
Student Name
</th>

<th>
Time In
</th>

<th>
Status
</th>

</tr>

</thead>


<tbody>


<?php foreach (
    $group['records']
    as $i => $record
): ?>

<tr>

<td>

<?= $i + 1 ?>

</td>

<td>

<?= htmlspecialchars(
    $record['student_number']
) ?>

</td>

<td>

<?= htmlspecialchars(
    $record['full_name']
) ?>

</td>

<td>

<?= date(
    'h:i A',
    strtotime(
        $record['time_in']
    )
) ?>

</td>

<td>

PRESENT

</td>

</tr>

<?php endforeach; ?>


</tbody>

</table>


<p style="margin-top:20px;">

<strong>
Total Present:
</strong>

<?= count($group['records']) ?>

</p>


<div class="print-footer">

<div class="signature-line">

Teacher Signature

</div>

</div>


</div>


<?php endforeach; ?>


</div>



<!-- ======================================================
     REPORTS
====================================================== -->

<div
    class="tab-pane fade"
    id="reportsTab"
>


<div class="card shadow-sm report-card">


<div class="card-body">


<div
    class="d-flex
    justify-content-between
    align-items-center
    mb-4"
>


<div>

<h4 class="fw-bold mb-1">

    📊 Attendance Reports

</h4>

<p class="text-muted mb-0">

    Summary for

    <strong>

        <?= htmlspecialchars(
            $historyDateDisplay
        ) ?>

    </strong>

</p>

</div>


</div>



<!-- SUMMARY -->

<div class="row g-3 mb-4">


<div class="col-md-4">

<div class="stat-card">

<small>
Selected Date
</small>

<h5 class="fw-bold mt-2">

<?= htmlspecialchars(
    $historyDateDisplay
) ?>

</h5>

</div>

</div>


<div class="col-md-4">

<div class="stat-card">

<small>
Total Present
</small>

<h2 class="text-success">

<?= $historyTotal ?>

</h2>

</div>

</div>


<div class="col-md-4">

<div class="stat-card">

<small>
Course Groups
</small>

<h2>

<?= count($reportCourses) ?>

</h2>

</div>

</div>


</div>



<!-- REPORT TABLE -->

<?php if (!$reportCourses): ?>


<div class="empty-box">

<h5>

No report data available.

</h5>

<p class="mb-0">

Select another date to view attendance.

</p>

</div>


<?php else: ?>


<?php foreach (
    $reportCourses
    as $course => $years
): ?>


<div class="card shadow-sm mb-4">


<div class="report-course">

📚

<?= htmlspecialchars(
    $course
) ?>

</div>


<div class="table-responsive">


<table class="table table-hover align-middle mb-0">


<thead class="table-light">

<tr>

<th>
Year Level
</th>

<th>
Section
</th>

<th>
Total Present
</th>

<th>
Status
</th>

</tr>

</thead>


<tbody>


<?php foreach (
    $years
    as $year => $sections
): ?>


<?php foreach (
    $sections
    as $section => $total
): ?>


<tr>


<td>

<strong>

<?= htmlspecialchars(
    $year
) ?>

</strong>

</td>


<td>

<?= htmlspecialchars(
    $section
) ?>

</td>


<td>

<span class="badge bg-primary fs-6">

<?= $total ?>

</span>

</td>


<td>

<span class="badge bg-success">

Present

</span>

</td>


</tr>


<?php endforeach; ?>


<?php endforeach; ?>


</tbody>


</table>


</div>

</div>


<?php endforeach; ?>


<?php endif; ?>


</div>

</div>


</div>


</div>


</div>



<!-- ======================================================
     BOOTSTRAP
====================================================== -->

<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>



<script>

/* ======================================================
   PRINT ONE CATEGORY / TABLE ONLY
====================================================== */

function printCategory(receiptId) {

    const receipt =
        document.getElementById(receiptId);


    if (!receipt) {

        alert(
            'Attendance receipt could not be found.'
        );

        return;

    }


    /*
    ------------------------------------------------------
    Remove previous active print class
    ------------------------------------------------------
    */

    document
        .querySelectorAll('.print-receipt')
        .forEach(function(element) {

            element.classList.remove(
                'print-active'
            );

        });


    /*
    ------------------------------------------------------
    Activate selected receipt
    ------------------------------------------------------
    */

    receipt.classList.add(
        'print-active'
    );


    /*
    ------------------------------------------------------
    Open browser print dialog
    ------------------------------------------------------
    */

    window.print();

}


/* ======================================================
   AFTER PRINT
====================================================== */

window.addEventListener(
    'afterprint',
    function() {

        document
            .querySelectorAll('.print-receipt')
            .forEach(function(element) {

                element.classList.remove(
                    'print-active'
                );

            });

    }
);

</script>


</body>

</html>