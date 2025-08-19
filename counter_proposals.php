<?php
session_start();
require_once('../config.php');

// Redirect if not homeowner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'homeowner') {
    header("Location: ../auth/index.php");
    exit();
}

$homeowner_id = $_SESSION['user_id'];

// Database connection
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle proposal actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accept_proposal'])) {
        $proposal_id = $_POST['proposal_id'];
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Get proposal details
            $stmt = $pdo->prepare("SELECT * FROM proposals WHERE proposal_id = ?");
            $stmt->execute([$proposal_id]);
            $proposal = $stmt->fetch();
            
            if (!$proposal) {
                throw new Exception("Proposal not found");
            }
            
            // Update proposal status
            $stmt = $pdo->prepare("UPDATE proposals SET status = 'accepted' WHERE proposal_id = ?");
            $stmt->execute([$proposal_id]);
            
            // Update job status and assign maid
            $stmt = $pdo->prepare("UPDATE jobs SET status = 'assigned', assigned_maid_id = ? WHERE job_id = ?");
            $stmt->execute([$proposal['maid_id'], $proposal['job_id']]);
            
            // Commit transaction
            $pdo->commit();
            
            $_SESSION['success'] = "Proposal accepted successfully! The job has been assigned.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error accepting proposal: " . $e->getMessage();
        }
    } 
    elseif (isset($_POST['reject_proposal'])) {
        $proposal_id = $_POST['proposal_id'];
        
        try {
            // Update proposal status
            $stmt = $pdo->prepare("UPDATE proposals SET status = 'rejected' WHERE proposal_id = ?");
            $stmt->execute([$proposal_id]);
            
            $_SESSION['success'] = "Proposal rejected successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error rejecting proposal: " . $e->getMessage();
        }
    }
    
    // Redirect to avoid form resubmission
    header("Location: counter_proposals.php");
    exit();
}

// Fetch proposals for current homeowner's jobs
$query = "SELECT p.*, j.title AS job_title, u.name AS maid_name 
          FROM proposals p
          JOIN jobs j ON p.job_id = j.job_id
          JOIN users u ON p.maid_id = u.id
          WHERE j.homeowner_id = ? AND p.status = 'pending'
          ORDER BY p.proposed_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$homeowner_id]);
$proposals = $stmt->fetchAll();

// Get counts for stats
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals p JOIN jobs j ON p.job_id = j.job_id WHERE j.homeowner_id = ?");
$total_stmt->execute([$homeowner_id]);
$total_proposals = $total_stmt->fetchColumn();

$pending_stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals p JOIN jobs j ON p.job_id = j.job_id WHERE j.homeowner_id = ? AND p.status = 'pending'");
$pending_stmt->execute([$homeowner_id]);
$pending_proposals = $pending_stmt->fetchColumn();

$accepted_stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals p JOIN jobs j ON p.job_id = j.job_id WHERE j.homeowner_id = ? AND p.status = 'accepted'");
$accepted_stmt->execute([$homeowner_id]);
$accepted_proposals = $accepted_stmt->fetchColumn();

