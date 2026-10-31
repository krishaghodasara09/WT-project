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
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$success = false;
$editingStudent = null;

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $message = "Invalid request. Please try again.";
    } else {

        $action = $_POST["action"] ?? "";

        try {

            // ADD STUDENT
            if ($action === "add") {
                $name = trim($_POST["name"] ?? "");
                $rollno = trim($_POST["rollno"] ?? "");
                $email = trim($_POST["email"] ?? "");
                $password = $_POST["password"] ?? "";
                $course = trim($_POST["course"] ?? "");

                if ($name === "" || $rollno === "" || $email === "" ||
                    $password === "" || $course === "") {
                    throw new Exception("All fields are required.");
                }

                if (!preg_match("/^[0-9]{2}aiml[0-9]{3}$/i", $rollno)) {
                    throw new Exception("Invalid roll number.");
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email address.");
                }

                if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{6,}$/", $password)) {
                    throw new Exception("Password must contain uppercase, lowercase, a digit and at least 6 characters.");
                }

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare(
                    "INSERT INTO students (name, rollno, email, password, course)
                     VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "sssss",
                    $name, $rollno, $email, $hashedPassword, $course
                );

                $stmt->execute();
                $stmt->close();

                $message = "Student added successfully!";
                $success = true;
            }

            // UPDATE STUDENT
            elseif ($action === "update") {
                $id = (int)($_POST["student_id"] ?? 0);
                $name = trim($_POST["name"] ?? "");
                $rollno = trim($_POST["rollno"] ?? "");
                $email = trim($_POST["email"] ?? "");
                $course = trim($_POST["course"] ?? "");

                if ($id <= 0 || $name === "" || $rollno === "" ||
                    $email === "" || $course === "") {
                    throw new Exception("Please fill in all fields.");
                }

                if (!preg_match("/^[0-9]{2}aiml[0-9]{3}$/i", $rollno)) {
                    throw new Exception("Invalid roll number.");
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email address.");
                }

                $stmt = $conn->prepare(
                    "UPDATE students
                     SET name = ?, rollno = ?, email = ?, course = ?
                     WHERE student_id = ? AND role = 'student'"
                );

                $stmt->bind_param("ssssi", $name, $rollno, $email, $course, $id);
                $stmt->execute();

                if ($stmt->affected_rows >= 0) {
                    $message = "Student details updated successfully!";
                    $success = true;
                }

                $stmt->close();
            }

            // DELETE STUDENT
            elseif ($action === "delete") {
                $id = (int)($_POST["student_id"] ?? 0);

                if ($id <= 0) {
                    throw new Exception("Invalid student ID.");
                }

                $stmt = $conn->prepare(
                    "DELETE FROM students
                     WHERE student_id = ? AND role = 'student'"
                );

                $stmt->bind_param("i", $id);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "Student deleted successfully!";
                    $success = true;
                } else {
                    $message = "Student not found or could not be deleted.";
                }

                $stmt->close();
            }

        } catch (mysqli_sql_exception $ex) {
            $message = "Operation failed. Roll number or email may already exist.";
        } catch (Exception $ex) {
            $message = $ex->getMessage();
        }
    }
}

// Get student for editing
if (isset($_GET["edit"])) {
    $id = (int)$_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT student_id, name, rollno, email, course
         FROM students
         WHERE student_id = ? AND role = 'student'"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $editingStudent = $result->fetch_assoc();

    $stmt->close();
}

// Get available courses for filter
$courseStmt = $conn->prepare(
    "SELECT DISTINCT course FROM students
     WHERE role = 'student' ORDER BY course"
);
$courseStmt->execute();
$courseResult = $courseStmt->get_result();

// Search and filter
$search = trim($_GET["search"] ?? "");
$courseFilter = trim($_GET["course"] ?? "");
$searchPattern = "%" . $search . "%";

$stmt = $conn->prepare(
    "SELECT student_id, name, rollno, email, course
     FROM students
     WHERE role = 'student'
     AND (? = '' OR name LIKE ? OR rollno LIKE ? OR email LIKE ?)
     AND (? = '' OR course = ?)
     ORDER BY student_id DESC"
);

$stmt->bind_param(
    "ssssss",
    $search,
    $searchPattern,
    $searchPattern,
    $searchPattern,
    $courseFilter,
    $courseFilter
);

