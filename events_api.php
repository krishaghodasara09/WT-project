<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");

function respond($success, $message, $data = [], $status = 200) {
    http_response_code($status);

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);

    exit();
}

// Check admin login
if (!isset($_SESSION["student_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin") {
    respond(false, "Unauthorized access. Admin login required.", [], 401);
}

// Session timeout
if (!isset($_SESSION["last_activity"]) ||
    time() - $_SESSION["last_activity"] > 900) {
    session_unset();
    session_destroy();
    respond(false, "Session expired. Please login again.", [], 401);
}

$_SESSION["last_activity"] = time();

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

include "db.php";

$method = $_SERVER["REQUEST_METHOD"];

try {

    // LIST EVENTS
    if ($method === "GET") {

        $stmt = $conn->prepare(
            "SELECT event_id, title, description, event_date, venue
             FROM events ORDER BY event_id DESC"
        );

        $stmt->execute();
        $result = $stmt->get_result();

        $events = [];

        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }

        respond(true, "Events retrieved successfully.", [
            "events" => $events,
            "csrf_token" => $_SESSION["csrf_token"]
        ]);

    }

    // READ JSON INPUT FOR OTHER METHODS
    else {

        $input = json_decode(file_get_contents("php://input"), true);

        if (!is_array($input)) {
            respond(false, "Invalid JSON input.", [], 400);
        }

        // CHECK CSRF TOKEN
        if (!hash_equals(
            $_SESSION["csrf_token"],
            $input["csrf_token"] ?? ""
        )) {
            respond(false, "Invalid security token.", [], 403);
        }

        // ADD EVENT
        if ($method === "POST") {

            $title = trim($input["title"] ?? "");
            $description = trim($input["description"] ?? "");
            $eventDate = trim($input["event_date"] ?? "");
            $venue = trim($input["venue"] ?? "");

            if ($title === "" || $eventDate === "" || $venue === "") {
                respond(false, "Title, date and venue are required.", [], 400);
            }

            $date = DateTime::createFromFormat("Y-m-d", $eventDate);

            if (!$date || $date->format("Y-m-d") !== $eventDate) {
                respond(false, "Invalid event date.", [], 400);
            }

            $stmt = $conn->prepare(
                "INSERT INTO events (title, description, event_date, venue)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $title, $description, $eventDate, $venue
            );

            $stmt->execute();
            $id = $conn->insert_id;
            $stmt->close();

            respond(true, "Event added successfully.", [
                "event_id" => $id
            ], 201);
        }

        // UPDATE EVENT
        elseif ($method === "PUT") {

            $id = (int)($input["event_id"] ?? 0);
            $title = trim($input["title"] ?? "");
            $description = trim($input["description"] ?? "");
            $eventDate = trim($input["event_date"] ?? "");
            $venue = trim($input["venue"] ?? "");

            if ($id <= 0 || $title === "" ||
                $eventDate === "" || $venue === "") {
                respond(false, "Please provide all required event details.", [], 400);
            }

            $date = DateTime::createFromFormat("Y-m-d", $eventDate);

            if (!$date || $date->format("Y-m-d") !== $eventDate) {
                respond(false, "Invalid event date.", [], 400);
            }

            $stmt = $conn->prepare(
                "UPDATE events
                 SET title = ?, description = ?, event_date = ?, venue = ?
                 WHERE event_id = ?"
            );

            $stmt->bind_param(
                "ssssi",
                $title, $description, $eventDate, $venue, $id
            );

            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $stmt->close();
                respond(true, "Event updated successfully.");
            }

            $stmt->close();

            $check = $conn->prepare(
                "SELECT event_id FROM events WHERE event_id = ?"
            );
            $check->bind_param("i", $id);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();

            if ($exists) {
                respond(true, "No changes were needed.");
            } else {
                respond(false, "Event not found.", [], 404);
            }
        }

        // DELETE EVENT
        elseif ($method === "DELETE") {

            $id = (int)($input["event_id"] ?? 0);

            if ($id <= 0) {
                respond(false, "Invalid event ID.", [], 400);
            }

            $stmt = $conn->prepare(
                "DELETE FROM events WHERE event_id = ?"
            );

            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $stmt->close();
                respond(true, "Event deleted successfully.");
            }

            $stmt->close();
            respond(false, "Event not found.", [], 404);
        }

        else {
            respond(false, "Unsupported request method.", [], 405);
        }
    }

} catch (mysqli_sql_exception $ex) {
    respond(false, "Database operation failed. Please try again.", [], 500);
}

$conn->close();
?>
