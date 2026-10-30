<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Receive and sanitize form inputs
    $name = trim($_POST["name"] ?? "");
    $rollno = trim($_POST["Rollno"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $course = trim($_POST["course"] ?? "");

    // Server-side validation
    if ($name == "" || $rollno == "" || $email == "" ||
        $password == "" || $course == "") {

        $message = "All fields are required.";
        $success = false;

    } elseif (!preg_match("/^[0-9]{2}aiml[0-9]{3}$/", $rollno)) {

        $message = "Invalid Student ID.";
        $success = false;

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Invalid email address.";
        $success = false;

    } elseif (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{6,}$/", $password)) {

        $message = "Password must contain uppercase, lowercase, digit and at least 6 characters.";
        $success = false;

    } else {

        // Hash password before storing
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Create data folder if it does not exist
        if (!is_dir("data")) {
            mkdir("data", 0777, true);
        }

        $file = fopen("data/registrations.csv", "a");

        if ($file) {

            flock($file, LOCK_EX);

            // Add headings if file is empty
            if (filesize("data/registrations.csv") == 0) {
                fputcsv($file, ["Name", "Student ID", "Email", "Password Hash", "Course"], ",", "\"", "\\");
            }

            // Save student record
            fputcsv($file, [
                $name,
                $rollno,
                $email,
                $hashedPassword,
                $course
            ], ",", "\"", "\\");

            flock($file, LOCK_UN);
            fclose($file);

            $message = "Registration successful! Your details have been saved.";
            $success = true;

        } else {

            $message = "Error: Unable to save registration details.";
            $success = false;
        }
    }

} else {

    $message = "Invalid request.";
    $success = false;
}

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
            <?php echo htmlspecialchars($message); ?>
        </h3>

        <a href="register.html">Go Back to Registration</a>
        <br><br>
        <a href="index.html">Go to Home Page</a>
    </div>
</main>

</body>
</html>