<?php
session_start();
require_once('../config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Validate job ID
if (!isset($_POST['job_id']) || !is_numeric($_POST['job_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid job ID']);
    exit;
}

$job_id = (int)$_POST['job_id'];
$homeowner_id = $_SESSION['user_id'];

try {
    // Verify job belongs to this homeowner
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE job_id = :job_id AND homeowner_id = :homeowner_id");
    $stmt->execute([':job_id' => $job_id, ':homeowner_id' => $homeowner_id]);
    $job = $stmt->fetch();
    
    if (!$job) {
        echo json_encode(['success' => false, 'error' => 'Job not found or you do not have permission to delete it']);
        exit;
    }
    
    // Delete the job
    $stmt = $pdo->prepare("DELETE FROM jobs WHERE job_id = :job_id");
    $stmt->execute([':job_id' => $job_id]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}