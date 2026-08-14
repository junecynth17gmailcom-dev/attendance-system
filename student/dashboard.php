<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

require_login('student');

$studentUserId = current_user_id();


// ======================================================
// GET STUDENT INFORMATION
// ======================================================

$stmt = $pdo->prepare("
    SELECT
        s.id,
        s.user_id,
        s.student_number,
        s.year_level,
        s.course,
        s.section,
        s.sex,
        s.birthdate,
        s.qr_token,
        s.status,

        u.full_name,
        u.username,
        u.email

    FROM students s

    INNER JOIN users u
        ON u.id = s.user_id

    WHERE s.user_id = ?

    LIMIT 1
");

$stmt->execute([
    $studentUserId
]);

$student = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$student) {

    exit(
        'Student profile not found.'
    );
}


// ======================================================
// GET STUDENT ATTENDANCE
//
// IMPORTANT:
// There is NO a.status here.
// Every record in attendance means PRESENT.
// ======================================================

$stmt = $pdo->prepare("
    SELECT

        a.id,
        a.attendance_date,
        a.time_in,

        sub.subject_code,
        sub.subject_name,

        t.full_name AS teacher_name

    FROM attendance a

    LEFT JOIN subjects sub
        ON sub.id = a.subject_id

    LEFT JOIN users t
        ON t.id = a.teacher_id

    WHERE a.student_id = ?

    ORDER BY
        a.attendance_date DESC,
        a.time_in DESC

    LIMIT 100
");

$stmt->execute([
    (int)$student['id']
]);

$attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ======================================================
// COUNT ATTENDANCE
// ======================================================

$totalAttendance = count($attendance);


// ======================================================
// TODAY ATTENDANCE
// ======================================================

$today = date('Y-m-d');

$todayPresent = 0;

foreach ($attendance as $record) {

    if ($record['attendance_date'] === $today) {

        $todayPresent++;

    }
}


// ======================================================
// QR CODE
// ======================================================

$qrToken = $student['qr_token'];

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
        Student Dashboard
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Your CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- QRCode.js -->

    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"
    ></script>


    <style>

        body {

            background: #f4f7fb;

        }


        .student-card {

            border: 0;

            border-radius: 18px;

            box-shadow:
                0 8px 25px
                rgba(0,0,0,.08);

        }


        .profile-title {

            font-weight: 700;

        }


        .student-info {

            background: #f8f9fa;

            border-radius: 12px;

            padding: 15px;

        }


        .student-info div {

            padding: 7px 0;

            border-bottom: 1px solid #e9ecef;

        }


        .student-info div:last-child {

            border-bottom: none;

        }


        .student-info strong {

            display: inline-block;

            min-width: 110px;

        }


        .qr-box {

            width: 260px;

            min-height: 260px;

            margin: 20px auto;

            display: flex;

            align-items: center;

            justify-content: center;

            background: white;

            padding: 10px;

            border-radius: 12px;

        }


        .stat-box {

            background: white;

            border-radius: 15px;

            padding: 20px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.07);

        }


        .stat-box h2 {

            font-weight: 700;

            margin: 0;

        }


        .attendance-card {

            border: 0;

            border-radius: 18px;

            box-shadow:
                0 8px 25px
                rgba(0,0,0,.08);

        }


        .subject-badge {

            font-size: 12px;

        }


        .profile-label {

            color: #6c757d;

            font-size: 13px;

        }


    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
====================================================== -->

<nav class="navbar navbar-dark app-nav">

    <div class="container-fluid">

        <span class="navbar-brand fw-bold">

            🎓 Student Portal

        </span>


        <div class="text-white">

            <?= htmlspecialchars(
                $student['full_name']
            ) ?>


            <a
                class="btn btn-sm btn-outline-light ms-2"
                href="../logout.php"
            >

                Logout

            </a>

        </div>

    </div>

</nav>



<!-- =====================================================
     MAIN CONTAINER
====================================================== -->

