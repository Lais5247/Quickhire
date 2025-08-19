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
$schedule = getWorkSchedule($user_id);
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
    <style>
        /* Add editing style */
        tr.editing td {
            background-color: #fff8e1;
        }
        
        .btn-edit, .btn-save, .btn-cancel, .btn-delete {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            font-size: 16px;
            margin: 0 3px;
            padding: 5px;
        }
        
        .btn-edit:hover {
            color: var(--primary);
        }
        
        .btn-save:hover {
            color: #28a745;
        }
        
        .btn-cancel:hover {
            color: #dc3545;
        }
        
        .btn-delete:hover {
            color: #dc3545;
        }
        
        .edit-field {
            padding: 5px 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            width: 100%;
        }
        
        .add-row-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .add-row-btn:hover {
            background: var(--secondary);
        }
        
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-bolt"></i>
                <h2>QuickHire</h2>
            </div>
            
            <ul class="nav-links">
                <li>
                    <a href="index.php" class="active">
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
                        <button class="add-row-btn" id="addScheduleBtn">
                            <i class="fas fa-plus"></i> Add New Schedule
                        </button>
                        
                        <div class="schedule-table-container">
                            <table class="schedule-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th>Time</th>
                                        <th>Maid</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="scheduleBody">
                                    <?php if (!empty($schedule)): ?>
                                        <?php foreach ($schedule as $job): ?>
                                        <tr data-id="<?= $job['id'] ?>">
                                            <td><?= $job['date_display'] ?></td>
                                            <td><?= $job['day'] ?></td>
                                            <td><?= $job['time_display'] ?></td>
                                            <td><?= htmlspecialchars($job['maid_name']) ?></td>
                                            <td>
                                                <span class="status-badge status-<?= $job['status'] ?>">
                                                    <?= ucfirst($job['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn-edit"><i class="fas fa-edit"></i></button>
                                                <button class="btn-delete"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No schedules found</td>
                                        </tr>
                                    <?php endif; ?>
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

    <script>
        // Initialize Statistics Chart with dual axes
        const statsCtx = document.getElementById('statsChart').getContext('2d');
        const statsChart = new Chart(statsCtx, {
            type: 'bar',
            data: {
                labels: ['Statistics'],
                datasets: [
                    {
                        label: 'Hired Maids',
                        data: [<?= $stats['hired_maids'] ?>],
                        backgroundColor: 'rgba(67, 97, 238, 0.7)',
                        borderColor: 'rgba(67, 97, 238, 1)',
                        borderWidth: 1,
                        yAxisID: 'maids-axis'
                    },
                    {
                        label: 'Money Spent ($)',
                        data: [<?= $stats['money_spent'] ?>],
                        backgroundColor: 'rgba(63, 55, 201, 0.7)',
                        borderColor: 'rgba(63, 55, 201, 1)',
                        borderWidth: 1,
                        yAxisID: 'money-axis'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    'maids-axis': {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Hired Maids',
                            color: 'rgba(67, 97, 238, 1)'
                        },
                        ticks: {
                            precision: 0
                        }
                    },
                    'money-axis': {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Money Spent ($)',
                            color: 'rgba(63, 55, 201, 1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label.includes('Maids')) {
                                    return label + ': ' + context.parsed.y;
                                } else {
                                    return label + ': $' + context.parsed.y;
                                }
                            }
                        }
                    }
                }
            }
        });
        
        // Work schedule functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add new schedule
            document.getElementById('addScheduleBtn').addEventListener('click', function() {
                const today = new Date();
                const formattedDate = today.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: '2-digit', 
                    year: 'numeric' 
                });
                
                const newRow = document.createElement('tr');
                newRow.dataset.id = 'new';
                newRow.innerHTML = `
                    <td><input type="text" class="edit-field date-field" value="${formattedDate}"></td>
                    <td>${today.toLocaleDateString('en-US', { weekday: 'long' })}</td>
                    <td><input type="text" class="edit-field" value="09:00 AM"></td>
                    <td><input type="text" class="edit-field" value="New Maid"></td>
                    <td>
                        <select class="edit-field">
                            <option value="pending" selected>Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                        </select>
                    </td>
                    <td>
                        <button class="btn-save"><i class="fas fa-check"></i></button>
                        <button class="btn-cancel"><i class="fas fa-times"></i></button>
                    </td>
                `;
                document.getElementById('scheduleBody').prepend(newRow);
            });

            // Event delegation for actions
            document.getElementById('scheduleBody').addEventListener('click', function(e) {
                const btn = e.target.closest('button');
                if (!btn) return;
                
                const row = btn.closest('tr');
                const rowId = row.dataset.id;
                
                if (btn.classList.contains('btn-edit')) {
                    enterEditMode(row);
                }
                else if (btn.classList.contains('btn-save')) {
                    saveSchedule(row);
                }
                else if (btn.classList.contains('btn-cancel')) {
                    if (rowId === 'new') row.remove();
                    else resetRow(row);
                }
                else if (btn.classList.contains('btn-delete')) {
                    if (confirm('Delete this schedule?')) {
                        deleteSchedule(rowId, row);
                    }
                }
            });

            // Enter edit mode
            function enterEditMode(row) {
                const cells = row.querySelectorAll('td');
                const originalData = {
                    date: cells[0].textContent,
                    time: cells[2].textContent,
                    maid: cells[3].textContent,
                    status: cells[4].querySelector('.status-badge').textContent.toLowerCase()
                };
                
                row.dataset.original = JSON.stringify(originalData);
                
                cells[0].innerHTML = `<input type="text" class="edit-field date-field" value="${originalData.date}">`;
                cells[2].innerHTML = `<input type="text" class="edit-field" value="${originalData.time}">`;
                cells[3].innerHTML = `<input type="text" class="edit-field" value="${originalData.maid}">`;
                cells[4].innerHTML = `
                    <select class="edit-field">
                        <option value="pending" ${originalData.status === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="confirmed" ${originalData.status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                        <option value="completed" ${originalData.status === 'completed' ? 'selected' : ''}>Completed</option>
                    </select>
                `;
                cells[5].innerHTML = `
                    <button class="btn-save"><i class="fas fa-check"></i></button>
                    <button class="btn-cancel"><i class="fas fa-times"></i></button>
                `;
                
                row.classList.add('editing');
            }

            // Reset row to original state
            function resetRow(row) {
                const original = JSON.parse(row.dataset.original);
                const cells = row.querySelectorAll('td');
                
                cells[0].textContent = original.date;
                cells[2].textContent = original.time;
                cells[3].textContent = original.maid;
                cells[4].innerHTML = `<span class="status-badge status-${original.status}">${original.status.charAt(0).toUpperCase() + original.status.slice(1)}</span>`;
                cells[5].innerHTML = `
                    <button class="btn-edit"><i class="fas fa-edit"></i></button>
                    <button class="btn-delete"><i class="fas fa-trash"></i></button>
                `;
                
                row.classList.remove('editing');
            }

            // Save schedule to database
            function saveSchedule(row) {
                const inputs = row.querySelectorAll('.edit-field');
                const data = {
                    id: row.dataset.id,
                    homeowner_id: <?= $_SESSION['user_id'] ?>,
                    date: inputs[0].value,
                    time: inputs[1].value,
                    maid: inputs[2].value,
                    status: inputs[3].value
                };
                
                fetch('save_schedule.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        updateRowAfterSave(row, result);
                    } else {
                        alert('Error saving: ' + (result.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while saving');
                });
            }

            // Update UI after successful save
            function updateRowAfterSave(row, result) {
                const dateObj = new Date(result.saved_date);
                const formattedDate = dateObj.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: '2-digit', 
                    year: 'numeric' 
                });
                
                row.dataset.id = result.id;
                row.innerHTML = `
                    <td>${formattedDate}</td>
                    <td>${dateObj.toLocaleDateString('en-US', { weekday: 'long' })}</td>
                    <td>${result.saved_time}</td>
                    <td>${result.maid}</td>
                    <td><span class="status-badge status-${result.status}">${result.status.charAt(0).toUpperCase() + result.status.slice(1)}</span></td>
                    <td>
                        <button class="btn-edit"><i class="fas fa-edit"></i></button>
                        <button class="btn-delete"><i class="fas fa-trash"></i></button>
                    </td>
                `;
            }

            // Delete schedule
            function deleteSchedule(id, row) {
                fetch('delete_schedule.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 300);
                    } else {
                        alert('Error deleting: ' + (result.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting');
                });
            }
        });

        // Modal functionality
        const settingsBtn = document.getElementById('settings-btn');
        const settingsModal = document.getElementById('settings-modal');
        const closeModal = document.querySelector('.close-modal');
        const logoutBtn = document.getElementById('logout-btn');
        
        if (settingsBtn) {
            settingsBtn.addEventListener('click', () => {
                settingsModal.style.display = 'flex';
            });
        }
        
        if (closeModal) {
            closeModal.addEventListener('click', () => {
                settingsModal.style.display = 'none';
            });
        }
        
        window.addEventListener('click', (e) => {
            if (e.target === settingsModal) {
                settingsModal.style.display = 'none';
            }
        });
        
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => {
                window.location.href = '../auth/logout.php';
            });
        }
        
        // Password form submission
        const passwordForm = document.getElementById('password-form');
        if (passwordForm) {
            passwordForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const currentPassword = document.getElementById('current-password').value;
                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;
                
                if (newPassword !== confirmPassword) {
                    alert('Passwords do not match!');
                    return;
                }
                
                if (newPassword.length < 6) {
                    alert('Password must be at least 6 characters long!');
                    return;
                }
                
                try {
                    const res = await fetch('../auth/update_password.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            current_password: currentPassword,
                            new_password: newPassword
                        })
                    });
                    
                    const data = await res.json();
                    alert(data.message);
                    if (data.status === 'success') {
                        settingsModal.style.display = 'none';
                        passwordForm.reset();
                    }
                } catch (err) {
                    alert('Password update failed: ' + err.message);
                    console.error(err);
                }
            });
        }
    </script>
</body>
</html>