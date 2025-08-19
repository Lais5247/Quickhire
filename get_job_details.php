<?php
session_start();
require_once('../config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

// Validate job ID
if (!isset($_GET['job_id']) || !is_numeric($_GET['job_id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$job_id = (int)$_GET['job_id'];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Fetch job details
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE job_id = ?");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        header("HTTP/1.1 404 Not Found");
        exit();
    }
    
    // Return job details as JSON
    header('Content-Type: application/json');
    echo json_encode($job);
} catch (PDOException $e) {
    header("HTTP/1.1 500 Internal Server Error");
    exit();
}