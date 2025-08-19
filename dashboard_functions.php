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

function getWorkSchedule($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, 
                DATE_FORMAT(schedule_date, '%b %d, %Y') AS date_display,
                DATE_FORMAT(schedule_date, '%W') AS day,
                DATE_FORMAT(schedule_time, '%h:%i %p') AS time_display,
                maid_name, status 
            FROM homeowner_schedule 
            WHERE homeowner_id = ? 
            ORDER BY schedule_date ASC, schedule_time ASC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getWorkSchedule: " . $e->getMessage());
        return [];
    }
}

function saveSchedule($data) {
    global $pdo;
    try {
        // Convert date format
        $dateObj = DateTime::createFromFormat('M d, Y', $data['date']);
        if (!$dateObj) {
            $dateObj = new DateTime($data['date']);
        }
        $formattedDate = $dateObj->format('Y-m-d');
        
        // Convert time to 24-hour format
        $time24 = date('H:i:s', strtotime($data['time']));
        
        if ($data['id'] === 'new') {
            $stmt = $pdo->prepare("INSERT INTO homeowner_schedule 
                (homeowner_id, schedule_date, schedule_time, maid_name, status)
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['homeowner_id'],
                $formattedDate,
                $time24,
                $data['maid'],
                $data['status']
            ]);
            return $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare("UPDATE homeowner_schedule SET
                schedule_date = ?,
                schedule_time = ?,
                maid_name = ?,
                status = ?
                WHERE id = ? AND homeowner_id = ?");
            $stmt->execute([
                $formattedDate,
                $time24,
                $data['maid'],
                $data['status'],
                $data['id'],
                $data['homeowner_id']
            ]);
            return $data['id'];
        }
    } catch (PDOException $e) {
        error_log("Database error in saveSchedule: " . $e->getMessage());
        return false;
    }
}

function deleteSchedule($id, $homeowner_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM homeowner_schedule 
            WHERE id = ? AND homeowner_id = ?");
        return $stmt->execute([$id, $homeowner_id]);
    } catch (PDOException $e) {
        error_log("Database error in deleteSchedule: " . $e->getMessage());
        return false;
    }
}

function getHomeownerStats($user_id) {
    global $pdo;
    try {
        // Hired Maids: Count of distinct maids assigned to jobs for this homeowner
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT assigned_maid_id) AS hired_maids 
                               FROM jobs 
                               WHERE homeowner_id = :user_id 
                                 AND status IN ('assigned', 'completed') 
                                 AND assigned_maid_id IS NOT NULL");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $hired_maids = $stmt->fetchColumn();

        // Money Spent: Sum of salary for completed jobs
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(salary), 0) AS money_spent 
                               FROM jobs 
                               WHERE homeowner_id = :user_id 
                                 AND status = 'assigned'");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $money_spent = $stmt->fetchColumn();

        return [
            'hired_maids' => $hired_maids,
            'money_spent' => $money_spent
        ];
    } catch (PDOException $e) {
        error_log("Database error in getHomeownerStats: " . $e->getMessage());
        return [
            'hired_maids' => 0,
            'money_spent' => 0
        ];
    }
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