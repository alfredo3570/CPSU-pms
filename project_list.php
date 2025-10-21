<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
include("db_connect.php");

// Restrict access to logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

// Fetch all projects with assigned engineer's name
$sql = "SELECT p.*, u.fullname AS engineer_name
        FROM projects p
        LEFT JOIN users u ON p.assigned_engineer = u.user_id
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPSU-pms</title>
    <link rel="stylesheet" href="css/project_list.css">
    <link rel="stylesheet" href="css/sidebar.css">
</head>
<body>

<div class="sidebar">
    <h2>CPSU-Project Monitoring System</h2>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="project_list.php" class="active">Projects List</a></li>
        <?php if ($role == 'engineer' || $role == 'head engineer') { ?>
            <li><a href="add_project.php">Add New Project</a></li>
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
    <h1>Project List</h1>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Location</th>
                <th>Budget</th>
                <th>Status</th>
                <th>Engineer</th>
                <th>Timeline</th>
                <th>Created</th>
                <!-- Add other columns as needed -->
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr class="clickable-row" data-href="project_view.php?project_id=<?php echo $row['project_id']; ?>">
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                    <td><?php echo number_format($row['budget_allocation'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['engineer_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['timeline']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="11" style="text-align:center;">No projects found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var rows = document.querySelectorAll(".clickable-row");
    rows.forEach(function(row) {
        row.addEventListener("click", function() {
            window.location = row.getAttribute("data-href");
        });
    });
});
</script>
</body>
</html>