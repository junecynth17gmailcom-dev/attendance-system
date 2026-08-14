<?php

session_start();


// ======================================================
// REDIRECT ALREADY LOGGED-IN USER
// ======================================================

if (
    isset(
        $_SESSION['user_id'],
        $_SESSION['role']
    )
) {

    header(
        'Location: ' .
        $_SESSION['role'] .
        '/dashboard.php'
    );

    exit;
}


$error = $_GET['error'] ?? '';

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        QR Code Attendance System
    </title>


    <!-- ==================================================
         BOOTSTRAP
    =================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- ==================================================
         GOOGLE FONT
    =================================================== -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- ==================================================
         YOUR CSS
    =================================================== -->

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>

        /* ==================================================
           INDEX PAGE
        ================================================== */

        .home-page {

            min-height: 100vh;

            background:
                radial-gradient(
                    circle at 10% 20%,
                    rgba(59,130,246,.18),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 90% 80%,
                    rgba(14,165,233,.15),
                    transparent 30%
                ),
                linear-gradient(
                    135deg,
                    #020617 0%,
                    #0f172a 45%,
                    #1e3a8a 100%
                );

            color: white;

            display: flex;

            flex-direction: column;

            position: relative;

            overflow: hidden;
        }


        /* ==================================================
           BACKGROUND DECORATION
        ================================================== */

        .background-circle {

            position: absolute;

            border-radius: 50%;

            pointer-events: none;

            opacity: .4;

            filter: blur(2px);
        }


        .circle-one {

            width: 300px;

            height: 300px;

            background:
                rgba(37,99,235,.20);

            top: -120px;

            left: -100px;
        }


        .circle-two {

            width: 400px;

            height: 400px;

            background:
                rgba(6,182,212,.12);

            bottom: -180px;

            right: -120px;
        }


        .circle-three {

            width: 180px;

            height: 180px;

            background:
                rgba(255,255,255,.05);

            top: 35%;

            right: 15%;
        }


        /* ==================================================
           MAIN CONTENT
        ================================================== */

        .home-container {

            width: 100%;

            max-width: 1200px;

            margin: auto;

            padding:
                55px 20px 30px;

            position: relative;

            z-index: 2;
        }


        /* ==================================================
           BRAND
        ================================================== */

        .brand-wrapper {

            text-align: center;

            margin-bottom: 45px;
        }


        .brand-logo {

            width: 88px;

            height: 88px;

            margin: 0 auto 20px;

            border-radius: 26px;

            background:
                linear-gradient(
                    135deg,
                    #ffffff,
                    #dbeafe
                );

            color: #1d4ed8;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 27px;

            font-weight: 900;

            letter-spacing: 1px;

            box-shadow:
                0 20px 50px
                rgba(0,0,0,.30);

            border:
                4px solid
                rgba(255,255,255,.20);

            transition:
                .3s ease;
        }


        .brand-logo:hover {

            transform:
                translateY(-5px)
                rotate(-3deg);

            box-shadow:
                0 25px 60px
                rgba(0,0,0,.40);
        }


        .brand-wrapper h1 {

            font-size:
                clamp(28px, 5vw, 46px);

            font-weight:
                800;

            letter-spacing:
                -.8px;

            margin-bottom:
                12px;
        }


        .brand-wrapper p {

            color:
                rgba(255,255,255,.70);

            font-size:
                16px;

            margin:
                0 auto;

            max-width:
                650px;

            line-height:
                1.7;
        }


        /* ==================================================
           ERROR
        ================================================== */

        .home-error {

            max-width:
                700px;

            margin:
                0 auto 30px;

            border-radius:
                14px;

            border:
                1px solid
                rgba(255,255,255,.15);

            background:
                rgba(220,53,69,.15);

            color:
                #fff;

            backdrop-filter:
                blur(10px);

            box-shadow:
                0 10px 30px
                rgba(0,0,0,.15);
        }


        /* ==================================================
           PORTAL GRID
        ================================================== */

        .portal-grid {

            display:
                grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap:
                24px;
        }


        /* ==================================================
           PORTAL CARD
        ================================================== */

        .home-portal-card {

            background:
                rgba(255,255,255,.96);

            color:
                #0f172a;

            border-radius:
                22px;

            padding:
                32px 28px;

            min-height:
                370px;

            display:
                flex;

            flex-direction:
                column;

            position:
                relative;

            overflow:
                hidden;

            border:
                1px solid
                rgba(255,255,255,.5);

            box-shadow:
                0 20px 55px
                rgba(0,0,0,.20);

            transition:
                transform .3s ease,
                box-shadow .3s ease;
        }


        .home-portal-card:hover {

            transform:
                translateY(-10px);

            box-shadow:
                0 30px 70px
                rgba(0,0,0,.32);
        }


        /* Top colored line */

        .home-portal-card::before {

            content: "";

            position: absolute;

            top: 0;

            left: 0;

            width: 100%;

            height: 5px;
        }


        .student-card::before {

            background:
                linear-gradient(
                    90deg,
                    #2563eb,
                    #3b82f6
                );
        }


        .teacher-card::before {

            background:
                linear-gradient(
                    90deg,
                    #16a34a,
                    #22c55e
                );
        }


        .admin-card::before {

            background:
                linear-gradient(
                    90deg,
                    #334155,
                    #020617
                );
        }


        /* ==================================================
           PORTAL ICON
        ================================================== */

        .home-portal-icon {

            width:
                78px;

            height:
                78px;

            border-radius:
                22px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                37px;

            margin-bottom:
                22px;

            transition:
                transform .3s ease;
        }


        .home-portal-card:hover
        .home-portal-icon {

            transform:
                scale(1.08)
                rotate(-3deg);
        }


        .student-icon {

            background:
                #eff6ff;
        }


        .teacher-icon {

            background:
                #f0fdf4;
        }


        .admin-icon {

            background:
                #f1f5f9;
        }


        /* ==================================================
           PORTAL TITLE
        ================================================== */

        .home-portal-card h3 {

            font-size:
                22px;

            font-weight:
                800;

            margin-bottom:
                12px;
        }


        .home-portal-card p {

            color:
                #64748b;

            line-height:
                1.7;

            margin-bottom:
                25px;

            flex-grow:
                1;
        }


        /* ==================================================
           PORTAL BUTTON
        ================================================== */

        .portal-login-btn {

            width:
                100%;

            border:
                0;

            border-radius:
                12px;

            padding:
                13px 18px;

            color:
                white;

            text-decoration:
                none;

            font-weight:
                700;

            text-align:
                center;

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }


        .portal-login-btn:hover {

            color:
                white;

            transform:
                translateY(-2px);

            box-shadow:
                0 10px 25px
                rgba(0,0,0,.18);
        }


        .student-btn {

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );
        }


        .teacher-btn {

            background:
                linear-gradient(
                    135deg,
                    #16a34a,
                    #15803d
                );
        }


        .admin-btn {

            background:
                linear-gradient(
                    135deg,
                    #334155,
                    #020617
                );
        }


        /* ==================================================
           FOOTER
        ================================================== */

        .home-footer {

            text-align:
                center;

            margin-top:
                45px;

            color:
                rgba(255,255,255,.50);

            font-size:
                13px;

            letter-spacing:
                .3px;
        }


        .home-footer strong {

            color:
                rgba(255,255,255,.75);
        }


        /* ==================================================
           RESPONSIVE
        ================================================== */

        @media (max-width: 900px) {

            .portal-grid {

                grid-template-columns:
                    1fr;

                max-width:
                    600px;

                margin:
                    auto;
            }


            .home-portal-card {

                min-height:
                    auto;
            }

        }


        @media (max-width: 576px) {

            .home-container {

                padding:
                    35px 16px 25px;
            }


            .brand-wrapper {

                margin-bottom:
                    30px;
            }


            .brand-logo {

                width:
                    72px;

                height:
                    72px;

                border-radius:
                    21px;

                font-size:
                    23px;
            }


            .brand-wrapper h1 {

                font-size:
                    27px;
            }


            .brand-wrapper p {

                font-size:
                    14px;
            }


            .home-portal-card {

                padding:
                    28px 24px;
            }

        }

    </style>

