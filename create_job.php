<?php
session_start();
require_once('dashboard_functions.php');
require_once('../config.php');
include('topbar.php');

// Initialize variables
$success = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $title = $_POST['jobTitle'];
    $service_type = $_POST['serviceType'];
    $description = $_POST['jobDescription'];
    $salary = floatval($_POST['estimatedSalary']);
    $work_hours = intval($_POST['estimatedHours']);
    $address = $_POST['workAddress'];
    $homeowner_id = $_SESSION['user_id'];
    
    // Basic validation
    if (empty($title) || empty($service_type) || empty($description) || empty($address)) {
        $error = "Please fill in all required fields";
    } elseif ($salary <= 0 || $work_hours <= 0) {
        $error = "Salary and work hours must be positive values";
    } else {
        try {
            // Insert into database using PDO
            $query = "INSERT INTO jobs (homeowner_id, title, service_type, description, salary, work_hours, address, status) 
                      VALUES (:homeowner_id, :title, :service_type, :description, :salary, :work_hours, :address, 'open')";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':homeowner_id' => $homeowner_id,
                ':title' => $title,
                ':service_type' => $service_type,
                ':description' => $description,
                ':salary' => $salary,
                ':work_hours' => $work_hours,
                ':address' => $address
            ]);
            
            $success = "Job posted successfully!";
            // Clear form fields
            $_POST = [];
        } catch (PDOException $e) {
            $error = "Error posting job: " . $e->getMessage();
        }
    }
}

