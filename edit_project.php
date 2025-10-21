<?php
session_start();
include("db_connect.php");

// Restrict access to logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['project_id'])) {
    echo "No project selected.";
    exit();
}

$project_id = intval($_GET['project_id']);
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $location = $_POST['location'];
    $budget = floatval($_POST['budget_allocation']);
    $status = $_POST['status'];
    $timeline = $_POST['timeline'];
    $plan = $_POST['plan'];
    $resources = $_POST['resources'];
    $technology = $_POST['technology'];
    $risk_safety = $_POST['risk_safety'];
    $quality_control = $_POST['quality_control'];

    $stmt = $conn->prepare("UPDATE projects SET title=?, location=?, budget_allocation=?, status=?, timeline=?, plan=?, resources=?, technology=?, risk_safety=?, quality_control=? WHERE project_id=?");
    $stmt->bind_param("ssdsssssssi", $title, $location, $budget, $status, $timeline, $plan, $resources, $technology, $risk_safety, $quality_control, $project_id);

    if ($stmt->execute()) {
        $message = "Project updated successfully!";
    } else {
        $message = "Error updating project.";
    }
    $stmt->close();
}

// Fetch project data
$stmt = $conn->prepare("SELECT * FROM projects WHERE project_id=?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();

if (!$project) {
    echo "Project not found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CPSU-pms</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/project_view.css">
    
</head>
<body>
<div class="sidebar">
    <h2>CPSU-Project Monitoring System</h2>
    <div style="text-align:center; margin-top:30px;" class="sidebar-links">
        <li><a href="project_list.php">&larr; Back to Project List</a></li>
        <br>
        <li><a href="update_progress.php?project_id=<?php echo urlencode($project['project_id']); ?>" >
            Update Progress
        </a></li>
        <br>
        <li><a class="active" href="edit_project.php?project_id=<?php echo urlencode($project['project_id']); ?>" >
            Edit Project
        </a></li>
    </div>
</div>
<div class="project-details">
    <h2>Edit Project: <?php echo htmlspecialchars($project['title']); ?></h2>
    <?php if ($message): ?>
        <div class="<?php echo strpos($message, 'successfully') !== false ? 'success-message' : 'error-message'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    <form method="post">
        <table>
            <tr>
                <th>Title</th>
                <td><input type="text" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required></td>
            </tr>
            <tr>
                <th>Location</th>
                <td><input type="text" name="location" value="<?php echo htmlspecialchars($project['location']); ?>" required></td>
            </tr>
            <tr>
                <th>Budget Allocation</th>
                <td><input type="number" step="0.01" name="budget_allocation" value="<?php echo htmlspecialchars($project['budget_allocation']); ?>" required></td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <?php
                    // ensure only allowed statuses appear in the select
                    $status = $project['status'];
                    if (!in_array($status, ['Ongoing', 'Completed'])) {
                        $status = 'Ongoing'; // fallback for legacy 'Pending' or other values
                    }
                    ?>
                    <select name="status" required>
                        <option value="Ongoing" <?php if($status == 'Ongoing') echo 'selected'; ?>>Ongoing</option>
                        <option value="Completed" <?php if($status == 'Completed') echo 'selected'; ?>>Completed</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>Timeline</th>
                <td><input type="text" name="timeline" value="<?php echo htmlspecialchars($project['timeline']); ?>" required></td>
            </tr>
            <tr>
                <th>Construction Plan</th>
                <td><textarea name="plan" required><?php echo htmlspecialchars($project['plan']); ?></textarea></td>
            </tr>
            <tr>
                <th>Resources (People and Materials)</th>
                <td><textarea name="resources" required><?php echo htmlspecialchars($project['resources']); ?></textarea></td>
            </tr>
            <tr>
                <th>Technology and Equipment</th>
                <td><textarea name="technology" required><?php echo htmlspecialchars($project['technology']); ?></textarea></td>
            </tr>
            <tr>
                <th>Risk and Safety</th>
                <td><textarea name="risk_safety" required><?php echo htmlspecialchars($project['risk_safety']); ?></textarea></td>
            </tr>
            <tr>
                <th>Quality Control/Performance</th>
                <td><textarea name="quality_control" required><?php echo htmlspecialchars($project['quality_control']); ?></textarea></td>
            </tr>
            <!-- Read-only fields below -->
            <tr>
                <th>Progress (%)</th>
                <td><input type="text" value="<?php echo htmlspecialchars($project['progress']); ?>" readonly></td>
            </tr>
            <tr>
                <th>Building Permit No.</th>
                <td><input type="text" value="<?php echo htmlspecialchars($project['building_permit_no']); ?>" readonly></td>
            </tr>
            <tr>
                <th>Date Issued</th>
                <td><input type="text" value="<?php echo htmlspecialchars($project['date_issued']); ?>" readonly></td>
            </tr>
            <tr>
                <th>Contractor Name</th>
                <td><input type="text" value="<?php echo htmlspecialchars($project['contractor_name']); ?>" readonly></td>
            </tr>
            <tr>
                <th>License No.</th>
                <td><input type="text" value="<?php echo htmlspecialchars($project['license_no']); ?>" readonly></td>
            </tr>
            <tr>
                <th>Date Approved</th>
                <td><input type="text" value="<?php echo htmlspecialchars($project['date_approved']); ?>" readonly></td>
            </tr>
            <tr>
                <th>Manpower Organization</th>
                <td><input type="text" value="<?php echo htmlspecialchars($project['manpower_organization']); ?>" readonly></td>
            </tr>
            <tr>
                <th>Equipment Use</th>
                <td><input type="text" value="<?php echo htmlspecialchars($project['equipment_use']); ?>" readonly></td>
            </tr>
        </table>
        <div class="button-container">
            <button class="update-project" type="submit">Update Project</button>
            <button class="cancel" onclick="location.href='project_view.php?project_id=<?php echo urlencode($project['project_id']); ?>'">Cancel</button>
        </div>
    </form>
</div>
</body>
</html>