<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

require_login('admin');


/*
|--------------------------------------------------------------------------
| SELECT DATE
|--------------------------------------------------------------------------
*/

$date = $_GET['date'] ?? date('Y-m-d');


/*
|--------------------------------------------------------------------------
| GET ATTENDANCE RECORDS
|--------------------------------------------------------------------------
|
| We DO NOT use a.scanned_by.
|
| Teacher comes from:
|
| attendance.subject_id
|       ↓
| subjects.id
|       ↓
| subjects.teacher_id
|       ↓
| users.id
|
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        a.id AS attendance_id,

        a.attendance_date,

        a.time_in,

        s.student_number,

        s.year_level,

        s.course,

        s.section,

        u.full_name AS student_name,

        sub.id AS subject_id,

        sub.subject_code,

        sub.subject_name,

        teacher.full_name AS teacher_name

    FROM attendance a

    INNER JOIN students s
        ON s.id = a.student_id

    INNER JOIN users u
        ON u.id = s.user_id

    LEFT JOIN subjects sub
        ON sub.id = a.subject_id

    LEFT JOIN users teacher
        ON teacher.id = sub.teacher_id

    WHERE a.attendance_date = ?

    ORDER BY

        s.course ASC,

        s.year_level ASC,

        s.section ASC,

        sub.subject_code ASC,

        u.full_name ASC,

        a.time_in ASC

";


$stmt = $pdo->prepare($sql);

$stmt->execute([
    $date
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| GROUP ATTENDANCE
|--------------------------------------------------------------------------
|
| Group by:
|
| COURSE
| YEAR LEVEL
| SECTION
|
|--------------------------------------------------------------------------
*/

$groups = [];

