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
if (!$data || !isset($data['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$success = deleteSchedule($data['id'], $_SESSION['user_id']);
echo json_encode(['success' => $success]);