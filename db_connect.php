<?php
// Database configuration
$config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'pms'
];

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Create connection with error handling
try {
    $conn = new mysqli(
        $config['host'],
        $config['username'],
        $config['password'],
        $config['database']
    );

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset to utf8mb4
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting charset: " . $conn->error);
    }

    // Set timezone
    date_default_timezone_set('Asia/Manila');

} catch (Exception $e) {
    // Log the error
    error_log("Database connection error: " . $e->getMessage());
    
    // Show user-friendly message
    die("Sorry, there was a problem connecting to the database. Please try again later.");
}

// Function to safely escape strings
function escape_string($string) {
    global $conn;
    return $conn->real_escape_string(trim($string));
}

// Function to safely prepare and execute queries
function execute_query($query, $types = "", $params = []) {
    global $conn;
    
    try {
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }

        if (!empty($types) && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }

        return $stmt;
    } catch (Exception $e) {
        error_log("Query execution error: " . $e->getMessage());
        return false;
    }
}
?>