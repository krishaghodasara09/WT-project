<?php
include "db.php";

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Receive form inputs
    $name = trim($_POST["name"] ?? "");
    $rollno = trim($_POST["Rollno"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $course = trim($_POST["course"] ?? "");

    // Server-side validation
    if ($name == "" || $rollno == "" || $email == "" ||
        $password == "" || $course == "") {

        $message = "All fields are required.";

    } elseif (!preg_match("/^[0-9]{2}aiml[0-9]{3}$/", $rollno)) {

        $message = "Invalid Student ID.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Invalid email address.";

    } elseif (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{6,}$/", $password)) {

        $message = "Password must contain uppercase, lowercase, digit and at least 6 characters.";

    } else {

        // Check duplicate username
        $checkUsername = $conn->prepare(
            "SELECT student_id FROM students WHERE rollno = ?"
        );
        $checkUsername->bind_param("s", $rollno);
        $checkUsername->execute();
        $usernameResult = $checkUsername->get_result();

        if ($usernameResult->num_rows > 0) {

            $message = "This Student ID / username is already registered.";

        } else {

            // Check duplicate email
            $checkEmail = $conn->prepare(
                "SELECT student_id FROM students WHERE email = ?"
            );
            $checkEmail->bind_param("s", $email);
            $checkEmail->execute();
            $emailResult = $checkEmail->get_result();

            if ($emailResult->num_rows > 0) {

                $message = "This email address is already registered.";

            } else {

                // Hash password before storing
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert student into MySQL database
                $stmt = $conn->prepare(
                    "INSERT INTO students (name, rollno, email, password, course)
                     VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "sssss",
                    $name,
                    $rollno,
                    $email,
                    $hashedPassword,
                    $course
                );

                if ($stmt->execute()) {
                    $message = "Registration successful! Your details have been saved.";
                    $success = true;
                } else {
                    $message = "Error: Unable to complete registration.";
                }

                $stmt->close();
            }

            $checkEmail->close();
        }

        $checkUsername->close();
    }

} else {

    $message = "Invalid request.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration Result - StudentHub</title>
    <link rel="stylesheet" href="register.css">
</head>

<body>

<header>
    <h1>StudentHub</h1>
    <h2>Registration Result</h2>
</header>

<main>
    <div style="text-align:center; margin-top:30px;">
        <h3 style="color:<?php echo $success ? 'green' : 'red'; ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </h3>

        <a href="register.html">Go Back to Registration</a>
        <br><br>
        <a href="index.html">Go to Home Page</a>
    </div>
</main>

</body>
</html>
