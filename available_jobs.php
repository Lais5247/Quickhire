<?php
session_start();
require_once('../config.php');



$maid_id = $_SESSION['user_id'];

// Database connection
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accept_job'])) {
        // Accept job directly
        $job_id = $_POST['job_id'];
        
        try {
            // Update job status and assign to maid
            $stmt = $pdo->prepare("UPDATE jobs SET status = 'assigned', assigned_maid_id = ? WHERE job_id = ?");
            $stmt->execute([$maid_id, $job_id]);
            
            $_SESSION['success'] = "Job accepted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error accepting job: " . $e->getMessage();
        }
    } 
    elseif (isset($_POST['submit_proposal'])) {
        // Submit counter proposal
        $job_id = $_POST['job_id'];
        $proposed_salary = $_POST['proposed_salary'];
        $message = $_POST['message'] ?? '';
        
        try {
            // Insert proposal
            $stmt = $pdo->prepare("INSERT INTO proposals (job_id, maid_id, proposed_salary, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$job_id, $maid_id, $proposed_salary, $message]);
            
            $_SESSION['success'] = "Counter proposal submitted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error submitting proposal: " . $e->getMessage();
        }
    }
    
    // Redirect to avoid form resubmission
    header("Location: available_jobs.php");
    exit();
}

// Apply filters
$filters = [];
$params = [];

// Service Type filter
if (!empty($_GET['service_type'])) {
    $filters[] = "service_type = ?";
    $params[] = $_GET['service_type'];
}

// Salary Range filter
if (!empty($_GET['salary_range'])) {
    list($min, $max) = explode('-', $_GET['salary_range']);
    $filters[] = "salary BETWEEN ? AND ?";
    $params[] = $min;
    $params[] = $max;
}

// Location filter
if (!empty($_GET['location'])) {
    $filters[] = "address LIKE ?";
    $params[] = '%' . $_GET['location'] . '%';
}

// Build query
$query = "SELECT * FROM jobs WHERE status = 'open'";
if (!empty($filters)) {
    $query .= " AND " . implode(" AND ", $filters);
}

// Sorting
$sort_options = [
    'newest' => "ORDER BY created_at DESC",
    'oldest' => "ORDER BY created_at ASC",
    'salary_high' => "ORDER BY salary DESC",
    'salary_low' => "ORDER BY salary ASC"
];
$sort = $_GET['sort'] ?? 'newest';
$sort_query = $sort_options[$sort] ?? $sort_options['newest'];
$query .= " " . $sort_query;

