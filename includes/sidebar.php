<?php
/**
 * includes/sidebar.php
 * Shared sidebar for all authenticated pages
 * Requires: $conn, $_SESSION, $active_page (string)
 */
$current_page = $active_page ?? '';
$user_role    = $_SESSION['user_role'] ?? 'applicant';
$user_name    = $_SESSION['user_name'] ?? 'User';
$user_initial = strtoupper(substr($user_name, 0, 1));

// Base paths differ for admin vs applicant
$base     = ($user_role === 'admin') ? '../' : '../';
$own_base = ($user_role === 'admin') ? ''    : '';

// Determine relative root
$is_admin = ($user_role === 'admin');
?>
<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <img src="<?= $base ?>logo.png" alt="Logo" onerror="this.style.display='none'">
        <div class="brand">
            IYA ABUBAKAR INSTITUTE OF ICT
            <span>Job Application System</span>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar"><?= e($user_initial) ?></div>
        <div class="user-info">
            <div class="name"><?= e($user_name) ?></div>
            <div class="role"><?= e($user_role) ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php if ($is_admin): ?>
        <div class="nav-section-label">Main</div>
        <a href="<?= $base ?>admin/dashboard.php" class="<?= $current_page==='admin_dashboard'?'active':'' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <div class="nav-section-label">Jobs</div>
        <a href="<?= $base ?>admin/jobs.php" class="<?= $current_page==='admin_jobs'?'active':'' ?>">
            <i class="bi bi-briefcase"></i> Manage Jobs
        </a>
        <a href="<?= $base ?>admin/view_applications.php" class="<?= $current_page==='admin_applications'?'active':'' ?>">
            <i class="bi bi-file-earmark-person"></i> View Applications
        </a>
        <div class="nav-section-label">Management</div>
        <a href="<?= $base ?>admin/users.php" class="<?= $current_page==='admin_users'?'active':'' ?>">
            <i class="bi bi-people"></i> User Management
        </a>
        <?php else: ?>
        <div class="nav-section-label">Main</div>
        <a href="<?= $base ?>applicant/dashboard.php" class="<?= $current_page==='app_dashboard'?'active':'' ?>">
            <i class="bi bi-house-door"></i> Dashboard
        </a>
        <div class="nav-section-label">Jobs</div>
        <a href="<?= $base ?>applicant/dashboard.php#available-jobs" class="<?= $current_page==='app_jobs'?'active':'' ?>">
            <i class="bi bi-search"></i> Browse Jobs
        </a>
        <a href="<?= $base ?>applicant/dashboard.php#my-applications" class="<?= $current_page==='app_my_apps'?'active':'' ?>">
            <i class="bi bi-clock-history"></i> My Applications
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= $base ?>logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</aside>
<!-- SIDEBAR OVERLAY (mobile) -->
<div class="overlay-sidebar" id="sidebarOverlay" onclick="closeSidebar()"></div>
