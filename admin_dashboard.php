<?php
session_start();

if (!isset($_SESSION["student_id"])) {
    header("Location: login.html");
    exit();
}

if (!isset($_SESSION["last_activity"]) ||
    time() - $_SESSION["last_activity"] > 900) {
    session_unset();
    session_destroy();
    header("Location: login.html");
    exit();
}

$_SESSION["last_activity"] = time();

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: student_dashboard.php");
    exit();
}

require_once "db.php";

// Total users
$userResult = $conn->query("SELECT COUNT(*) AS total FROM students");
$totalUsers = $userResult->fetch_assoc()["total"];

// Total students (excluding admins)
$studentResult = $conn->query(
    "SELECT COUNT(*) AS total FROM students WHERE role = 'student'"
);
$totalStudents = $studentResult->fetch_assoc()["total"];

// Total events
$eventResult = $conn->query("SELECT COUNT(*) AS total FROM events");
$totalEvents = $eventResult->fetch_assoc()["total"];

// Latest 5 registrations
$registrationResult = $conn->query("
    SELECT s.name, e.title, r.registration_date, r.status
    FROM registrations r
    JOIN students s ON r.student_id = s.student_id
    JOIN events e ON r.event_id = e.event_id
    ORDER BY r.registration_date DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>StudentHub - Admin Dashboard</title>

    <style>
        body {
            background-color: honeydew;
            text-align: center;
            font-family: Arial, sans-serif;
            margin: 0;
        }

        header {
            background-color: #c8e6c9;
            padding: 15px;
        }

        h1, h2 {
            color: darkgreen;
        }

        .highlight {
            display: inline-block;
            background-color: #c8e6c9;
            color: darkgreen;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
        }

        .stats {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
            margin: 25px;
        }

        .card {
            background-color: white;
            border: 2px solid #a5d6a7;
            border-radius: 10px;
            padding: 20px;
            width: 180px;
        }

        .number {
            font-size: 30px;
            color: darkgreen;
            font-weight: bold;
        }

        nav {
            margin: 20px;
        }

        nav a {
            display: inline-block;
            background-color: darkgreen;
            color: white;
            padding: 10px 15px;
            margin: 5px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        nav a:hover, .logout:hover {
            background-color: #2e7d32;
        }

        table {
            margin: 20px auto;
            border-collapse: collapse;
            width: 90%;
            max-width: 900px;
            background-color: white;
        }

        th, td {
            border: 1px solid #a5d6a7;
            padding: 12px;
        }

        th {
            background-color: #c8e6c9;
            color: darkgreen;
        }

        .logout {
            display: inline-block;
            background-color: darkgreen;
            color: white;
            padding: 10px 25px;
            text-decoration: none;
            font-weight: bold;
            border-radius: 8px;
            margin: 20px;
        }

        @media (max-width: 600px) {
            .stats {
                flex-direction: column;
                align-items: center;
            }

            .card {
                width: 75%;
            }

            table {
                width: 95%;
                font-size: 12px;
            }

            th, td {
                padding: 7px;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>StudentHub</h1>
    <h2>Admin Dashboard</h2>
</header>

<main>

    <h2>Welcome,</h2>

    <h2 class="highlight">
        <?php echo htmlspecialchars($_SESSION["name"]); ?>!
    </h2>

    <h2>User Statistics</h2>

    <section class="stats">

        <div class="card">
            <h3>Total Users</h3>
            <p class="number"><?php echo $totalUsers; ?></p>
        </div>

        <div class="card">
            <h3>Total Students</h3>
            <p class="number"><?php echo $totalStudents; ?></p>
        </div>

        <div class="card">
            <h3>Total Events</h3>
            <p class="number"><?php echo $totalEvents; ?></p>
        </div>

    </section>

    <h2>Admin Menu</h2>

    <nav>
        <?php if ($_SESSION["role"] === "admin") { ?>
            <a href="manage_students.php">Manage Students</a>
            <a href="manage_events.php">Manage Events</a>
            <a href="events_api.html">Events API</a>
        <?php } elseif ($_SESSION["role"] === "student") { ?>
            <a href="student_dashboard.php">Student Dashboard</a>
        <?php } ?>
    </nav>

    <h2>Latest Registrations</h2>

    <table>
        <tr>
            <th>Student Name</th>
            <th>Event</th>
            <th>Registration Date</th>
            <th>Status</th>
        </tr>

        <?php if ($registrationResult->num_rows > 0) { ?>

            <?php while ($row = $registrationResult->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row["name"]); ?></td>
                    <td><?php echo htmlspecialchars($row["title"]); ?></td>
                    <td><?php echo htmlspecialchars($row["registration_date"]); ?></td>
                    <td><?php echo htmlspecialchars($row["status"]); ?></td>
                </tr>
            <?php } ?>

        <?php } else { ?>
            <tr>
                <td colspan="4">No registrations found.</td>
            </tr>
        <?php } ?>

    </table>

    <a href="logout.php" class="logout">Logout</a>

</main>

<hr>

</body>
</html>

<?php
$conn->close();
?>
