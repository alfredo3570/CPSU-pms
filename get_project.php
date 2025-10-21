<?php
session_start();
include("db_connect.php");

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (isset($_GET['id'])) {
    $project_id = intval($_GET['id']);

    $stmt = $conn->prepare("SELECT project_id, name, budget, deadline, duration, status FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $project = $result->fetch_assoc();
        echo json_encode($project);
    } else {
        echo json_encode(["error" => "Project not found."]);
    }

    $stmt->close();
} else {
    echo json_encode(["error" => "Invalid request."]);
}
?>