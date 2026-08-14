<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

$role = $_GET['role'] ?? $_POST['role'] ?? 'student';

$allowedRoles = [
    'student',
    'teacher',
    'admin'
];

if (!in_array($role, $allowedRoles, true)) {
    $role = 'student';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = 'Please enter your username and password.';

    } else {

        try {

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    username,
                    password,
                    full_name,
                    email,
                    role
                FROM users
                WHERE username = ?
                AND role = ?
                LIMIT 1
            ");

            $stmt->execute([
                $username,
                $role
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {

                $error = 'Account not found for this portal.';

            } elseif (!password_verify($password, $user['password'])) {

                $error = 'Incorrect password.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | LOGIN SUCCESS
                |--------------------------------------------------------------------------
                */

                session_regenerate_id(true);

                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'] ?? '';
                $_SESSION['role'] = $user['role'];

                /*
                |--------------------------------------------------------------------------
                | REDIRECT BY ROLE
                |--------------------------------------------------------------------------
                */

                if ($user['role'] === 'admin') {

                    header('Location: admin/dashboard.php');
                    exit;

                } elseif ($user['role'] === 'teacher') {

                    header('Location: teacher/dashboard.php');
                    exit;

                } elseif ($user['role'] === 'student') {

                    header('Location: student/dashboard.php');
                    exit;

                } else {

                    session_destroy();

                    $error = 'Invalid account role.';
                }
            }

        } catch (PDOException $e) {

            $error = 'Database error. Please check your database connection.';
        }
    }
}

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
        <?= htmlspecialchars(ucfirst($role)) ?> Login
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        body.login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f7fb;
        }

        .login-box {
            width: 100%;
            max-width: 430px;
            background: #ffffff;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 10px 35px rgba(0,0,0,.10);
        }

        .brand-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 15px;
            border-radius: 50%;
            background: #0d6efd;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 22px;
        }

        .form-control {
            border-radius: 10px;
        }

        .btn {
            border-radius: 10px;
        }

    </style>

</head>

<body class="login-page">

<div class="login-box">

    <div class="text-center mb-4">

        <div class="brand-icon">
            QR
        </div>

        <h2 class="fw-bold">
            <?= htmlspecialchars(ucfirst($role)) ?> Portal
        </h2>

        <p class="text-muted">
            QR Code Attendance System
        </p>

    </div>


    <?php if ($error): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <form method="POST">

        <input
            type="hidden"
            name="role"
            value="<?= htmlspecialchars($role) ?>"
        >


        <div class="mb-3">

            <label class="form-label fw-bold">
                Username
            </label>

            <input
                type="text"
                name="username"
                class="form-control form-control-lg"
                placeholder="Enter username"
                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                required
                autofocus
            >

        </div>


        <div class="mb-4">

            <label class="form-label fw-bold">
                Password
            </label>

            <input
                type="password"
                name="password"
                class="form-control form-control-lg"
                placeholder="Enter password"
                required
            >

        </div>


        <button
            type="submit"
            class="btn btn-primary btn-lg w-100"
        >
            Login
        </button>

    </form>


    <div class="text-center mt-4">

        <a
            href="index.php"
            class="text-decoration-none"
        >
            ← Back to Portals
        </a>

    </div>

</div>

</body>
</html>