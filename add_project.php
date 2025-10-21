<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include("db_connect.php");

// Restrict access to engineer and head engineer roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['engineer', 'head engineer'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

// Error handling function
function handleQueryError($conn, $query_name) {
    if (!$conn->error) {
        return true;
    }
    error_log("Database error in $query_name: " . $conn->error);
    return false;
}

// Handle Add Project
$message = "";
$error = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_project'])) {
    $title = trim($_POST['title']);
    $location = trim($_POST['location']);
    $budget_allocation = floatval($_POST['budget_allocation']);
    $timeline = trim($_POST['timeline']);
    $contractor = trim($_POST['contractor']);
    $plan = trim($_POST['plan']);
    $resources = trim($_POST['resources']);
    $technology = trim($_POST['technology']);
    $risk_safety = trim($_POST['risk_safety']);
    $quality_control = trim($_POST['quality_control']);
    $assigned_engineer = $_SESSION['user_id'];

    // Validate inputs
    if (empty($title)) {
        $message = "Project title is required.";
        $error = true;
    } elseif (empty($location)) {
        $message = "Location is required.";
        $error = true;
    } elseif ($budget_allocation <= 0) {
        $message = "Budget allocation must be greater than 0.";
        $error = true;
    } elseif (empty($timeline)) {
        $message = "Project timeline is required.";
        $error = true;
    } elseif (empty($contractor)) {
        $message = "Name of contractor is required.";
        $error = true;
    } elseif (empty($plan)) {
        $message = "Construction plan is required.";
        $error = true;
    } elseif (empty($resources)) {
        $message = "Resources are required.";
        $error = true;
    } elseif (empty($technology)) {
        $message = "Technology and equipment are required.";
        $error = true;
    } elseif (empty($risk_safety)) {
        $message = "Risk and safety information is required.";
        $error = true;
    } elseif (empty($quality_control)) {
        $message = "Quality control/performance is required.";
        $error = true;
    } else {
        // Insert project (make sure your DB table matches these fields)
        $stmt = $conn->prepare("INSERT INTO projects 
            (title, location, budget_allocation, timeline, contractor, plan, resources, technology, risk_safety, quality_control, assigned_engineer) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsssssssi", $title, $location, $budget_allocation, $timeline, $contractor, $plan, $resources, $technology, $risk_safety, $quality_control, $assigned_engineer);

        if ($stmt->execute()) {
            $message = "Project added successfully!";
        } else {
            error_log("MySQL Error: " . $stmt->error);
            $message = "Error adding project.";
            $error = true;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CPSU-pms</title>
    <link rel="stylesheet" href="css/add_project.css">
    <link rel="stylesheet" href="css/sidebar.css">
    
</head>
<body>

<div class="sidebar">
    <h2>CPSU-Project Monitoring System</h2>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="project_list.php">Projects List</a></li>
        <?php if ($role == 'engineer' || $role == 'head engineer') { ?>
            <li><a href="add_project.php" class="active">Add New Project</a></li>
        <?php } ?>
        <?php if ($role == 'engineer' || $role == 'head engineer') { ?>
            <li><a href="update_progress.php">Update Progress</a></li>
        <?php } ?>
        <?php if ($role == 'admin' || $role == 'mayor') { ?>
            <li><a href="users.php">User Management</a></li>
        <?php } ?>
        <li><a href="reports.php">Reports</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>
<div class="container">
    <h1>Add New Project</h1>
    <?php if ($message): ?>
        <p class="message<?php echo $error ? ' error' : ''; ?>"><?php echo $message; ?></p>
    <?php endif; ?>
    <form method="POST" action="add_project.php">
        <label>Project Title:</label>
        <input type="text" name="title" required>

        <label>Location:</label>
        <input type="text" name="location" required>
        
        <label>Budget Allocation:</label>
        <input type="number" step="0.01" name="budget_allocation" required>

        <label>Construction/Project Timeline:</label>
        <input type="text" name="timeline" required>

        <label>Name of Contractor:</label>
        <input type="text" name="contractor" required>

        <label>Construction Plan:</label>
        <textarea name="plan" required></textarea>

        <label>Resources (People and Materials):</label>
        <textarea name="resources" required></textarea>

        <label>Technology and Equipment:</label>
        <textarea name="technology" required></textarea>

        <label>Risk and Safety:</label>
        <textarea name="risk_safety" required></textarea>

        <label>Quality Control/Performance:</label>
        <textarea name="quality_control" required></textarea>

        <button class="btn" type="submit" name="add_project">Add Project</button>
    </form>
</div>
</body>
</html>