$rejected_stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals p JOIN jobs j ON p.job_id = j.job_id WHERE j.homeowner_id = ? AND p.status = 'rejected'");
$rejected_stmt->execute([$homeowner_id]);
$rejected_proposals = $rejected_stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counter Proposals - QuickHire</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="index_style.css">
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
            --card-radius: 12px;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        .main-content .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
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

        /* Proposals Table */
        .proposals-table {
            background: white;
            border-radius: var(--card-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 40px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        th {
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
        }

        tbody tr {
            border-bottom: 1px solid var(--light-gray);
            transition: var(--transition);
        }

        tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.03);
        }

        td {
            padding: 16px 20px;
            color: var(--dark);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.15);
            color: #e0a800;
        }

        .status-accepted {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }

        .status-rejected {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-accept {
            background: var(--success);
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-accept:hover {
            background: #3db8d8;
            transform: translateY(-2px);
        }

        .btn-reject {
            background: var(--danger);
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-reject:hover {
            background: #ff5252;
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
            margin-bottom: 20px;
            color: var(--light-gray);
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

        /* Alert Styles */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 16px;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
            border: 1px solid #28a745;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            border: 1px solid #dc3545;
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
            max-width: 600px;
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

        .proposal-detail {
            margin-bottom: 20px;
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

        .modal-actions {
            display: flex;
            gap: 15px;
            padding-top: 20px;
            margin-top: 20px;
            border-top: 1px solid var(--light-gray);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
            }
            
            .stats-container {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .modal-content {
                max-width: 95%;
            }
            
            .modal-actions {
                flex-direction: column;
            }
            
            .btn-accept, .btn-reject {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include('topbar.php'); ?>
    
    <div class="main-content">
        <div class="container">
            <h1><i class="fas fa-file-contract"></i> Counter Proposals</h1>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-value"><?= $total_proposals ?></div>
                    <div class="stat-label">Total Proposals</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $pending_proposals ?></div>
                    <div class="stat-label">Pending Review</div>
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
                <div class="proposals-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Maid</th>
                                <th>Proposed Salary</th>
                                <th>Proposed At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proposals as $proposal): ?>
                                <tr>
                                    <td><?= htmlspecialchars($proposal['job_title']) ?></td>
                                    <td><?= htmlspecialchars($proposal['maid_name']) ?></td>
                                    <td>$<?= number_format($proposal['proposed_salary'], 2) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($proposal['proposed_at'])) ?></td>
                                    <td class="action-buttons">
                                        <button class="btn-accept" onclick="showAcceptModal(
                                            <?= $proposal['proposal_id'] ?>, 
                                            '<?= htmlspecialchars($proposal['job_title']) ?>', 
                                            '<?= htmlspecialchars($proposal['maid_name']) ?>', 
                                            <?= $proposal['proposed_salary'] ?>, 
                                            '<?= htmlspecialchars($proposal['message']) ?>'
                                        )">
                                            <i class="fas fa-check"></i> Accept
                                        </button>
                                        <button class="btn-reject" onclick="showRejectModal(
                                            <?= $proposal['proposal_id'] ?>, 
                                            '<?= htmlspecialchars($proposal['job_title']) ?>', 
                                            '<?= htmlspecialchars($proposal['maid_name']) ?>', 
                                            <?= $proposal['proposed_salary'] ?>
                                        )">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Pending Proposals</h3>
                    <p>You currently have no counter proposals to review.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Accept Proposal Modal -->
    <div class="modal" id="acceptModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Accept Proposal</h2>
                <button class="close-modal" onclick="closeModal('acceptModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="proposal-detail">
                    <h3 class="detail-title"><i class="fas fa-info-circle"></i> Proposal Details</h3>
                    <div class="detail-content">
                        <div class="detail-row">
                            <div class="detail-label">Job Title:</div>
                            <div class="detail-value" id="acceptJobTitle"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Maid:</div>
                            <div class="detail-value" id="acceptMaidName"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Proposed Salary:</div>
                            <div class="detail-value" id="acceptSalary"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Message:</div>
                            <div class="detail-value" id="acceptMessage"></div>
                        </div>
                    </div>
                </div>
                
                <form id="acceptForm" method="POST">
                    <input type="hidden" name="proposal_id" id="acceptProposalId">
                    <input type="hidden" name="accept_proposal">
                    
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check-circle"></i> Confirm Acceptance
                        </button>
                        <button type="button" class="btn btn-outline" onclick="closeModal('acceptModal')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Reject Proposal Modal -->
    <div class="modal" id="rejectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Reject Proposal</h2>
                <button class="close-modal" onclick="closeModal('rejectModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="proposal-detail">
                    <h3 class="detail-title"><i class="fas fa-info-circle"></i> Proposal Details</h3>
                    <div class="detail-content">
                        <div class="detail-row">
                            <div class="detail-label">Job Title:</div>
                            <div class="detail-value" id="rejectJobTitle"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Maid:</div>
                            <div class="detail-value" id="rejectMaidName"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Proposed Salary:</div>
                            <div class="detail-value" id="rejectSalary"></div>
                        </div>
                    </div>
                </div>
                
                <form id="rejectForm" method="POST">
                    <input type="hidden" name="proposal_id" id="rejectProposalId">
                    <input type="hidden" name="reject_proposal">
                    
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-reject">
                            <i class="fas fa-times-circle"></i> Confirm Rejection
                        </button>
                        <button type="button" class="btn btn-outline" onclick="closeModal('rejectModal')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functions
        function showAcceptModal(proposalId, jobTitle, maidName, salary, message) {
            document.getElementById('acceptJobTitle').textContent = jobTitle;
            document.getElementById('acceptMaidName').textContent = maidName;
            document.getElementById('acceptSalary').textContent = '$' + salary.toFixed(2);
            document.getElementById('acceptMessage').textContent = message || 'No message provided';
            document.getElementById('acceptProposalId').value = proposalId;
            document.getElementById('acceptModal').style.display = 'flex';
        }
        
        function showRejectModal(proposalId, jobTitle, maidName, salary) {
            document.getElementById('rejectJobTitle').textContent = jobTitle;
            document.getElementById('rejectMaidName').textContent = maidName;
            document.getElementById('rejectSalary').textContent = '$' + salary.toFixed(2);
            document.getElementById('rejectProposalId').value = proposalId;
            document.getElementById('rejectModal').style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    modal.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>