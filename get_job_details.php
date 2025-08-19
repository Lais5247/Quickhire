<?php
session_start();
require_once('../config.php');

// Check if user is homeowner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'homeowner') {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Validate job ID
if (!isset($_GET['job_id']) || !is_numeric($_GET['job_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid job ID']);
    exit();
}

$job_id = (int)$_GET['job_id'];
$homeowner_id = $_SESSION['user_id'];

// Database connection
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Fetch job details
try {
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE job_id = :job_id AND homeowner_id = :homeowner_id");
    $stmt->execute([':job_id' => $job_id, ':homeowner_id' => $homeowner_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Job not found']);
        exit();
    }
    
    // Return job data
    header('Content-Type: application/json');
    echo json_encode($job);
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}