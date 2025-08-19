<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Include necessary functions
require_once('dashboard_functions.php');

// Get user data if function exists
if (function_exists('getUserData') && isset($_SESSION['user_id'])) {
    $userData = getUserData($_SESSION['user_id']);
} else {
    $userData = ['name' => 'Guest', 'profile_pic' => 'default.jpg'];
}
?>

<nav class="sidebar">
    <div class="logo">
        <h2><i class="fas fa-bolt"></i> QuickHire</h2>
    </div>
    <ul class="nav-links">
        <li><a href="index.php" <?= ($current_page == 'index.php') ? 'class="active"' : '' ?>><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="create_job.php" <?= ($current_page == 'create_job.php') ? 'class="active"' : '' ?>><i class="fas fa-plus-circle"></i> Create Job</a></li>
        <li><a href="counter_proposals.php" <?= ($current_page == 'counter_proposals.php') ? 'class="active"' : '' ?>><i class="fas fa-exchange-alt"></i> Counter Proposals</a></li>
        <li><a href="finalized_jobs.php"<?= ($current_page == 'finalized_jobs.php') ? 'class="active"' : '' ?>><i class="fas fa-check-circle"></i> Finalized Jobs</a></li>
    </ul>
    <button class="logout-btn" onclick="logout()">
        <i class="fas fa-sign-out-alt"></i> Logout
    </button>
</nav>

<script>
function logout() {
    if (confirm("Are you sure you want to logout?")) {
        window.location.href = "../auth/logout.php";
    }
}
</script>