foreach ($rows as $row) {

    $course = trim(
        $row['course'] ?? ''
    );

    $yearLevel = trim(
        $row['year_level'] ?? ''
    );

    $section = trim(
        $row['section'] ?? ''
    );


    $key =
        $course .
        '|' .
        $yearLevel .
        '|' .
        $section;


    if (!isset($groups[$key])) {

        $groups[$key] = [];

    }


    $groups[$key][] = $row;
}

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
        Attendance Reports
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Your CSS -->

    <link
        href="../assets/css/style.css"
        rel="stylesheet"
    >


    <style>

        /*
        |--------------------------------------------------------------------------
        | NORMAL PAGE
        |--------------------------------------------------------------------------
        */

        body {
            background: #f5f7fb;
        }


        .group-card {

            border: none;

            border-radius: 14px;

            overflow: hidden;

        }


        .group-header {

            background: #0d6efd;

            color: white;

            padding: 16px 20px;

        }


        .group-title {

            font-size: 18px;

            font-weight: 700;

        }


        .group-subtitle {

            font-size: 14px;

            opacity: .9;

        }


        .subject-badge {

            font-size: 13px;

        }


        .teacher-name {

            font-weight: 600;

        }


        .empty-message {

            padding: 50px;

        }


        /*
        |--------------------------------------------------------------------------
        | PRINT BUTTON
        |--------------------------------------------------------------------------
        */

        .print-button {

            min-width: 190px;

        }


        /*
        |--------------------------------------------------------------------------
        | PRINT HEADER
        |--------------------------------------------------------------------------
        */

        .print-header {

            display: none;

        }


        /*
        |--------------------------------------------------------------------------
        | PRINT SIGNATURE
        |--------------------------------------------------------------------------
        */

        .signature-area {

            display: none;

        }


        /*
        |--------------------------------------------------------------------------
        | PRINT SETTINGS
        |--------------------------------------------------------------------------
        */

        @media print {

            @page {

                size: A4 portrait;

                margin: 15mm;

            }


            body {

                background: white !important;

                font-family: Arial, Helvetica, sans-serif;

                color: #000;

            }


            /*
            | Hide normal navigation
            */

            nav,

            .no-print,

            .date-selector,

            .alert,

            .btn,

            button {

                display: none !important;

            }


            /*
            | Main container
            */

            .container {

                width: 100% !important;

                max-width: 100% !important;

                margin: 0 !important;

                padding: 0 !important;

            }


            /*
            | Print header
            */

            .print-header {

                display: block !important;

                text-align: center;

                margin-bottom: 20px;

                border-bottom: 2px solid #000;

                padding-bottom: 12px;

            }


            .print-system-title {

                font-size: 22px;

                font-weight: bold;

                text-transform: uppercase;

            }


            .print-report-title {

                font-size: 18px;

                font-weight: bold;

                margin-top: 5px;

            }


            .print-date {

                font-size: 14px;

                margin-top: 5px;

            }


            /*
            | Group card
            */

            .group-card {

                box-shadow: none !important;

                border: 1px solid #000 !important;

                border-radius: 0 !important;

                margin-bottom: 25px !important;

                page-break-inside: avoid;

            }


            /*
            | Start each class on a new page except first
            */

            .group-card:not(:first-of-type) {

                page-break-before: always;

            }


            /*
            | Group header
            */

            .group-header {

                background: white !important;

                color: #000 !important;

                border-bottom: 1px solid #000;

                padding: 10px 12px;

            }


            .group-title {

                font-size: 16px;

                font-weight: bold;

            }


            .group-subtitle {

                color: #000 !important;

                opacity: 1 !important;

                font-size: 12px;

                margin-top: 3px;

            }


            /*
            | Present badge
            */

            .group-header .badge {

                color: #000 !important;

                background: white !important;

                border: 1px solid #000;

            }


            /*
            | Attendance table
            */

            table {

                width: 100% !important;

                border-collapse: collapse !important;

            }


            th,

            td {

                border: 1px solid #000 !important;

                padding: 7px !important;

                font-size: 11px;

                color: #000 !important;

            }


            th {

                background: #eee !important;

                font-weight: bold;

            }


            /*
            | Hide subject badge styling
            */

            .subject-badge {

                color: #000 !important;

                background: white !important;

                border: none !important;

                padding: 0 !important;

            }


            .teacher-name {

                font-weight: normal;

            }


            /*
            | Signature area
            */

            .signature-area {

                display: flex !important;

                justify-content: space-between;

                margin-top: 45px;

                page-break-inside: avoid;

            }


            .signature-box {

                width: 40%;

                text-align: center;

            }


            .signature-line {

                border-top: 1px solid #000;

                margin-top: 45px;

                padding-top: 5px;

                font-size: 12px;

            }


            /*
            | Footer
            */

            .print-footer {

                display: block !important;

                text-align: center;

                font-size: 10px;

                margin-top: 20px;

                color: #555;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | PRINT FOOTER NORMAL
        |--------------------------------------------------------------------------
        */

        .print-footer {

            display: none;

        }

    </style>

</head>


<body class="app-bg">


<!-- ======================================================
     NAVBAR
====================================================== -->

<nav class="navbar navbar-dark app-nav no-print">

    <div class="container-fluid">

        <a
            class="navbar-brand fw-bold"
            href="dashboard.php"
        >

            ⚙️ Admin Portal

        </a>


        <div>

            <a
                href="dashboard.php"
                class="btn btn-sm btn-outline-light me-2"
            >

                Dashboard

            </a>


            <a
                class="btn btn-sm btn-outline-light"
                href="../logout.php"
            >

                Logout

            </a>

        </div>

    </div>

</nav>



<!-- ======================================================
     MAIN CONTENT
====================================================== -->

<div class="container py-4">


    <!-- ==================================================
         PRINT HEADER
    ================================================== -->

    <div class="print-header">

        <div class="print-system-title">

            QR CODE ATTENDANCE SYSTEM

        </div>


        <div class="print-report-title">

            ATTENDANCE REPORT

        </div>


        <div class="print-date">

            Date:

            <strong>

                <?= htmlspecialchars(
                    date(
                        'F d, Y',
                        strtotime($date)
                    )
                ) ?>

            </strong>

        </div>

    </div>



    <!-- ==================================================
         PAGE HEADER
    ================================================== -->

    <div
        class="d-flex justify-content-between align-items-center mb-4 no-print"
    >

        <div>

            <h2 class="fw-bold mb-1">

                Attendance Reports

            </h2>


            <p class="text-muted mb-0">

                Attendance records separated by
                Course, Year Level and Section.

            </p>

        </div>


        <!-- PRINT BUTTON -->

        <button
            type="button"
            class="btn btn-success print-button"
            onclick="printAttendance()"
        >

            🖨️ Print Attendance Report

        </button>

    </div>



    <!-- ==================================================
         DATE SELECTOR
    ================================================== -->

    <div class="card shadow-sm mb-4 date-selector no-print">

        <div class="card-body">

            <form
                method="GET"
                class="row g-3 align-items-end"
            >


                <div class="col-md-4">

                    <label
                        class="form-label fw-bold"
                    >

                        Attendance Date

                    </label>


                    <input
                        type="date"
                        name="date"
                        class="form-control"
                        value="<?= htmlspecialchars($date) ?>"
                        required
                    >

                </div>



                <div class="col-md-auto">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        🔍 View Attendance

                    </button>

                </div>



                <div class="col-md-auto">

                    <a
                        href="attendance.php"
                        class="btn btn-outline-secondary"
                    >

                        Today

                    </a>

                </div>



                <!-- PRINT -->

                <div class="col-md-auto">

                    <button
                        type="button"
                        class="btn btn-success"
                        onclick="printAttendance()"
                    >

                        🖨️ Print

                    </button>

                </div>


            </form>

        </div>

    </div>



    <!-- ==================================================
         SUMMARY
    ================================================== -->

    <div class="alert alert-primary no-print">

        <strong>

            Attendance Date:

        </strong>


        <?= htmlspecialchars(
            date(
                'F d, Y',
                strtotime($date)
            )
        ) ?>


        <span class="ms-3">

            <strong>

                Total Present:

            </strong>


            <?= count($rows) ?>

        </span>

    </div>



    <!-- ==================================================
         NO RECORDS
    ================================================== -->

    <?php if (!$groups): ?>

        <div class="card shadow-sm">

            <div
                class="card-body text-center empty-message"
            >

                <div
                    style="font-size:50px;"
                >

                    📋

                </div>


                <h4 class="mt-3">

                    No Attendance Records

                </h4>


                <p class="text-muted mb-0">

                    There are no attendance records for

                    <strong>

                        <?= htmlspecialchars(
                            date(
                                'F d, Y',
                                strtotime($date)
                            )
                        ) ?>

                    </strong>.

                </p>

            </div>

        </div>

    <?php endif; ?>



    <!-- ==================================================
         GROUP TABLES
    ================================================== -->

    <?php foreach ($groups as $group): ?>

        <?php

        $first = $group[0];


        $course =
            $first['course']
            ?? 'Unknown Course';


        $yearLevel =
            $first['year_level']
            ?? 'Unknown Year';


        $section =
            $first['section']
            ?? 'Unknown Section';

        ?>


        <!-- ==================================================
             GROUP CARD
        ================================================== -->

        <div
            class="card shadow-sm group-card mb-4"
        >


            <!-- GROUP HEADER -->

            <div class="group-header">

                <div
                    class="d-flex justify-content-between align-items-center"
                >

                    <div>

                        <div class="group-title">

                            <?= htmlspecialchars(
                                $course
                            ) ?>

                            -

                            <?= htmlspecialchars(
                                $yearLevel
                            ) ?>

                            -

                            <?= htmlspecialchars(
                                $section
                            ) ?>

                        </div>


                        <div class="group-subtitle">

                            Course:

                            <strong>

                                <?= htmlspecialchars(
                                    $course
                                ) ?>

                            </strong>


                            &nbsp; | &nbsp;


                            Year Level:

                            <strong>

                                <?= htmlspecialchars(
                                    $yearLevel
                                ) ?>

                            </strong>


                            &nbsp; | &nbsp;


                            Section:

                            <strong>

                                <?= htmlspecialchars(
                                    $section
                                ) ?>

                            </strong>

                        </div>

                    </div>


                    <span
                        class="badge bg-light text-primary"
                    >

                        <?= count($group) ?>

                        Present

                    </span>

                </div>

            </div>



            <!-- ==================================================
                 TABLE
            ================================================== -->

            <div class="table-responsive">

                <table
                    class="table table-hover align-middle mb-0"
                >

                    <thead class="table-light">

                        <tr>

                            <th style="width:60px;">

                                #

                            </th>


                            <th>

                                Student No.

                            </th>


                            <th>

                                Student Name

                            </th>


                            <th>

                                Subject

                            </th>


                            <th>

                                Teacher

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
                        $group
                        as $i => $row
                    ): ?>


                        <tr>


                            <!-- NUMBER -->

                            <td>

                                <?= $i + 1 ?>

                            </td>



                            <!-- STUDENT NUMBER -->

                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $row['student_number']
                                    ) ?>

                                </strong>

                            </td>



                            <!-- STUDENT NAME -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['student_name']
                                ) ?>

                            </td>



                            <!-- SUBJECT -->

                            <td>

                                <?php

                                $subjectCode =
                                    trim(
                                        $row['subject_code']
                                        ?? ''
                                    );


                                $subjectName =
                                    trim(
                                        $row['subject_name']
                                        ?? ''
                                    );

                                ?>


                                <?php if (
                                    $subjectCode !== '' ||
                                    $subjectName !== ''
                                ): ?>

                                    <span
                                        class="badge bg-primary subject-badge"
                                    >

                                        <?= htmlspecialchars(
                                            $subjectCode
                                        ) ?>

                                    </span>


                                    <?php if (
                                        $subjectName !== ''
                                    ): ?>

                                        <br>

                                        <small
                                            class="text-muted"
                                        >

                                            <?= htmlspecialchars(
                                                $subjectName
                                            ) ?>

                                        </small>

                                    <?php endif; ?>


                                <?php else: ?>

                                    <span
                                        class="badge bg-secondary"
                                    >

                                        No Subject

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- TEACHER -->

                            <td>

                                <?php

                                $teacherName =
                                    trim(
                                        $row['teacher_name']
                                        ?? ''
                                    );

                                ?>


                                <?php if (
                                    $teacherName !== ''
                                ): ?>

                                    <span
                                        class="teacher-name"
                                    >

                                        👨‍🏫

                                        <?= htmlspecialchars(
                                            $teacherName
                                        ) ?>

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="text-muted"
                                    >

                                        —

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- TIME -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $row['time_in']
                                    )
                                ) {

                                    echo htmlspecialchars(

                                        date(

                                            'h:i A',

                                            strtotime(
                                                $row['time_in']
                                            )

                                        )

                                    );

                                } else {

                                    echo '—';

                                }

                                ?>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <span
                                    class="badge bg-success"
                                >

                                    Present

                                </span>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>



            <!-- ==================================================
                 SIGNATURE AREA
            ================================================== -->

            <div class="signature-area">

                <div class="signature-box">

                    <div class="signature-line">

                        Teacher's Signature

                    </div>

                </div>


                <div class="signature-box">

                    <div class="signature-line">

                        Admin's Signature

                    </div>

                </div>

            </div>


        </div>


    <?php endforeach; ?>



    <!-- ==================================================
         PRINT FOOTER
    ================================================== -->

    <div class="print-footer">

        Generated by QR Code Attendance System

        &nbsp; | &nbsp;

        <?= date('F d, Y h:i A') ?>

    </div>


</div>



<!-- ======================================================
     BOOTSTRAP
====================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>



<!-- ======================================================
     PRINT JAVASCRIPT
====================================================== -->

<script>

/*
|--------------------------------------------------------------------------
| PRINT ATTENDANCE REPORT
|--------------------------------------------------------------------------
*/

function printAttendance() {

    window.print();

}


/*
|--------------------------------------------------------------------------
| OPTIONAL: CTRL + P
|--------------------------------------------------------------------------
|
| Browser already supports Ctrl + P.
|
*/

</script>


</body>

</html>