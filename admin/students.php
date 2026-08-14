<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

require_login('admin');

$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| GET CLASS GROUPS
|--------------------------------------------------------------------------
|
| These are the Course / Year Level / Section combinations
| created by the Admin in classes.php.
|
*/

$classStmt = $pdo->query("
    SELECT
        id,
        course,
        year_level,
        section,
        status
    FROM class_groups
    WHERE status = 'active'
    ORDER BY
        course ASC,
        year_level ASC,
        section ASC
");

$classGroups = $classStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| ADD / EDIT / DELETE STUDENT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    try {

        /*
        |--------------------------------------------------------------------------
        | ADD STUDENT
        |--------------------------------------------------------------------------
        */

        if ($action === 'add') {

            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');

            $studentNumber = trim(
                $_POST['student_number'] ?? ''
            );

            $sex = trim(
                $_POST['sex'] ?? ''
            );

            $birthdate = trim(
                $_POST['birthdate'] ?? ''
            );

            $yearLevel = trim(
                $_POST['year_level'] ?? ''
            );

            $course = trim(
                $_POST['course'] ?? ''
            );

            $section = trim(
                $_POST['section'] ?? ''
            );


            /*
            |--------------------------------------------------------------------------
            | VALIDATION
            |--------------------------------------------------------------------------
            */

            if (
                $username === '' ||
                $password === '' ||
                $fullName === '' ||
                $studentNumber === '' ||
                $sex === '' ||
                $birthdate === '' ||
                $course === '' ||
                $yearLevel === '' ||
                $section === ''
            ) {

                throw new RuntimeException(
                    'Please complete all required fields.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | VALIDATE CLASS GROUP
            |--------------------------------------------------------------------------
            |
            | Make sure the selected Course / Year / Section
            | actually exists in class_groups.
            |
            */

            $classCheck = $pdo->prepare("
                SELECT id
                FROM class_groups
                WHERE course = ?
                AND year_level = ?
                AND section = ?
                AND status = 'active'
                LIMIT 1
            ");

            $classCheck->execute([
                $course,
                $yearLevel,
                $section
            ]);


            if (!$classCheck->fetch()) {

                throw new RuntimeException(
                    'Invalid Course, Year Level or Section. Please select a class created by the Admin.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | CHECK USERNAME
            |--------------------------------------------------------------------------
            */

            $checkUsername = $pdo->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $checkUsername->execute([
                $username
            ]);


            if ($checkUsername->fetch()) {

                throw new RuntimeException(
                    'Username already exists. Please use another username.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | CHECK STUDENT NUMBER
            |--------------------------------------------------------------------------
            */

            $checkStudentNumber = $pdo->prepare("
                SELECT id
                FROM students
                WHERE student_number = ?
                LIMIT 1
            ");

            $checkStudentNumber->execute([
                $studentNumber
            ]);


            if ($checkStudentNumber->fetch()) {

                throw new RuntimeException(
                    'Student number already exists.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | GENERATE UNIQUE QR TOKEN
            |--------------------------------------------------------------------------
            */

            do {

                $qrToken =
                    'STU-' .
                    strtoupper(
                        bin2hex(
                            random_bytes(16)
                        )
                    );


                $checkQR = $pdo->prepare("
                    SELECT id
                    FROM students
                    WHERE qr_token = ?
                    LIMIT 1
                ");

                $checkQR->execute([
                    $qrToken
                ]);

                $qrExists = $checkQR->fetch();

            } while ($qrExists);


            /*
            |--------------------------------------------------------------------------
            | START TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | INSERT USER
            |--------------------------------------------------------------------------
            */

            $userStmt = $pdo->prepare("
                INSERT INTO users
                (
                    username,
                    password,
                    full_name,
                    email,
                    role
                )
                VALUES
                (?, ?, ?, ?, 'student')
            ");


            $userStmt->execute([
                $username,

                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                ),

                $fullName,

                $email
            ]);


            $userId = (int)$pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | INSERT STUDENT
            |--------------------------------------------------------------------------
            */

            $studentStmt = $pdo->prepare("
                INSERT INTO students
                (
                    user_id,
                    student_number,
                    sex,
                    birthdate,
                    year_level,
                    course,
                    section,
                    qr_token,
                    status
                )
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");


            $studentStmt->execute([
                $userId,
                $studentNumber,
                $sex,
                $birthdate,
                $yearLevel,
                $course,
                $section,
                $qrToken
            ]);


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            $message =
                'Student added successfully. QR code generated automatically.';
        }


        /*
        |--------------------------------------------------------------------------
        | EDIT STUDENT
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'edit') {

            $studentId = (int)(
                $_POST['student_id'] ?? 0
            );

            $username = trim(
                $_POST['username'] ?? ''
            );

            $password = $_POST['password'] ?? '';

            $fullName = trim(
                $_POST['full_name'] ?? ''
            );

            $email = trim(
                $_POST['email'] ?? ''
            );

            $studentNumber = trim(
                $_POST['student_number'] ?? ''
            );

            $sex = trim(
                $_POST['sex'] ?? ''
            );

            $birthdate = trim(
                $_POST['birthdate'] ?? ''
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


            /*
            |--------------------------------------------------------------------------
            | VALIDATION
            |--------------------------------------------------------------------------
            */

            if (
                $studentId <= 0 ||
                $username === '' ||
                $fullName === '' ||
                $studentNumber === '' ||
                $sex === '' ||
                $birthdate === '' ||
                $course === '' ||
                $yearLevel === '' ||
                $section === ''
            ) {

                throw new RuntimeException(
                    'Please complete all required fields.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | GET EXISTING STUDENT
            |--------------------------------------------------------------------------
            */

            $findStudent = $pdo->prepare("
                SELECT
                    s.id,
                    s.user_id
                FROM students s
                WHERE s.id = ?
                LIMIT 1
            ");

            $findStudent->execute([
                $studentId
            ]);


            $existingStudent =
                $findStudent->fetch(PDO::FETCH_ASSOC);


            if (!$existingStudent) {

                throw new RuntimeException(
                    'Student not found.'
                );
            }


            $userId =
                (int)$existingStudent['user_id'];


            /*
            |--------------------------------------------------------------------------
            | VALIDATE CLASS GROUP
            |--------------------------------------------------------------------------
            */

            $classCheck = $pdo->prepare("
                SELECT id
                FROM class_groups
                WHERE course = ?
                AND year_level = ?
                AND section = ?
                AND status = 'active'
                LIMIT 1
            ");

            $classCheck->execute([
                $course,
                $yearLevel,
                $section
            ]);


            if (!$classCheck->fetch()) {

                throw new RuntimeException(
                    'Invalid Course, Year Level or Section.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | CHECK USERNAME
            |--------------------------------------------------------------------------
            */

            $checkUsername = $pdo->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                AND id != ?
                LIMIT 1
            ");

            $checkUsername->execute([
                $username,
                $userId
            ]);


            if ($checkUsername->fetch()) {

                throw new RuntimeException(
                    'Username already exists.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | CHECK STUDENT NUMBER
            |--------------------------------------------------------------------------
            */

            $checkStudentNumber = $pdo->prepare("
                SELECT id
                FROM students
                WHERE student_number = ?
                AND id != ?
                LIMIT 1
            ");

            $checkStudentNumber->execute([
                $studentNumber,
                $studentId
            ]);


            if ($checkStudentNumber->fetch()) {

                throw new RuntimeException(
                    'Student number already exists.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | START TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | UPDATE USERS
            |--------------------------------------------------------------------------
            */

            if ($password !== '') {

                $updateUser = $pdo->prepare("
                    UPDATE users
                    SET
                        username = ?,
                        password = ?,
                        full_name = ?,
                        email = ?
                    WHERE id = ?
                ");

                $updateUser->execute([

                    $username,

                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    ),

                    $fullName,

                    $email,

                    $userId
                ]);

            } else {

                /*
                |--------------------------------------------------------------
                | Do not change password if password field is empty.
                |--------------------------------------------------------------
                */

                $updateUser = $pdo->prepare("
                    UPDATE users
                    SET
                        username = ?,
                        full_name = ?,
                        email = ?
                    WHERE id = ?
                ");

                $updateUser->execute([

                    $username,

                    $fullName,

                    $email,

                    $userId
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE STUDENT
            |--------------------------------------------------------------------------
            */

            $updateStudent = $pdo->prepare("
                UPDATE students
                SET
                    student_number = ?,
                    sex = ?,
                    birthdate = ?,
                    year_level = ?,
                    course = ?,
                    section = ?
                WHERE id = ?
            ");


            $updateStudent->execute([

                $studentNumber,

                $sex,

                $birthdate,

                $yearLevel,

                $course,

                $section,

                $studentId

            ]);


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            $message =
                'Student information updated successfully.';
        }


        /*
        |--------------------------------------------------------------------------
        | DELETE STUDENT
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'delete') {

            $studentId =
                (int)($_POST['id'] ?? 0);


            if ($studentId <= 0) {

                throw new RuntimeException(
                    'Invalid student ID.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | GET USER ID
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT user_id
                FROM students
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([
                $studentId
            ]);


            $student =
                $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$student) {

                throw new RuntimeException(
                    'Student not found.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | DELETE USER
            |--------------------------------------------------------------------------
            |
            | If students.user_id has ON DELETE CASCADE,
            | the student record will also be removed.
            |
            */

            $deleteUser = $pdo->prepare("
                DELETE FROM users
                WHERE id = ?
            ");

            $deleteUser->execute([
                (int)$student['user_id']
            ]);


            $message =
                'Student deleted successfully.';
        }

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {

            $pdo->rollBack();
        }

        $error =
            $e->getMessage();
    }
}


/*
|--------------------------------------------------------------------------
| GET ALL STUDENTS
|--------------------------------------------------------------------------
*/

$studentsStmt = $pdo->query("

    SELECT

        s.id,

        s.user_id,

        s.student_number,

        s.sex,

        s.birthdate,

        s.year_level,

        s.course,

        s.section,

        s.qr_token,

        s.status,

        s.created_at,

        u.username,

        u.full_name,

        u.email

    FROM students s

    INNER JOIN users u
        ON u.id = s.user_id

    WHERE u.role = 'student'

    ORDER BY

        s.course ASC,

        s.year_level ASC,

        s.section ASC,

        u.full_name ASC

");


$students =
    $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!doctype html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
Manage Students - QR Attendance
</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<link
    href="../assets/css/style.css"
    rel="stylesheet"
>


<script
    src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js">
</script>


<style>

.student-table th {

    white-space: nowrap;

}

.qr-preview {

    width: 90px;

    height: 90px;

    margin: auto;

    display: flex;

    align-items: center;

    justify-content: center;

}

.qr-preview img {

    max-width: 90px;

    max-height: 90px;

}

.qr-modal-box {

    text-align: center;

    padding: 20px;

}

#modalQRCode {

    display: flex;

    justify-content: center;

    align-items: center;

    margin: 20px auto;

}

#modalQRCode img {

    width: 260px;

    height: 260px;

}

.student-details {

    background: #f8fafc;

    border-radius: 12px;

    padding: 15px;

    margin-bottom: 15px;

}

.course-badge {

    font-size: 12px;

}

@media print {

    body * {

        visibility: hidden;

    }

    #printArea,
    #printArea * {

        visibility: visible;

    }

    #printArea {

        position: absolute;

        left: 0;

        top: 0;

        width: 100%;

    }

}

</style>

</head>


<body class="app-bg">


<!-- =========================================================
     NAVIGATION
========================================================= -->

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
    href="classes.php"
    class="btn btn-sm btn-outline-light me-2"
>

    Manage Classes

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


<!-- =========================================================
     MAIN
========================================================= -->

<div class="container-fluid py-4">


<!-- HEADER -->

<div
    class="d-flex justify-content-between align-items-center mb-4"
>

<div>

<h2 class="fw-bold mb-1">

Manage Students

</h2>

<p class="text-muted mb-0">

Add, edit and manage student accounts and QR codes.

</p>

</div>


<span class="badge bg-primary fs-6">

<?= count($students) ?>

Students

</span>

</div>


<!-- =========================================================
     MESSAGES
========================================================= -->

<?php if ($message): ?>

<div
    class="alert alert-success alert-dismissible fade show"
>

<strong>
Success!
</strong>

<?= htmlspecialchars($message) ?>

<button
    type="button"
    class="btn-close"
    data-bs-dismiss="alert"
></button>

</div>

<?php endif; ?>


<?php if ($error): ?>

<div
    class="alert alert-danger alert-dismissible fade show"
>

<strong>
Error!
</strong>

<?= htmlspecialchars($error) ?>

<button
    type="button"
    class="btn-close"
    data-bs-dismiss="alert"
></button>

</div>

<?php endif; ?>


<!-- =========================================================
     ADD STUDENT
========================================================= -->

<div class="card shadow-sm mb-4">

<div class="card-body p-4">

<h5 class="fw-bold mb-3">

➕ Add New Student

</h5>


<form
    method="POST"
    class="row g-3"
>


<input
    type="hidden"
    name="action"
    value="add"
>


<!-- FULL NAME -->

<div class="col-lg-3 col-md-6">

<label class="form-label fw-bold">

Full Name *

</label>

<input
    type="text"
    class="form-control"
    name="full_name"
    placeholder="Juan Dela Cruz"
    required
>

</div>


<!-- STUDENT NUMBER -->

<div class="col-lg-2 col-md-6">

<label class="form-label fw-bold">

Student Number *

</label>

<input
    type="text"
    class="form-control"
    name="student_number"
    placeholder="2026-0001"
    required
>

</div>


<!-- SEX -->

<div class="col-lg-2 col-md-6">

<label class="form-label fw-bold">

Sex *

</label>

<select
    name="sex"
    class="form-select"
    required
>

<option value="">
Select Sex
</option>

<option value="Male">
Male
</option>

<option value="Female">
Female
</option>

</select>

</div>


<!-- BIRTHDATE -->

<div class="col-lg-2 col-md-6">

<label class="form-label fw-bold">

Birthdate *

</label>

<input
    type="date"
    class="form-control"
    name="birthdate"
    required
>

</div>


<!-- USERNAME -->

<div class="col-lg-3 col-md-6">

<label class="form-label fw-bold">

Username *

</label>

<input
    type="text"
    class="form-control"
    name="username"
    placeholder="student001"
    required
>

</div>


<!-- PASSWORD -->

<div class="col-lg-3 col-md-6">

<label class="form-label fw-bold">

Password *

</label>

<input
    type="password"
    class="form-control"
    name="password"
    placeholder="Password"
    required
>

</div>


<!-- EMAIL -->

<div class="col-lg-3 col-md-6">

<label class="form-label fw-bold">

Email

</label>

<input
    type="email"
    class="form-control"
    name="email"
    placeholder="student@example.com"
>

</div>


<!-- COURSE -->

<div class="col-lg-2 col-md-6">

<label class="form-label fw-bold">

Course *

</label>

<select
    name="course"
    id="addCourse"
    class="form-select"
    required
>

<option value="">

-- Select Course --

</option>

<?php

$uniqueCourses = [];

foreach ($classGroups as $class) {

    if (
        !in_array(
            $class['course'],
            $uniqueCourses,
            true
        )
    ) {

        $uniqueCourses[] =
            $class['course'];

    }
}

foreach ($uniqueCourses as $course):

?>

<option
    value="<?= htmlspecialchars($course) ?>"
>

<?= htmlspecialchars($course) ?>

</option>

<?php endforeach; ?>

</select>

</div>


<!-- YEAR LEVEL -->

<div class="col-lg-2 col-md-6">

<label class="form-label fw-bold">

Year Level *

</label>

<select
    name="year_level"
    id="addYear"
    class="form-select"
    required
    disabled
>

<option value="">

-- Select Course First --

</option>

</select>

</div>


<!-- SECTION -->

<div class="col-lg-2 col-md-6">

<label class="form-label fw-bold">

Section *

</label>

<select
    name="section"
    id="addSection"
    class="form-select"
    required
    disabled
>

<option value="">

-- Select Course and Year --

</option>

</select>

</div>


<!-- BUTTON -->

<div class="col-lg-2 col-md-6 d-flex align-items-end">

<button
    type="submit"
    class="btn btn-primary w-100"
>

➕ Add Student

</button>

</div>


</form>

</div>

</div>


<!-- =========================================================
     STUDENT LIST
========================================================= -->

<div class="card shadow-sm">

<div class="card-body">

<div class="d-flex justify-content-between align-items-center mb-3">

<div>

<h5 class="fw-bold mb-1">

Student List

</h5>

<small class="text-muted">

Students are assigned to the classes created by the Admin.

</small>

</div>

</div>


<div class="table-responsive">

<table
    class="table table-hover align-middle student-table"
>

<thead class="table-dark">

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
Sex
</th>

<th>
Birthdate
</th>

<th>
Username
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

<th class="text-center">
QR Code
</th>

<th>
Status
</th>

<th>
Action
</th>

</tr>

</thead>


<tbody>


<?php if (!$students): ?>

<tr>

<td
    colspan="12"
    class="text-center text-muted py-5"
>

No students have been added yet.

</td>

</tr>

<?php endif; ?>


<?php foreach ($students as $i => $s): ?>

<tr>


<td>

<?= $i + 1 ?>

</td>


<td>

<strong>

<?= htmlspecialchars(
    $s['full_name']
) ?>

</strong>

<br>

<small class="text-muted">

<?= htmlspecialchars(
    $s['email'] ?? ''
) ?>

</small>

</td>


<td>

<?= htmlspecialchars(
    $s['student_number']
) ?>

</td>


<td>

<?= htmlspecialchars(
    $s['sex'] ?? '—'
) ?>

</td>


<td>

<?= !empty($s['birthdate'])
    ? date(
        'M d, Y',
        strtotime($s['birthdate'])
    )
    : '—'
?>

</td>


<td>

<?= htmlspecialchars(
    $s['username']
) ?>

</td>


<td>

<span class="badge bg-primary course-badge">

<?= htmlspecialchars(
    $s['course']
) ?>

</span>

</td>


<td>

<?= htmlspecialchars(
    $s['year_level']
) ?>

</td>


<td>

<?= htmlspecialchars(
    $s['section']
) ?>

</td>


<!-- QR -->

<td class="text-center">

<div
    class="qr-preview"
    id="qr-<?= (int)$s['id'] ?>"
></div>


<button
    type="button"
    class="btn btn-sm btn-primary mt-2"
    onclick="showQR(
        <?= (int)$s['id'] ?>,
        <?= htmlspecialchars(
            json_encode($s['full_name']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        <?= htmlspecialchars(
            json_encode($s['student_number']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        <?= htmlspecialchars(
            json_encode($s['sex']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        <?= htmlspecialchars(
            json_encode($s['birthdate']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        <?= htmlspecialchars(
            json_encode($s['year_level']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        <?= htmlspecialchars(
            json_encode($s['course']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        <?= htmlspecialchars(
            json_encode($s['section']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        <?= htmlspecialchars(
            json_encode($s['qr_token']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    )"
>

View QR

</button>

</td>


<!-- STATUS -->

<td>

<?php if (
    $s['status'] === 'active'
): ?>

<span class="badge bg-success">

Active

</span>

<?php else: ?>

<span class="badge bg-secondary">

Inactive

</span>

<?php endif; ?>

</td>


<!-- ACTION -->

<td>

<div class="d-flex gap-2">

<button
    type="button"
    class="btn btn-sm btn-outline-primary"
    onclick="editStudent(
        <?= (int)$s['id'] ?>,
        <?= htmlspecialchars(
            json_encode($s['full_name']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        <?= htmlspecialchars(
            json_encode($s['student_number']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        <?= htmlspecialchars(
            json_encode($s['sex']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        <?= htmlspecialchars(
            json_encode($s['birthdate']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        <?= htmlspecialchars(
            json_encode($s['username']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        <?= htmlspecialchars(
            json_encode($s['email']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        <?= htmlspecialchars(
            json_encode($s['course']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        <?= htmlspecialchars(
            json_encode($s['year_level']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>,
        <?= htmlspecialchars(
            json_encode($s['section']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    )"
>

✏️ Edit

</button>


<form
    method="POST"
    onsubmit="return confirm(
        'Are you sure you want to delete this student?'
    );"
>

<input
    type="hidden"
    name="action"
    value="delete"
>

<input
    type="hidden"
    name="id"
    value="<?= (int)$s['id'] ?>"
>

<button
    type="submit"
    class="btn btn-sm btn-outline-danger"
>

🗑 Delete

</button>

</form>

</div>

</td>

</tr>

<?php endforeach; ?>


</tbody>

</table>

</div>

</div>

</div>


</div>


<!-- =========================================================
     EDIT STUDENT MODAL
========================================================= -->

<div
    class="modal fade"
    id="editStudentModal"
    tabindex="-1"
>

<div
    class="modal-dialog modal-lg modal-dialog-centered"
>

<div class="modal-content">


<div class="modal-header">

<h5 class="modal-title fw-bold">

✏️ Edit Student

</h5>

<button
    type="button"
    class="btn-close"
    data-bs-dismiss="modal"
></button>

</div>


<form
    method="POST"
>

<div class="modal-body">

<input
    type="hidden"
    name="action"
    value="edit"
>

<input
    type="hidden"
    name="student_id"
    id="editStudentId"
>


<div class="row g-3">


<!-- NAME -->

<div class="col-md-6">

<label class="form-label fw-bold">

Full Name *

</label>

<input
    type="text"
    name="full_name"
    id="editFullName"
    class="form-control"
    required
>

</div>


<!-- STUDENT NUMBER -->

<div class="col-md-6">

<label class="form-label fw-bold">

Student Number *

</label>

<input
    type="text"
    name="student_number"
    id="editStudentNumber"
    class="form-control"
    required
>

</div>


<!-- SEX -->

<div class="col-md-4">

<label class="form-label fw-bold">

Sex *

</label>

<select
    name="sex"
    id="editSex"
    class="form-select"
    required
>

<option value="Male">
Male
</option>

<option value="Female">
Female
</option>

</select>

</div>


<!-- BIRTHDATE -->

<div class="col-md-4">

<label class="form-label fw-bold">

Birthdate *

</label>

<input
    type="date"
    name="birthdate"
    id="editBirthdate"
    class="form-control"
    required
>

</div>


<!-- USERNAME -->

<div class="col-md-4">

<label class="form-label fw-bold">

Username *

</label>

<input
    type="text"
    name="username"
    id="editUsername"
    class="form-control"
    required
>

</div>


<!-- EMAIL -->

<div class="col-md-6">

<label class="form-label fw-bold">

Email

</label>

<input
    type="email"
    name="email"
    id="editEmail"
    class="form-control"
>

</div>


<!-- PASSWORD -->

<div class="col-md-6">

<label class="form-label fw-bold">

New Password

</label>

<input
    type="password"
    name="password"
    class="form-control"
    placeholder="Leave blank to keep current password"
>

<div class="form-text">

Only enter a password if you want to change it.

</div>

</div>


<!-- COURSE -->

<div class="col-md-4">

<label class="form-label fw-bold">

Course *

</label>

<select
    name="course"
    id="editCourse"
    class="form-select"
    required
>

<option value="">

-- Select Course --

</option>

<?php foreach ($uniqueCourses as $course): ?>

<option
    value="<?= htmlspecialchars($course) ?>"
>

<?= htmlspecialchars($course) ?>

</option>

<?php endforeach; ?>

</select>

</div>


<!-- YEAR -->

<div class="col-md-4">

<label class="form-label fw-bold">

Year Level *

</label>

<select
    name="year_level"
    id="editYear"
    class="form-select"
    required
>

<option value="">

-- Select Course First --

</option>

</select>

</div>


<!-- SECTION -->

<div class="col-md-4">

<label class="form-label fw-bold">

Section *

</label>

<select
    name="section"
    id="editSection"
    class="form-select"
    required
>

<option value="">

-- Select Course and Year --

</option>

</select>

</div>


</div>


</div>


<div class="modal-footer">

<button
    type="button"
    class="btn btn-secondary"
    data-bs-dismiss="modal"
>

Cancel

</button>


<button
    type="submit"
    class="btn btn-primary"
>

💾 Save Changes

</button>

</div>

</form>

</div>

</div>

</div>


<!-- =========================================================
     QR MODAL
========================================================= -->

<div
    class="modal fade"
    id="qrModal"
    tabindex="-1"
>

<div
    class="modal-dialog modal-dialog-centered"
>

<div class="modal-content">


<div class="modal-header">

<h5 class="modal-title">

Student QR Code

</h5>

<button
    type="button"
    class="btn-close"
    data-bs-dismiss="modal"
></button>

</div>


<div class="modal-body">

<div
    class="qr-modal-box"
    id="printArea"
>


<h4
    id="modalStudentName"
    class="fw-bold"
>
</h4>


<div
    id="modalStudentNumber"
    class="text-muted"
>
</div>


<div class="student-details mt-3">


<div>

<strong>
Sex:
</strong>

<span id="modalSex">
</span>

</div>


<div>

<strong>
Birthdate:
</strong>

<span id="modalBirthdate">
</span>

</div>


<div>

<strong>
Year Level:
</strong>

<span id="modalYear">
</span>

</div>


<div>

<strong>
Course:
</strong>

<span id="modalCourse">
</span>

</div>


<div>

<strong>
Section:
</strong>

<span id="modalSection">
</span>

</div>


</div>


<div id="modalQRCode"></div>


<div
    class="small text-muted"
    id="modalQRToken"
>
</div>


<p class="small mt-3">

Present this QR code to the teacher
for attendance scanning.

</p>


</div>

</div>


<div class="modal-footer">

<button
    type="button"
    class="btn btn-success"
    onclick="downloadQR()"
>

⬇ Download QR

</button>


<button
    type="button"
    class="btn btn-primary"
    onclick="printQR()"
>

🖨 Print QR

</button>


<button
    type="button"
    class="btn btn-secondary"
    data-bs-dismiss="modal"
>

Close

</button>

</div>

</div>

</div>

</div>


<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<script>

/*
|--------------------------------------------------------------------------
| CLASS GROUP DATA FROM DATABASE
|--------------------------------------------------------------------------
*/

const classGroups =
    <?= json_encode(
        $classGroups,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP
    ) ?>;


/*
|--------------------------------------------------------------------------
| ADD STUDENT SELECTS
|--------------------------------------------------------------------------
*/

const addCourse =
    document.getElementById('addCourse');

const addYear =
    document.getElementById('addYear');

const addSection =
    document.getElementById('addSection');


/*
|--------------------------------------------------------------------------
| UPDATE YEAR LEVELS
|--------------------------------------------------------------------------
*/

function updateAddYears() {

    const course =
        addCourse.value;

    addYear.innerHTML =
        '<option value="">-- Select Year Level --</option>';

    addSection.innerHTML =
        '<option value="">-- Select Course and Year --</option>';

    addYear.disabled = true;

    addSection.disabled = true;


    if (!course) {

        return;

    }


    const years = [];


    classGroups.forEach(function (item) {

        if (
            item.course === course &&
            !years.includes(item.year_level)
        ) {

            years.push(item.year_level);

        }

    });


    years.sort(function (a, b) {

        return a.localeCompare(b, undefined, {
            numeric: true
        });

    });


    years.forEach(function (year) {

        const option =
            document.createElement('option');

        option.value =
            year;

        option.textContent =
            year;

        addYear.appendChild(option);

    });


    addYear.disabled =
        false;

}


addCourse.addEventListener(
    'change',
    updateAddYears
);


/*
|--------------------------------------------------------------------------
| UPDATE SECTIONS
|--------------------------------------------------------------------------
*/

addYear.addEventListener(
    'change',
    function () {

        const course =
            addCourse.value;

        const year =
            addYear.value;


        addSection.innerHTML =
            '<option value="">-- Select Section --</option>';

        addSection.disabled =
            true;


        if (!course || !year) {

            return;

        }


        const sections = [];


        classGroups.forEach(function (item) {

            if (
                item.course === course &&
                item.year_level === year &&
                !sections.includes(item.section)
            ) {

                sections.push(item.section);

            }

        });


        sections.sort();


        sections.forEach(function (section) {

            const option =
                document.createElement('option');

            option.value =
                section;

            option.textContent =
                section;

            addSection.appendChild(option);

        });


        addSection.disabled =
            false;

    }
);


/*
|--------------------------------------------------------------------------
| EDIT STUDENT
|--------------------------------------------------------------------------
*/

function editStudent(
    id,
    fullName,
    studentNumber,
    sex,
    birthdate,
    username,
    email,
    course,
    year,
    section
) {

    document.getElementById(
        'editStudentId'
    ).value = id;


    document.getElementById(
        'editFullName'
    ).value = fullName;


    document.getElementById(
        'editStudentNumber'
    ).value = studentNumber;


    document.getElementById(
        'editSex'
    ).value = sex;


    document.getElementById(
        'editBirthdate'
    ).value = birthdate;


    document.getElementById(
        'editUsername'
    ).value = username;


    document.getElementById(
        'editEmail'
    ).value = email || '';


    /*
    |--------------------------------------------------------------------------
    | COURSE
    |--------------------------------------------------------------------------
    */

    const editCourse =
        document.getElementById('editCourse');

    const editYear =
        document.getElementById('editYear');

    const editSection =
        document.getElementById('editSection');


    editCourse.value =
        course;


    /*
    |--------------------------------------------------------------------------
    | LOAD YEARS
    |--------------------------------------------------------------------------
    */

    editYear.innerHTML =
        '<option value="">-- Select Year Level --</option>';

    editSection.innerHTML =
        '<option value="">-- Select Section --</option>';


    const years = [];


    classGroups.forEach(function (item) {

        if (
            item.course === course &&
            !years.includes(item.year_level)
        ) {

            years.push(item.year_level);

        }

    });


    years.sort(function (a, b) {

        return a.localeCompare(b, undefined, {
            numeric: true
        });

    });


    years.forEach(function (itemYear) {

        const option =
            document.createElement('option');

        option.value =
            itemYear;

        option.textContent =
            itemYear;

        editYear.appendChild(option);

    });


    editYear.value =
        year;


    /*
    |--------------------------------------------------------------------------
    | LOAD SECTIONS
    |--------------------------------------------------------------------------
    */

    const sections = [];


    classGroups.forEach(function (item) {

        if (
            item.course === course &&
            item.year_level === year &&
            !sections.includes(item.section)
        ) {

            sections.push(item.section);

        }

    });


    sections.sort();


    sections.forEach(function (itemSection) {

        const option =
            document.createElement('option');

        option.value =
            itemSection;

        option.textContent =
            itemSection;

        editSection.appendChild(option);

    });


    editSection.value =
        section;


    /*
    |--------------------------------------------------------------------------
    | OPEN EDIT MODAL
    |--------------------------------------------------------------------------
    */

    const modal =
        new bootstrap.Modal(
            document.getElementById(
                'editStudentModal'
            )
        );


    modal.show();

}


/*
|--------------------------------------------------------------------------
| EDIT COURSE CHANGE
|--------------------------------------------------------------------------
*/

document.getElementById(
    'editCourse'
).addEventListener(
    'change',
    function () {

        const course =
            this.value;

        const editYear =
            document.getElementById(
                'editYear'
            );

        const editSection =
            document.getElementById(
                'editSection'
            );


        editYear.innerHTML =
            '<option value="">-- Select Year Level --</option>';

        editSection.innerHTML =
            '<option value="">-- Select Section --</option>';


        if (!course) {

            return;

        }


        const years = [];


        classGroups.forEach(function (item) {

            if (
                item.course === course &&
                !years.includes(item.year_level)
            ) {

                years.push(item.year_level);

            }

        });


        years.sort(function (a, b) {

            return a.localeCompare(b, undefined, {
                numeric: true
            });

        });


        years.forEach(function (year) {

            const option =
                document.createElement('option');

            option.value =
                year;

            option.textContent =
                year;

            editYear.appendChild(option);

        });

    }
);


/*
|--------------------------------------------------------------------------
| EDIT YEAR CHANGE
|--------------------------------------------------------------------------
*/

document.getElementById(
    'editYear'
).addEventListener(
    'change',
    function () {

        const course =
            document.getElementById(
                'editCourse'
            ).value;

        const year =
            this.value;

        const editSection =
            document.getElementById(
                'editSection'
            );


        editSection.innerHTML =
            '<option value="">-- Select Section --</option>';


        if (!course || !year) {

            return;

        }


        const sections = [];


        classGroups.forEach(function (item) {

            if (
                item.course === course &&
                item.year_level === year &&
                !sections.includes(item.section)
            ) {

                sections.push(item.section);

            }

        });


        sections.sort();


        sections.forEach(function (section) {

            const option =
                document.createElement('option');

            option.value =
                section;

            option.textContent =
                section;

            editSection.appendChild(option);

        });

    }
);


/*
|--------------------------------------------------------------------------
| QR VARIABLES
|--------------------------------------------------------------------------
*/

let currentQRToken = '';

let currentStudentName = '';

let qrModalInstance = null;


/*
|--------------------------------------------------------------------------
| CREATE SMALL QR CODES
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        <?php foreach ($students as $s): ?>

        new QRCode(

            document.getElementById(
                "qr-<?= (int)$s['id'] ?>"
            ),

            {

                text:
                    <?= json_encode(
                        $s['qr_token']
                    ) ?>,

                width: 80,

                height: 80,

                correctLevel:
                    QRCode.CorrectLevel.M

            }

        );

        <?php endforeach; ?>


        qrModalInstance =
            new bootstrap.Modal(
                document.getElementById(
                    'qrModal'
                )
            );

    }
);


/*
|--------------------------------------------------------------------------
| SHOW QR
|--------------------------------------------------------------------------
*/

function showQR(
    studentId,
    name,
    studentNumber,
    sex,
    birthdate,
    year,
    course,
    section,
    qrToken
) {

    currentQRToken =
        qrToken;

    currentStudentName =
        name;


    document.getElementById(
        'modalStudentName'
    ).textContent =
        name;


    document.getElementById(
        'modalStudentNumber'
    ).textContent =
        studentNumber;


    document.getElementById(
        'modalSex'
    ).textContent =
        sex;


    document.getElementById(
        'modalBirthdate'
    ).textContent =
        birthdate;


    document.getElementById(
        'modalYear'
    ).textContent =
        year;


    document.getElementById(
        'modalCourse'
    ).textContent =
        course;


    document.getElementById(
        'modalSection'
    ).textContent =
        section;


    document.getElementById(
        'modalQRToken'
    ).textContent =
        'QR ID: ' + qrToken;


    const qrContainer =
        document.getElementById(
            'modalQRCode'
        );


    qrContainer.innerHTML =
        '';


    new QRCode(

        qrContainer,

        {

            text:
                qrToken,

            width:
                260,

            height:
                260,

            correctLevel:
                QRCode.CorrectLevel.H

        }

    );


    if (qrModalInstance) {

        qrModalInstance.show();

    }

}


/*
|--------------------------------------------------------------------------
| DOWNLOAD QR
|--------------------------------------------------------------------------
*/

function downloadQR() {

    const qrContainer =
        document.getElementById(
            'modalQRCode'
        );


    const image =
        qrContainer.querySelector(
            'img'
        );


    if (!image) {

        alert(
            'QR code is not ready yet.'
        );

        return;

    }


    const link =
        document.createElement(
            'a'
        );


    link.href =
        image.src;


    link.download =
        'QR-' +
        currentStudentName
            .replace(
                /[^a-z0-9]/gi,
                '_'
            ) +
        '.png';


    document.body.appendChild(
        link
    );


    link.click();


    document.body.removeChild(
        link
    );

}


/*
|--------------------------------------------------------------------------
| PRINT QR
|--------------------------------------------------------------------------
*/

function printQR() {

    window.print();

}

</script>


</body>

</html>