<div class="container py-4">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="mb-4">

        <h2 class="fw-bold">

            Student Dashboard

        </h2>

        <p class="text-muted">

            Welcome,
            <?= htmlspecialchars(
                $student['full_name']
            ) ?>

        </p>

    </div>



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="row g-3 mb-4">


        <!-- STUDENT NUMBER -->

        <div class="col-md-4">

            <div class="stat-box">

                <small class="text-muted">
                    Student Number
                </small>

                <h5 class="fw-bold mt-2">

                    <?= htmlspecialchars(
                        $student['student_number']
                    ) ?>

                </h5>

            </div>

        </div>



        <!-- TOTAL ATTENDANCE -->

        <div class="col-md-4">

            <div class="stat-box">

                <small class="text-muted">
                    Total Attendance
                </small>

                <h2 class="mt-2">

                    <?= $totalAttendance ?>

                </h2>

            </div>

        </div>



        <!-- PRESENT TODAY -->

        <div class="col-md-4">

            <div class="stat-box">

                <small class="text-muted">
                    Present Today
                </small>

                <h2 class="mt-2 text-success">

                    <?= $todayPresent ?>

                </h2>

            </div>

        </div>


    </div>



    <!-- =================================================
         PROFILE + ATTENDANCE
    ================================================== -->

    <div class="row g-4">


        <!-- =================================================
             STUDENT PROFILE + QR
        ================================================== -->

        <div class="col-lg-4">

            <div class="card student-card h-100">

                <div class="card-body text-center p-4">


                    <!-- NAME -->

                    <h3 class="profile-title">

                        <?= htmlspecialchars(
                            $student['full_name']
                        ) ?>

                    </h3>


                    <!-- STUDENT NUMBER -->

                    <p class="text-muted">

                        <?= htmlspecialchars(
                            $student['student_number']
                        ) ?>

                    </p>



                    <!-- =================================================
                         STUDENT INFORMATION
                    ================================================== -->

                    <div class="student-info text-start mb-3">


                        <!-- SEX -->

                        <div>

                            <strong>
                                Sex:
                            </strong>

                            <?php if (
                                !empty($student['sex'])
                            ): ?>

                                <?= htmlspecialchars(
                                    $student['sex']
                                ) ?>

                            <?php else: ?>

                                <span class="text-muted">
                                    Not specified
                                </span>

                            <?php endif; ?>

                        </div>



                        <!-- BIRTHDATE -->

                        <div>

                            <strong>
                                Birthdate:
                            </strong>

                            <?php if (
                                !empty($student['birthdate'])
                            ): ?>

                                <?= htmlspecialchars(
                                    date(
                                        'F d, Y',
                                        strtotime(
                                            $student['birthdate']
                                        )
                                    )
                                ) ?>

                            <?php else: ?>

                                <span class="text-muted">
                                    Not specified
                                </span>

                            <?php endif; ?>

                        </div>



                        <!-- YEAR LEVEL -->

                        <div>

                            <strong>
                                Year Level:
                            </strong>

                            <?= htmlspecialchars(
                                $student['year_level']
                            ) ?>

                        </div>



                        <!-- COURSE -->

                        <div>

                            <strong>
                                Course:
                            </strong>

                            <?= htmlspecialchars(
                                $student['course']
                            ) ?>

                        </div>



                        <!-- SECTION -->

                        <div>

                            <strong>
                                Section:
                            </strong>

                            <?= htmlspecialchars(
                                $student['section']
                            ) ?>

                        </div>



                        <!-- EMAIL -->

                        <div>

                            <strong>
                                Email:
                            </strong>

                            <?php if (
                                !empty($student['email'])
                            ): ?>

                                <?= htmlspecialchars(
                                    $student['email']
                                ) ?>

                            <?php else: ?>

                                <span class="text-muted">
                                    Not specified
                                </span>

                            <?php endif; ?>

                        </div>



                        <!-- STATUS -->

                        <div>

                            <strong>
                                Status:
                            </strong>


                            <?php if (
                                $student['status'] === 'active'
                            ): ?>

                                <span
                                    class="badge bg-success"
                                >

                                    Active

                                </span>

                            <?php else: ?>

                                <span
                                    class="badge bg-secondary"
                                >

                                    <?= htmlspecialchars(
                                        $student['status']
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>


                    </div>



                    <!-- =================================================
                         QR CODE
                    ================================================== -->

                    <h5 class="fw-bold">

                        Your Personal QR Code

                    </h5>


                    <div
                        id="qrcode"
                        class="qr-box"
                    ></div>


                    <p class="small text-muted">

                        Show this QR code to your teacher
                        when attending class.

                    </p>


                </div>

            </div>

        </div>



        <!-- =================================================
             ATTENDANCE HISTORY
        ================================================== -->

        <div class="col-lg-8">

            <div class="card attendance-card">

                <div class="card-body p-4">


                    <div class="mb-3">

                        <h4 class="fw-bold mb-1">

                            My Attendance History

                        </h4>

                        <p class="text-muted">

                            Your attendance records,
                            teacher, and subject are
                            shown below.

                        </p>

                    </div>



                    <div class="table-responsive">


                        <table
                            class="table
                            table-hover
                            align-middle"
                        >

                            <thead>

                                <tr>

                                    <th>
                                        #
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Time In
                                    </th>

                                    <th>
                                        Subject
                                    </th>

                                    <th>
                                        Teacher
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach (
                                $attendance
                                as $i => $record
                            ): ?>


                                <tr>


                                    <!-- NUMBER -->

                                    <td>

                                        <?= $i + 1 ?>

                                    </td>



                                    <!-- DATE -->

                                    <td>

                                        <?= date(
                                            'M d, Y',
                                            strtotime(
                                                $record[
                                                    'attendance_date'
                                                ]
                                            )
                                        ) ?>

                                    </td>



                                    <!-- TIME -->

                                    <td>

                                        <?= date(
                                            'h:i A',
                                            strtotime(
                                                $record[
                                                    'time_in'
                                                ]
                                            )
                                        ) ?>

                                    </td>



                                    <!-- SUBJECT -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $record[
                                                    'subject_code'
                                                ]
                                            )
                                        ): ?>


                                            <strong>

                                                <?= htmlspecialchars(
                                                    $record[
                                                        'subject_code'
                                                    ]
                                                ) ?>

                                            </strong>


                                            <br>


                                            <small
                                                class="text-muted"
                                            >

                                                <?= htmlspecialchars(
                                                    $record[
                                                        'subject_name'
                                                    ]
                                                ) ?>

                                            </small>


                                        <?php else: ?>


                                            <span
                                                class="
                                                badge
                                                bg-warning
                                                text-dark"
                                            >

                                                No Subject

                                            </span>


                                        <?php endif; ?>

                                    </td>



                                    <!-- TEACHER -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $record[
                                                    'teacher_name'
                                                ]
                                            )
                                        ): ?>


                                            <?= htmlspecialchars(
                                                $record[
                                                    'teacher_name'
                                                ]
                                            ) ?>


                                        <?php else: ?>


                                            <span
                                                class="text-muted"
                                            >

                                                Unknown Teacher

                                            </span>


                                        <?php endif; ?>

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



                            <?php if (
                                !$attendance
                            ): ?>

                                <tr>

                                    <td
                                        colspan="6"
                                        class="
                                        text-center
                                        text-muted
                                        py-4"
                                    >

                                        No attendance
                                        records yet.

                                    </td>

                                </tr>

                            <?php endif; ?>


                            </tbody>

                        </table>


                    </div>

                </div>

            </div>

        </div>


    </div>


</div>



<!-- =====================================================
     QR CODE JAVASCRIPT
====================================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const qrContainer =
            document.getElementById(
                'qrcode'
            );


        if (
            qrContainer &&
            <?= json_encode(
                !empty($qrToken)
            ) ?>
        ) {

            new QRCode(

                qrContainer,

                {

                    text:
                        <?= json_encode(
                            $qrToken
                        ) ?>,

                    width: 240,

                    height: 240,

                    correctLevel:
                        QRCode.CorrectLevel.M

                }

            );

        }

    }
);

</script>


</body>

</html>