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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Dashboard</title>

    <style>
        body {
            background-color: honeydew;
            margin: 0;
            font-family: Arial, sans-serif;
            text-align: center;
        }

        header {
            color: darkgreen;
            padding: 15px;
        }

        nav {
            background-color: darkgreen;
            padding: 15px;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 25px;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        nav a:hover {
            text-decoration: underline;
        }

        main {
            padding: 35px 15px;
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
            list-style: none;
            padding: 0;
            line-height: 2.5;
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

        footer {
            margin-top: 40px;
            padding: 15px;
        }
    </style>
</head>

<body>

<header>
    <h1>StudentHub</h1>
</header>

<nav>
    <a href="student_dashboard.php">Home</a>
    <a href="profile.php">Profile</a>
    <a href="view_events.php">Events</a>
    <a href="contact.html">Contact</a>
</nav>

<main>
    <section>
        <h2>Student Dashboard</h2>

        <p>Welcome,</p>

        <h2 class="highlight">
            <?php echo htmlspecialchars($_SESSION["name"]); ?>!
        </h2>

        <p>You are successfully logged in as a Student.</p>

        <h3>Quick Links</h3>

        <ul class="menu">
            <li><a href="profile.php">View Profile</a></li>
            <li><a href="view_events.php">View and Register for Events</a></li>
            <li><a href="contact.html">Contact Us</a></li>
        </ul>

        <a href="logout.php" class="logout">Logout</a>
    </section>
</main>

<footer>
    <hr>
    <p>StudentHub - Student Portal</p>
</footer>

</body>
</html>
