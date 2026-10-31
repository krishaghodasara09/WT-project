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

if ($_SESSION["role"] !== "admin") {
    header("Location: student_dashboard.php");
    exit();
}

include "db.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$uploadDir = __DIR__ . "/uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$success = false;
$editingEvent = null;

// Validate and save uploaded poster
function uploadPoster($file, $uploadDir) {

    if (!isset($file) || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new Exception("Poster upload failed.");
    }

    if ($file["size"] > 2 * 1024 * 1024) {
        throw new Exception("Poster size must not exceed 2 MB.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);

    $allowed = [
        "image/jpeg" => "jpg",
        "image/png" => "png"
    ];

    if (!isset($allowed[$mime])) {
        throw new Exception("Only JPG, JPEG and PNG posters are allowed.");
    }

    $filename = bin2hex(random_bytes(16)) . "." . $allowed[$mime];

    if (!move_uploaded_file(
        $file["tmp_name"],
        $uploadDir . $filename
    )) {
        throw new Exception("Unable to save poster.");
    }

    return $filename;
}

// Handle Add, Update and Delete
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        if (!hash_equals(
            $_SESSION["csrf_token"],
            $_POST["csrf_token"] ?? ""
        )) {
            throw new Exception("Invalid request. Please try again.");
        }

        $action = $_POST["action"] ?? "";

        // ADD EVENT
        if ($action === "add") {

            $title = trim($_POST["title"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $eventDate = $_POST["event_date"] ?? "";
            $venue = trim($_POST["venue"] ?? "");

            if ($title === "" || $eventDate === "" || $venue === "") {
                throw new Exception("Title, date and venue are required.");
            }

            $date = DateTime::createFromFormat("Y-m-d", $eventDate);

            if (!$date || $date->format("Y-m-d") !== $eventDate) {
                throw new Exception("Invalid event date.");
            }

            $poster = uploadPoster(
                $_FILES["poster"] ?? null,
                $uploadDir
            );

            $stmt = $conn->prepare(
                "INSERT INTO events (title, description, event_date, venue, poster)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssss",
                $title, $description, $eventDate, $venue, $poster
            );

            $stmt->execute();
            $stmt->close();

            $message = "Event added successfully!";
            $success = true;
        }

        // UPDATE EVENT
        elseif ($action === "update") {

            $id = (int)($_POST["event_id"] ?? 0);
            $title = trim($_POST["title"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $eventDate = $_POST["event_date"] ?? "";
            $venue = trim($_POST["venue"] ?? "");

            if ($id <= 0 || $title === "" ||
                $eventDate === "" || $venue === "") {
                throw new Exception("Please fill in all required fields.");
            }

            $date = DateTime::createFromFormat("Y-m-d", $eventDate);

            if (!$date || $date->format("Y-m-d") !== $eventDate) {
                throw new Exception("Invalid event date.");
            }

            $check = $conn->prepare(
                "SELECT poster FROM events WHERE event_id = ?"
            );
            $check->bind_param("i", $id);
            $check->execute();
            $oldEvent = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$oldEvent) {
                throw new Exception("Event not found.");
            }

            $newPoster = uploadPoster(
                $_FILES["poster"] ?? null,
                $uploadDir
            );

            $poster = $newPoster ?? $oldEvent["poster"];

            $stmt = $conn->prepare(
                "UPDATE events
                 SET title = ?, description = ?, event_date = ?,
                     venue = ?, poster = ?
                 WHERE event_id = ?"
            );

            $stmt->bind_param(
                "sssssi",
                $title, $description, $eventDate,
                $venue, $poster, $id
            );

            $stmt->execute();
            $stmt->close();

            if ($newPoster !== null && !empty($oldEvent["poster"])) {
                $oldPath = $uploadDir . basename($oldEvent["poster"]);

                if (is_file($oldPath)) {
                    unlink($oldPath);
                }
            }

            $message = "Event updated successfully!";
            $success = true;
        }

        // DELETE EVENT
        elseif ($action === "delete") {

            $id = (int)($_POST["event_id"] ?? 0);

            if ($id <= 0) {
                throw new Exception("Invalid event ID.");
            }

            $check = $conn->prepare(
                "SELECT poster FROM events WHERE event_id = ?"
            );
            $check->bind_param("i", $id);
            $check->execute();
            $event = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$event) {
                throw new Exception("Event not found.");
            }

            $stmt = $conn->prepare(
                "DELETE FROM events WHERE event_id = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                if (!empty($event["poster"])) {
                    $path = $uploadDir . basename($event["poster"]);

                    if (is_file($path)) {
                        unlink($path);
                    }
                }

                $message = "Event deleted successfully!";
                $success = true;
            } else {
                $message = "Unable to delete event.";
            }

            $stmt->close();
        }

    } catch (mysqli_sql_exception $ex) {
        $message = "Database operation failed. The event may be linked to student registrations.";
    } catch (Exception $ex) {
        $message = $ex->getMessage();
    }
}

// Load event details for editing
if (isset($_GET["edit"])) {

    $id = (int)$_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT * FROM events WHERE event_id = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $editingEvent = $stmt->get_result()->fetch_assoc();

    $stmt->close();
}

// Display all events
$stmt = $conn->prepare(
    "SELECT * FROM events ORDER BY event_date DESC"
);

$stmt->execute();
$events = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Event Management - StudentHub</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: honeydew;
            text-align: center;
            margin: 20px;
        }

        h1, h2 {
            color: darkgreen;
        }

        input, textarea {
            padding: 10px;
            margin: 7px;
            width: 250px;
        }

        textarea {
            height: 65px;
        }

        button {
            background-color: darkgreen;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background-color: #2e7d32;
        }

        table {
            width: 95%;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: white;
        }

        th, td {
            border: 1px solid #aaa;
            padding: 10px;
        }

        th {
            background-color: darkgreen;
            color: white;
        }

        .message {
            color: darkgreen;
            font-weight: bold;
        }

        .error {
            color: red;
            font-weight: bold;
        }

        .poster {
            width: 100px;
            height: auto;
        }

        .edit-link {
            color: darkgreen;
            font-weight: bold;
        }

        .logout {
            display: inline-block;
            background-color: darkgreen;
            color: white;
            padding: 10px 22px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }
    </style>
