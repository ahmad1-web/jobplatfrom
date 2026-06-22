<?php
/**
 * admin/dashboard.php — Admin Dashboard
 */
require_once '../config.php';
require_login('admin');

// Fetch overview counts
$total_jobs    = $conn->query('SELECT COUNT(*) FROM jobs')->fetch_row()[0] ?? 0;
$total_users   = $conn->query('SELECT COUNT(*) FROM users WHERE role="applicant"')->fetch_row()[0] ?? 0;
$pending_apps  = $conn->query('SELECT COUNT(*) FROM applications WHERE status="pending"')->fetch_row()[0] ?? 0;
$shortlisted   = $conn->query('SELECT COUNT(*) FROM applications WHERE status="shortlisted"')->fetch_row()[0] ?? 0;
$total_apps    = $conn->query('SELECT COUNT(*) FROM applications')->fetch_row()[0] ?? 0;

// Recent applications (last 8)
$recent = $conn->query(
    'SELECT a.*, u.full_name, j.title AS job_title
     FROM applications a
     JOIN users u ON u.id = a.user_id
     JOIN jobs j  ON j.id = a.job_id
     ORDER BY a.applied_date DESC LIMIT 8'
)->fetch_all(MYSQLI_ASSOC);

// Jobs summary
$jobs_summary = $conn->query(
    'SELECT j.id, j.title, j.status, j.type, j.location,
            COUNT(a.id) AS app_count
     FROM jobs j
     LEFT JOIN applications a ON a.job_id = j.id
     GROUP BY j.id ORDER BY j.posted_date DESC LIMIT 6'
)->fetch_all(MYSQLI_ASSOC);

$active_page = 'admin_dashboard';
$page_title  = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="app-shell">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>

        <main class="page-content">
            <?= flash_message() ?>

            <div class="page-header">
                <div class="page-header-left">
                    <div class="breadcrumb"><i class="bi bi-house"></i> Admin Panel</div>
                    <h2>Dashboard Overview</h2>
                    <p>Monitor your recruitment activities at a glance.</p>
                </div>
                <a href="jobs.php?action=new" class="btn btn-accent">
                    <i class="bi bi-plus-circle"></i> Post New Job
                </a>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card card-blue">
                    <div class="stat-icon"><i class="bi bi-briefcase"></i></div>
                    <div class="stat-body">
                        <div class="label">Total Jobs Posted</div>
                        <div class="value"><?= $total_jobs ?></div>
                        <div class="sub"><a href="jobs.php">Manage jobs →</a></div>
                    </div>
                </div>
                <div class="stat-card card-gold">
                    <div class="stat-icon"><i class="bi bi-people"></i></div>
                    <div class="stat-body">
                        <div class="label">Registered Applicants</div>
                        <div class="value"><?= $total_users ?></div>
                        <div class="sub"><a href="users.php">Manage users →</a></div>
                    </div>
                </div>
                <div class="stat-card card-red">
                    <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div class="stat-body">
                        <div class="label">Pending Applications</div>
                        <div class="value"><?= $pending_apps ?></div>
                        <div class="sub"><a href="view_applications.php?status=pending">Review pending →</a></div>
                    </div>
                </div>
                <div class="stat-card card-green">
                    <div class="stat-icon"><i class="bi bi-person-check"></i></div>
                    <div class="stat-body">
                        <div class="label">Shortlisted</div>
                        <div class="value"><?= $shortlisted ?></div>
                        <div class="sub"><a href="view_applications.php?status=shortlisted">View shortlisted →</a></div>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;">
                <!-- Recent Applications -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="bi bi-clock-history"></i> Recent Applications</h3>
                        <a href="view_applications.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <?php if (empty($recent)): ?>
                    <div class="card-body">
                        <div class="empty-state"><i class="bi bi-inbox"></i><p>No applications yet.</p></div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Job</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent as $app): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600;font-size:.85rem;"><?= e($app['full_name']) ?></div>
                                    </td>
                                    <td style="font-size:.82rem;"><?= e($app['job_title']) ?></td>
                                    <td style="font-size:.78rem;color:var(--text-muted);"><?= date('d M Y', strtotime($app['applied_date'])) ?></td>
                                    <td><?= status_badge($app['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Jobs Summary -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="bi bi-briefcase"></i> Job Listings Summary</h3>
                        <a href="jobs.php" class="btn btn-sm btn-outline-primary">Manage</a>
                    </div>
                    <?php if (empty($jobs_summary)): ?>
                    <div class="card-body">
                        <div class="empty-state"><i class="bi bi-briefcase"></i><p>No jobs posted yet.</p></div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Applications</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jobs_summary as $job): ?>
                                <tr>
                                    <td style="font-weight:600;font-size:.85rem;"><?= e($job['title']) ?></td>
                                    <td style="font-size:.82rem;"><?= e($job['type']) ?></td>
                                    <td>
                                        <span style="font-weight:700;color:var(--primary);"><?= $job['app_count'] ?></span>
                                    </td>
                                    <td><?= status_badge($job['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Application Status Summary Bars -->
            <?php
            $status_counts = $conn->query(
                'SELECT status, COUNT(*) as cnt FROM applications GROUP BY status'
            )->fetch_all(MYSQLI_ASSOC);
            $sc = array_column($status_counts, 'cnt', 'status');
            $total_for_bar = max(1, $total_apps);
            ?>
            <div class="card" style="margin-top:22px;">
                <div class="card-header">
                    <h3><i class="bi bi-bar-chart"></i> Application Status Breakdown</h3>
                    <span style="font-size:.85rem;color:var(--text-muted);"><?= $total_apps ?> total applications</span>
                </div>
                <div class="card-body">
                    <?php
                    $bars = [
                        ['label'=>'Pending',     'key'=>'pending',     'color'=>'#f39c12'],
                        ['label'=>'Reviewed',    'key'=>'reviewed',    'color'=>'#2980b9'],
                        ['label'=>'Shortlisted', 'key'=>'shortlisted', 'color'=>'#27ae60'],
                        ['label'=>'Rejected',    'key'=>'rejected',    'color'=>'#e74c3c'],
                    ];
                    foreach ($bars as $bar):
                        $count = $sc[$bar['key']] ?? 0;
                        $pct   = $total_for_bar > 0 ? round(($count / $total_for_bar) * 100) : 0;
                    ?>
                    <div style="margin-bottom:14px;">
                        <div style="display:flex;justify-content:space-between;font-size:.83rem;margin-bottom:5px;">
                            <span style="font-weight:600;"><?= $bar['label'] ?></span>
                            <span style="color:var(--text-muted);"><?= $count ?> (<?= $pct ?>%)</span>
                        </div>
                        <div style="background:var(--border);border-radius:20px;height:10px;overflow:hidden;">
                            <div style="width:<?= $pct ?>%;background:<?= $bar['color'] ?>;height:100%;border-radius:20px;transition:width .8s ease;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
}
</script>
</body>
</html>
