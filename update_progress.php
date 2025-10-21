<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
include("db_connect.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? null;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_update'])) {
    $project_id = $_POST['project_id'] ?? null;
    $progress = $_POST['progress'] ?? null;
    $update_text = $_POST['update_text'] ?? null; // This will now contain the combined daily activities
    $completed_milestones = $_POST['completed_milestones'] ?? [];
    $update_date = date('Y-m-d H:i:s');

    // New fields from the logbook sheet (you'll need to add these to your database/form)
    $weather_condition = $_POST['weather_condition'] ?? null;
    $equipment_use = $_POST['equipment_use'] ?? null;
    $manpower_organization = $_POST['manpower_organization'] ?? null;
    $contractor_name = $_POST['contractor_name'] ?? null; // Assuming this comes from the project's details or a form field
    $license_no = $_POST['license_no'] ?? null; // Assuming this comes from the project's details or a form field

    if ($project_id && $progress && $update_text) {
        // Verify the project belongs to the current user (for engineers)
        if ($role == 'engineer') {
            $check_project = $conn->prepare("SELECT project_id FROM projects WHERE project_id = ? AND assigned_engineer = ?");
            $check_project->bind_param("ii", $project_id, $user_id);
            $check_project->execute();
            $check_project->store_result();

            if ($check_project->num_rows == 0) {
                die("You don't have permission to update this project");
            }
            $check_project->close();
        }

        // Insert the update
        // You might consider adding columns for weather, equipment, manpower to project_updates
        // For now, let's keep it simple and just use update_text for the main content.
        $stmt = $conn->prepare("INSERT INTO project_updates (project_id, user_id, update_text, progress, update_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $project_id, $user_id, $update_text, $progress, $update_date);

        if ($stmt->execute()) {
            // Update project progress
            $update_project = $conn->prepare("UPDATE projects SET progress = ? WHERE project_id = ?");
            $update_project->bind_param("ii", $progress, $project_id);
            $update_project->execute();

            // If progress is 100%, set status to 'Completed'
            if (intval($progress) >= 100) {
                $complete_stmt = $conn->prepare("UPDATE projects SET status = 'Completed' WHERE project_id = ?");
                $complete_stmt->bind_param("i", $project_id);
                $complete_stmt->execute();
                $complete_stmt->close();
            }

            // Mark milestones as completed
            if (!empty($completed_milestones)) {
                $milestone_stmt = $conn->prepare("UPDATE project_milestones SET is_completed = 1 WHERE milestone_id = ?");
                foreach ($completed_milestones as $milestone_id) {
                    $milestone_stmt->bind_param("i", $milestone_id);
                    $milestone_stmt->execute();
                }
                $milestone_stmt->close();
            }
            echo "<script>alert('Progress updated successfully!'); window.location.href = 'update_progress.php?project_id=" . $project_id . "';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Missing required fields!";
        if (!$project_id) echo " Project ID is missing!";
    }
}

// Get project ID from URL if not submitting
$project_id = $_GET['project_id'] ?? $_POST['project_id'] ?? null;

// Fetch project details, updates, and milestones
$project = [];
$updates = [];
$milestones = [];

if ($project_id) {
    // Get project info (only projects assigned to this engineer if role is engineer)
    $project_query = $conn->prepare("SELECT p.project_id, p.title, p.progress,
                                        p.building_permit_no, p.date_issued, p.contractor_name, p.license_no,
                                        p.date_approved, p.manpower_organization, p.equipment_use
                                 FROM projects p
                                 WHERE p.project_id = ?" .
                                 ($role == 'engineer' ? " AND p.assigned_engineer = ?" : ""));

    if ($role == 'engineer') {
        $project_query->bind_param("ii", $project_id, $user_id);
    } else {
        $project_query->bind_param("i", $project_id);
    }

    $project_query->execute();
    $project_result = $project_query->get_result();
    $project = $project_result->fetch_assoc() ?? [];
    $project_query->close();

    // Get updates for this project
    if (!empty($project)) {
        $updates_query = $conn->prepare("SELECT u.*, u.user_id as updater_id
                                         FROM project_updates u
                                         WHERE u.project_id = ?
                                         ORDER BY u.update_date DESC");
        $updates_query->bind_param("i", $project_id);
        $updates_query->execute();
        $updates_result = $updates_query->get_result();

        while ($update = $updates_result->fetch_assoc()) {
            $updates[] = $update;
        }
        $updates_query->close();

        
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPSU-pms</title>
    <link rel="stylesheet" href="css/update_progress.css">
    <link rel="stylesheet" href="css/sidebar.css">    
   
</head>
<body>
<div class="sidebar">
    <h2>CPSU-Project Monitoring System</h2>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="project_list.php">Projects List</a></li>
        <?php if ($role == 'engineer' || $role == 'head engineer') { ?>
            <li><a href="add_project.php">Add New Project</a></li>
        <?php } ?>
        <?php if ($role == 'engineer' || $role == 'head engineer') { ?>
            <li><a href="update_progress.php" class="active">Update Progress</a></li>
        <?php } ?>
        <?php if ($role == 'admin' || $role == 'mayor') { ?>
            <li><a href="users.php">User Management</a></li>
        <?php } ?>
        <li><a href="reports.php">Reports</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>
<div class="main-content">

    <?php if (empty($project)): ?>
        <div class="alert alert-danger">
            <p>Project not found. Please select a project from the list.</p>
            <p><a href="project_list.php">Return to projects list</a></p>
        </div>
    <?php else: ?>
        <form method="POST" action="update_progress.php" class="logbook-container">
            <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project['project_id']); ?>">
            <input type="hidden" name="progress" value="<?php echo htmlspecialchars($project['progress']); ?>">

            <div class="logbook-header">
                <h3>OFFICE OF THE BUILDING OFFICIAL</h3>
                <h1>Construction LOGBOOK SHEET</h1>
            </div>

            <div class="info-section">
                <div class="info-item">
                    <label>Building Permit No:</label>
                    <input type="text" name="building_permit_no" value="<?php echo htmlspecialchars($project['building_permit_no'] ?? ''); ?>" readonly>
                </div>
                <div class="info-item">
                    <label>Date Issued:</label>
                    <input type="text" name="date_issued" value="<?php echo htmlspecialchars($project['date_issued'] ? date('M d, Y', strtotime($project['date_issued'])) : ''); ?>" readonly>
                </div>
                <div class="info-item">
                    <label>Contractor:</label>
                    <input type="text" name="contractor_name" value="<?php echo htmlspecialchars($project['contractor_name'] ?? ''); ?>" readonly>
                </div>
                <div class="info-item">
                    <label>License No:</label>
                    <input type="text" name="license_no" value="<?php echo htmlspecialchars($project['license_no'] ?? ''); ?>" readonly>
                </div>
                <div class="info-item" style="width: 100%;">
                    <label>Project:</label>
                    <input type="text" name="project_name_display" value="<?php echo htmlspecialchars($project['title']); ?>" readonly style="width: calc(100% - 70px);">
                </div>
                <div class="info-item">
                    <label>Date Approved:</label>
                    <input type="text" name="date_approved" value="<?php echo htmlspecialchars($project['date_approved'] ? date('M d, Y', strtotime($project['date_approved'])) : ''); ?>" readonly>
                </div>
            </div>

            <div class="info-section">
                <div class="info-item">
                    <label>Manpower Organization:</label>
                    <input type="text" name="manpower_organization" value="<?php echo htmlspecialchars($project['manpower_organization'] ?? ''); ?>">
                </div>
                <div class="info-item">
                    <label>Equipment use:</label>
                    <input type="text" name="equipment_use" value="<?php echo htmlspecialchars($project['equipment_use'] ?? ''); ?>">
                </div>
                <div class="info-item">
                    <label>Weather Condition:</label>
                    <input type="text" name="weather_condition" placeholder="e.g., Fairly Sunny">
                </div>
                <div class="info-item">
                    <label>Date:</label>
                    <input type="text" name="current_date" value="<?php echo date('M d, Y'); ?>" readonly>
                </div>
            </div>

            <div class="section-title">DAILY CONSTRUCTION ACTIVITIES</div>
            <div class="daily-activities-grid">
                <div class="activity-category">
                    <label>AS TO ARCHITECTURAL WORKS</label>
                    <textarea name="update_text_architectural"></textarea>
                </div>
                <div class="activity-category">
                    <label>AS TO CIVIL/STRUCTURAL WORKS</label>
                    <textarea name="update_text_civil_structural"></textarea>
                </div>
                <div class="activity-category">
                    <label>AS TO ELECTRICAL WORKS</label>
                    <textarea name="update_text_electrical"></textarea>
                </div>
                <div class="activity-category">
                    <label>AS TO MECHANICAL WORKS</label>
                    <textarea name="update_text_mechanical"></textarea>
                </div>
                <div class="activity-category">
                    <label>AS TO PLUMBING WORKS</label>
                    <textarea name="update_text_plumbing"></textarea>
                </div>
                <div class="activity-category">
                    <label>AS TO SANITARY WORKS</label>
                    <textarea name="update_text_sanitary"></textarea>
                </div>
                <div class="activity-category">
                    <label>AS TO ELECTRONICS WORKS</label>
                    <textarea name="update_text_electronics"></textarea>
                </div>
                <div class="activity-category">
                    <label>AS TO INTERIOR DESIGN WORKS</label>
                    <textarea name="update_text_interior_design"></textarea>
                </div>
                <div class="activity-category" style="grid-column: span 2;">
                    <label>AS TO ACCESSIBILITY FEATURES</label>
                    <textarea name="update_text_accessibility"></textarea>
                </div>
            </div>
            <input type="hidden" id="combined_update_text" name="update_text">


            <?php if (!empty($milestones)): ?>
            <div class="progress-calculation-section">
                <h3>Complete Milestones to Update Progress</h3>
                <div class="form-group">
                    <?php foreach ($milestones as $milestone): ?>
                    <div class="milestone-checkbox">
                        <label>
                        <input type="checkbox" name="completed_milestones[]" value="<?php echo $milestone['milestone_id']; ?>" data-percentage="<?php echo $milestone['milestone_percentage']; ?>">
                        <?php echo htmlspecialchars($milestone['milestone_name']); ?> (<?php echo $milestone['milestone_percentage']; ?>%)
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p id="progress-display">Current Project Progress: <?php echo htmlspecialchars($project['progress']); ?>%</p>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo htmlspecialchars($project['progress']); ?>%"></div>
                </div>
            </div>
            <?php endif; ?>

            <div class="prepared-by-section">
                <div class="section-title">PREPARED AND SUBMITTED BY:</div>
                <div class="signature-line"></div>
                <p class="signature-label">Architect or Civil Engineer</p>
                <p class="signature-label">(Full-Time Inspector and Supervisor of the Construction Works)</p>
            </div>

            <div class="comments-section">
                <label>Comments/Recommendations:</label>
                <textarea name="comments_recommendations"></textarea>
            </div>

            <div class="info-section" style="margin-top: 20px;">
                <div class="info-item" style="width: 100%;">
                    <label>Building Official/Technical Inspector:</label>
                    <input type="text" name="building_official_inspector" style="width: calc(100% - 220px);">
                </div>
                <div class="info-item">
                    <label>Date of Inspection:</label>
                    <input type="text" name="date_of_inspection" value="<?php echo date('M d, Y'); ?>" readonly>
                </div>
            </div>

            <button type="submit" name="submit_update" class="btn-update-logbook">Submit Logbook Update</button>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const checkboxes = document.querySelectorAll('input[name="completed_milestones[]"]');
                const progressInput = document.querySelector('input[name="progress"]');
                const progressDisplay = document.getElementById('progress-display');
                const progressBar = document.querySelector('.progress-bar');

                function calculateAndUpdateProgress() {
                    let totalPercentage = parseInt(<?php echo $project['progress']; ?>); // Start with the current progress

                    checkboxes.forEach(checkbox => {
                        if (checkbox.checked) {
                            totalPercentage += parseInt(checkbox.getAttribute('data-percentage'));
                        }
                    });

                    // Ensure the total percentage does not exceed 100%
                    totalPercentage = Math.min(totalPercentage, 100);

                    // Update hidden progress input
                    progressInput.value = totalPercentage;

                    // Update displayed text
                    if (progressDisplay) {
                        progressDisplay.textContent = `Updated Project Progress: ${totalPercentage}%`;
                    }
                    // Update progress bar width
                    if (progressBar) {
                        progressBar.style.width = `${totalPercentage}%`;
                        progressBar.textContent = `${totalPercentage}%`; // Display percentage inside bar
                    }
                }

                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', calculateAndUpdateProgress);
                });

                // Initial calculation on page load
                calculateAndUpdateProgress();

                // Combine all daily activity textareas into one hidden input before form submission
                const logbookForm = document.querySelector('.logbook-container');
                logbookForm.addEventListener('submit', function() {
                    let combinedText = "";
                    const architecturalText = document.querySelector('textarea[name="update_text_architectural"]').value;
                    const civilStructuralText = document.querySelector('textarea[name="update_text_civil_structural"]').value;
                    const electricalText = document.querySelector('textarea[name="update_text_electrical"]').value;
                    const mechanicalText = document.querySelector('textarea[name="update_text_mechanical"]').value;
                    const plumbingText = document.querySelector('textarea[name="update_text_plumbing"]').value;
                    const sanitaryText = document.querySelector('textarea[name="update_text_sanitary"]').value;
                    const electronicsText = document.querySelector('textarea[name="update_text_electronics"]').value;
                    const interiorDesignText = document.querySelector('textarea[name="update_text_interior_design"]').value;
                    const accessibilityText = document.querySelector('textarea[name="update_text_accessibility"]').value;

                    if (architecturalText) combinedText += "ARCHITECTURAL:\n" + architecturalText + "\n\n";
                    if (civilStructuralText) combinedText += "CIVIL/STRUCTURAL:\n" + civilStructuralText + "\n\n";
                    if (electricalText) combinedText += "ELECTRICAL:\n" + electricalText + "\n\n";
                    if (mechanicalText) combinedText += "MECHANICAL:\n" + mechanicalText + "\n\n";
                    if (plumbingText) combinedText += "PLUMBING:\n" + plumbingText + "\n\n";
                    if (sanitaryText) combinedText += "SANITARY:\n" + sanitaryText + "\n\n";
                    if (electronicsText) combinedText += "ELECTRONICS:\n" + electronicsText + "\n\n";
                    if (interiorDesignText) combinedText += "INTERIOR DESIGN:\n" + interiorDesignText + "\n\n";
                    if (accessibilityText) combinedText += "ACCESSIBILITY:\n" + accessibilityText + "\n\n";

                    const commentsRecommendations = document.querySelector('textarea[name="comments_recommendations"]').value;
                    if (commentsRecommendations) combinedText += "COMMENTS/RECOMMENDATIONS:\n" + commentsRecommendations + "\n\n";

                    document.getElementById('combined_update_text').value = combinedText;
                });
            });
        </script>
        
    <?php endif; ?>
</div>
</body>
</html>