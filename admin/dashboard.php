<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

require_login('admin');


// ======================================================
// BASIC COUNTS
// ======================================================

$totalStudents = (int)$pdo->query("
    SELECT COUNT(*)
    FROM students
")->fetchColumn();


$totalTeachers = (int)$pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'teacher'
")->fetchColumn();


$totalAttendance = (int)$pdo->query("
    SELECT COUNT(*)
    FROM attendance
")->fetchColumn();


$today = date('Y-m-d');


// ======================================================
// PRESENT TODAY
// ======================================================

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM attendance
    WHERE attendance_date = ?
");

$stmt->execute([
    $today
]);

$todayPresent = (int)$stmt->fetchColumn();


// ======================================================
// ATTENDANCE FOR LAST 7 DAYS
// ======================================================

$startDate = date(
    'Y-m-d',
    strtotime('-6 days')
);

$stmt = $pdo->prepare("
    SELECT
        attendance_date,
        COUNT(*) AS total
    FROM attendance
    WHERE attendance_date BETWEEN ? AND ?
    GROUP BY attendance_date
    ORDER BY attendance_date ASC
");

$stmt->execute([
    $startDate,
    $today
]);

$attendanceData = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ======================================================
// CREATE LAST 7 DAYS DATA
// This also displays dates with zero attendance.
// ======================================================

$dailyAttendance = [];

for ($i = 6; $i >= 0; $i--) {

    $date = date(
        'Y-m-d',
        strtotime("-{$i} days")
    );

    $dailyAttendance[$date] = 0;
}


foreach ($attendanceData as $row) {

    $date = $row['attendance_date'];

    if (isset($dailyAttendance[$date])) {

        $dailyAttendance[$date] =
            (int)$row['total'];
    }
}


// ======================================================
// GRAPH LABELS
// ======================================================

$chartLabels = [];
$chartValues = [];

foreach ($dailyAttendance as $date => $total) {

    $chartLabels[] = date(
        'M d',
        strtotime($date)
    );

    $chartValues[] = $total;
}


// ======================================================
// RECENT ATTENDANCE
// ======================================================

$stmt = $pdo->query("
    SELECT
        a.attendance_date,
        a.time_in,

        u.full_name,

        s.student_number,
        s.course,
        s.year_level,
        s.section

    FROM attendance a

    INNER JOIN students s
        ON s.id = a.student_id

    INNER JOIN users u
        ON u.id = s.user_id

    ORDER BY
        a.attendance_date DESC,
        a.time_in DESC

    LIMIT 8
");

$recentAttendance =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

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
Admin Dashboard
</title>


<!-- BOOTSTRAP -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- STYLE -->

<link
    href="../assets/css/style.css"
    rel="stylesheet"
>


<!-- CHART.JS -->

<script
    src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"
></script>


<style>

/* ======================================================
   ADMIN DASHBOARD
====================================================== */

.admin-dashboard {

    padding-bottom: 50px;

}


/* ======================================================
   WELCOME HEADER
====================================================== */

.dashboard-header {

    background:
        linear-gradient(
            135deg,
            #0f172a,
            #1d4ed8
        );

    color: white;

    border-radius: 24px;

    padding: 30px;

    margin-bottom: 25px;

    box-shadow:
        0 15px 40px
        rgba(15, 23, 42, .18);

}


.dashboard-header h2 {

    font-weight: 800;

    margin-bottom: 5px;

}


.dashboard-header p {

    opacity: .75;

    margin-bottom: 0;

}


/* ======================================================
   STAT CARDS
====================================================== */

.admin-stat {

    position: relative;

    overflow: hidden;

    background: white;

    border-radius: 20px;

    padding: 24px;

    min-height: 150px;

    box-shadow:
        0 8px 30px
        rgba(15, 23, 42, .07);

    transition:
        transform .2s ease,
        box-shadow .2s ease;

}


.admin-stat:hover {

    transform:
        translateY(-5px);

    box-shadow:
        0 15px 35px
        rgba(15, 23, 42, .12);

}


.admin-stat-icon {

    width: 52px;

    height: 52px;

    border-radius: 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

    margin-bottom: 15px;

}


.admin-stat small {

    color: #64748b;

    font-weight: 600;

}


.admin-stat h2 {

    font-size: 34px;

    font-weight: 800;

    margin: 5px 0 0;

}


/* ======================================================
   STAT COLORS
====================================================== */

.stat-blue
.admin-stat-icon {

    background:
        #dbeafe;

}


.stat-green
.admin-stat-icon {

    background:
        #dcfce7;

}


.stat-orange
.admin-stat-icon {

    background:
        #ffedd5;

}


.stat-purple
.admin-stat-icon {

    background:
        #ede9fe;

}


/* ======================================================
   CHART CARD
====================================================== */

.chart-card {

    background: white;

    border-radius: 22px;

    border: 0;

    box-shadow:
        0 8px 30px
        rgba(15, 23, 42, .07);

    overflow: hidden;

}


.chart-header {

    padding: 24px 25px 10px;

}


.chart-header h4 {

    font-weight: 800;

    margin-bottom: 3px;

}


.chart-header p {

    color: #64748b;

    margin-bottom: 0;

}


.chart-container {

    position: relative;

    height: 330px;

    padding: 10px 25px 25px;

}


/* ======================================================
   MENU CARDS
====================================================== */

.admin-menu-card {

    display: block;

    text-decoration: none;

    background: white;

    border-radius: 20px;

    padding: 24px;

    height: 100%;

    color: #0f172a;

    box-shadow:
        0 8px 30px
        rgba(15, 23, 42, .07);

    transition:
        transform .2s ease,
        box-shadow .2s ease;

}


.admin-menu-card:hover {

    transform:
        translateY(-5px);

    color: #0f172a;

    box-shadow:
        0 15px 35px
        rgba(15, 23, 42, .12);

}


.menu-icon {

    width: 55px;

    height: 55px;

    border-radius: 16px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 26px;

    margin-bottom: 15px;

    background: #eff6ff;

}


.admin-menu-card h5 {

    font-weight: 800;

    margin-bottom: 5px;

}


.admin-menu-card p {

    color: #64748b;

    margin: 0;

    font-size: 14px;

}


/* ======================================================
   RECENT ATTENDANCE
====================================================== */

.recent-card {

    background: white;

    border-radius: 22px;

    border: 0;

    box-shadow:
        0 8px 30px
        rgba(15, 23, 42, .07);

}


.recent-card-header {

    padding: 24px 25px 10px;

}


.recent-card-header h4 {

    font-weight: 800;

}


.recent-table {

    margin-bottom: 0;

}


.recent-table th {

    color: #64748b;

    font-size: 13px;

    font-weight: 700;

    border-bottom: 1px solid #e2e8f0;

}


.recent-table td {

    padding-top: 15px;

    padding-bottom: 15px;

}


.student-avatar {

    width: 40px;

    height: 40px;

    border-radius: 12px;

    background:
        #dbeafe;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    margin-right: 10px;

    font-weight: 800;

    color: #1d4ed8;

}


/* ======================================================
   RESPONSIVE
====================================================== */

@media (max-width: 768px) {

    .dashboard-header {

        padding: 22px;

    }

    .admin-stat {

        min-height: 130px;

    }

    .chart-container {

        height: 280px;

        padding:
            10px 10px 20px;

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

    ⚙️ Admin Portal

</a>


<div class="text-white">

    <span class="d-none d-md-inline">

        Welcome,

    </span>

    <strong>

        <?= htmlspecialchars(
            $_SESSION['full_name'] ?? 'Administrator'
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

<div class="container py-4 admin-dashboard">


<!-- ======================================================
     HEADER
====================================================== -->

<div class="dashboard-header">

<div
    class="d-flex
    justify-content-between
    align-items-center
    flex-wrap
    gap-3"
>

<div>

<h2>

    Administrator Dashboard

</h2>

<p>

    Monitor your QR Code Attendance System

</p>

</div>


<div>

<span class="badge bg-light text-dark p-3">

    📅 <?= date('F d, Y') ?>

</span>

</div>

</div>

</div>



<!-- ======================================================
     STATISTICS
====================================================== -->

<div class="row g-4 mb-4">


<!-- STUDENTS -->

<div class="col-lg-3 col-md-6">

<div class="admin-stat stat-blue">

<div class="admin-stat-icon">

    🎓

</div>

<small>

    Total Students

</small>

<h2>

    <?= $totalStudents ?>

</h2>

</div>

</div>


<!-- TEACHERS -->

<div class="col-lg-3 col-md-6">

<div class="admin-stat stat-green">

<div class="admin-stat-icon">

    👨‍🏫

</div>

<small>

    Total Teachers

</small>

<h2>

    <?= $totalTeachers ?>

</h2>

</div>

</div>


<!-- PRESENT -->

<div class="col-lg-3 col-md-6">

<div class="admin-stat stat-orange">

<div class="admin-stat-icon">

    ✅

</div>

<small>

    Present Today

</small>

<h2>

    <?= $todayPresent ?>

</h2>

</div>

</div>


<!-- ALL ATTENDANCE -->

<div class="col-lg-3 col-md-6">

<div class="admin-stat stat-purple">

<div class="admin-stat-icon">

    📊

</div>

<small>

    All Attendance

</small>

<h2>

    <?= $totalAttendance ?>

</h2>

</div>

</div>


</div>



<!-- ======================================================
     WAVE ATTENDANCE GRAPH
====================================================== -->

<div class="chart-card mb-4">

<div class="chart-header">

<div
    class="d-flex
    justify-content-between
    align-items-center"
>

<div>

<h4>

    📈 Attendance Overview

</h4>

<p>

    Student attendance for the last 7 days

</p>

</div>


<span class="badge bg-primary">

    Last 7 Days

</span>

</div>

</div>


<div class="chart-container">

<canvas id="attendanceWaveChart"></canvas>

</div>

</div>



<!-- ======================================================
     MANAGEMENT MENU
====================================================== -->

<div class="mb-4">

<div class="mb-3">

<h4 class="fw-bold">

    System Management

</h4>

<p class="text-muted">

    Manage your attendance system

</p>

</div>


<div class="row g-4">


<!-- STUDENTS -->

<div class="col-lg-3 col-md-6">

<a
    class="admin-menu-card"
    href="students.php"
>

<div class="menu-icon">

    🎓

</div>

<h5>

    Manage Students

</h5>

<p>

    Add, edit and remove student accounts.

</p>

</a>

</div>


<!-- TEACHERS -->

<div class="col-lg-3 col-md-6">

<a
    class="admin-menu-card"
    href="teachers.php"
>

<div class="menu-icon">

    👨‍🏫

</div>

<h5>

    Manage Teachers

</h5>

<p>

    Create and manage teacher accounts.

</p>

</a>

</div>


<!-- CLASSES -->

<div class="col-lg-3 col-md-6">

<a
    class="admin-menu-card"
    href="classes.php"
>

<div class="menu-icon">

    🏫

</div>

<h5>

    Manage Classes

</h5>

<p>

    Create and manage classes and subjects.

</p>

</a>

</div>


<!-- REPORTS -->

<div class="col-lg-3 col-md-6">

<a
    class="admin-menu-card"
    href="attendance.php"
>

<div class="menu-icon">

    📊

</div>

<h5>

    Attendance Reports

</h5>

<p>

    View attendance records and reports.

</p>

</a>

</div>


</div>

</div>



<!-- ======================================================
     RECENT ATTENDANCE
====================================================== -->

<div class="recent-card">

<div
    class="recent-card-header
    d-flex
    justify-content-between
    align-items-center"
>

<div>

<h4>

    🕐 Recent Attendance

</h4>

<p class="text-muted mb-0">

    Latest student attendance records

</p>

</div>


<a
    href="attendance.php"
    class="btn btn-primary"
>

    View All

</a>

</div>


<div class="table-responsive">

<table class="table recent-table align-middle">

<thead>

<tr>

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
Date
</th>

<th>
Time
</th>

<th>
Status
</th>

</tr>

</thead>


<tbody>


<?php if (!$recentAttendance): ?>

<tr>

<td
    colspan="8"
    class="text-center
    text-muted
    py-5"
>

    📋 No attendance records yet.

</td>

</tr>

<?php endif; ?>


<?php foreach (
    $recentAttendance
    as $record
): ?>


<tr>


<td>

<div class="d-flex align-items-center">

<div class="student-avatar">

    <?= strtoupper(
        substr(
            $record['full_name'],
            0,
            1
        )
    ) ?>

</div>

<strong>

<?= htmlspecialchars(
    $record['full_name']
) ?>

</strong>

</div>

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
    'M d, Y',
    strtotime(
        $record['attendance_date']
    )
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
     WAVE CHART JAVASCRIPT
====================================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const canvas =
            document.getElementById(
                'attendanceWaveChart'
            );


        if (!canvas) {

            return;

        }


        const ctx =
            canvas.getContext('2d');


        /*
         * Create a vertical gradient
         * for the wave area.
         */

        const gradient =
            ctx.createLinearGradient(
                0,
                0,
                0,
                330
            );


        gradient.addColorStop(
            0,
            'rgba(29, 78, 216, 0.35)'
        );


        gradient.addColorStop(
            1,
            'rgba(29, 78, 216, 0.02)'
        );


        new Chart(
            ctx,
            {

                type: 'line',

                data: {

                    labels:
                        <?= json_encode(
                            $chartLabels
                        ) ?>,

                    datasets: [

                        {

                            label:
                                'Present Students',

                            data:
                                <?= json_encode(
                                    $chartValues
                                ) ?>,

                            borderColor:
                                '#1d4ed8',

                            backgroundColor:
                                gradient,

                            borderWidth: 4,

                            fill: true,

                            tension: 0.5,

                            pointRadius: 5,

                            pointHoverRadius: 8,

                            pointBackgroundColor:
                                '#ffffff',

                            pointBorderColor:
                                '#1d4ed8',

                            pointBorderWidth: 3,

                            cubicInterpolationMode:
                                'monotone'

                        }

                    ]

                },


                options: {

                    responsive: true,

                    maintainAspectRatio: false,


                    interaction: {

                        intersect: false,

                        mode: 'index'

                    },


                    plugins: {

                        legend: {

                            display: false

                        },


                        tooltip: {

                            backgroundColor:
                                '#0f172a',

                            titleColor:
                                '#ffffff',

                            bodyColor:
                                '#ffffff',

                            padding: 12,

                            displayColors: false,

                            callbacks: {

                                label:
                                    function(context) {

                                        return (
                                            ' Present: ' +
                                            context.parsed.y +
                                            ' students'
                                        );

                                    }

                            }

                        }

                    },


                    scales: {

                        x: {

                            grid: {

                                display: false

                            },

                            ticks: {

                                color: '#64748b',

                                font: {

                                    weight: '600'

                                }

                            }

                        },


                        y: {

                            beginAtZero: true,

                            ticks: {

                                precision: 0,

                                color: '#64748b'

                            },

                            grid: {

                                color:
                                    'rgba(148,163,184,.15)'

                            }

                        }

                    }

                }

            }

        );

    }

);

</script>


</body>

</html>