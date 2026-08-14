<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

require_login('admin');


// ======================================================
// GET TEACHER ID
// ======================================================

$teacherId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$teacherId) {

    header(
        'Location: teachers.php?error=' .
        urlencode('Invalid teacher ID.')
    );

    exit;
}


// ======================================================
// GET TEACHER
// ======================================================

$stmt = $pdo->prepare("

    SELECT
        id,
        username,
        full_name,
        email,
        role

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

    header(
        'Location: teachers.php?error=' .
        urlencode('Teacher account not found.')
    );

    exit;
}


$message = '';
$error = '';


// ======================================================
// UPDATE TEACHER
// ======================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    try {

        $fullName =
            trim(
                $_POST['full_name'] ?? ''
            );

        $username =
            trim(
                $_POST['username'] ?? ''
            );

        $email =
            trim(
                $_POST['email'] ?? ''
            );

        $password =
            $_POST['password'] ?? '';


        // ==================================================
        // VALIDATION
        // ==================================================

        if (
            $fullName === '' ||
            $username === ''
        ) {

            throw new RuntimeException(
                'Full name and username are required.'
            );
        }


        // ==================================================
        // CHECK DUPLICATE USERNAME
        // ==================================================

        $check = $pdo->prepare("

            SELECT id

            FROM users

            WHERE username = ?

            AND id != ?

            LIMIT 1

        ");

        $check->execute([

            $username,

            $teacherId

        ]);


        if ($check->fetch()) {

            throw new RuntimeException(
                'Username already exists. Please choose another username.'
            );
        }


        // ==================================================
        // UPDATE WITH PASSWORD
        // ==================================================

        if ($password !== '') {

            $stmt = $pdo->prepare("

                UPDATE users

                SET
                    full_name = ?,
                    username = ?,
                    email = ?,
                    password = ?

                WHERE id = ?
                AND role = 'teacher'

            ");

            $stmt->execute([

                $fullName,

                $username,

                $email,

                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                ),

                $teacherId

            ]);

        }


        // ==================================================
        // UPDATE WITHOUT PASSWORD
        // ==================================================

        else {

            $stmt = $pdo->prepare("

                UPDATE users

                SET
                    full_name = ?,
                    username = ?,
                    email = ?

                WHERE id = ?
                AND role = 'teacher'

            ");

            $stmt->execute([

                $fullName,

                $username,

                $email,

                $teacherId

            ]);

        }


        // ==================================================
        // SUCCESS
        // ==================================================

        header(
            'Location: teachers.php?message=' .
            urlencode(
                'Teacher updated successfully.'
            )
        );

        exit;


    } catch (Throwable $e) {

        $error =
            $e->getMessage();
    }
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
Edit Teacher
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

.edit-container {

    max-width: 800px;

    margin: 0 auto;

}


.edit-card {

    border: 0;

    border-radius: 20px;

    box-shadow:
        0 10px 30px
        rgba(15,23,42,.08);

}


.edit-header {

    background:
        linear-gradient(
            135deg,
            #0f172a,
            #1d4ed8
        );

    color: white;

    padding: 25px;

    border-radius:
        20px 20px 0 0;

}


.profile-circle {

    width: 70px;

    height: 70px;

    border-radius: 50%;

    background: white;

    color: #1d4ed8;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 28px;

    font-weight: 800;

}


.form-control {

    border-radius: 10px;

    padding: 11px 14px;

}


.form-label {

    font-weight: 600;

}


.password-help {

    font-size: 13px;

    color: #6c757d;

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


<div>

<a
    href="manage-teachers.php"
    class="btn btn-sm btn-outline-light me-2"
>

    ← Teachers

</a>


<a
    href="../logout.php"
    class="btn btn-sm btn-outline-light"
>

    Logout

</a>

</div>


</div>

</nav>



<!-- ======================================================
     MAIN
====================================================== -->

<div class="container py-5">


<div class="edit-container">


<?php if ($error): ?>

<div class="alert alert-danger">

    ❌

    <?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>



<div class="card edit-card">


<!-- HEADER -->

<div class="edit-header">


<div
    class="d-flex
    align-items-center
    gap-3"
>


<div class="profile-circle">

<?= strtoupper(
    substr(
        $teacher['full_name'],
        0,
        1
    )
) ?>

</div>


<div>

<h3 class="fw-bold mb-1">

    Edit Teacher

</h3>

<p class="mb-0 opacity-75">

    Update teacher account information.

</p>

</div>


</div>

</div>



<!-- FORM -->

<div class="card-body p-4">


<form
    method="post"
>


<!-- FULL NAME -->

<div class="mb-3">

<label class="form-label">

    Full Name

</label>

<input
    type="text"
    name="full_name"
    class="form-control"
    value="<?= htmlspecialchars(
        $teacher['full_name']
    ) ?>"
    required
>

</div>



<!-- USERNAME -->

<div class="mb-3">

<label class="form-label">

    Username

</label>

<input
    type="text"
    name="username"
    class="form-control"
    value="<?= htmlspecialchars(
        $teacher['username']
    ) ?>"
    required
>

</div>



<!-- EMAIL -->

<div class="mb-3">

<label class="form-label">

    Email

</label>

<input
    type="email"
    name="email"
    class="form-control"
    value="<?= htmlspecialchars(
        $teacher['email'] ?? ''
    ) ?>"
    placeholder="teacher@example.com"
>

</div>



<!-- PASSWORD -->

<div class="mb-4">

<label class="form-label">

    New Password

</label>

<input
    type="password"
    name="password"
    class="form-control"
    placeholder="Leave blank to keep current password"
>

<div class="password-help mt-2">

    🔒 Leave this field empty if you do not want
    to change the teacher's password.

</div>

</div>



<!-- BUTTONS -->

<div
    class="d-flex
    justify-content-between"
>


<a
    href="teachers.php"
    class="btn btn-outline-secondary"
>

    Cancel

</a>


<button
    type="submit"
    class="btn btn-primary px-4"
>

    💾 Save Changes

</button>


</div>


</form>

</div>

</div>


</div>

</div>


<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>