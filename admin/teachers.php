<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

require_login('admin');

$message = '';
$error = '';


// ======================================================
// ADD TEACHER
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $full = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');


        if (
            $username === '' ||
            $password === '' ||
            $full === ''
        ) {

            throw new RuntimeException(
                'Name, username and password are required.'
            );
        }


        // Check duplicate username

        $check = $pdo->prepare("
            SELECT id
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        $check->execute([
            $username
        ]);


        if ($check->fetch()) {

            throw new RuntimeException(
                'Username already exists.'
            );
        }


        // Insert teacher

        $stmt = $pdo->prepare("

            INSERT INTO users
            (
                username,
                password,
                full_name,
                email,
                role
            )

            VALUES
            (?, ?, ?, ?, 'teacher')

        ");


        $stmt->execute([

            $username,

            password_hash(
                $password,
                PASSWORD_DEFAULT
            ),

            $full,

            $email

        ]);


        $message =
            'Teacher added successfully.';


    } catch (Throwable $e) {

        $error =
            $e->getMessage();
    }
}


// ======================================================
// GET TEACHERS
// ======================================================

$teachers = $pdo->query("

    SELECT
        id,
        username,
        full_name,
        email,
        created_at

    FROM users

    WHERE role = 'teacher'

    ORDER BY full_name ASC

")->fetchAll(PDO::FETCH_ASSOC);

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
Manage Teachers
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

.teacher-header {

    background:
        linear-gradient(
            135deg,
            #0f172a,
            #1d4ed8
        );

    color: white;

    border-radius: 18px;

    padding: 25px;

    margin-bottom: 25px;

}


.form-card {

    border: 0;

    border-radius: 18px;

    box-shadow:
        0 8px 25px
        rgba(15,23,42,.08);

}


.table-card {

    border: 0;

    border-radius: 18px;

    box-shadow:
        0 8px 25px
        rgba(15,23,42,.08);

}


.teacher-avatar {

    width: 42px;

    height: 42px;

    border-radius: 50%;

    background: #e8f0fe;

    color: #1d4ed8;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: 700;

}


.table > :not(caption) > * > * {

    padding: 15px;

}


.btn-edit {

    min-width: 80px;

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
    href="dashboard.php"
    class="btn btn-sm btn-outline-light me-2"
>

    Dashboard

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

<div class="container py-4">


<!-- HEADER -->

<div class="teacher-header">

<div
    class="d-flex
    justify-content-between
    align-items-center"
>

<div>

<h2 class="fw-bold mb-1">

    👨‍🏫 Manage Teachers

</h2>

<p class="mb-0 opacity-75">

    Add, view and edit teacher accounts.

</p>

</div>


<div>

<span class="badge bg-light text-primary fs-6">

    <?= count($teachers) ?>

    Teachers

</span>

</div>

</div>

</div>



<!-- ======================================================
     MESSAGES
====================================================== -->

<?php if ($message): ?>

<div class="alert alert-success alert-dismissible fade show">

    ✅
    <?= htmlspecialchars($message) ?>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
    ></button>

</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="alert alert-danger alert-dismissible fade show">

    ❌
    <?= htmlspecialchars($error) ?>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
    ></button>

</div>

<?php endif; ?>



<!-- ======================================================
     ADD TEACHER
====================================================== -->

<div class="card form-card mb-4">

<div class="card-body p-4">


<h4 class="fw-bold mb-1">

    ➕ Add New Teacher

</h4>

<p class="text-muted mb-4">

    Create a new teacher login account.

</p>


<form
    method="post"
    class="row g-3"
>


<div class="col-md-6">

<label class="form-label fw-semibold">

    Full Name

</label>

<input
    type="text"
    class="form-control"
    name="full_name"
    placeholder="Enter teacher full name"
    required
>

</div>


<div class="col-md-6">

<label class="form-label fw-semibold">

    Username

</label>

<input
    type="text"
    class="form-control"
    name="username"
    placeholder="Enter username"
    required
>

</div>


<div class="col-md-6">

<label class="form-label fw-semibold">

    Password

</label>

<input
    type="password"
    class="form-control"
    name="password"
    placeholder="Enter password"
    required
>

</div>


<div class="col-md-6">

<label class="form-label fw-semibold">

    Email

</label>

<input
    type="email"
    class="form-control"
    name="email"
    placeholder="Enter email address"
>

</div>


<div class="col-12">

<button
    type="submit"
    class="btn btn-success px-4"
>

    ➕ Add Teacher

</button>

</div>


</form>

</div>

</div>



<!-- ======================================================
     TEACHER LIST
====================================================== -->

<div class="card table-card">

<div class="card-body p-4">


<div
    class="d-flex
    justify-content-between
    align-items-center
    mb-3"
>

<div>

<h4 class="fw-bold mb-1">

    👨‍🏫 Teacher Accounts

</h4>

<p class="text-muted mb-0">

    List of registered teachers.

</p>

</div>

</div>



<?php if (!$teachers): ?>

<div class="alert alert-info text-center">

    No teacher accounts found.

</div>

<?php else: ?>


<div class="table-responsive">

<table class="table table-hover align-middle">

<thead class="table-dark">

<tr>

<th>
#
</th>

<th>
Teacher
</th>

<th>
Username
</th>

<th>
Email
</th>

<th>
Created
</th>

<th class="text-center">
Action
</th>

</tr>

</thead>


<tbody>


<?php foreach (
    $teachers
    as $i => $t
): ?>

<tr>


<!-- NUMBER -->

<td>

<?= $i + 1 ?>

</td>



<!-- TEACHER -->

<td>

<div
    class="d-flex
    align-items-center
    gap-3"
>


<div class="teacher-avatar">

<?= strtoupper(
    substr(
        $t['full_name'],
        0,
        1
    )
) ?>

</div>


<div>

<strong>

<?= htmlspecialchars(
    $t['full_name']
) ?>

</strong>

<br>

<span class="badge bg-primary">

Teacher

</span>

</div>


</div>

</td>



<!-- USERNAME -->

<td>

<code>

<?= htmlspecialchars(
    $t['username']
) ?>

</code>

</td>



<!-- EMAIL -->

<td>

<?php if (
    !empty($t['email'])
): ?>

<?= htmlspecialchars(
    $t['email']
) ?>

<?php else: ?>

<span class="text-muted">

No email

</span>

<?php endif; ?>

</td>



<!-- CREATED -->

<td>

<small>

<?= htmlspecialchars(
    $t['created_at']
) ?>

</small>

</td>



<!-- ACTION -->

<td class="text-center">

<a
    href="edit-teacher.php?id=<?= (int)$t['id'] ?>"
    class="btn btn-warning btn-sm btn-edit"
>

    ✏️ Edit

</a>

</td>


</tr>

<?php endforeach; ?>


</tbody>

</table>

</div>


<?php endif; ?>


</div>

</div>


</div>



<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>