// Fetch active jobs for display
$active_jobs = [];
if (isset($_SESSION['user_id'])) {
    try {
        $homeowner_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE homeowner_id = :homeowner_id AND status = 'open' ORDER BY created_at DESC");
        $stmt->execute([':homeowner_id' => $homeowner_id]);
        $active_jobs = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = $error ? $error . "<br>Error fetching jobs: " . $e->getMessage() : "Error fetching jobs: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Job - QuickHire</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="index_style.css">
    <link rel="stylesheet" href="create_job.css"> 
    <style>
        /* Additional styles for alerts and animations */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 0 0 25px 0;
            font-size: 16px;
            position: relative;
            z-index: 10;
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
        
        .create-job-container {
            position: relative;
            padding-top: 20px;
        }
        
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .job-card {
            transition: opacity 0.3s, transform 0.3s;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="create-job-container">
            <!-- Page Header -->
            <div class="page-header">
                <h2><i class="fas fa-briefcase"></i> Create New Job</h2>
                <div class="header-actions">
                    
                </div>
            </div>
            
            <!-- Display messages -->
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Job Form Section -->
            <div class="job-form-section">
                <h3 class="section-title">
                    <i class="fas fa-file-alt"></i> Job Details
                </h3>
                
                <!-- Job Form -->
                <form id="jobForm" method="POST">
                    <div class="form-group">
                        <label for="jobTitle">Job Title *</label>
                        <input type="text" class="form-control" id="jobTitle" name="jobTitle" 
                               placeholder="e.g., Weekly House Cleaning" 
                               value="<?php echo isset($_POST['jobTitle']) ? htmlspecialchars($_POST['jobTitle']) : ''; ?>" required>
                        <i class="fas fa-heading input-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="serviceType">Service Type *</label>
                        <select class="form-control" id="serviceType" name="serviceType" required>
                            <option value="" disabled <?php echo !isset($_POST['serviceType']) ? 'selected' : ''; ?>>Select service type</option>
                            <option value="Cleaning Services" <?php echo (isset($_POST['serviceType']) && $_POST['serviceType'] === 'Cleaning Services') ? 'selected' : ''; ?>>Cleaning Services</option>
                            <option value="Cooking Services" <?php echo (isset($_POST['serviceType']) && $_POST['serviceType'] === 'Cooking Services') ? 'selected' : ''; ?>>Cooking Services</option>
                            <option value="Baby Caretaker" <?php echo (isset($_POST['serviceType']) && $_POST['serviceType'] === 'Baby Caretaker') ? 'selected' : ''; ?>>Baby Caretaker</option>
                            <option value="Personal Assistance" <?php echo (isset($_POST['serviceType']) && $_POST['serviceType'] === 'Personal Assistance') ? 'selected' : ''; ?>>Personal Assistance</option>
                            <option value="Older Caretaker" <?php echo (isset($_POST['serviceType']) && $_POST['serviceType'] === 'Older Caretaker') ? 'selected' : ''; ?>>Older Caretaker</option>
                        </select>
                        <i class="fas fa-tasks input-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="jobDescription">Job Description *</label>
                        <textarea class="form-control" id="jobDescription" name="jobDescription" 
                                  placeholder="Describe the job details..." required><?php 
                            echo isset($_POST['jobDescription']) ? htmlspecialchars($_POST['jobDescription']) : ''; 
                        ?></textarea>
                        <i class="fas fa-file-alt input-icon"></i>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="estimatedSalary">Estimated Salary ($) *</label>
                            <input type="number" class="form-control" id="estimatedSalary" name="estimatedSalary" 
                                   placeholder="e.g., 50.00" min="0" step="0.01" 
                                   value="<?php echo isset($_POST['estimatedSalary']) ? htmlspecialchars($_POST['estimatedSalary']) : ''; ?>" required>
                            <i class="fas fa-dollar-sign input-icon"></i>
                        </div>
                        <div class="form-group">
                            <label for="estimatedHours">Estimated Work Hours *</label>
                            <input type="number" class="form-control" id="estimatedHours" name="estimatedHours" 
                                   placeholder="e.g., 3" min="0" 
                                   value="<?php echo isset($_POST['estimatedHours']) ? htmlspecialchars($_POST['estimatedHours']) : ''; ?>" required>
                            <i class="fas fa-clock input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="workAddress">Work Address *</label>
                        <input type="text" class="form-control" id="workAddress" name="workAddress" 
                               placeholder="Full address where service will be performed" 
                               value="<?php echo isset($_POST['workAddress']) ? htmlspecialchars($_POST['workAddress']) : ''; ?>" required>
                        <i class="fas fa-map-marker-alt input-icon"></i>
                    </div>
                    
                    <div class="post-job-btn-container">
                        <button type="submit" class="post-job-btn">
                            <i class="fas fa-paper-plane"></i> Post Job
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Active Job Postings Section -->
            <div class="posted-jobs-section">
                <h3 class="section-title">
                    <i class="fas fa-list"></i> Your Active Jobs
                </h3>
                
                <div class="jobs-list">
                    <?php if (count($active_jobs) > 0): ?>
                        <?php foreach ($active_jobs as $job): ?>
                            <div class="job-card" data-job-id="<?php echo $job['job_id']; ?>">
                                <div class="job-header">
                                    <div class="job-title"><?php echo htmlspecialchars($job['title']); ?></div>
                                    <div class="job-meta">
                                        <span class="job-type"><?php echo htmlspecialchars($job['service_type']); ?></span>
                                        <span class="job-status status-open">Open</span>
                                    </div>
                                </div>
                                <div class="job-details">
                                    <div class="job-description">
                                        <?php echo htmlspecialchars($job['description']); ?>
                                    </div>
                                    <div class="job-info">
                                        <div class="job-info-item">
                                            <i class="fas fa-dollar-sign"></i>
                                            <span>$<?php echo number_format($job['salary'], 2); ?></span>
                                        </div>
                                        <div class="job-info-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo $job['work_hours']; ?> hours</span>
                                        </div>
                                        <div class="job-info-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($job['address']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="job-actions">
                                    <button class="job-action-btn delete-btn">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>You haven't posted any jobs yet</p>
                            <p>Get started by creating your first job posting</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form submission handling
        document.getElementById('jobForm').addEventListener('submit', function(e) {
            const jobTitle = document.getElementById('jobTitle').value;
            const serviceType = document.getElementById('serviceType').value;
            
            if(!jobTitle || !serviceType) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return;
            }
        });
        
        // Logout button
        document.querySelector('.logout-btn').addEventListener('click', function() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../auth/logout.php';
            }
        });
        
        // Job deletion functionality
        const jobsList = document.querySelector('.jobs-list');
        if (jobsList) {
            jobsList.addEventListener('click', function(e) {
                const deleteBtn = e.target.closest('.delete-btn');
                if (!deleteBtn) return;
                
                const jobCard = deleteBtn.closest('.job-card');
                const jobId = jobCard.dataset.jobId;
                const jobTitle = jobCard.querySelector('.job-title').textContent;
                
                if (confirm(`Are you sure you want to delete "${jobTitle}"? This cannot be undone.`)) {
                    // Show loading indicator
                    const originalText = deleteBtn.innerHTML;
                    deleteBtn.innerHTML = '<i class="fas fa-spinner"></i> Deleting...';
                    deleteBtn.disabled = true;
                    
                    // Send delete request
                    const formData = new FormData();
                    formData.append('job_id', jobId);
                    
                    fetch('delete_job.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove job card with animation
                            jobCard.style.opacity = '1';
                            jobCard.style.transition = 'opacity 0.3s, transform 0.3s';
                            
                            setTimeout(() => {
                                jobCard.style.opacity = '0';
                                jobCard.style.transform = 'scale(0.95)';
                                
                                setTimeout(() => {
                                    jobCard.remove();
                                    
                                    // Show empty state if no jobs left
                                    if (document.querySelectorAll('.job-card').length === 0) {
                                        jobsList.innerHTML = `
                                            <div class="empty-state">
                                                <i class="fas fa-inbox"></i>
                                                <p>You haven't posted any jobs yet</p>
                                                <p>Get started by creating your first job posting</p>
                                            </div>
                                        `;
                                    }
                                }, 300);
                            }, 100);
                        } else {
                            alert('Error deleting job: ' + data.error);
                            deleteBtn.innerHTML = originalText;
                            deleteBtn.disabled = false;
                        }
                    })
                    .catch(error => {
                        alert('Network error: ' + error.message);
                        deleteBtn.innerHTML = originalText;
                        deleteBtn.disabled = false;
                    });
                }
            });
        }
    });
    </script>
</body>
</html>