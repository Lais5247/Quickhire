<?php
// proposal_status.php
session_start();
require_once('../config.php');



$maid_id = $_SESSION['user_id'];

// Database connection
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch proposals for current maid
$query = "SELECT p.*, j.title AS job_title, u.name AS homeowner_name 
          FROM proposals p
          JOIN jobs j ON p.job_id = j.job_id
          JOIN users u ON j.homeowner_id = u.id
          WHERE p.maid_id = ?
          ORDER BY p.proposed_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$maid_id]);
$proposals = $stmt->fetchAll();

// Get counts for stats
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals WHERE maid_id = ?");
$total_stmt->execute([$maid_id]);
$total_proposals = $total_stmt->fetchColumn();

$pending_stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals WHERE maid_id = ? AND status = 'pending'");
$pending_stmt->execute([$maid_id]);
$pending_proposals = $pending_stmt->fetchColumn();

$accepted_stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals WHERE maid_id = ? AND status = 'accepted'");
$accepted_stmt->execute([$maid_id]);
$accepted_proposals = $accepted_stmt->fetchColumn();

$rejected_stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals WHERE maid_id = ? AND status = 'rejected'");
$rejected_stmt->execute([$maid_id]);
$rejected_proposals = $rejected_stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Proposals - QuickHire</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #ff6b6b;
            --warning: #ffd166;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border: #dee2e6;
            --card-radius: 16px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f9ff;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 15px 0;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo i {
            font-size: 32px;
            color: var(--success);
        }

        .logo h1 {
            font-size: 28px;
            font-weight: 700;
        }

        .user-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            border: none;
        }

        .btn-primary {
            background: white;
            color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--light);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        h1 {
            font-size: 32px;
            margin: 20px 0 30px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Stats Section */
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: white;
            border-radius: var(--card-radius);
            padding: 20px;
            flex: 1;
            min-width: 200px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            font-size: 16px;
            color: var(--gray);
        }

        /* Proposals Container */
        .proposals-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .proposal-card {
            background: white;
            border-radius: var(--card-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
            display: flex;
            flex-direction: column;
        }

        .proposal-card[data-status="pending"] {
            border-left: 4px solid var(--warning);
        }

        .proposal-card[data-status="accepted"] {
            border-left: 4px solid var(--success);
        }

        .proposal-card[data-status="rejected"] {
            border-left: 4px solid var(--danger);
        }

        .proposal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .proposal-header {
            padding: 20px;
            background: var(--light);
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .proposal-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            flex: 1;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            display: inline-block;
        }

        .proposal-card[data-status="pending"] .status-badge {
            background: rgba(255, 193, 7, 0.15);
            color: #e0a800;
        }

        .proposal-card[data-status="accepted"] .status-badge {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }

        .proposal-card[data-status="rejected"] .status-badge {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        .proposal-details {
            padding: 20px;
            flex-grow: 1;
        }

        .detail-row {
            display: flex;
            margin-bottom: 15px;
        }

        .detail-label {
            font-weight: 600;
            min-width: 130px;
            color: var(--gray);
        }

        .detail-value {
            flex: 1;
            color: var(--dark);
        }

        .proposal-footer {
            padding: 15px 20px;
            background: var(--light);
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pending-message, .accepted-message, .rejected-message {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 500;
        }

        .pending-message {
            color: #e0a800;
        }

        .accepted-message {
            color: #28a745;
        }

        .rejected-message {
            color: #dc3545;
        }

        .btn-action {
            background: var(--primary);
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-action:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: white;
            border-radius: var(--card-radius);
            box-shadow: var(--shadow);
        }

        .empty-state i {
            font-size: 64px;
            color: var(--light-gray);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: var(--dark);
        }

        .empty-state p {
            font-size: 18px;
            color: var(--gray);
            margin-bottom: 25px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
            padding: 20px;
        }

        .modal-content {
            background: white;
            border-radius: var(--card-radius);
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .modal-header {
            padding: 20px;
            background: var(--primary);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top-left-radius: var(--card-radius);
            border-top-right-radius: var(--card-radius);
        }

        .modal-header h2 {
            font-size: 24px;
            font-weight: 600;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }

        .close-modal:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 25px;
        }

        .job-detail {
            margin-bottom: 25px;
        }

        .detail-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary);
            padding-bottom: 10px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-content {
            font-size: 16px;
            line-height: 1.6;
            color: var(--dark);
        }

        .detail-content p {
            margin-bottom: 15px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 12px;
        }

        .detail-label {
            font-weight: 600;
            min-width: 150px;
            color: var(--gray);
        }

        .detail-value {
            flex: 1;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 20px;
            }
            
            .stats-container {
                flex-direction: column;
            }
            
            .proposals-container {
                grid-template-columns: 1fr;
            }
            
            .proposal-footer {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .modal-content {
                max-width: 95%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <i class="fas fa-bolt"></i>
                <h1>QuickHire</h1>
            </div>
            <div class="user-actions">
                <button class="btn btn-primary" onclick="location.href='index.php'">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </button>
                <button class="btn btn-outline" onclick="location.href='../auth/logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
    </header>

    <div class="container">
        <h1><i class="fas fa-file-contract"></i> My Proposals</h1>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-value"><?= $total_proposals ?></div>
                <div class="stat-label">Total Proposals</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $pending_proposals ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $accepted_proposals ?></div>
                <div class="stat-label">Accepted</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $rejected_proposals ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>
        
        <?php if (count($proposals) > 0): ?>
            <div class="proposals-container">
                <?php foreach ($proposals as $proposal): ?>
                    <div class="proposal-card" data-status="<?= $proposal['status'] ?>">
                        <div class="proposal-header">
                            <h3><?= htmlspecialchars($proposal['job_title']) ?></h3>
                            <span class="status-badge"><?= ucfirst($proposal['status']) ?></span>
                        </div>
                        
                        <div class="proposal-details">
                            <div class="detail-row">
                                <div class="detail-label">Homeowner:</div>
                                <div class="detail-value"><?= htmlspecialchars($proposal['homeowner_name']) ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Proposed Salary:</div>
                                <div class="detail-value">$<?= number_format($proposal['proposed_salary'], 2) ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Proposed At:</div>
                                <div class="detail-value"><?= date('M d, Y h:i A', strtotime($proposal['proposed_at'])) ?></div>
                            </div>
                            <?php if ($proposal['message']): ?>
                                <div class="detail-row">
                                    <div class="detail-label">Your Message:</div>
                                    <div class="detail-value"><?= htmlspecialchars($proposal['message']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="proposal-footer">
                            <?php if ($proposal['status'] === 'accepted'): ?>
                                <div class="accepted-message">
                                    <i class="fas fa-check-circle"></i> Your proposal has been accepted!
                                </div>
                                <button class="btn btn-action" onclick="viewJobDetails(<?= $proposal['job_id'] ?>)">
                                    <i class="fas fa-info-circle"></i> View Job Details
                                </button>
                            <?php elseif ($proposal['status'] === 'rejected'): ?>
                                <div class="rejected-message">
                                    <i class="fas fa-times-circle"></i> Your proposal was not accepted
                                </div>
                            <?php else: ?>
                                <div class="pending-message">
                                    <i class="fas fa-clock"></i> Waiting for homeowner's response
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No Proposals Yet</h3>
                <p>You haven't submitted any proposals yet. Start by browsing available jobs.</p>
                <button class="btn btn-primary" onclick="location.href='available_jobs.php'">
                    <i class="fas fa-briefcase"></i> Browse Jobs
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Job Details Modal -->
    <div class="modal" id="jobDetailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Job Details</h2>
                <button class="close-modal" onclick="closeModal('jobDetailModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="job-detail">
                    <h3 class="detail-title"><i class="fas fa-info-circle"></i> Job Information</h3>
                    <div class="detail-content">
                        <div class="detail-row">
                            <div class="detail-label">Job Title:</div>
                            <div class="detail-value" id="jobTitle"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Service Type:</div>
                            <div class="detail-value" id="serviceType"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Salary:</div>
                            <div class="detail-value" id="salary"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Estimated Hours:</div>
                            <div class="detail-value" id="workHours"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value" id="jobStatus"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Posted:</div>
                            <div class="detail-value" id="createdAt"></div>
                        </div>
                    </div>
                </div>
                
                <div class="job-detail">
                    <h3 class="detail-title"><i class="fas fa-map-marker-alt"></i> Location</h3>
                    <div class="detail-content">
                        <div class="detail-value" id="address"></div>
                    </div>
                </div>
                
                <div class="job-detail">
                    <h3 class="detail-title"><i class="fas fa-file-alt"></i> Description</h3>
                    <div class="detail-content">
                        <p id="description"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Simple animations
        document.querySelectorAll('.proposal-card').forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 * index);
        });
        
        // Modal functions
        function viewJobDetails(jobId) {
            // Fetch job details
            fetch(`get_job_details.php?job_id=${jobId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('jobTitle').textContent = data.title;
                    document.getElementById('serviceType').textContent = data.service_type;
                    document.getElementById('salary').textContent = '$' + data.salary;
                    document.getElementById('workHours').textContent = data.work_hours + ' hours';
                    document.getElementById('jobStatus').textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                    document.getElementById('address').textContent = data.address;
                    document.getElementById('description').textContent = data.description;
                    document.getElementById('createdAt').textContent = new Date(data.created_at).toLocaleDateString();
                    
                    document.getElementById('jobDetailModal').style.display = 'flex';
                })
                .catch(error => {
                    console.error('Error fetching job details:', error);
                    alert('Error loading job details. Please try again.');
                });
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                closeModal('jobDetailModal');
            }
        });
    </script>
</body>
</html>