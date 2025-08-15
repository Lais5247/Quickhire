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
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM activities WHERE user_id = ? AND user_type = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$user_id, $user_type]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching activities: " . $e->getMessage());
        return [];
    }
}

function getWorkSchedule($user_id, $user_type) {
    // In a real application, this would come from the database
    $schedule = [
        [
            'date' => date('M d, Y', strtotime('+2 days')),
            'day' => date('l', strtotime('+2 days')),
            'time' => '10:00 AM',
            'maid' => 'Maria Lopez',
            'status' => 'confirmed'
        ],
        [
            'date' => date('M d, Y', strtotime('+5 days')),
            'day' => date('l', strtotime('+5 days')),
            'time' => '2:00 PM',
            'maid' => 'James Smith',
            'status' => 'pending'
        ],
        [
            'date' => date('M d, Y', strtotime('+9 days')),
            'day' => date('l', strtotime('+9 days')),
            'time' => '9:00 AM',
            'maid' => 'Sarah Johnson',
            'status' => 'confirmed'
        ],
        [
            'date' => date('M d, Y', strtotime('-3 days')),
            'day' => date('l', strtotime('-3 days')),
            'time' => '1:00 PM',
            'maid' => 'Robert Brown',
            'status' => 'completed'
        ]
    ];
    return $schedule;
}

function getHomeownerStats($user_id) {
    // In a real application, this would come from the database
    return [
        'hired_maids' => 8,
        'money_spent' => 1200
    ];
}
function createJob($homeowner_id, $title, $type, $description, $salary, $work_hours, $address) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO jobs (homeowner_id, title, type, description, salary, work_hours, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$homeowner_id, $title, $type, $description, $salary, $work_hours, $address]);
    } catch (PDOException $e) {
        error_log("Error creating job: " . $e->getMessage());
        return false;
    }
}

function getHomeownerJobs($homeowner_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE homeowner_id = ? AND status != 'cancelled' ORDER BY created_at DESC");
        $stmt->execute([$homeowner_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching jobs: " . $e->getMessage());
        return [];
    }
}

function addActivity($user_id, $user_type, $activity_type, $description, $icon) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO activities (user_id, user_type, activity_type, description, icon) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $user_type, $activity_type, $description, $icon]);
        return true;
    } catch (PDOException $e) {
        error_log("Error adding activity: " . $e->getMessage());
        return false;
    }
}

function getJobTypeLabel($type) {
    $labels = [
        'cleaner' => 'House Cleaning',
        'cook' => 'Cooking',
        'baby-care' => 'Baby Care',
        'elder-care' => 'Elder Care',
        'gardener' => 'Gardening',
        'driver' => 'Driver',
        'other' => 'Other Service'
    ];
    return $labels[$type] ?? ucfirst($type);
}
?>