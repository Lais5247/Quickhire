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
                client_name, status 
            FROM maid_schedule 
            WHERE maid_id = ? 
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
            $stmt = $pdo->prepare("INSERT INTO maid_schedule 
                (maid_id, schedule_date, schedule_time, client_name, status)
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['maid_id'],
                $formattedDate,
                $time24,
                $data['client'],
                $data['status']
            ]);
            return $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare("UPDATE maid_schedule SET
                schedule_date = ?,
                schedule_time = ?,
                client_name = ?,
                status = ?
                WHERE id = ? AND maid_id = ?");
            $stmt->execute([
                $formattedDate,
                $time24,
                $data['client'],
                $data['status'],
                $data['id'],
                $data['maid_id']
            ]);
            return $data['id'];
        }
    } catch (PDOException $e) {
        error_log("Database error in saveSchedule: " . $e->getMessage());
        return false;
    }
}

function deleteSchedule($id, $maid_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM maid_schedule 
            WHERE id = ? AND maid_id = ?");
        return $stmt->execute([$id, $maid_id]);
    } catch (PDOException $e) {
        error_log("Database error in deleteSchedule: " . $e->getMessage());
        return false;
    }
}

function getMaidStats($user_id) {
    global $pdo;
    try {
        // Total jobs: assigned or completed
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total_jobs FROM jobs WHERE assigned_maid_id = :user_id AND status IN ('assigned', 'completed')");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $total_jobs = $stmt->fetchColumn();

        // Total earnings: only completed jobs
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(salary), 0) AS total_earnings FROM jobs WHERE assigned_maid_id = :user_id AND status = 'assigned'");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $total_earnings = $stmt->fetchColumn();

        return [
            'total_jobs' => $total_jobs,
            'total_earnings' => $total_earnings
        ];
    } catch (PDOException $e) {
        error_log("Database error in getMaidStats: " . $e->getMessage());
        return [
            'total_jobs' => 0,
            'total_earnings' => 0
        ];
    }
}
?>