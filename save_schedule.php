<?php
session_start();
require_once '../config.php';
require_once 'dashboard_functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'maid') {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

try {
    $id = saveSchedule([
        'id' => $data['id'],
        'maid_id' => $_SESSION['user_id'],
        'date' => $data['date'],
        'time' => $data['time'],
        'client' => $data['client'],
        'status' => $data['status']
    ]);
    
    if ($id) {
        // Return saved data for UI update
        $stmt = $pdo->prepare("SELECT 
                DATE_FORMAT(schedule_date, '%b %d, %Y') AS saved_date,
                DATE_FORMAT(schedule_time, '%h:%i %p') AS saved_time,
                client_name AS client,
                status
            FROM maid_schedule WHERE id = ?");
        $stmt->execute([$id]);
        $savedData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'id' => $id,
            ...$savedData
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save schedule']);
    }
} catch (Exception $e) {
    error_log("Error in save_schedule: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}