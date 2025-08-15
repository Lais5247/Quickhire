<?php
require_once '../config.php';

function getUserData($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['name' => 'Unknown User', 'email' => 'unknown@example.com'];
        }
        
        return $user;
    } catch (PDOException $e) {
        error_log("Database error in getUserData: " . $e->getMessage());
        return ['name' => 'Error Loading', 'email' => 'error@example.com'];
    }
}


function getRecentActivities($user_id, $user_type) {
    // In a real application, this would come from the database
    $activities = [
        [
            'icon' => 'fas fa-handshake',
            'title' => 'Job Accepted',
            'description' => 'John Homeowner accepted your proposal',
            'time' => '15 minutes ago'
        ],
        [
            'icon' => 'fas fa-file-invoice-dollar',
            'title' => 'New Proposal',
            'description' => 'You sent a proposal for a cleaning job',
            'time' => '3 hours ago'
        ],
        [
            'icon' => 'fas fa-star',
            'title' => 'New Rating',
            'description' => 'Robert Brown rated your service 5 stars',
            'time' => '1 day ago'
        ],
        [
            'icon' => 'fas fa-wallet',
            'title' => 'Payment Received',
            'description' => 'You received $120 for cleaning service',
            'time' => '2 days ago'
        ]
    ];
    return $activities;
}

function getWorkSchedule($user_id, $user_type) {
    // In a real application, this would come from the database
    $schedule = [
        [
            'date' => date('M d, Y', strtotime('+2 days')),
            'day' => date('l', strtotime('+2 days')),
            'time' => '10:00 AM',
            'client' => 'John Homeowner',
            'status' => 'confirmed'
        ],
        [
            'date' => date('M d, Y', strtotime('+4 days')),
            'day' => date('l', strtotime('+4 days')),
            'time' => '1:00 PM',
            'client' => 'Sarah Johnson',
            'status' => 'confirmed'
        ],
        [
            'date' => date('M d, Y', strtotime('+6 days')),
            'day' => date('l', strtotime('+6 days')),
            'time' => '9:00 AM',
            'client' => 'Michael Brown',
            'status' => 'pending'
        ],
        [
            'date' => date('M d, Y', strtotime('-3 days')),
            'day' => date('l', strtotime('-3 days')),
            'time' => '11:00 AM',
            'client' => 'Emily Davis',
            'status' => 'completed'
        ]
    ];
    return $schedule;
}

function getMaidStats($user_id) {
    // In a real application, this would come from the database
    return [
        'total_jobs' => 24,
        'total_earnings' => 2850
    ];
}
?>