$stmt->execute();
$students = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Management - StudentHub</title>

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

        form {
            margin: 20px auto;
        }

        input, select {
            padding: 9px;
            margin: 6px;
        }

        button {
            background-color: darkgreen;
            color: white;
            padding: 9px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background-color: #2e7d32;
        }

        table {
            margin: 20px auto;
            border-collapse: collapse;
            background-color: white;
            width: 95%;
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
            font-weight: bold;
            color: darkgreen;
        }

        .error {
            color: red;
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
<h2>Student Management Module</h2>

<hr>

<?php if ($message !== ""): ?>
    <p class="<?php echo $success ? 'message' : 'error'; ?>">
        <?php echo e($message); ?>
    </p>
<?php endif; ?>

<!-- ADD OR EDIT FORM -->
<h2><?php echo $editingStudent ? "Edit Student" : "Add New Student"; ?></h2>

<form method="POST" action="manage_students.php">
    <input type="hidden" name="csrf_token"
           value="<?php echo e($_SESSION["csrf_token"]); ?>">

    <input type="hidden" name="action"
           value="<?php echo $editingStudent ? 'update' : 'add'; ?>">

    <?php if ($editingStudent): ?>
        <input type="hidden" name="student_id"
               value="<?php echo e($editingStudent["student_id"]); ?>">
    <?php endif; ?>

    <input type="text" name="name" placeholder="Student Name"
           value="<?php echo e($editingStudent["name"] ?? ""); ?>" required>

    <input type="text" name="rollno" placeholder="Roll Number"
           value="<?php echo e($editingStudent["rollno"] ?? ""); ?>" required>

    <input type="email" name="email" placeholder="Email"
           value="<?php echo e($editingStudent["email"] ?? ""); ?>" required>

    <input type="text" name="course" placeholder="Course"
           value="<?php echo e($editingStudent["course"] ?? ""); ?>" required>

    <?php if (!$editingStudent): ?>
        <input type="password" name="password"
               placeholder="Password" required>
    <?php endif; ?>

    <br>

    <button type="submit">
        <?php echo $editingStudent ? "Update Student" : "Add Student"; ?>
    </button>

    <?php if ($editingStudent): ?>
        <a href="manage_students.php">Cancel Edit</a>
    <?php endif; ?>
</form>

<hr>

<!-- SEARCH AND FILTER -->
<h2>Search and Filter Students</h2>

<form method="GET" action="manage_students.php">

    <input type="text" name="search"
           placeholder="Search name, roll number or email"
           value="<?php echo e($search); ?>">

    <select name="course">
        <option value="">All Courses</option>

        <?php while ($courseRow = $courseResult->fetch_assoc()): ?>
            <option value="<?php echo e($courseRow["course"]); ?>"
                <?php echo $courseFilter === $courseRow["course"] ? "selected" : ""; ?>>
                <?php echo e($courseRow["course"]); ?>
            </option>
        <?php endwhile; ?>
    </select>

    <button type="submit">Search / Filter</button>
    <a href="manage_students.php">Clear</a>

</form>

<!-- STUDENT RECORDS -->
<h2>Student Records</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Roll Number</th>
        <th>Email</th>
        <th>Course</th>
        <th>Actions</th>
    </tr>

    <?php if ($students->num_rows > 0): ?>

        <?php while ($student = $students->fetch_assoc()): ?>
            <tr>
                <td><?php echo e($student["student_id"]); ?></td>
                <td><?php echo e($student["name"]); ?></td>
                <td><?php echo e($student["rollno"]); ?></td>
                <td><?php echo e($student["email"]); ?></td>
                <td><?php echo e($student["course"]); ?></td>

                <td>
                    <a class="edit-link"
                       href="manage_students.php?edit=<?php echo e($student["student_id"]); ?>">
                        Edit
                    </a>

                    <form method="POST" action="manage_students.php"
                          style="display:inline;"
                          onsubmit="return confirm('Are you sure you want to delete this student?');">

                        <input type="hidden" name="csrf_token"
                               value="<?php echo e($_SESSION["csrf_token"]); ?>">

                        <input type="hidden" name="action" value="delete">

                        <input type="hidden" name="student_id"
                               value="<?php echo e($student["student_id"]); ?>">

                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>

    <?php else: ?>

        <tr>
            <td colspan="6">No student records found.</td>
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
$courseStmt->close();
$conn->close();
?>
