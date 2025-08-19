<?php
// finalized_jobs.php
session_start();
require_once('../config.php');

// Check if homeowner is logged in
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

// Initialize filter parameters from GET
$service_type = $_GET['service_type'] ?? '';
$status_filter = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build base query
$query = "SELECT * FROM jobs 
          WHERE homeowner_id = :homeowner_id 
          AND (status = 'assigned' OR status = 'completed')";

$params = [':homeowner_id' => $homeowner_id];

// Apply service type filter
if (!empty($service_type)) {
    $query .= " AND service_type = :service_type";
    $params[':service_type'] = $service_type;
}

// Apply status filter
if (!empty($status_filter)) {
    $query .= " AND status = :status";
    $params[':status'] = $status_filter;
}

// Apply sorting
$sort_options = [
    'newest' => " ORDER BY created_at DESC",
    'oldest' => " ORDER BY created_at ASC",
    'salary_high' => " ORDER BY salary DESC",
    'salary_low' => " ORDER BY salary ASC"
];

$query .= $sort_options[$sort] ?? $sort_options['newest'];

// Prepare and execute the query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Get counts for stats
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE homeowner_id = ?");
$total_stmt->execute([$homeowner_id]);
$total_jobs = $total_stmt->fetchColumn();

$assigned_stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE homeowner_id = ? AND status = 'assigned'");
$assigned_stmt->execute([$homeowner_id]);
$assigned_jobs = $assigned_stmt->fetchColumn();

$completed_stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE homeowner_id = ? AND status = 'completed'");
$completed_stmt->execute([$homeowner_id]);
$completed_jobs = $completed_stmt->fetchColumn();

// Get service types for filter dropdown
$service_types = $pdo->query("SELECT DISTINCT service_type FROM jobs")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalized Jobs - QuickHire</title>
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
            --card-radius: 16px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
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

        /* Filters Section */
        .filters {
            background: white;
            border-radius: var(--card-radius);
            padding: 25px;
            margin: 30px 0;
            box-shadow: var(--shadow);
        }

        .filters h2 {
            font-size: 22px;
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 15px;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
            background: white;
        }

        .filter-actions {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }

        /* Jobs Container */
        .jobs-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .job-card {
            background: white;
            border-radius: var(--card-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s forwards;
            border-top: 4px solid var(--primary);
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .job-card.completed {
            border-top: 4px solid var(--success);
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .job-header {
            padding: 20px;
            background: var(--light);
            border-bottom: 1px solid var(--border);
        }

        .job-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .job-type {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            display: inline-block;
        }

        .job-body {
            padding: 20px;
            flex-grow: 1;
        }

        .job-meta {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
        }

        .meta-item i {
            color: var(--primary);
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .job-footer {
            padding: 15px 20px;
            background: var(--light);
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .job-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-assigned {
            background: rgba(255, 193, 7, 0.15);
            color: #e0a800;
        }

        .status-completed {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }

        .btn-details {
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

        .btn-details:hover {
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
            grid-column: 1 / -1;
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
            .filter-row {
                flex-direction: column;
            }
            
            .stats-container {
                flex-direction: column;
            }
            
            .jobs-container {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                max-width: 95%;
            }
        }
    </style>
</head>
<body>
    <?php include('topbar.php'); ?>
    
    <div class="main-content">
        <div class="container">
            <h1><i class="fas fa-check-circle"></i> Finalized Jobs</h1>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-value"><?= $total_jobs ?></div>
                    <div class="stat-label">Total Jobs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $assigned_jobs ?></div>
                    <div class="stat-label">Assigned</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $completed_jobs ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            
            <div class="filters">
                <h2><i class="fas fa-filter"></i> Filter Jobs</h2>
                <form id="filterForm" method="GET">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="service_type">Service Type</label>
                            <select id="service_type" name="service_type" class="form-control">
                                <option value="">All Types</option>
                                <?php foreach ($service_types as $type): ?>
                                    <option value="<?= $type ?>" <?= $service_type === $type ? 'selected' : '' ?>>
                                        <?= $type ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="assigned" <?= $status_filter === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                                <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="sort">Sort By</label>
                            <select id="sort" name="sort" class="form-control">
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                <option value="salary_high" <?= $sort === 'salary_high' ? 'selected' : '' ?>>Salary: High to Low</option>
                                <option value="salary_low" <?= $sort === 'salary_low' ? 'selected' : '' ?>>Salary: Low to High</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <button type="button" class="btn btn-outline" id="resetFilters">
                            <i class="fas fa-sync-alt"></i> Reset
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="jobs-container">
                <?php if (count($jobs) > 0): ?>
                    <?php $counter = 0; ?>
                    <?php foreach ($jobs as $job): ?>
                        <div class="job-card <?= $job['status'] === 'completed' ? 'completed' : '' ?>" 
                             style="animation-delay: <?= $counter * 0.1 ?>s">
                            <div class="job-header">
                                <h3><?= htmlspecialchars($job['title']) ?></h3>
                                <span class="job-type"><?= htmlspecialchars($job['service_type']) ?></span>
                            </div>
                            <div class="job-body">
                                <div class="job-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-dollar-sign"></i>
                                        <span>$<?= number_format($job['salary'], 2) ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?= $job['work_hours'] ?> hours</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?= htmlspecialchars(substr($job['address'], 0, 50)) ?>...</span>
                                    </div>
                                </div>
                                <p><?= htmlspecialchars(substr($job['description'], 0, 100)) ?>...</p>
                            </div>
                            <div class="job-footer">
                                <div class="job-status status-<?= $job['status'] ?>">
                                    <?= ucfirst($job['status']) ?>
                                </div>
                                <button class="btn-details" onclick="viewJobDetails(<?= $job['job_id'] ?>)">
                                    <i class="fas fa-eye"></i> Show Details
                                </button>
                            </div>
                        </div>
                        <?php $counter++; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Finalized Jobs</h3>
                        <p>You don't have any assigned or completed jobs matching your filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
                
                <div class="job-detail">
                    <h3 class="detail-title"><i class="fas fa-calendar-alt"></i> Timeline</h3>
                    <div class="detail-content">
                        <div class="detail-row">
                            <div class="detail-label">Assigned On:</div>
                            <div class="detail-value" id="assignedDate"></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Completed On:</div>
                            <div class="detail-value" id="completedDate">Not completed yet</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
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
                    
                    // For demo purposes, set assigned and completed dates
                    document.getElementById('assignedDate').textContent = new Date(data.created_at).toLocaleDateString();
                    
                    if (data.status === 'completed') {
                        // Add 1-5 days to created_at for completed date
                        const completedDate = new Date(data.created_at);
                        completedDate.setDate(completedDate.getDate() + Math.floor(Math.random() * 5) + 1);
                        document.getElementById('completedDate').textContent = completedDate.toLocaleDateString();
                    } else {
                        document.getElementById('completedDate').textContent = "Not completed yet";
                    }
                    
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
        
        // Reset filters button
        document.getElementById('resetFilters').addEventListener('click', function() {
            document.getElementById('service_type').value = '';
            document.getElementById('status').value = '';
            document.getElementById('sort').value = 'newest';
            document.getElementById('filterForm').submit();
        });
    </script>
</body>
</html>