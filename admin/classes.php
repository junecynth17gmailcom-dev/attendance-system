<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

require_login('admin');

$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| SELECTED COURSE AND YEAR LEVEL
|--------------------------------------------------------------------------
*/

$selectedCourse = trim($_GET['course'] ?? '');
$selectedYear   = trim($_GET['year_level'] ?? '');


/*
|--------------------------------------------------------------------------
| ADD / DELETE / TOGGLE CLASS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    try {

        /*
        |--------------------------------------------------------------------------
        | ADD CLASS
        |--------------------------------------------------------------------------
        */

        if ($action === 'add') {

            $course = trim($_POST['course'] ?? '');
            $yearLevel = trim($_POST['year_level'] ?? '');
            $section = trim($_POST['section'] ?? '');

            if (
                $course === '' ||
                $yearLevel === '' ||
                $section === ''
            ) {

                throw new RuntimeException(
                    'Please complete Course, Year Level and Section.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | CHECK DUPLICATE
            |--------------------------------------------------------------------------
            */

            $check = $pdo->prepare("
                SELECT id
                FROM class_groups
                WHERE course = ?
                AND year_level = ?
                AND section = ?
                LIMIT 1
            ");

            $check->execute([
                $course,
                $yearLevel,
                $section
            ]);


            if ($check->fetch()) {

                throw new RuntimeException(
                    'This Course, Year Level and Section already exists.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | INSERT CLASS
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO class_groups
                (
                    course,
                    year_level,
                    section,
                    status
                )
                VALUES
                (?, ?, ?, 'active')
            ");

            $stmt->execute([
                $course,
                $yearLevel,
                $section
            ]);


            $message = 'Class added successfully.';


            /*
            |--------------------------------------------------------------------------
            | KEEP SELECTED FILTER
            |--------------------------------------------------------------------------
            */

            $selectedCourse = $course;
            $selectedYear = $yearLevel;

        }


        /*
        |--------------------------------------------------------------------------
        | DELETE CLASS
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'delete') {

            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {

                throw new RuntimeException(
                    'Invalid class.'
                );
            }


            $stmt = $pdo->prepare("
                DELETE FROM class_groups
                WHERE id = ?
            ");

            $stmt->execute([
                $id
            ]);


            $message = 'Class deleted successfully.';


            /*
            | Keep selected values after delete
            */

            $selectedCourse =
                trim($_POST['selected_course'] ?? '');

            $selectedYear =
                trim($_POST['selected_year'] ?? '');
        }


        /*
        |--------------------------------------------------------------------------
        | TOGGLE STATUS
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'toggle') {

            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {

                throw new RuntimeException(
                    'Invalid class.'
                );
            }


            $stmt = $pdo->prepare("
                UPDATE class_groups

                SET status =
                    CASE
                        WHEN status = 'active'
                        THEN 'inactive'
                        ELSE 'active'
                    END

                WHERE id = ?
            ");

            $stmt->execute([
                $id
            ]);


            $message = 'Class status updated.';


            /*
            | Keep selected values after toggle
            */

            $selectedCourse =
                trim($_POST['selected_course'] ?? '');

            $selectedYear =
                trim($_POST['selected_year'] ?? '');
        }

    }

    catch (PDOException $e) {

        $error =
            'Database error: ' .
            $e->getMessage();

    }

    catch (Throwable $e) {

        $error =
            $e->getMessage();
    }
}


/*
|--------------------------------------------------------------------------
| GET COURSES
|--------------------------------------------------------------------------
|
| These are used in the Course dropdown.
|
*/

$courseStmt = $pdo->query("
    SELECT DISTINCT course
    FROM class_groups
    WHERE course IS NOT NULL
    AND course <> ''
    ORDER BY course ASC
");

$courses = $courseStmt->fetchAll(PDO::FETCH_COLUMN);


/*
|--------------------------------------------------------------------------
| GET YEAR LEVELS
|--------------------------------------------------------------------------
|
| These are all available year levels.
|
*/

$yearStmt = $pdo->query("
    SELECT DISTINCT year_level
    FROM class_groups
    WHERE year_level IS NOT NULL
    AND year_level <> ''
    ORDER BY
        CASE year_level
            WHEN '1st Year' THEN 1
            WHEN '2nd Year' THEN 2
            WHEN '3rd Year' THEN 3
            WHEN '4th Year' THEN 4
            ELSE 5
        END,
        year_level ASC
");

$yearLevels = $yearStmt->fetchAll(PDO::FETCH_COLUMN);


/*
|--------------------------------------------------------------------------
| GET SECTIONS FOR SELECTED COURSE + YEAR
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| There is NO section dropdown.
|
| When admin chooses:
|
| BSIT + 2nd Year
|
| this query automatically gets:
|
| Section A
| Section B
| Section C
|
|--------------------------------------------------------------------------
*/

$filteredClasses = [];

if (
    $selectedCourse !== '' &&
    $selectedYear !== ''
) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            course,
            year_level,
            section,
            status,
            created_at

        FROM class_groups

        WHERE course = ?
        AND year_level = ?

        ORDER BY
            section ASC
    ");

    $stmt->execute([
        $selectedCourse,
        $selectedYear
    ]);

    $filteredClasses =
        $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/*
|--------------------------------------------------------------------------
| AVAILABLE SECTIONS
|--------------------------------------------------------------------------
*/

$sections = [];

foreach ($filteredClasses as $class) {

    $sections[] = $class['section'];
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
Manage Classes
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

/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

body {
    min-height: 100vh;
}


/*
|--------------------------------------------------------------------------
| COURSE CARD
|--------------------------------------------------------------------------
*/

.course-card {

    border: none;

    border-radius: 16px;

    overflow: hidden;

}


/*
|--------------------------------------------------------------------------
| COURSE HEADER
|--------------------------------------------------------------------------
*/

.course-header {

    background: linear-gradient(
        135deg,
        #0d6efd,
        #084298
    );

    color: white;

    padding: 22px 24px;

}


.course-title {

    font-size: 24px;

    font-weight: 700;

    margin: 0;

}


.course-subtitle {

    font-size: 14px;

    opacity: .9;

    margin-top: 4px;

}


/*
|--------------------------------------------------------------------------
| FILTER CARD
|--------------------------------------------------------------------------
*/

.filter-card {

    border: none;

    border-radius: 16px;

}


/*
|--------------------------------------------------------------------------
| TABLE
|--------------------------------------------------------------------------
*/

.table th {

    background: #f1f5f9;

    font-weight: 700;

}


.table td {

    vertical-align: middle;

}


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

.status-active {

    background: #198754;

}


.status-inactive {

    background: #6c757d;

}


/*
|--------------------------------------------------------------------------
| SECTION BADGE
|--------------------------------------------------------------------------
*/

.section-badge {

    font-size: 14px;

    padding: 8px 12px;

}


/*
|--------------------------------------------------------------------------
| EMPTY BOX
|--------------------------------------------------------------------------
*/

.empty-box {

    padding: 50px 20px;

    text-align: center;

}


/*
|--------------------------------------------------------------------------
| SELECTED CLASS
|--------------------------------------------------------------------------
*/

.selected-info {

    border-left: 5px solid #0d6efd;

    background: #f8fafc;

    padding: 15px 18px;

    border-radius: 8px;

}

</style>

</head>


<body class="app-bg">


<!-- =====================================================
     NAVBAR
===================================================== -->

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



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<div class="container py-4">


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<div class="mb-4">

    <h2 class="fw-bold mb-1">

        Manage Classes

    </h2>


    <p class="text-muted mb-0">

        Select a Course and Year Level to display all
        sections under that class.

    </p>

</div>



<!-- =====================================================
     MESSAGES
===================================================== -->

<?php if ($message): ?>

<div class="alert alert-success alert-dismissible fade show">

    <strong>Success!</strong>

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

    <strong>Error!</strong>

    <?= htmlspecialchars($error) ?>


    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
    ></button>

</div>

<?php endif; ?>



<!-- =====================================================
     ADD NEW CLASS
===================================================== -->

<div class="card shadow-sm mb-4">

    <div class="card-body p-4">


        <h4 class="fw-bold mb-3">

            ➕ Add New Class

        </h4>


        <form
            method="POST"
            class="row g-3"
        >


            <input
                type="hidden"
                name="action"
                value="add"
            >


            <!-- COURSE -->

            <div class="col-md-4">

                <label class="form-label fw-bold">

                    Course

                </label>


                <input
                    type="text"
                    name="course"
                    class="form-control"
                    placeholder="Example: BSIT"
                    required
                >


                <div class="form-text">

                    Example: BSIT, BSBA, BSHM

                </div>

            </div>



            <!-- YEAR LEVEL -->

            <div class="col-md-4">

                <label class="form-label fw-bold">

                    Year Level

                </label>


                <select
                    name="year_level"
                    class="form-select"
                    required
                >

                    <option value="">

                        -- Select Year Level --

                    </option>


                    <option value="1st Year">

                        1st Year

                    </option>


                    <option value="2nd Year">

                        2nd Year

                    </option>


                    <option value="3rd Year">

                        3rd Year

                    </option>


                    <option value="4th Year">

                        4th Year

                    </option>

                </select>

            </div>



            <!-- SECTION -->

            <div class="col-md-4">

                <label class="form-label fw-bold">

                    Section

                </label>


                <input
                    type="text"
                    name="section"
                    class="form-control"
                    placeholder="Example: Section A"
                    required
                >


                <div class="form-text">

                    Add each section separately.

                </div>

            </div>



            <!-- BUTTON -->

            <div class="col-12">

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    ➕ Add Class

                </button>

            </div>


        </form>

    </div>

</div>



<!-- =====================================================
     SELECT COURSE + YEAR LEVEL
===================================================== -->

<div class="card shadow-sm filter-card mb-4">

    <div class="card-body p-4">


        <h4 class="fw-bold mb-3">

            🔎 Select Class

        </h4>


        <form
            method="GET"
            id="classFilterForm"
            class="row g-3"
        >


            <!-- COURSE SELECT -->

            <div class="col-md-6">

                <label class="form-label fw-bold">

                    Course

                </label>


                <select
                    name="course"
                    id="courseSelect"
                    class="form-select form-select-lg"
                    required
                >

                    <option value="">

                        -- Select Course --

                    </option>


                    <?php foreach ($courses as $course): ?>

                        <option
                            value="<?= htmlspecialchars($course) ?>"
                            <?= $selectedCourse === $course
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars($course) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>



            <!-- YEAR LEVEL SELECT -->

            <div class="col-md-6">

                <label class="form-label fw-bold">

                    Year Level

                </label>


                <select
                    name="year_level"
                    id="yearSelect"
                    class="form-select form-select-lg"
                    required
                >

                    <option value="">

                        -- Select Year Level --

                    </option>


                    <?php foreach ($yearLevels as $year): ?>

                        <option
                            value="<?= htmlspecialchars($year) ?>"
                            <?= $selectedYear === $year
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars($year) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>



            <div class="col-12">

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    🔎 Show Classes

                </button>


                <a
                    href="classes.php"
                    class="btn btn-outline-secondary"
                >

                    Reset

                </a>

            </div>


        </form>

    </div>

</div>



<!-- =====================================================
     SHOW TABLE ONLY AFTER COURSE + YEAR LEVEL SELECTED
===================================================== -->

<?php if (
    $selectedCourse !== '' &&
    $selectedYear !== ''
): ?>


<!-- =====================================================
     SELECTED CLASS INFORMATION
===================================================== -->

<div class="selected-info mb-4">

    <div class="fw-bold">

        Selected Class

    </div>


    <div class="fs-5">

        <?= htmlspecialchars($selectedCourse) ?>

        -

        <?= htmlspecialchars($selectedYear) ?>

    </div>


    <div class="text-muted">

        All sections under this Course and Year Level
        are displayed below.

    </div>

</div>



<!-- =====================================================
     TABLE
===================================================== -->

<div class="card shadow-sm course-card mb-4">


    <!-- COURSE HEADER -->

    <div class="course-header">

        <div class="d-flex justify-content-between
                    align-items-center">


            <div>

                <div class="course-title">

                    <?= htmlspecialchars(
                        $selectedCourse
                    ) ?>

                    -

                    <?= htmlspecialchars(
                        $selectedYear
                    ) ?>

                </div>


                <div class="course-subtitle">

                    Sections for this Course and Year Level

                </div>

            </div>


            <span class="badge bg-light text-dark fs-6">

                <?= count($filteredClasses) ?>

                Section/s

            </span>


        </div>

    </div>



    <!-- TABLE -->

    <?php if ($filteredClasses): ?>

    <div class="table-responsive">

        <table
            class="table table-hover align-middle mb-0"
        >

            <thead>

                <tr>

                    <th style="width:70px;">

                        #

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

                        Status

                    </th>


                    <th>

                        Action

                    </th>

                </tr>

            </thead>


            <tbody>


            <?php foreach (
                $filteredClasses
                as $index => $class
            ): ?>


                <tr>


                    <!-- NUMBER -->

                    <td>

                        <strong>

                            <?= $index + 1 ?>

                        </strong>

                    </td>



                    <!-- COURSE -->

                    <td>

                        <strong>

                            <?= htmlspecialchars(
                                $class['course']
                            ) ?>

                        </strong>

                    </td>



                    <!-- YEAR -->

                    <td>

                        <?= htmlspecialchars(
                            $class['year_level']
                        ) ?>

                    </td>



                    <!-- SECTION -->

                    <td>

                        <span
                            class="badge bg-primary section-badge"
                        >

                            <?= htmlspecialchars(
                                $class['section']
                            ) ?>

                        </span>

                    </td>



                    <!-- STATUS -->

                    <td>

                        <?php if (
                            $class['status']
                            === 'active'
                        ): ?>

                            <span
                                class="badge status-active"
                            >

                                Active

                            </span>

                        <?php else: ?>

                            <span
                                class="badge status-inactive"
                            >

                                Inactive

                            </span>

                        <?php endif; ?>

                    </td>



                    <!-- ACTION -->

                    <td>

                        <div class="d-flex gap-2">


                            <!-- TOGGLE -->

                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="action"
                                    value="toggle"
                                >


                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int)$class['id'] ?>"
                                >


                                <input
                                    type="hidden"
                                    name="selected_course"
                                    value="<?= htmlspecialchars(
                                        $selectedCourse
                                    ) ?>"
                                >


                                <input
                                    type="hidden"
                                    name="selected_year"
                                    value="<?= htmlspecialchars(
                                        $selectedYear
                                    ) ?>"
                                >


                                <button
                                    type="submit"
                                    class="btn btn-sm btn-outline-warning"
                                >

                                    <?php

                                    echo $class['status']
                                        === 'active'
                                        ? 'Deactivate'
                                        : 'Activate';

                                    ?>

                                </button>

                            </form>



                            <!-- DELETE -->

                            <form
                                method="POST"
                                onsubmit="
                                    return confirm(
                                        'Delete this section?'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="delete"
                                >


                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int)$class['id'] ?>"
                                >


                                <input
                                    type="hidden"
                                    name="selected_course"
                                    value="<?= htmlspecialchars(
                                        $selectedCourse
                                    ) ?>"
                                >


                                <input
                                    type="hidden"
                                    name="selected_year"
                                    value="<?= htmlspecialchars(
                                        $selectedYear
                                    ) ?>"
                                >


                                <button
                                    type="submit"
                                    class="btn btn-sm btn-outline-danger"
                                >

                                    Delete

                                </button>

                            </form>


                        </div>

                    </td>


                </tr>


            <?php endforeach; ?>


            </tbody>

        </table>

    </div>


    <?php else: ?>


        <div class="empty-box">

            <h5 class="text-muted">

                No sections found.

            </h5>


            <p class="text-muted mb-0">

                There are no sections registered for

                <strong>
                    <?= htmlspecialchars($selectedCourse) ?>
                </strong>

                -

                <strong>
                    <?= htmlspecialchars($selectedYear) ?>
                </strong>.

            </p>

        </div>


    <?php endif; ?>


</div>



<?php else: ?>


<!-- =====================================================
     NOTHING SELECTED
===================================================== -->

<div class="card shadow-sm">

    <div class="empty-box">

        <div style="font-size:50px;">

            📚

        </div>


        <h5 class="fw-bold mt-3">

            Select a Course and Year Level

        </h5>


        <p class="text-muted mb-0">

            Choose a Course and Year Level above.

            The system will automatically display
            all sections belonging to that class.

        </p>

    </div>

</div>


<?php endif; ?>


</div>



<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>