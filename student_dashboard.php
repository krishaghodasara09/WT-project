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

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>StudentHub - Dashboard</title>

    <style>
        body {
            background-color: honeydew;
            text-align: center;
            font-family: Arial, sans-serif;
        }

        .highlight {
            display: inline-block;
            background-color: #c8e6c9;
            color: darkgreen;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
        }

        .menu {
            list-style-position: inside;
            padding: 0;
        }

        .menu a {
            color: darkgreen;
            font-weight: bold;
            text-decoration: none;
        }

        .menu a:hover {
            text-decoration: underline;
        }

        .logout {
            display: inline-block;
            background-color: darkgreen;
            color: white;
            padding: 10px 25px;
            text-decoration: none;
            font-weight: bold;
            border-radius: 8px;
            margin-top: 15px;
        }

        .logout:hover {
            background-color: #2e7d32;
        }
    </style>
</head>

<body>

<header>
    <h1 style="color: darkgreen;">StudentHub</h1>
    <h1 style="color: darkgreen;">Dashboard</h1>
    <hr>
</header>

<main>
    <section>
        <h2>Student Dashboard</h2>

        <p>Welcome,</p>

        <h2 class="highlight">
            <?php echo htmlspecialchars($_SESSION["name"]); ?>!
        </h2>

        <p>You are successfully logged in as a Student.</p>

        <ul class="menu">
            <li>View Profile</li>
            <li><a href="view_events.php">View and Register for Events</a></li>
            <li>View Announcements</li>
            <li>Update Details</li>
        </ul>

        <a href="logout.php" class="logout">Logout</a>
    </section>
</main>

<hr>

</body>
</html>
