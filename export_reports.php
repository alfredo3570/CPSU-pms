<?php

session_start();
include("db_connect.php");

$search = $_POST['search'] ?? '';
$status = $_POST['status'] ?? '';

$query = "SELECT p.*, u.fullname AS engineer_name 
          FROM projects p 
          LEFT JOIN users u ON p.assigned_engineer = u.user_id 
          WHERE p.name LIKE ?";
$params = ["%{$search}%"];

if (!empty($status)) {
    $query .= " AND p.status = ?";
    $params[] = $status;
}

$stmt = $conn->prepare($query);
$types = str_repeat('s', count($params));
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=project_reports_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Project Name', 'Status', 'Progress', 'Budget', 'Duration (days)', 'Deadline', 'Engineer', 'Completion Report']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['name'],
        $row['status'],
        $row['progress'] . '%',
        $row['budget'],
        $row['duration'],
        $row['deadline'],
        $row['engineer_name'],
        (!empty($row['is_completed']) && !empty($row['completion_report'])) ? $row['completion_report'] : 'Not available'
    ]);
}
fclose($output);
exit;
?>