</head>

<body>

<h1>StudentHub</h1>
<h2>Event Management Module</h2>

<hr>

<?php if ($message !== ""): ?>
    <p class="<?php echo $success ? 'message' : 'error'; ?>">
        <?php echo e($message); ?>
    </p>
<?php endif; ?>

<!-- ADD / EDIT EVENT FORM -->

<h2><?php echo $editingEvent ? "Update Event" : "Add New Event"; ?></h2>

<form method="POST" action="manage_events.php"
      enctype="multipart/form-data">

    <input type="hidden" name="csrf_token"
           value="<?php echo e($_SESSION["csrf_token"]); ?>">

    <input type="hidden" name="action"
           value="<?php echo $editingEvent ? 'update' : 'add'; ?>">

    <?php if ($editingEvent): ?>
        <input type="hidden" name="event_id"
               value="<?php echo e($editingEvent["event_id"]); ?>">
    <?php endif; ?>

    <br>

    <input type="text" name="title"
           placeholder="Event Title"
           value="<?php echo e($editingEvent["title"] ?? ""); ?>"
           required>

    <br>

    <textarea name="description"
              placeholder="Event Description"><?php
        echo e($editingEvent["description"] ?? "");
    ?></textarea>

    <br>

    <label>Event Date:</label>
    <input type="date" name="event_date"
           value="<?php echo e($editingEvent["event_date"] ?? ""); ?>"
           required>

    <br>

    <input type="text" name="venue"
           placeholder="Event Venue"
           value="<?php echo e($editingEvent["venue"] ?? ""); ?>"
           required>

    <br>

    <label>Upload Poster (JPG, PNG - Max 2 MB):</label>
    <br>

    <input type="file" name="poster" accept=".jpg,.jpeg,.png">

    <?php if (!empty($editingEvent["poster"])): ?>
        <p>Current Poster:</p>
        <img class="poster"
             src="uploads/<?php echo rawurlencode(basename($editingEvent["poster"])); ?>"
             alt="Current event poster">
    <?php endif; ?>

    <br><br>

    <button type="submit">
        <?php echo $editingEvent ? "Update Event" : "Add Event"; ?>
    </button>

    <?php if ($editingEvent): ?>
        <a href="manage_events.php">Cancel</a>
    <?php endif; ?>

</form>

<hr>

<!-- VIEW EVENTS -->

<h2>All Events</h2>

<table>

    <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Description</th>
        <th>Date</th>
        <th>Venue</th>
        <th>Poster</th>
        <th>Actions</th>
    </tr>

    <?php if ($events->num_rows > 0): ?>

        <?php while ($event = $events->fetch_assoc()): ?>

            <tr>
                <td><?php echo e($event["event_id"]); ?></td>
                <td><?php echo e($event["title"]); ?></td>
                <td><?php echo e($event["description"]); ?></td>
                <td><?php echo e($event["event_date"]); ?></td>
                <td><?php echo e($event["venue"]); ?></td>

                <td>
                    <?php if (!empty($event["poster"])): ?>
                        <img class="poster"
                             src="uploads/<?php echo rawurlencode(basename($event["poster"])); ?>"
                             alt="Event poster">
                    <?php else: ?>
                        No Poster
                    <?php endif; ?>
                </td>

                <td>
                    <a class="edit-link"
                       href="manage_events.php?edit=<?php echo e($event["event_id"]); ?>">
                        Edit
                    </a>

                    <form method="POST" action="manage_events.php"
                          style="display:inline;"
                          onsubmit="return confirm('Are you sure you want to delete this event?');">

                        <input type="hidden" name="csrf_token"
                               value="<?php echo e($_SESSION["csrf_token"]); ?>">

                        <input type="hidden" name="action" value="delete">

                        <input type="hidden" name="event_id"
                               value="<?php echo e($event["event_id"]); ?>">

                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>

        <?php endwhile; ?>

    <?php else: ?>

        <tr>
            <td colspan="7">No events available.</td>
        </tr>

    <?php endif; ?>

</table>

<br>

<a href="admin_dashboard.php">Back to Admin Dashboard</a>

<br><br>

<a href="logout.php" class="logout">Logout</a>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
