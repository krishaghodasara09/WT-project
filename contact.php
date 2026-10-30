<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Receive and sanitize inputs
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $message = trim($_POST["message"] ?? "");

    // Server-side validation
    if ($name == "" || $email == "" || $message == "") {

        $result = "All fields are required.";
        $success = false;

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $result = "Please enter a valid email address.";
        $success = false;

    } else {

        // Create data folder if it does not exist
        if (!is_dir("data")) {
            mkdir("data", 0777, true);
        }

        $file = fopen("data/contacts.csv", "a");

        if ($file) {

            flock($file, LOCK_EX);

            // Add headings if file is empty
            if (filesize("data/contacts.csv") == 0) {
                fputcsv($file, ["Name", "Email", "Message"], ",", "\"", "\\");
            }

            // Store contact form details
            fputcsv($file, [
                $name,
                $email,
                $message
            ], ",", "\"", "\\");

            flock($file, LOCK_UN);
            fclose($file);

            $result = "Your message has been submitted successfully!";
            $success = true;

        } else {

            $result = "Error: Unable to save your message.";
            $success = false;
        }
    }

} else {

    $result = "Invalid request.";
    $success = false;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Result - StudentHub</title>
</head>

<body bgcolor="honeydew">

    <h1 align="center">
        <font color="darkgreen">StudentHub</font>
    </h1>

    <hr>

    <div align="center">

        <h2>Contact Form Result</h2>

        <h3 style="color:<?php echo $success ? 'green' : 'red'; ?>">
            <?php echo htmlspecialchars($result); ?>
        </h3>

        <a href="contact.html">Go Back to Contact Page</a>
        <br><br>

        <a href="index.html">Go to Home Page</a>

    </div>

</body>
</html>