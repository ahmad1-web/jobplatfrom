<?php
/**
 * applicant/dashboard.php — Applicant Dashboard
 * Updated with notifications, shortlist messages, and next steps
 */
require_once '../config.php';
require_login('applicant');

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Mark notification as read via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notif_id = (int)$_POST['notif_id'];
    $upd = $conn->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?');
    $upd->bind_param('ii', $notif_id, $user_id);
    $upd->execute();
    $upd->close();
    echo json_encode(['ok'=>true]);
    exit;
}

// Mark all notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $upd = $conn->prepare('UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0');
    $upd->bind_param('i', $user_id);
    $upd->execute();
    $upd->close();
    echo json_encode(['ok'=>true]);
    exit;
}

// Fetch open jobs
$jobs_stmt = $conn->prepare(
    'SELECT * FROM jobs WHERE status = "open" ORDER BY posted_date DESC'
);
$jobs_stmt->execute();
$jobs = $jobs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$jobs_stmt->close();

// Fetch user's applications with job info and additional details
$apps_stmt = $conn->prepare(
    'SELECT a.*, j.title AS job_title, j.type, j.location, j.description AS job_description
     FROM applications a
     JOIN jobs j ON j.id = a.job_id
     WHERE a.user_id = ?
     ORDER BY a.applied_date DESC'
);
$apps_stmt->bind_param('i', $user_id);
$apps_stmt->execute();
$my_applications = $apps_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$apps_stmt->close();

// Get job IDs the user has already applied to
$applied_job_ids = array_column($my_applications, 'job_id');

// Fetch user notifications
$notif_stmt = $conn->prepare(
    'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20'
);
$notif_stmt->bind_param('i', $user_id);
$notif_stmt->execute();
$notifications = $notif_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$notif_stmt->close();

$unread_count = unread_notification_count($conn, $user_id);

// Stats
$stats = [
    'total_apps'   => count($my_applications),
    'pending'      => count(array_filter($my_applications, fn($a) => $a['status']==='pending')),
    'shortlisted'  => count(array_filter($my_applications, fn($a) => $a['status']==='shortlisted')),
    'open_jobs'    => count($jobs),
];

