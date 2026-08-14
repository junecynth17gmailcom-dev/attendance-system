<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

require_login('teacher');

$teacherId = current_user_id();

$message = '';
$error = '';


// =====================================================
// ADD SUBJECT
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'add_subject') {

        $subjectCode = trim(
            $_POST['subject_code'] ?? ''
        );

        $subjectName = trim(
            $_POST['subject_name'] ?? ''
        );


        if (
            $subjectCode === '' ||
            $subjectName === ''
        ) {

            $error =
                'Please enter Subject Code and Subject Name.';

        } else {

            try {

                // Check teacher exists
                $checkTeacher = $pdo->prepare("
                    SELECT id
                    FROM users
                    WHERE id = ?
                    AND role = 'teacher'
                    LIMIT 1
                ");

                $checkTeacher->execute([
                    $teacherId
                ]);


                if (!$checkTeacher->fetch()) {

                    throw new RuntimeException(
                        'Your teacher account was not found. Please log in again.'
                    );
                }


                // Check duplicate subject
                $checkSubject = $pdo->prepare("
                    SELECT id
                    FROM subjects
                    WHERE teacher_id = ?
                    AND subject_code = ?
                    LIMIT 1
                ");

                $checkSubject->execute([
                    $teacherId,
                    $subjectCode
                ]);


                if ($checkSubject->fetch()) {

                    throw new RuntimeException(
                        'You already have this subject code.'
                    );
                }


                // Insert subject
                $stmt = $pdo->prepare("
                    INSERT INTO subjects
                    (
                        teacher_id,
                        subject_code,
                        subject_name
                    )
                    VALUES
                    (?, ?, ?)
                ");

                $stmt->execute([
                    $teacherId,
                    $subjectCode,
                    $subjectName
                ]);


                $message =
                    'Subject added successfully.';


            } catch (PDOException $e) {

                $error =
                    'Database error: ' .
                    $e->getMessage();

            } catch (Throwable $e) {

                $error =
                    $e->getMessage();
            }
        }
    }
}


// =====================================================
// GET SUBJECTS
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        id,
        subject_code,
        subject_name

    FROM subjects

    WHERE teacher_id = ?

    ORDER BY
        subject_code ASC
");

$stmt->execute([
    $teacherId
]);

$subjects =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


// =====================================================
// GET ADMIN CLASSES
// =====================================================

$stmt = $pdo->query("
    SELECT
        id,
        course,
        year_level,
        section

    FROM class_groups

    WHERE status = 'active'

    ORDER BY
        course ASC,
        year_level ASC,
        section ASC
");

$classes =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


// =====================================================
// BUILD COURSES
// =====================================================

$courses = [];

foreach ($classes as $class) {

    if (
        !in_array(
            $class['course'],
            $courses,
            true
        )
    ) {

        $courses[] =
            $class['course'];
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
QR Attendance Scanner
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
    src="https://unpkg.com/html5-qrcode"
    type="text/javascript"
></script>


<style>

body {
    background: #f4f7fb;
}

.app-nav {
    background: #172554;
}

.card {
    border: none;
    border-radius: 15px;
}

#reader {
    width: 100%;
}

#reader video {
    border-radius: 10px;
}

.settings-box {
    background: #f8fafc;
    border-radius: 12px;
    padding: 15px;
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
📷 Teacher Portal
</a>


<div class="text-white">

Teacher:

<strong>
<?= htmlspecialchars(
    $_SESSION['full_name'] ?? ''
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


<div class="container py-4">


<h2 class="fw-bold mb-4">
QR Attendance Scanner
</h2>


<!-- =====================================================
     MESSAGES
===================================================== -->

<?php if ($message): ?>

<div class="alert alert-success">

<strong>Success!</strong><br>

<?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="alert alert-danger">

<strong>Error!</strong><br>

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>


<div class="row g-4">


<!-- =====================================================
     SETTINGS
===================================================== -->

<div class="col-lg-4">

<div class="card shadow-sm">

<div class="card-body p-4">


<h4 class="fw-bold">
Attendance Settings
</h4>


<p class="text-muted">

Select the subject and class before
scanning the student QR code.

</p>


<!-- =================================================
     ADD SUBJECT
================================================= -->

<form
    method="POST"
    class="mb-4"
>

<input
    type="hidden"
    name="action"
    value="add_subject"
>


<label class="form-label fw-bold">
Add New Subject
</label>


<input
    type="text"
    name="subject_code"
    class="form-control mb-2"
    placeholder="Subject Code e.g. IT101"
    required
>


<input
    type="text"
    name="subject_name"
    class="form-control mb-2"
    placeholder="Subject Name e.g. Programming"
    required
>


<button
    type="submit"
    class="btn btn-primary w-100"
>
➕ Add Subject
</button>

</form>


<hr>


<!-- =================================================
     SUBJECT
================================================= -->

<label class="form-label fw-bold">
Subject
</label>


<select
    id="subject_id"
    class="form-select mb-3"
>

<option value="">
-- Select Subject --
</option>


<?php foreach ($subjects as $subject): ?>

<option
    value="<?= (int)$subject['id'] ?>"
>

<?= htmlspecialchars(
    $subject['subject_code']
) ?>

 -

<?= htmlspecialchars(
    $subject['subject_name']
) ?>

</option>

<?php endforeach; ?>

</select>


<!-- =================================================
     COURSE
================================================= -->

<label class="form-label fw-bold">
Course
</label>


<select
    id="course"
    class="form-select mb-3"
>

<option value="">
-- Select Course --
</option>


<?php foreach ($courses as $course): ?>

<option
    value="<?= htmlspecialchars(
        $course,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<?= htmlspecialchars($course) ?>

</option>

<?php endforeach; ?>

</select>


<!-- =================================================
     YEAR
================================================= -->

<label class="form-label fw-bold">
Year Level
</label>


<select
    id="year_level"
    class="form-select mb-3"
    disabled
>

<option value="">
-- Select Year Level --
</option>

</select>


<!-- =================================================
     SECTION
================================================= -->

<label class="form-label fw-bold">
Section
</label>


<select
    id="section"
    class="form-select mb-3"
    disabled
>

<option value="">
-- Select Section --
</option>

</select>


<!-- =================================================
     CURRENT SELECTION
================================================= -->

<div
    id="settingsMessage"
    class="alert alert-warning"
>

Please select:

<br>

<strong>
Subject
</strong>,

<strong>
Course
</strong>,

<strong>
Year Level
</strong>,

<strong>
Section
</strong>

</div>


</div>

</div>

</div>


<!-- =====================================================
     SCANNER
===================================================== -->

<div class="col-lg-8">

<div class="card shadow-sm">

<div class="card-body p-4">


<h3 class="fw-bold text-center">
Scan Student QR Code
</h3>


<p class="text-center text-muted">

The scanned student must belong
to the selected Course, Year Level
and Section.

</p>


<div
    id="reader"
></div>


<div
    id="scanResult"
    class="mt-3"
></div>


<div class="d-flex gap-2 mt-3">


<button
    id="startBtn"
    type="button"
    class="btn btn-success flex-fill"
>
📷 Start Scanner
</button>


<button
    id="stopBtn"
    type="button"
    class="btn btn-danger flex-fill"
>
⛔ Stop Scanner
</button>


</div>


<a
    href="dashboard.php"
    class="btn btn-outline-primary w-100 mt-3"
>
← Back to Dashboard
</a>


</div>

</div>

</div>


</div>

</div>


<script>


// =====================================================
// CLASS DATA FROM DATABASE
// =====================================================

const classes =
<?= json_encode(
    $classes,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
) ?>;


// =====================================================
// ELEMENTS
// =====================================================

const subjectSelect =
    document.getElementById(
        'subject_id'
    );


const courseSelect =
    document.getElementById(
        'course'
    );


const yearSelect =
    document.getElementById(
        'year_level'
    );


const sectionSelect =
    document.getElementById(
        'section'
    );


const settingsMessage =
    document.getElementById(
        'settingsMessage'
    );


// =====================================================
// COURSE CHANGE
// =====================================================

courseSelect.addEventListener(
    'change',
    function () {

        yearSelect.innerHTML =
            '<option value="">-- Select Year Level --</option>';

        sectionSelect.innerHTML =
            '<option value="">-- Select Section --</option>';

        yearSelect.disabled = true;

        sectionSelect.disabled = true;


        if (!this.value) {

            updateSettingsMessage();

            return;
        }


        const years = [];


        classes.forEach(
            function (item) {

                if (
                    item.course ===
                    courseSelect.value
                ) {

                    if (
                        !years.includes(
                            item.year_level
                        )
                    ) {

                        years.push(
                            item.year_level
                        );
                    }
                }

            }
        );


        years.forEach(
            function (year) {

                const option =
                    document.createElement(
                        'option'
                    );

                option.value = year;

                option.textContent = year;

                yearSelect.appendChild(
                    option
                );

            }
        );


        yearSelect.disabled =
            years.length === 0;


        updateSettingsMessage();

    }
);


// =====================================================
// YEAR CHANGE
// =====================================================

yearSelect.addEventListener(
    'change',
    function () {

        sectionSelect.innerHTML =
            '<option value="">-- Select Section --</option>';

        sectionSelect.disabled = true;


        if (!this.value) {

            updateSettingsMessage();

            return;
        }


        const sections = [];


        classes.forEach(
            function (item) {

                if (

                    item.course ===
                    courseSelect.value

                    &&

                    item.year_level ===
                    yearSelect.value

                ) {

                    if (
                        !sections.includes(
                            item.section
                        )
                    ) {

                        sections.push(
                            item.section
                        );
                    }

                }

            }
        );


        sections.forEach(
            function (section) {

                const option =
                    document.createElement(
                        'option'
                    );

                option.value = section;

                option.textContent = section;

                sectionSelect.appendChild(
                    option
                );

            }
        );


        sectionSelect.disabled =
            sections.length === 0;


        updateSettingsMessage();

    }
);


// =====================================================
// SETTINGS CHECK
// =====================================================

function settingsReady() {

    return (

        subjectSelect.value !== ''

        &&

        courseSelect.value !== ''

        &&

        yearSelect.value !== ''

        &&

        sectionSelect.value !== ''

    );
}


// =====================================================
// UPDATE SETTINGS MESSAGE
// =====================================================

function updateSettingsMessage() {

    if (settingsReady()) {

        const subjectText =
            subjectSelect.options[
                subjectSelect.selectedIndex
            ].text;


        settingsMessage.className =
            'alert alert-success';


        settingsMessage.innerHTML =

            '<strong>✓ Ready to Scan</strong><br>' +

            subjectText +
            '<br>' +

            courseSelect.value +
            ' - ' +

            yearSelect.value +
            ' - ' +

            sectionSelect.value;

    } else {

        settingsMessage.className =
            'alert alert-warning';


        settingsMessage.innerHTML =

            'Please select Subject, Course, ' +
            'Year Level and Section.';
    }
}


subjectSelect.addEventListener(
    'change',
    updateSettingsMessage
);


// =====================================================
// QR SCANNER
// =====================================================

let scanner = null;

let running = false;

let processing = false;


// =====================================================
// RESULT MESSAGE
// =====================================================

function resultBox(
    type,
    message
) {

    document.getElementById(
        'scanResult'
    ).innerHTML =

        '<div class="alert alert-' +
        type +
        '">' +
        message +
        '</div>';
}


// =====================================================
// START SCANNER
// =====================================================

async function startScanner() {

    if (running) {
        return;
    }


    if (!settingsReady()) {

        resultBox(
            'warning',
            '<strong>Cannot Start Scanner</strong><br>' +
            'Please select Subject, Course, Year Level and Section first.'
        );

        return;
    }


    scanner =
        new Html5Qrcode(
            'reader'
        );


    try {

        await scanner.start(

            {
                facingMode: 'environment'
            },

            {
                fps: 10,

                qrbox: {
                    width: 250,
                    height: 250
                }
            },

            onScanSuccess,

            function () {}

        );


        running = true;


        resultBox(
            'info',
            'Scanner is ready. Scan the student QR code.'
        );


    } catch (error) {

        console.error(error);


        resultBox(
            'danger',
            'Camera could not start. Please allow camera permission.'
        );
    }
}


// =====================================================
// STOP SCANNER
// =====================================================

async function stopScanner() {

    if (
        scanner &&
        running
    ) {

        try {

            await scanner.stop();

        } catch (error) {

            console.log(error);

        }


        running = false;
    }
}


// =====================================================
// QR SCAN SUCCESS
// =====================================================

async function onScanSuccess(
    decodedText
) {

    if (processing) {
        return;
    }


    processing = true;


    await stopScanner();


    const form =
        new FormData();


    form.append(
        'qr_token',
        decodedText
    );


    form.append(
        'subject_id',
        subjectSelect.value
    );


    form.append(
        'course',
        courseSelect.value
    );


    form.append(
        'year_level',
        yearSelect.value
    );


    form.append(
        'section',
        sectionSelect.value
    );


    try {

        const response =
            await fetch(
                '../api/scan_attendance.php',
                {
                    method: 'POST',
                    body: form
                }
            );


        const data =
            await response.json();


        if (data.success) {

            const student =
                data.student;


            resultBox(

                'success',

                '<strong>✓ Attendance Added!</strong><br>' +

                'Student: ' +
                escapeHtml(
                    student.name
                ) +

                '<br>' +

                'Student No.: ' +
                escapeHtml(
                    student.student_number
                ) +

                '<br>' +

                'Course: ' +
                escapeHtml(
                    student.course
                ) +

                '<br>' +

                'Year: ' +
                escapeHtml(
                    student.year_level
                ) +

                '<br>' +

                'Section: ' +
                escapeHtml(
                    student.section
                ) +

                '<br>' +

                'Subject: ' +
                escapeHtml(
                    student.subject
                ) +

                '<br>' +

                'Teacher: ' +
                escapeHtml(
                    student.teacher
                ) +

                '<br>' +

                'Time In: ' +
                escapeHtml(
                    student.time_in
                )
            );


        } else {

            resultBox(

                data.duplicate
                    ? 'warning'
                    : 'danger',

                '<strong>✕ Invalid Attendance</strong><br>' +

                escapeHtml(
                    data.message
                )
            );
        }


    } catch (error) {

        console.error(error);


        resultBox(
            'danger',
            'Could not contact the attendance server.'
        );
    }


    setTimeout(
        function () {

            processing = false;

            startScanner();

        },
        2500
    );
}


// =====================================================
// SECURITY
// =====================================================

function escapeHtml(value) {

    return String(value)
        .replace(
            /&/g,
            '&amp;'
        )
        .replace(
            /</g,
            '&lt;'
        )
        .replace(
            />/g,
            '&gt;'
        )
        .replace(
            /"/g,
            '&quot;'
        )
        .replace(
            /'/g,
            '&#039;'
        );
}


// =====================================================
// BUTTONS
// =====================================================

document
    .getElementById('startBtn')
    .addEventListener(
        'click',
        startScanner
    );


document
    .getElementById('stopBtn')
    .addEventListener(
        'click',
        stopScanner
    );

</script>


</body>

</html>