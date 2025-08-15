<?php
session_start();
require_once 'dashboard_functions.php';

// Check if user is logged in and is homeowner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'homeowner') {
    header("Location: ../auth/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_data = getUserData($user_id);
$activities = getRecentActivities($user_id, 'homeowner');
$schedule = getWorkSchedule($user_id, 'homeowner');
$stats = getHomeownerStats($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homeowner Dashboard | QuickHire</title>
    <link rel="stylesheet" href="index_style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-broom"></i>
                <h2>QuickHire</h2>
            </div>
            
            <ul class="nav-links">
                <li>
                    <a href="#" class="active">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="create_job.php">
                        <i class="fas fa-plus-circle"></i>
                        <span>Create A Job</span>
                    </a>
                </li>
                <li>
                    <a href="counter_proposals.php">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Counter Proposals</span>
                    </a>
                </li>
                <li>
                    <a href="finalized_jobs.php">
                        <i class="fas fa-check-circle"></i>
                        <span>Finalized Jobs</span>
                    </a>
                </li>
                <li>
                    <button class="logout-btn" id="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search...">
                </div>
                
                <div class="user-actions">
                    <div class="notification">
                        <i class="fas fa-bell"></i>
                        <span class="badge"><?= count($activities) ?></span>
                    </div>
                    
                    <div class="user-profile">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User">
                        <div class="user-info">
                            <h4><?= htmlspecialchars($user_data['name']) ?></h4>
                            <p>Homeowner</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-header">
                        <h3>Statistics</h3>
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statsChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Activity</h3>
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="card-body">
                        <div class="activity-container">
                            <?php foreach ($activities as $activity): ?>
                            <div class="activity-item homeowner">
                                <div class="activity-icon">
                                    <i class="<?= $activity['icon'] ?>"></i>
                                </div>
                                <div class="activity-details">
                                    <h4><?= $activity['title'] ?></h4>
                                    <p><?= $activity['description'] ?></p>
                                    <div class="activity-time"><?= $activity['time'] ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Work Schedule</h3>
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="card-body">
                        <div class="schedule-table-container">
                            <table class="schedule-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th>Time</th>
                                        <th>Maid</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedule as $job): ?>
                                    <tr>
                                        <td><?= $job['date'] ?></td>
                                        <td><?= $job['day'] ?></td>
                                        <td><?= $job['time'] ?></td>
                                        <td><?= $job['maid'] ?></td>
                                        <td><span class="status-badge status-<?= $job['status'] ?>"><?= ucfirst($job['status']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <button class="btn btn-primary" id="settings-btn">
                <i class="fas fa-cog"></i> Account Settings
            </button>
        </div>
    </div>
    
    <!-- Settings Modal -->
    <div class="modal" id="settings-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Account Settings</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="password-form">
                    <div class="form-group">
                        <label for="current-password">Current Password</label>
                        <input type="password" id="current-password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="new-password">New Password</label>
                        <input type="password" id="new-password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirm New Password</label>
                        <input type="password" id="confirm-password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Change Password</button>
                </form>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        // Initialize Statistics Chart
        const statsCtx = document.getElementById('statsChart').getContext('2d');
        const statsChart = new Chart(statsCtx, {
            type: 'bar',
            data: {
                labels: ['Hired Maids', 'Money Spent'],
                datasets: [{
                    label: 'Homeowner Statistics',
                    data: [<?= $stats['hired_maids'] ?>, <?= $stats['money_spent'] ?>],
                    backgroundColor: [
                        'rgba(67, 97, 238, 0.7)',
                        'rgba(63, 55, 201, 0.7)'
                    ],
                    borderColor: [
                        'rgba(67, 97, 238, 1)',
                        'rgba(63, 55, 201, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value === <?= $stats['hired_maids'] ?> ? value : '$' + value;
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y === <?= $stats['hired_maids'] ?>) {
                                    label += context.parsed.y + ' maids';
                                } else {
                                    label += '$' + context.parsed.y;
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>