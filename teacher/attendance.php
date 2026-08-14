<?php

require_once __DIR__ .
    '/../config/database.php';

require_once __DIR__ .
    '/../config/auth.php';

require_login('teacher');


$date =
    $_GET['date']
    ?? date('Y-m-d');


$stmt = $pdo->prepare("

    SELECT

        a.id,

        a.time_in,

        u.full_name,

        s.student_number,

        s.year_level,

        s.course,

        s.section,

        scanner.full_name AS scanned_by_name

    FROM attendance a

    JOIN students s
        ON s.id = a.student_id

    JOIN users u
        ON u.id = s.user_id

    LEFT JOIN users scanner
        ON scanner.id = a.scanned_by

    WHERE a.attendance_date=?

    ORDER BY

        s.year_level,
        s.course,
        s.section,
        u.full_name

");


$stmt->execute([
    $date
]);


$rows =
    $stmt->fetchAll();


$groups = [];


foreach ($rows as $row) {

    $key =
        $row['year_level'] .
        '|' .
        $row['course'] .
        '|' .
        $row['section'];


    $groups[$key][] =
        $row;
}

?>

<!doctype html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width,initial-scale=1">

<title>
Attendance Records
</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">


<link
href="../assets/css/style.css"
rel="stylesheet">

</head>


<body class="app-bg">


<nav class="navbar navbar-dark app-nav">

<div class="container-fluid">

<a
class="navbar-brand fw-bold"
href="dashboard.php">

📷 Teacher Portal

</a>


<a
class="btn btn-sm btn-outline-light"
href="../logout.php">

Logout

</a>

</div>

</nav>


<div class="container py-4">


<div
class="d-flex justify-content-between align-items-center mb-4">


<div>

<h2>
Attendance Records
</h2>

<p class="text-muted">

Grouped by Year Level,
Course and Section

</p>

</div>


<a
href="scanner.php"
class="btn btn-success">

Open Scanner

</a>


</div>


<form
class="row g-2 mb-4">


<div class="col-md-4">

<input
type="date"
name="date"
value="<?= htmlspecialchars($date) ?>"
class="form-control">

</div>


<div class="col-auto">

<button
class="btn btn-primary">

Load Date

</button>

</div>

</form>


<?php if (!$groups): ?>

<div class="alert alert-info">

No attendance records for
<?= htmlspecialchars($date) ?>.

</div>

<?php endif; ?>


<?php foreach (
    $groups
    as $key => $group
):

$first =
    $group[0];

?>


<div
class="card shadow-sm mb-4">


<div
class="card-header group-header">


<strong>

<?= htmlspecialchars(
    $first['year_level']
) ?>

</strong>

—

<?= htmlspecialchars(
    $first['course']
) ?>

—

<?= htmlspecialchars(
    $first['section']
) ?>


<span
class="badge bg-primary float-end">

<?= count($group) ?>

Present

</span>


</div>


<div class="table-responsive">


<table
class="table table-hover mb-0">


<thead>

<tr>

<th>#</th>

<th>Student No.</th>

<th>Name</th>

<th>Time In</th>

<th>Scanned By</th>

</tr>

</thead>


<tbody>


<?php foreach (
    $group
    as $i => $r
): ?>

<tr>

<td>
<?= $i + 1 ?>
</td>


<td>

<?= htmlspecialchars(
    $r['student_number']
) ?>

</td>


<td>

<?= htmlspecialchars(
    $r['full_name']
) ?>

</td>


<td>

<?= date(
    'h:i A',
    strtotime(
        $r['time_in']
    )
) ?>

</td>


<td>

<?= htmlspecialchars(
    $r['scanned_by_name']
    ?? '—'
) ?>

</td>

</tr>

<?php endforeach; ?>


</tbody>

</table>

</div>

</div>


<?php endforeach; ?>


</div>

</body>

</html>