<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is homeowner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'homeowner') {
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

$id = $data['id'];
$homeowner_id = $_SESSION['user_id'];

try {
    // Debug: Log received data
    error_log("Received update data: " . print_r($data, true));
    
    // Convert date to proper format
    $dateObj = DateTime::createFromFormat('M d, Y', $data['date']);
    if (!$dateObj) {
        $dateObj = new DateTime($data['date']);
    }
    $dateFormatted = $dateObj->format('Y-m-d');
    
    // Convert time to 24-hour format
    $time24 = date('H:i:s', strtotime($data['time']));
    
    // Update schedule with homeowner check
    $stmt = $pdo->prepare("UPDATE homeowner_work_schedule 
                          SET date = ?, time = ?, maid = ?, status = ? 
                          WHERE id = ? AND homeowner_id = ?");
    $success = $stmt->execute([
        $dateFormatted,
        $time24,
        $data['maid'],
        $data['status'],
        $id,
        $homeowner_id
    ]);
    
    if ($success && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log("Database error: " . print_r($errorInfo, true));
        echo json_encode([
            'success' => false, 
            'message' => 'No changes made or schedule not found: ' . $errorInfo[2]
        ]);
    }
} catch (PDOException | Exception $e) {
    error_log("Database error in update_schedule: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}