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

require_once "db.php";

$studentId = (int) $_SESSION["student_id"];

if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])) {
        $message = "Invalid security token. Please try again.";
        $messageType = "error";

    } else {

        $eventId = filter_input(INPUT_POST, "event_id", FILTER_VALIDATE_INT);

        if (!$eventId || $eventId <= 0) {
            $message = "Invalid event selected.";
            $messageType = "error";

        } else {

            // Check whether the event exists and is upcoming
            $check = $conn->prepare(
                "SELECT event_id FROM events
                 WHERE event_id = ? AND event_date >= CURDATE()"
            );
            $check->bind_param("i", $eventId);
            $check->execute();
            $eventResult = $check->get_result();

            if ($eventResult->num_rows === 0) {
                $message = "Event not found or registration is closed.";
                $messageType = "error";

            } else {

                // Insert registration and prevent duplicates
                $stmt = $conn->prepare(
                    "INSERT INTO registrations (student_id, event_id)
                     VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE registration_id = registration_id"
                );

                $stmt->bind_param("ii", $studentId, $eventId);
                $stmt->execute();

                if ($stmt->affected_rows === 1) {
                    $message = "Successfully registered for the event!";
                    $messageType = "success";
                } else {
                    $message = "You have already registered for this event.";
                    $messageType = "error";
                }

                $stmt->close();
            }

            $check->close();
        }
    }
}

// Get upcoming events and registration status
$stmt = $conn->prepare("
    SELECT e.event_id, e.title, e.description,
           e.event_date, e.venue,
           r.registration_id
    FROM events e
    LEFT JOIN registrations r
        ON e.event_id = r.event_id
        AND r.student_id = ?
    WHERE e.event_date >= CURDATE()
    ORDER BY e.event_date ASC
");

$stmt->bind_param("i", $studentId);
$stmt->execute();
$events = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>StudentHub - Available Events</title>

    <style>
        body {
            background-color: honeydew;
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
        }

        header {
            background-color: #c8e6c9;
            padding: 15px;
        }

        h1, h2 {
            color: darkgreen;
        }

        .event-card {
            background-color: white;
            border: 2px solid #a5d6a7;
            border-radius: 10px;
            padding: 20px;
            margin: 20px auto;
            width: 80%;
            max-width: 550px;
        }

        .register {
            background-color: darkgreen;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        .register:hover {
            background-color: #2e7d32;
        }

        .registered {
            color: darkgreen;
            font-weight: bold;
        }

        .message {
            padding: 12px;
            margin: 15px auto;
            width: 80%;
            max-width: 500px;
            border-radius: 6px;
        }

        .success {
            background-color: #c8e6c9;
            color: darkgreen;
        }

        .error {
            background-color: #ffcdd2;
            color: darkred;
        }

        .back {
            display: inline-block;
            background-color: darkgreen;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            margin: 20px;
        }

        @media (max-width: 600px) {
            .event-card {
                width: 85%;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>StudentHub</h1>
    <h2>Available Events</h2>
</header>

<main>

    <h2>Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>!</h2>

    <?php if ($message !== "") { ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <?php if ($events->num_rows > 0) { ?>

        <?php while ($event = $events->fetch_assoc()) { ?>

            <div class="event-card">

                <h2><?php echo htmlspecialchars($event["title"]); ?></h2>

                <p>
                    <?php echo nl2br(htmlspecialchars($event["description"] ?? "")); ?>
                </p>

                <p>
                    <strong>Date:</strong>
                    <?php echo htmlspecialchars($event["event_date"]); ?>
                </p>

                <p>
                    <strong>Venue:</strong>
                    <?php echo htmlspecialchars($event["venue"]); ?>
                </p>

                <?php if ($event["registration_id"] !== null) { ?>

                    <p class="registered">Already Registered</p>

                <?php } else { ?>

                    <form method="POST" action="view_events.php">

                        <input type="hidden" name="event_id"
                               value="<?php echo (int) $event["event_id"]; ?>">

                        <input type="hidden" name="csrf_token"
                               value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

                        <button type="submit" class="register">
                            Register
                        </button>

                    </form>

                <?php } ?>

            </div>

        <?php } ?>

    <?php } else { ?>

        <p>No upcoming events are available right now.</p>

    <?php } ?>

    <a href="student_dashboard.php" class="back">
        Back to Dashboard
    </a>

</main>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
