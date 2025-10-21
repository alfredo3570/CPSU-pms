<?php

session_start();
header('Content-Type: text/html; charset=utf-8');
include("db_connect.php");

// Restrict access to logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : null;

if (!$project_id) {
    echo "No project selected.";
    exit();
}

// Fetch project details
$sql = "SELECT p.*, u.fullname AS engineer_name
        FROM projects p
        LEFT JOIN users u ON p.assigned_engineer = u.user_id
        WHERE p.project_id = ?";
$stmt = $conn->prepare($sql);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPSU-pms</title>
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/project_view.css">
</head>
<body>
<div class="sidebar">
    <h2>CPSU-Project Monitoring System</h2>
    <div style="text-align:center; margin-top:30px;" class="sidebar-links">
        <li><a href="project_list.php"">&larr; Back to Project List</a></li>
        <br>
        <li><a href="update_progress.php?project_id=<?php echo urlencode($project['project_id']); ?>" >
            Update Progress
        </a></li>
        <br>
        <li><a href="edit_project.php?project_id=<?php echo urlencode($project['project_id']); ?>" >
            Edit Project
        </a></li>
    </div>
</div>
<div class="project-details">
    <h2><?php echo htmlspecialchars($project['title']); ?></h2>
    <table>
        <tr><th>Location</th><td><?php echo htmlspecialchars($project['location']); ?></td></tr>
        <tr><th>Budget Allocation</th><td><?php echo number_format($project['budget_allocation'], 2); ?></td></tr>
        <tr><th>Timeline</th><td><?php echo htmlspecialchars($project['timeline']); ?></td></tr>
        <tr><th>Contractor</th><td><?php echo htmlspecialchars($project['contractor']); ?></td></tr>
        <tr><th>Plan</th><td><?php echo htmlspecialchars($project['plan']); ?></td></tr>
        <tr><th>Resources</th><td><?php echo htmlspecialchars($project['resources']); ?></td></tr>
        <tr><th>Technology</th><td><?php echo htmlspecialchars($project['technology']); ?></td></tr>
        <tr><th>Risk & Safety</th><td><?php echo htmlspecialchars($project['risk_safety']); ?></td></tr>
        <tr><th>Quality Control</th><td><?php echo htmlspecialchars($project['quality_control']); ?></td></tr>
        <tr><th>Assigned Engineer</th><td><?php echo htmlspecialchars($project['engineer_name']); ?></td></tr>
        <tr><th>Date Created</th><td><?php echo htmlspecialchars($project['created_at']); ?></td></tr>
        <tr><th>Status</th><td><?php echo htmlspecialchars($project['status']); ?></td></tr>
        <tr><th>Progress (%)</th><td><?php echo htmlspecialchars($project['progress']); ?></td></tr>
        <tr><th>Building Permit No.</th><td><?php echo htmlspecialchars($project['building_permit_no']); ?></td></tr>
        <tr><th>Date Issued</th><td><?php echo htmlspecialchars($project['date_issued']); ?></td></tr>
        <tr><th>Contractor Name</th><td><?php echo htmlspecialchars($project['contractor_name']); ?></td></tr>
        <tr><th>License No.</th><td><?php echo htmlspecialchars($project['license_no']); ?></td></tr>
        <tr><th>Date Approved</th><td><?php echo htmlspecialchars($project['date_approved']); ?></td></tr>
        <tr><th>Manpower Organization</th><td><?php echo htmlspecialchars($project['manpower_organization']); ?></td></tr>
        <tr><th>Equipment Use</th><td><?php echo htmlspecialchars($project['equipment_use']); ?></td></tr>
    </table>
    
</div>
</body>
</html>