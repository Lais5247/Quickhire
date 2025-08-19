<?php
session_start();
require_once 'dashboard_functions.php';

// Check if user is logged in and is maid
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'maid') {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

// Update schedule
$success = updateWorkSchedule(
    $data['id'],
    $data['date'],
    $data['time'],
    $data['client'],
    $data['status']
);

if ($success) {
    echo json_encode(['success' => true]);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}