$active_page = 'app_dashboard';
$page_title  = 'My Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .notif-bell { position:relative; cursor:pointer; }
        .notif-badge {
            position:absolute; top:-6px; right:-8px;
            background:var(--danger); color:#fff;
            font-size:.65rem; font-weight:700;
            min-width:18px; height:18px;
            border-radius:10px; display:flex;
            align-items:center; justify-content:center;
            padding:0 4px;
        }
        .notif-dropdown {
            display:none; position:absolute; top:100%; right:0;
            width:380px; max-height:420px; overflow-y:auto;
            background:#fff; border:1px solid var(--border);
            border-radius:10px; box-shadow:0 8px 30px rgba(0,0,0,.15);
            z-index:1000; margin-top:8px;
        }
        .notif-dropdown.show { display:block; }
        .notif-item {
            padding:12px 16px; border-bottom:1px solid var(--border);
            font-size:.84rem; cursor:pointer; transition:background .15s;
        }
        .notif-item:last-child { border-bottom:none; }
        .notif-item:hover { background:var(--bg); }
        .notif-item.unread { background:#eef5ff; border-left:3px solid var(--primary); }
        .notif-item .notif-title { font-weight:600; margin-bottom:3px; }
        .notif-item .notif-msg { color:var(--text-muted); font-size:.78rem; line-height:1.5; }
        .notif-item .notif-time { color:#999; font-size:.72rem; margin-top:4px; }

        .next-steps-box {
            background:linear-gradient(135deg,#1a73e8,#0d47a1);
            color:#fff; border-radius:10px; padding:18px 22px;
            margin-top:14px; font-size:.88rem; line-height:1.7;
        }
        .next-steps-box h4 { color:#fff; margin-bottom:8px; display:flex; align-items:center; gap:8px; }
        .next-steps-box ul { padding-left:18px; margin:6px 0; }
        .next-steps-box li { margin-bottom:4px; }
        .next-steps-box strong { color:#ffd54f; }

        .app-expandable { cursor:pointer; }
        .app-expandable .extra-info { display:none; padding:12px 16px; background:var(--bg); border-radius:8px; margin:6px 0; font-size:.82rem; }
        .app-expandable.open .extra-info { display:block; }
    </style>
</head>
<body>
<div class="app-shell">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>

        <main class="page-content">
            <?= flash_message() ?>

            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-left">
                    <div class="breadcrumb">
                        <i class="bi bi-house"></i> Home
                        <i class="bi bi-chevron-right"></i> Dashboard
                    </div>
                    <h2>Welcome back, <?= e(explode(' ', $user_name)[0]) ?>! 👋</h2>
                    <p>Here's an overview of your job search activity.</p>
                </div>

                <!-- Notification Bell -->
                <div class="notif-bell" onclick="toggleNotifications()">
                    <i class="bi bi-bell" style="font-size:1.5rem;color:var(--text-muted);"></i>
                    <?php if ($unread_count > 0): ?>
                    <span class="notif-badge"><?= $unread_count > 9 ? '9+' : $unread_count ?></span>
                    <?php endif; ?>

                    <!-- Notifications Dropdown -->
                    <div class="notif-dropdown" id="notifDropdown">
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid var(--border);font-size:.85rem;">
                            <strong>Notifications</strong>
                            <?php if ($unread_count > 0): ?>
                            <button onclick="markAllRead()" class="btn btn-sm btn-outline-primary" style="font-size:.72rem;padding:3px 10px;">
                                <i class="bi bi-check-all"></i> Mark all read
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($notifications)): ?>
                        <div style="padding:30px 16px;text-align:center;color:var(--text-muted);font-size:.85rem;">
                            <i class="bi bi-bell-slash" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                            No notifications yet. We'll notify you when your application status changes.
                        </div>
                        <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                        <div class="notif-item <?= $notif['is_read'] ? '' : 'unread' ?>"
                             onclick="markRead(<?= $notif['id'] ?>, this)"
                             data-id="<?= $notif['id'] ?>">
                            <div class="notif-title"><?= e($notif['title']) ?></div>
                            <div class="notif-msg"><?= e($notif['message']) ?></div>
                            <div class="notif-time"><?= fmt_date($notif['created_at']) ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card card-blue">
                    <div class="stat-icon"><i class="bi bi-briefcase"></i></div>
                    <div class="stat-body">
                        <div class="label">Open Jobs</div>
                        <div class="value"><?= $stats['open_jobs'] ?></div>
                        <div class="sub">Available positions</div>
                    </div>
                </div>
                <div class="stat-card card-gold">
                    <div class="stat-icon"><i class="bi bi-send"></i></div>
                    <div class="stat-body">
                        <div class="label">Total Applications</div>
                        <div class="value"><?= $stats['total_apps'] ?></div>
                        <div class="sub">Jobs applied for</div>
                    </div>
                </div>
                <div class="stat-card card-green">
                    <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                    <div class="stat-body">
                        <div class="label">Shortlisted</div>
                        <div class="value"><?= $stats['shortlisted'] ?></div>
                        <div class="sub">Successful reviews</div>
                    </div>
                </div>
                <div class="stat-card card-red">
                    <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div class="stat-body">
                        <div class="label">Pending Review</div>
                        <div class="value"><?= $stats['pending'] ?></div>
                        <div class="sub">Awaiting response</div>
                    </div>
                </div>
            </div>

            <!-- Available Jobs -->
            <div id="available-jobs" style="margin-bottom:32px;">
                <div class="section-title" style="font-size:1.15rem;">
                    <i class="bi bi-briefcase-fill"></i> Available Job Openings
                </div>

                <?php if (empty($jobs)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="bi bi-briefcase"></i>
                            <p>No open positions at the moment. Check back later!</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="jobs-grid">
                    <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <div>
                            <div class="job-card-title"><?= e($job['title']) ?></div>
                            <div class="job-meta" style="margin-top:8px;">
                                <div class="job-meta-item"><i class="bi bi-geo-alt"></i> <?= e($job['location']) ?></div>
                                <div class="job-meta-item"><i class="bi bi-clock"></i> <?= e($job['type']) ?></div>
                                <div class="job-meta-item"><i class="bi bi-calendar3"></i> <?= date('d M Y', strtotime($job['posted_date'])) ?></div>
                            </div>
                        </div>
                        <div class="job-description">
                            <?= nl2br(e(substr($job['description'], 0, 200))) ?>…
                        </div>
                        <div class="job-card-footer">
                            <?= status_badge($job['status']) ?>
                            <?php if (in_array($job['id'], $applied_job_ids)): ?>
                                <span class="btn btn-sm btn-success" style="pointer-events:none;">
                                    <i class="bi bi-check"></i> Applied
                                </span>
                            <?php else: ?>
                                <a href="apply.php?job_id=<?= $job['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-send"></i> Apply Now
                                </a>
                            <?php endif; ?>
                        </div>
                        <details style="margin-top:4px;">
                            <summary style="font-size:.8rem;cursor:pointer;color:var(--primary);font-weight:600;">
                                <i class="bi bi-list-check"></i> View Requirements
                            </summary>
                            <div style="margin-top:10px;font-size:.82rem;color:var(--text-muted);line-height:1.6;">
                                <?= nl2br(e($job['requirements'])) ?>
                            </div>
                        </details>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- My Applications -->
            <div id="my-applications">
                <div class="section-title" style="font-size:1.15rem;">
                    <i class="bi bi-clock-history"></i> My Applications
                </div>
                <div class="card">
                    <?php if (empty($my_applications)): ?>
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="bi bi-file-earmark-x"></i>
                            <p>You haven't applied for any jobs yet.<br>Browse the openings above and submit your first application!</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Job Title</th>
                                    <th>Location</th>
                                    <th>Type</th>
                                    <th>Applied Date</th>
                                    <th>Status</th>
                                    <th>Next Steps</th>
                                    <th>CV</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_applications as $i => $app): ?>
                                <tr class="app-expandable" onclick="toggleExpand(this)">
                                    <td><?= $i + 1 ?></td>
                                    <td><strong><?= e($app['job_title']) ?></strong></td>
                                    <td><?= e($app['location']) ?></td>
                                    <td><?= e($app['type']) ?></td>
                                    <td><?= fmt_date($app['applied_date']) ?></td>
                                    <td><?= status_badge($app['status']) ?></td>
                                    <td style="font-size:.78rem;">
                                        <?php if ($app['status'] === 'shortlisted'): ?>
                                            <span class="badge badge-shortlisted" style="font-size:.75rem;padding:4px 10px;">
                                                <i class="bi bi-envelope-paper"></i> View Message
                                            </span>
                                            <?php if ($app['shortlist_message']): ?>
                                            <div style="margin-top:6px;max-width:250px;color:var(--text-muted);line-height:1.5;font-size:.78rem;background:#ecf9f1;padding:8px 10px;border-radius:6px;border:1px solid #b7e4c7;">
                                                <strong style="color:var(--success);">📋 Next Steps:</strong><br>
                                                <?= nl2br(e($app['shortlist_message'])) ?>
                                            </div>
                                            <?php else: ?>
                                            <div style="margin-top:6px;max-width:250px;color:var(--text-muted);line-height:1.5;font-size:.78rem;background:#ecf9f1;padding:8px 10px;border-radius:6px;border:1px solid #b7e4c7;">
                                                <strong style="color:var(--success);">🎉 Congratulations!</strong><br>
                                                You have been shortlisted. The admin will contact you with further details. Please monitor your email and this dashboard for updates.
                                            </div>
                                            <?php endif; ?>
                                        <?php elseif ($app['status'] === 'reviewed'): ?>
                                            <span style="color:var(--info);font-weight:600;">
                                                <i class="bi bi-check2-circle"></i> Application Reviewed
                                            </span>
                                            <div style="margin-top:6px;max-width:250px;color:var(--text-muted);line-height:1.5;font-size:.78rem;background:#cfe2ff;padding:8px 10px;border-radius:6px;border:1px solid #9ec5fe;">
                                                <strong style="color:#084298;">📋 Reviewed</strong><br>
                                                Your application has been reviewed by the hiring team and is currently under consideration. You will be notified of any updates.
                                            </div>
                                        <?php elseif ($app['status'] === 'rejected'): ?>
                                            <span class="badge badge-rejected" style="font-size:.75rem;padding:4px 10px;">
                                                <i class="bi bi-x-circle"></i> Not Selected
                                            </span>
                                            <?php if ($app['rejection_message']): ?>
                                            <div style="margin-top:6px;max-width:250px;color:var(--text-muted);line-height:1.5;font-size:.78rem;background:#f8d7da;padding:8px 10px;border-radius:6px;border:1px solid #f1aeb5;">
                                                <strong style="color:#842029;">❌ Application Update</strong><br>
                                                <?= nl2br(e($app['rejection_message'])) ?>
                                            </div>
                                            <?php else: ?>
                                            <div style="margin-top:6px;max-width:250px;color:var(--text-muted);line-height:1.5;font-size:.78rem;background:#f8d7da;padding:8px 10px;border-radius:6px;border:1px solid #f1aeb5;">
                                                <strong style="color:#842029;">❌ Application Update</strong><br>
                                                Thank you for your interest. Unfortunately, you have not been selected to proceed at this time. We encourage you to apply for future openings that match your qualifications.
                                            </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted);">
                                                <i class="bi bi-hourglass-split"></i> Awaiting review by the hiring team.
                                            </span>
                                            <div style="margin-top:6px;max-width:250px;color:var(--text-muted);line-height:1.5;font-size:.78rem;background:#fef3cd;padding:8px 10px;border-radius:6px;border:1px solid #ffecb5;">
                                                <strong style="color:#856404;">⏳ Pending Review</strong><br>
                                                Your application is pending review. We will notify you once it has been reviewed by the hiring team.
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="../<?= e($app['cv_path']) ?>" target="_blank"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <tr style="display:none;" class="extra-info-row-<?= $i ?>">
                                    <td colspan="8" style="padding:0;">
                                        <div style="padding:14px 22px;background:var(--bg);border-top:1px solid var(--border);">
                                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;font-size:.82rem;">
                                                <div>
                                                    <strong style="color:var(--text-muted);display:block;font-size:.75rem;">QUALIFICATION</strong>
                                                    <?= e($app['qualification'] ?? 'N/A') ?>
                                                </div>
                                                <div>
                                                    <strong style="color:var(--text-muted);display:block;font-size:.75rem;">EXPERIENCE</strong>
                                                    <?= $app['experience_years'] ? e($app['experience_years']) . ' years' : 'N/A' ?>
                                                </div>
                                                <div>
                                                    <strong style="color:var(--text-muted);display:block;font-size:.75rem;">ADDRESS</strong>
                                                    <?= e($app['address'] ?? 'N/A') ?>
                                                </div>
                                            </div>
                                            <?php if ($app['cover_letter']): ?>
                                            <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--border);">
                                                <strong style="color:var(--text-muted);display:block;font-size:.75rem;margin-bottom:4px;">COVER LETTER</strong>
                                                <div style="font-size:.8rem;color:var(--text-muted);line-height:1.6;white-space:pre-wrap;max-height:120px;overflow-y:auto;">
                                                    <?= e($app['cover_letter']) ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            <?php if ($app['admin_notes'] && $app['status'] === 'rejected'): ?>
                                            <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--border);">
                                                <strong style="color:var(--danger);display:block;font-size:.75rem;margin-bottom:4px;">ADMIN FEEDBACK</strong>
                                                <div style="font-size:.8rem;color:var(--text-muted);line-height:1.6;">
                                                    <?= nl2br(e($app['admin_notes'])) ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Shortlisted Next Steps Summary -->
            <?php $shortlisted_apps = array_filter($my_applications, fn($a) => $a['status'] === 'shortlisted'); ?>
            <?php if (!empty($shortlisted_apps)): ?>
            <div class="card" style="margin-top:22px;border:2px solid var(--success);">
                <div class="card-header" style="background:#ecf9f1;">
                    <h3 style="color:var(--success);"><i class="bi bi-star-fill"></i> You've Been Shortlisted!</h3>
                </div>
                <div class="card-body">
                    <p style="font-size:.92rem;margin-bottom:14px;">
                        Congratulations! You have been shortlisted for <strong><?= count($shortlisted_apps) ?> position(s)</strong>.
                        Here's what you need to do next:
                    </p>
                    <div class="next-steps-box">
                        <h4><i class="bi bi-clipboard-check"></i> Next Steps for Shortlisted Applicants</h4>
                        <ol style="margin:0;padding-left:20px;">
                            <li><strong>Check your email regularly</strong> — The admin will contact you via your registered email (<strong><?= e($_SESSION['user_email']) ?></strong>).</li>
                            <li><strong>Prepare your documents</strong> — Have your original certificates, credentials, and identification ready for verification.</li>
                            <li><strong>Monitor this dashboard</strong> — Additional updates and instructions will appear here.</li>
                            <li><strong>Respond promptly</strong> — Reply to any communication from the admin within 48 hours.</li>
                            <li><strong>Prepare for interview</strong> — Research the organization and practice common interview questions.</li>
                        </ol>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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

// Toggle expandable row
function toggleExpand(row) {
    // Find the next sibling row (hidden extra info row)
    const nextRow = row.nextElementSibling;
    if (nextRow && nextRow.style.display === 'none') {
        nextRow.style.display = 'table-row';
        row.style.background = 'var(--bg)';
    } else if (nextRow) {
        nextRow.style.display = 'none';
        row.style.background = '';
    }
}

// Toggle notifications dropdown
function toggleNotifications() {
    const dropdown = document.getElementById('notifDropdown');
    dropdown.classList.toggle('show');
}

// Close notifications on click outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('notifDropdown');
    const bell = document.querySelector('.notif-bell');
    if (dropdown.classList.contains('show') && !bell.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

// Mark single notification as read
function markRead(notifId, element) {
    const fd = new FormData();
    fd.append('mark_read', '1');
    fd.append('notif_id', notifId);

    fetch('dashboard.php', { method:'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                element.classList.remove('unread');
                updateNotifBadge();
            }
        })
        .catch(() => {});
}

// Mark all notifications as read
function markAllRead() {
    const fd = new FormData();
    fd.append('mark_all_read', '1');

    fetch('dashboard.php', { method:'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
                updateNotifBadge();
            }
        })
        .catch(() => {});
}

// Update notification badge count
function updateNotifBadge() {
    const unreadCount = document.querySelectorAll('.notif-item.unread').length;
    const badge = document.querySelector('.notif-badge');
    if (badge) {
        if (unreadCount > 0) {
            badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
        } else {
            badge.remove();
        }
    }
}
</script>
</body>
</html>