// Fetch jobs
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Fetch service types for filter dropdown
$service_types = $pdo->query("SELECT DISTINCT service_type FROM jobs")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Jobs - QuickHire</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="available_jobs.css">
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
        <h1><i class="fas fa-briefcase"></i> Available Jobs</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="filters">
            <h2><i class="fas fa-filter"></i> Filter Jobs</h2>
            <form method="GET" id="filterForm">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="service_type">Service Type</label>
                        <select id="service_type" name="service_type" class="form-control">
                            <option value="">All Types</option>
                            <?php foreach ($service_types as $type): ?>
                                <option value="<?= $type ?>" <?= ($_GET['service_type'] ?? '') === $type ? 'selected' : '' ?>>
                                    <?= $type ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="salary_range">Salary Range</label>
                        <select id="salary_range" name="salary_range" class="form-control">
                            <option value="">Any Salary</option>
                            <option value="0-50" <?= ($_GET['salary_range'] ?? '') === '0-50' ? 'selected' : '' ?>>Up to $50</option>
                            <option value="50-100" <?= ($_GET['salary_range'] ?? '') === '50-100' ? 'selected' : '' ?>>$50 - $100</option>
                            <option value="100-200" <?= ($_GET['salary_range'] ?? '') === '100-200' ? 'selected' : '' ?>>$100 - $200</option>
                            <option value="200-500" <?= ($_GET['salary_range'] ?? '') === '200-500' ? 'selected' : '' ?>>$200 - $500</option>
                            <option value="500-1000" <?= ($_GET['salary_range'] ?? '') === '500-1000' ? 'selected' : '' ?>>$500 - $1000</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-control" 
                               placeholder="Enter location" value="<?= $_GET['location'] ?? '' ?>">
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="sort">Sort By</label>
                        <select id="sort" name="sort" class="form-control">
                            <option value="newest" <?= ($_GET['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest First</option>
                            <option value="oldest" <?= ($_GET['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="salary_high" <?= ($_GET['sort'] ?? '') === 'salary_high' ? 'selected' : '' ?>>Salary: High to Low</option>
                            <option value="salary_low" <?= ($_GET['sort'] ?? '') === 'salary_low' ? 'selected' : '' ?>>Salary: Low to High</option>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <button type="button" class="btn btn-outline" onclick="resetFilters()">
                            <i class="fas fa-sync-alt"></i> Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="jobs-grid">
            <?php if (count($jobs) > 0): ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card" data-job-id="<?= $job['job_id'] ?>">
                        <div class="job-header">
                            <h3 class="job-title"><?= htmlspecialchars($job['title']) ?></h3>
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
                                    <span><?= htmlspecialchars($job['address']) ?></span>
                                </div>
                            </div>
                            <p class="job-description"><?= htmlspecialchars(substr($job['description'], 0, 100)) ?>...</p>
                        </div>
                        <div class="job-footer">
                            <div class="posted-date">
                                <i class="far fa-clock"></i> Posted <?= date('M d, Y', strtotime($job['created_at'])) ?>
                            </div>
                            <button class="btn view-details">View Details</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Jobs Available</h3>
                    <p>There are currently no jobs matching your filters.</p>
                    <button class="btn btn-primary" onclick="resetFilters()">
                        Reset Filters
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Job Detail Modal -->
    <div class="modal" id="jobDetailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalJobTitle">Job Title</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="job-detail">
                    <h3 class="detail-title"><i class="fas fa-info-circle"></i> Job Details</h3>
                    <div class="detail-content">
                        <p><strong>Service Type:</strong> <span id="modalServiceType"></span></p>
                        <p><strong>Salary:</strong> $<span id="modalSalary"></span></p>
                        <p><strong>Estimated Hours:</strong> <span id="modalHours"></span> hours</p>
                        <p><strong>Location:</strong> <span id="modalLocation"></span></p>
                        <p><strong>Posted:</strong> <span id="modalPosted"></span></p>
                    </div>
                </div>
                
                <div class="job-detail">
                    <h3 class="detail-title"><i class="fas fa-file-alt"></i> Description</h3>
                    <div class="detail-content">
                        <p id="modalDescription"></p>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <form method="POST" id="acceptForm">
                        <input type="hidden" name="job_id" id="acceptJobId">
                        <button type="submit" name="accept_job" class="btn accept-btn">
                            <i class="fas fa-check-circle"></i> Accept Job
                        </button>
                    </form>
                    <button class="btn proposal-btn" id="proposalBtn">
                        <i class="fas fa-exchange-alt"></i> Counter Proposal
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Proposal Modal -->
    <div class="modal" id="proposalModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Submit Counter Proposal</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="proposalForm">
                    <input type="hidden" name="job_id" id="proposalJobId">
                    
                    <div class="form-group">
                        <label for="proposed_salary">Proposed Salary ($)</label>
                        <input type="number" id="proposed_salary" name="proposed_salary" 
                               class="form-control" min="0" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message to Homeowner (Optional)</label>
                        <textarea id="message" name="message" class="form-control" rows="4" 
                                  placeholder="Explain your proposal or provide additional information"></textarea>
                    </div>
                    
                    <button type="submit" name="submit_proposal" class="btn submit-proposal">
                        <i class="fas fa-paper-plane"></i> Submit Proposal
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // DOM Elements
        const jobDetailModal = document.getElementById('jobDetailModal');
        const proposalModal = document.getElementById('proposalModal');
        const closeModalButtons = document.querySelectorAll('.close-modal');
        const jobCards = document.querySelectorAll('.job-card');
        const proposalBtn = document.getElementById('proposalBtn');
        const acceptForm = document.getElementById('acceptForm');
        const proposalForm = document.getElementById('proposalForm');
        
        // Store job data for modal
        let currentJob = null;
        
        // Event Listeners
        jobCards.forEach(card => {
            card.addEventListener('click', (e) => {
                // Don't trigger for buttons inside the card
                if (e.target.tagName === 'BUTTON') return;
                
                const jobId = card.dataset.jobId;
                fetchJobDetails(jobId);
            });
            
            const viewBtn = card.querySelector('.view-details');
            viewBtn.addEventListener('click', () => {
                const jobId = card.dataset.jobId;
                fetchJobDetails(jobId);
            });
        });
        
        closeModalButtons.forEach(button => {
            button.addEventListener('click', () => {
                jobDetailModal.style.display = 'none';
                proposalModal.style.display = 'none';
            });
        });
        
        proposalBtn.addEventListener('click', () => {
            jobDetailModal.style.display = 'none';
            document.getElementById('proposalJobId').value = currentJob.job_id;
            document.getElementById('proposed_salary').value = currentJob.salary;
            proposalModal.style.display = 'flex';
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === jobDetailModal) jobDetailModal.style.display = 'none';
            if (e.target === proposalModal) proposalModal.style.display = 'none';
        });
        
        // Functions
        function fetchJobDetails(jobId) {
            fetch(`get_job_details.php?job_id=${jobId}`)
                .then(response => response.json())
                .then(data => {
                    currentJob = data;
                    showJobDetail(data);
                })
                .catch(error => {
                    console.error('Error fetching job details:', error);
                    alert('Error loading job details. Please try again.');
                });
        }
        
        function showJobDetail(job) {
            document.getElementById('modalJobTitle').textContent = job.title;
            document.getElementById('modalServiceType').textContent = job.service_type;
            document.getElementById('modalSalary').textContent = job.salary;
            document.getElementById('modalHours').textContent = job.work_hours;
            document.getElementById('modalLocation').textContent = job.address;
            document.getElementById('modalPosted').textContent = new Date(job.created_at).toLocaleDateString();
            document.getElementById('modalDescription').textContent = job.description;
            
            document.getElementById('acceptJobId').value = job.job_id;
            
            jobDetailModal.style.display = 'flex';
        }
        
        function resetFilters() {
            document.getElementById('filterForm').reset();
            document.getElementById('filterForm').submit();
        }
    </script>
</body>
</html>