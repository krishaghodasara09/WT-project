<?php
session_start();

// Set session timeout to 15 minutes
$timeout = 900;

// Check database connection
include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Receive login details
    $rollno = trim($_POST["uname"] ?? "");
    $password = $_POST["pword"] ?? "";

    // Validate empty fields
    if ($rollno == "" || $password == "") {

        $message = "Please enter username and password.";

    } else {

        // Find student using roll number
        $stmt = $conn->prepare(
            "SELECT student_id, name, rollno, password, role
             FROM students WHERE rollno = ?"
        );

        $stmt->bind_param("s", $rollno);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $user = $result->fetch_assoc();

            // Verify password hash
            if (password_verify($password, $user["password"])) {

                // Regenerate session ID after successful login
                session_regenerate_id(true);

                // Store user details in session
                $_SESSION["student_id"] = $user["student_id"];
                $_SESSION["name"] = $user["name"];
                $_SESSION["rollno"] = $user["rollno"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["last_activity"] = time();

                // Role-based redirection
                if ($user["role"] == "admin") {
                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    header("Location: student_dashboard.php");
                    exit();
                }

            } else {

                $message = "Invalid username or password.";
            }

        } else {

            $message = "Invalid username or password.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Result - StudentHub</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>

<header>
    <h1>StudentHub</h1>
    <h2>Login Result</h2>
</header>

<main>
    <div style="text-align:center; margin-top:30px;">

        <h3 style="color:red;">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </h3>

        <a href="login.html">Try Again</a>

    </div>
</main>

</body>
</html>
