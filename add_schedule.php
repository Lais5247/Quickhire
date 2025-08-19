<?php
session_start();
require_once '../config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Check if user is logged in and is homeowner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'homeowner') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$homeowner_id = $_SESSION['user_id'];

try {
    // Format date
    $dateObj = DateTime::createFromFormat('M d, Y', $data['date']) ?: new DateTime($data['date']);
    $dateFormatted = $dateObj->format('Y-m-d');

    // Format time
    $time24 = date('H:i:s', strtotime($data['time']));

    // Ensure maid and status are set
    $maid   = $data['maid']   ?? '';
    $status = $data['status'] ?? 'pending';

    // Insert new schedule
    $stmt = $pdo->prepare("INSERT INTO homeowner_work_schedule 
                          (homeowner_id, `date`, `time`, maid, status) 
                          VALUES (?, ?, ?, ?, ?)");
    $success = $stmt->execute([
        $homeowner_id,
        $dateFormatted,
        $time24,
        $maid,
        $status
    ]);

    if ($success) {
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log("Database error: " . print_r($errorInfo, true));
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create schedule: ' . $errorInfo[2]
        ]);
    }

} catch (Exception $e) {
    error_log("Database error in add_schedule: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
