<?php
session_start();
include("db_connect.php");

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['fullname'];
$role = $_SESSION['role'];

// Error handling function
function handleQueryError($conn, $query_name) {
    if (!$conn->error) {
        return true;
    }
    error_log("Database error in $query_name: " . $conn->error);
    return false;
}

// Fetch total projects
$total_projects_query = "SELECT COUNT(*) AS total FROM projects";
$total_projects_result = $conn->query($total_projects_query);
if (!handleQueryError($conn, "total_projects_query")) {
    $total_projects = 0;
} else {
    $total_projects = $total_projects_result->fetch_assoc()['total'];
}

// Fetch ongoing projects
$ongoing_projects_query = "SELECT COUNT(*) AS ongoing FROM projects WHERE status='Ongoing'";
$ongoing_projects_result = $conn->query($ongoing_projects_query);
if (!handleQueryError($conn, "ongoing_projects_query")) {
    $ongoing_projects = 0;
} else {
    $ongoing_projects = $ongoing_projects_result->fetch_assoc()['ongoing'];
}

// Fetch completed projects
$completed_projects_query = "SELECT COUNT(*) AS completed FROM projects WHERE status='Completed'";
$completed_projects_result = $conn->query($completed_projects_query);
if (!handleQueryError($conn, "completed_projects_query")) {
    $completed_projects = 0;
} else {
    $completed_projects = $completed_projects_result->fetch_assoc()['completed'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPSU-pms</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script> <!-- Data Labels Plugin -->

</head>
<body>
<div class="sidebar">
    <h2>CPSU-Project Monitoring System</h2>
    <ul>
        <li><a href="dashboard.php" class="active">Dashboard</a></li>
        <li><a href="project_list.php">Projects List</a></li>
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

    <div class="main-content">
        <h1>Welcome, <?php echo $username; ?>!</h1>
        <p class="role"><?php echo ucfirst($role); ?></p>

        <div class="dashboard-summary">
            <div class="summary-card">
            <p>Total Projects</p>
            <h3><?php echo $total_projects; ?></h3>
            </div>
            <div class="summary-card">
                <p>Ongoing Projects</p>
                <h3><?php echo $ongoing_projects; ?></h3>
            </div>
            <div class="summary-card">
                <p>Completed Projects</p>
                <h3><?php echo $completed_projects; ?></h3>
            </div>
            
        </div>

        <h2>Ongoing Projects</h2>
        <div class="progress-chart-container">
            <div class="progress-container">
                <?php
                $progress_query = "SELECT title, progress FROM projects WHERE status='Ongoing' ORDER BY progress DESC LIMIT 4";
                $progress_result = $conn->query($progress_query);
                while ($row = $progress_result->fetch_assoc()) {
                    $project_name = $row['title'];
                    $progress = $row['progress'];
                ?>
                <div class="progress-item">
                    <p><?php echo $project_name; ?></p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                    </div>
                    <span><?php echo $progress; ?>%</span>
                </div>
                <?php } ?>
            </div>
            <div class="chart-container">
                <canvas id="projectStatusChart"></canvas>
            </div>
        </div>

        <h2>Recent Updates</h2>
        <table>
            <tr>
                <th>Project Name</th>
                <th>Status</th>
                <th>Last Update</th>
            </tr>
            <?php
            $latest_updates_query = "SELECT p.title, p.status, u.created_at 
                                      FROM projects p 
                                      INNER JOIN (
                                          SELECT title, MAX(created_at) as created_at 
                                          FROM projects 
                                          GROUP BY title
                                      ) u 
                                      ON p.title = u.title AND p.created_at = u.created_at 
                                      ORDER BY u.created_at DESC LIMIT 4";
            $latest_updates_result = $conn->query($latest_updates_query);
            while ($row = $latest_updates_result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['title']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td><?php echo date("M j, Y h:i A", strtotime($row['created_at'])); ?></td>
                </tr>
            <?php } ?>
        </table>
    </div>

    <script>
    // Get data from PHP
    var totalProjects = <?php echo $total_projects; ?>;
    var ongoingProjects = <?php echo $ongoing_projects; ?>;
    var completedProjects = <?php echo $completed_projects; ?>;

    // Calculate percentage
    function calculatePercentage(value) {
        return totalProjects > 0 ? ((value / totalProjects) * 100).toFixed(1) + "%" : "0%";
    }

    var ctx = document.getElementById('projectStatusChart').getContext('2d');
    var projectStatusChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Ongoing', 'Completed'],
            datasets: [{
                data: [ongoingProjects, completedProjects],
                backgroundColor: ['#FFC107', '#28a745'],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                datalabels: {
                    color: '#fff',
                    font: { weight: 'bold' },
                    formatter: function(value) {
                        return calculatePercentage(value);
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
    </script>

</body>
</html>