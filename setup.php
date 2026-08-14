<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$message = '';
$error = '';

try {

    $sql = file_get_contents(
        __DIR__ . '/database.sql'
    );

    $statements =
        preg_split(
            '/;\s*(?:\r?\n|$)/',
            $sql
        );


    foreach ($statements as $statement) {

        $statement = trim($statement);

        if (
            $statement !== '' &&
            stripos(
                $statement,
                'CREATE DATABASE'
            ) !== 0 &&
            stripos(
                $statement,
                'USE '
            ) !== 0
        ) {

            $pdo->exec($statement);
        }
    }


    $users = [

        [
            'admin',
            'admin123',
            'System Administrator',
            'admin@example.com',
            'admin'
        ],

        [
            'teacher',
            'teacher123',
            'Demo Teacher',
            'teacher@example.com',
            'teacher'
        ],

        [
            'student',
            'student123',
            'Juan Dela Cruz',
            'student@example.com',
            'student'
        ]

    ];


    $check =
        $pdo->prepare(
            "SELECT id FROM users
             WHERE username = ?"
        );

    $insert =
        $pdo->prepare(
            "INSERT INTO users
            (username,password,full_name,email,role)
            VALUES (?,?,?,?,?)"
        );


    foreach ($users as $u) {

        $check->execute([
            $u[0]
        ]);

        if (!$check->fetch()) {

            $insert->execute([

                $u[0],

                password_hash(
                    $u[1],
                    PASSWORD_DEFAULT
                ),

                $u[2],
                $u[3],
                $u[4]

            ]);
        }
    }


    $studentUser =
        $pdo->query(
            "SELECT id
             FROM users
             WHERE username='student'
             LIMIT 1"
        )->fetch();


    if ($studentUser) {

        $checkStudent =
            $pdo->prepare(
                "SELECT id
                 FROM students
                 WHERE user_id=?"
            );

        $checkStudent->execute([
            (int)$studentUser['id']
        ]);


        if (!$checkStudent->fetch()) {

            $insertStudent =
                $pdo->prepare("
                    INSERT INTO students
                    (
                        user_id,
                        student_number,
                        year_level,
                        course,
                        section,
                        qr_token
                    )
                    VALUES (?,?,?,?,?,?)
                ");

            $insertStudent->execute([

                (int)$studentUser['id'],

                '2026-0001',

                '1st Year',

                'BSIT',

                'Section A',

                'STU-' .
                bin2hex(
                    random_bytes(16)
                )

            ]);
        }
    }


    $message =
        'Setup completed successfully.';

} catch (Throwable $e) {

    $error =
        $e->getMessage();
}

?>

<!doctype html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width,initial-scale=1">

<title>
System Setup
</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

</head>

<body class="bg-light">

<div
class="container py-5"
style="max-width:800px">

<div class="card shadow-sm">

<div class="card-body p-4">

<h2>
QR Attendance System Setup
</h2>


<?php if ($message): ?>

<div class="alert alert-success">

<?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="alert alert-danger">

<strong>
Setup Error:
</strong>

<br>

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>


<table class="table table-bordered">

<thead>

<tr>

<th>Portal</th>

<th>Username</th>

<th>Password</th>

</tr>

</thead>

<tbody>

<tr>

<td>Admin</td>

<td>admin</td>

<td>admin123</td>

</tr>

<tr>

<td>Teacher</td>

<td>teacher</td>

<td>teacher123</td>

</tr>

<tr>

<td>Student</td>

<td>student</td>

<td>student123</td>

</tr>

</tbody>

</table>


<div class="alert alert-warning">

Delete
<strong>setup.php</strong>
after installation.

</div>


<a
href="index.php"
class="btn btn-primary">

Go to Login Portals

</a>

</div>

</div>

</div>

</body>
</html>