</head>


<body class="home-page">


<!-- ======================================================
     BACKGROUND DECORATIONS
====================================================== -->

<div class="background-circle circle-one"></div>

<div class="background-circle circle-two"></div>

<div class="background-circle circle-three"></div>



<!-- ======================================================
     MAIN
====================================================== -->

<main class="home-container">


    <!-- ==================================================
         BRAND HEADER
    =================================================== -->

    <section class="brand-wrapper">


        <div class="brand-logo">

            QR

        </div>


        <h1>

            QR Code Attendance System

        </h1>


        <p>

            A secure and convenient attendance platform
            for students, teachers, and administrators.

        </p>


    </section>



    <!-- ==================================================
         ERROR MESSAGE
    =================================================== -->

    <?php if ($error): ?>

        <div class="alert home-error">

            <strong>
                Login Error
            </strong>

            <br>

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>



    <!-- ==================================================
         PORTAL CARDS
    =================================================== -->

    <section class="portal-grid">


        <!-- ==============================================
             STUDENT
        =============================================== -->

        <div class="home-portal-card student-card">


            <div
                class="home-portal-icon student-icon"
            >

                🎓

            </div>


            <h3>

                Student Portal

            </h3>


            <p>

                Access your student profile,
                view your attendance history,
                and display your personal QR code
                for attendance scanning.

            </p>


            <a
                href="login.php?role=student"
                class="portal-login-btn student-btn"
            >

                Login as Student

                <span class="ms-1">
                    →
                </span>

            </a>


        </div>



        <!-- ==============================================
             TEACHER
        =============================================== -->

        <div class="home-portal-card teacher-card">


            <div
                class="home-portal-icon teacher-icon"
            >

                📷

            </div>


            <h3>

                Teacher Portal

            </h3>


            <p>

                Scan student QR codes,
                record attendance,
                view attendance history,
                and generate attendance reports.

            </p>


            <a
                href="login.php?role=teacher"
                class="portal-login-btn teacher-btn"
            >

                Login as Teacher

                <span class="ms-1">
                    →
                </span>

            </a>


        </div>



        <!-- ==============================================
             ADMIN
        =============================================== -->

        <div class="home-portal-card admin-card">


            <div
                class="home-portal-icon admin-icon"
            >

                ⚙️

            </div>


            <h3>

                Admin Portal

            </h3>


            <p>

                Manage students and teachers,
                organize courses and sections,
                monitor attendance,
                and manage system records.

            </p>


            <a
                href="login.php?role=admin"
                class="portal-login-btn admin-btn"
            >

                Login as Administrator

                <span class="ms-1">
                    →
                </span>

            </a>


        </div>


    </section>



    <!-- ==================================================
         FOOTER
    =================================================== -->

    <footer class="home-footer">

        <div>

            <strong>
                QR Code Attendance System
            </strong>

        </div>

        <div class="mt-1">

            PHP &nbsp;•&nbsp;
            MySQL &nbsp;•&nbsp;
            JavaScript &nbsp;•&nbsp;
            QR Scanner

        </div>

        <div class="mt-2">

            © <?= date('Y') ?>
            Attendance Management System

        </div>

    </footer>


</main>



<!-- ======================================================
     BOOTSTRAP JAVASCRIPT
====================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>