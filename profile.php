<?php
session_start();

if (!isset($_SESSION["student_id"])) {
    header("Location: login.html");
    exit();
}

// Check session timeout
if (isset($_SESSION["last_activity"]) &&
    time() - $_SESSION["last_activity"] > 900) {
    session_unset();
    session_destroy();
    header("Location: login.html");
    exit();
}

$_SESSION["last_activity"] = time();

require_once "db.php";

$studentId = (int) $_SESSION["student_id"];

$stmt = $conn->prepare(
    "SELECT name, rollno, course, email, role
     FROM students
     WHERE student_id = ?"
);

$stmt->bind_param("i", $studentId);
$stmt->execute();

$result = $stmt->get_result();
$student = $result->fetch_assoc();

$stmt->close();

if (!$student) {
    die("Profile not found.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Profile</title>

    <style>
        body {
            background-color: honeydew;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        header {
            text-align: center;
            color: darkgreen;
        }

        .profile {
            max-width: 500px;
            margin: 30px auto;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: darkgreen;
            text-align: center;
        }

        .detail {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        .detail b {
            color: darkgreen;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: darkgreen;
        }
    </style>
</head>

<body>

<header>
    <h1>StudentHub</h1>
    <hr>
</header>

<main>
    <section class="profile">

        <h2>Student Profile</h2>

        <div class="detail">
            <b>Name:</b>
            <?= htmlspecialchars($student["name"]) ?>
        </div>

        <div class="detail">
            <b>Roll Number:</b>
            <?= htmlspecialchars($student["rollno"]) ?>
        </div>

        <div class="detail">
            <b>Course:</b>
            <?= htmlspecialchars($student["course"] ?? "Not specified") ?>
        </div>

        <div class="detail">
            <b>Email:</b>
            <?= htmlspecialchars($student["email"]) ?>
        </div>

        <div class="detail">
            <b>Role:</b>
            <?= htmlspecialchars($student["role"]) ?>
        </div>

        <a class="back" href="student_dashboard.php">
            Back to Dashboard
        </a>

    </section>
</main>

<hr>

</body>
</html>
