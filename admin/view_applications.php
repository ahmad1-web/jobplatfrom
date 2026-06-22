<?php
/**
 * admin/view_applications.php — View & Manage Applications
 * Updated with full applicant details, admin notes, shortlist message, and notifications
 */
require_once '../config.php';
require_login('admin');

$admin_id = $_SESSION['user_id'];

/* ── AJAX: Update application status ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $app_id     = (int)$_POST['app_id'];
    $new_status = trim($_POST['status']);
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    $shortlist_message = trim($_POST['shortlist_message'] ?? '');
    $allowed    = ['pending','reviewed','shortlisted','rejected'];

    if ($app_id > 0 && in_array($new_status, $allowed)) {
        // Update status with optional notes
        $sql = 'UPDATE applications SET status=?, reviewed_date=NOW()';
        $params = [$new_status];
        $types  = 's';

        if ($admin_notes !== '') {
            $sql .= ', admin_notes=?';
            $params[] = $admin_notes;
            $types .= 's';
        }
        if ($shortlist_message !== '' && $new_status === 'shortlisted') {
            $sql .= ', shortlist_message=?';
            $params[] = $shortlist_message;
            $types .= 's';
        }
        $rejection_message = trim($_POST['rejection_message'] ?? '');
        if ($rejection_message !== '' && $new_status === 'rejected') {
            $sql .= ', rejection_message=?';
            $params[] = $rejection_message;
            $types .= 's';
        }

        $sql .= ' WHERE id=?';
        $params[] = $app_id;
        $types .= 'i';

        $upd = $conn->prepare($sql);
        $upd->bind_param($types, ...$params);
        $upd->execute();
        $upd->close();

        // Get the user_id for this application to create notification
        $user_q = $conn->prepare('SELECT a.user_id, u.full_name, j.title FROM applications a JOIN users u ON u.id=a.user_id JOIN jobs j ON j.id=a.job_id WHERE a.id=?');
        $user_q->bind_param('i', $app_id);
        $user_q->execute();
        $app_info = $user_q->get_result()->fetch_assoc();
        $user_q->close();

        if ($app_info) {
            $user_id = $app_info['user_id'];
            $job_title = $app_info['title'];

            if ($new_status === 'shortlisted') {
                $msg = $shortlist_message ?: "Congratulations! You have been shortlisted for the {$job_title} position. We will contact you with further details.";
                create_notification($conn, $user_id, $app_id, '🎉 Congratulations - Shortlisted!', $msg, 'shortlisted');
                log_activity($conn, $admin_id, 'Shortlisted applicant', "Application #{$app_id} for {$job_title}");
            } elseif ($new_status === 'reviewed') {
                create_notification($conn, $user_id, $app_id, '📋 Application Reviewed', "Your application for {$job_title} has been reviewed. Status: Reviewed.", 'reviewed');
                log_activity($conn, $admin_id, 'Reviewed application', "Application #{$app_id} for {$job_title}");
            } elseif ($new_status === 'rejected') {
                $msg = $rejection_message ?: "Thank you for applying for the {$job_title} position. Unfortunately, you have not been selected to proceed at this time.";
                create_notification($conn, $user_id, $app_id, '❌ Application Update', $msg, 'rejected');
                log_activity($conn, $admin_id, 'Rejected applicant', "Application #{$app_id} for {$job_title}");
            }
        }

        echo json_encode(['ok'=>true]);
    } else {
        echo json_encode(['ok'=>false]);
    }
    exit;
}

// Filters from GET
$filter_job    = (int)($_GET['job_id'] ?? 0);
$filter_status = trim($_GET['status'] ?? '');
$search_name   = trim($_GET['search'] ?? '');

// Build query
$where  = [];
$params = [];
$types  = '';

if ($filter_job > 0)        { $where[] = 'a.job_id = ?';      $params[] = $filter_job;    $types .= 'i'; }
if ($filter_status !== '')  { $where[] = 'a.status = ?';      $params[] = $filter_status; $types .= 's'; }
if ($search_name !== '')    { $where[] = 'u.full_name LIKE ?'; $params[] = "%{$search_name}%"; $types .= 's'; }

$sql = 'SELECT a.*, u.full_name, u.email, u.phone, j.title AS job_title, j.location, j.type
        FROM applications a
        JOIN users u ON u.id = a.user_id
        JOIN jobs j  ON j.id = a.job_id';

if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY a.applied_date DESC';

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Jobs list for filter dropdown
$all_jobs = $conn->query('SELECT id, title FROM jobs ORDER BY title')->fetch_all(MYSQLI_ASSOC);

// Print mode - render clean printable view
$is_print = (int)($_GET['print'] ?? 0);

if ($is_print) {
    $print_title = 'Applications Report';
    if ($filter_status !== '') {
        $print_title = ucfirst($filter_status) . ' Applications Report';
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $print_title ?> — <?= SITE_NAME ?></title>
        <link rel="stylesheet" href="../style.css">
        <style>
            body { background: #fff; padding: 30px; font-family: 'Plus Jakarta Sans', sans-serif; }
            .print-header { text-align: center; margin-bottom: 30px; border-bottom: 3px double var(--primary); padding-bottom: 15px; }
            .print-header h1 { font-size: 1.6rem; color: var(--primary); margin-bottom: 5px; }
            .print-header p { color: var(--text-muted); font-size: .85rem; }
            .print-header .report-title { font-size: 1.1rem; color: var(--accent); font-weight: 700; margin-top: 8px; }
            table { width: 100%; border-collapse: collapse; font-size: .82rem; }
            thead th { background: var(--primary); color: #fff; padding: 10px 12px; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; text-align: left; }
            tbody td { padding: 8px 12px; border-bottom: 1px solid var(--border); }
            tbody tr:nth-child(even) { background: #f8fafd; }
            .status-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: .68rem; font-weight: 700; text-transform: uppercase; }
            .badge-pending { background: #fef3cd; color: #856404; }
            .badge-reviewed { background: #cfe2ff; color: #084298; }
            .badge-shortlisted { background: #d1e7dd; color: #0a3622; }
            .badge-rejected { background: #f8d7da; color: #842029; }
            .print-footer { text-align: center; margin-top: 30px; font-size: .78rem; color: var(--text-muted); border-top: 1px solid var(--border); padding-top: 15px; }
            .no-print { display: none !important; }
            .sidebar, .topbar, .main-content, .app-shell { margin: 0; padding: 0; }
            @media print { body { padding: 10px; } }
        </style>
    </head>
    <body>
        <div class="print-header">
            <h1>IYA ABUBAKAR INSTITUTE OF ICT</h1>
            <p>ABU Zaria, Kaduna State — Online Job Application System</p>
            <div class="report-title"><?= $print_title ?></div>
            <p style="margin-top:5px;">Generated: <?= date('d M Y, g:i A') ?> | Total: <?= count($applications) ?> application<?= count($applications)!=1?'s':'' ?></p>
        </div>

        <?php if (empty($applications)): ?>
        <p style="text-align:center;color:var(--text-muted);padding:40px 0;">No applications found.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Applicant</th>
                    <th>Email / Phone</th>
                    <th>Job Title</th>
                    <th>Location</th>
                    <th>Applied Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $i => $app): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= e($app['full_name']) ?></strong></td>
                    <td>
                        <?= e($app['email']) ?>
                        <?php if ($app['phone']): ?><br><?= e($app['phone']) ?><?php endif; ?>
                    </td>
                    <td><?= e($app['job_title']) ?></td>
                    <td><?= e($app['location']) ?></td>
                    <td><?= date('d M Y', strtotime($app['applied_date'])) ?></td>
                    <td><?= status_badge($app['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <div class="print-footer">
            <p>IYA ABUBAKAR INSTITUTE OF ICT — ABU Zaria, Kaduna State, Nigeria</p>
            <p>Tel: +234 800 000 0000 | Email: info@iabuict.edu.ng | Website: www.iabuict.edu.ng</p>
        </div>

        <script>
            window.onload = function() { window.print(); };
        </script>
    </body>
    </html>
    <?php
    exit;
}

$active_page = 'admin_applications';
$page_title  = 'Applications';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .app-detail-modal .info-row { display:flex; gap:8px; align-items:flex-start; margin-bottom:10px; font-size:.88rem; }
        .app-detail-modal .info-label { font-weight:700; min-width:120px; color:var(--text-muted); flex-shrink:0; }
        .app-detail-modal .cover-letter-box { background:var(--bg); border:1px solid var(--border); border-radius:8px; padding:14px; font-size:.86rem; line-height:1.7; white-space:pre-wrap; max-height:250px; overflow-y:auto; margin-top:8px; }
        .app-detail-modal .section-divider { border:none; border-top:1px solid var(--border); margin:16px 0; }
        .notes-textarea { width:100%; min-height:80px; font-size:.85rem; padding:10px; border:1px solid var(--border); border-radius:6px; resize:vertical; font-family:inherit; }
        .notes-textarea:focus { outline:2px solid var(--primary); outline-offset:-1px; }
        .status-update-form { background:var(--bg); border-radius:8px; padding:14px; margin-top:10px; border:1px solid var(--border); }
        .status-update-form label { font-weight:600; font-size:.82rem; margin-bottom:4px; display:block; }
    </style>
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
                    <div class="breadcrumb"><i class="bi bi-house"></i> Admin <i class="bi bi-chevron-right"></i> Applications</div>
                    <h2>All Applications</h2>
                    <p><?= count($applications) ?> application<?= count($applications)!=1?'s':'' ?> found.</p>
                </div>
                <a href="view_applications.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-counterclockwise"></i> Clear Filters
                </a>
            </div>

            <!-- Filters -->
            <div class="card" style="margin-bottom:18px;">
                <div class="card-body" style="padding:16px 22px;">
                    <form method="GET" action="view_applications.php" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
                        <div>
                            <label class="form-label" style="font-size:.78rem;">Search Applicant</label>
                            <div class="input-icon-wrap" style="width:220px;">
                                <i class="icon bi bi-search"></i>
                                <input type="text" name="search" class="form-control"
                                       placeholder="Name..." value="<?= e($search_name) ?>">
                            </div>
                        </div>
                        <div>
                            <label class="form-label" style="font-size:.78rem;">Filter by Job</label>
                            <select name="job_id" class="form-select" style="width:200px;">
                                <option value="">All Jobs</option>
                                <?php foreach ($all_jobs as $j): ?>
                                <option value="<?= $j['id'] ?>" <?= $filter_job===$j['id']?'selected':'' ?>><?= e($j['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size:.78rem;">Filter by Status</label>
                            <select name="status" class="form-select" style="width:160px;">
                                <option value="">All Statuses</option>
                                <?php foreach (['pending','reviewed','shortlisted','rejected'] as $s): ?>
                                <option value="<?= $s ?>" <?= $filter_status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary" style="margin-top:22px;">
                                <i class="bi bi-funnel"></i> Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Print Buttons -->
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
                <button class="btn btn-success no-print" onclick="printTable('shortlisted')">
                    <i class="bi bi-printer"></i> Print Shortlisted
                </button>
                <button class="btn btn-info no-print" onclick="printTable('all')">
                    <i class="bi bi-printer-fill"></i> Print Current View
                </button>
                <?php if ($filter_status !== ''): ?>
                <button class="btn btn-warning no-print" onclick="printTable('<?= e($filter_status) ?>')">
                    <i class="bi bi-printer"></i> Print <?= ucfirst(e($filter_status)) ?>
                </button>
                <?php endif; ?>
            </div>

            <!-- Applications Table -->
            <div class="card">
                <?php if (empty($applications)): ?>
                <div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>No applications found matching your filters.</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Applicant</th>
                                <th>Job Title</th>
                                <th>Location / Type</th>
                                <th>Applied Date</th>
                                <th>Status</th>
                                <th style="text-align:center;">Update Status</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $i => $app): ?>
                            <tr id="row-<?= $app['id'] ?>">
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <div style="font-weight:700;font-size:.87rem;"><?= e($app['full_name']) ?></div>
                                    <div style="font-size:.75rem;color:var(--text-muted);"><?= e($app['email']) ?></div>
                                    <?php if ($app['phone']): ?>
                                    <div style="font-size:.73rem;color:var(--text-muted);"><i class="bi bi-telephone"></i> <?= e($app['phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight:600;font-size:.87rem;"><?= e($app['job_title']) ?></td>
                                <td style="font-size:.8rem;color:var(--text-muted);">
                                    <div><i class="bi bi-geo-alt"></i> <?= e($app['location']) ?></div>
                                    <div><i class="bi bi-clock"></i> <?= e($app['type']) ?></div>
                                </td>
                                <td style="font-size:.78rem;color:var(--text-muted);"><?= fmt_date($app['applied_date']) ?></td>
                                <td id="badge-<?= $app['id'] ?>"><?= status_badge($app['status']) ?></td>
                                <td style="text-align:center;">
                                    <select class="status-select" data-app-id="<?= $app['id'] ?>"
                                            onchange="openStatusModal(this)">
                                        <?php foreach (['pending','reviewed','shortlisted','rejected'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $app['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td style="text-align:center;">
                                    <div style="display:flex;gap:5px;justify-content:center;flex-wrap:wrap;">
                                        <!-- View Details -->
                                        <button class="btn btn-sm btn-info"
                                                onclick='showDetail(<?= json_encode([
                                                    "id"    => $app['id'],
                                                    "name"  => $app['full_name'],
                                                    "email" => $app['email'],
                                                    "phone" => $app['phone'] ?? '',
                                                    "job"   => $app['job_title'],
                                                    "date"  => fmt_date($app['applied_date']),
                                                    "cover" => $app['cover_letter'] ?? '',
                                                    "cv"    => '../' . $app['cv_path'],
                                                    "status"=> $app['status'],
                                                    "qualification" => $app['qualification'] ?? '',
                                                    "experience_years" => $app['experience_years'] ?? '',
                                                    "address" => $app['address'] ?? '',
                                                    "admin_notes" => $app['admin_notes'] ?? '',
                                                    "shortlist_message" => $app['shortlist_message'] ?? '',
                                                    "reviewed_date" => $app['reviewed_date'] ?? '',
                                                ]) ?>)'
                                                title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <!-- View CV -->
                                        <a href="../<?= e($app['cv_path']) ?>" target="_blank"
                                           class="btn btn-sm btn-success" title="View CV">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </a>
                                        <!-- Download CV -->
                                        <a href="../<?= e($app['cv_path']) ?>" download
                                           class="btn btn-sm btn-primary" title="Download CV">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Application Detail Modal -->
<div class="modal-overlay" id="detailModal">
    <div class="modal-box app-detail-modal" style="max-width:750px;">
        <div class="modal-header">
            <h4><i class="bi bi-person-badge"></i> Application Detail</h4>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody"></div>
        <div class="modal-footer">
            <button class="btn btn-outline-primary" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal-overlay" id="statusModal">
    <div class="modal-box" style="max-width:500px;">
        <div class="modal-header">
            <h4><i class="bi bi-arrow-repeat"></i> Update Application Status</h4>
            <button class="modal-close" onclick="closeStatusModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="statusForm">
                <input type="hidden" name="app_id" id="statusAppId">
                <input type="hidden" name="status" id="statusValue">
                <input type="hidden" name="update_status" value="1">

                <div style="margin-bottom:14px;">
                    <div style="font-weight:700;font-size:1rem;" id="statusLabel"></div>
                    <p style="font-size:.85rem;color:var(--text-muted);margin-top:4px;" id="statusDesc"></p>
                </div>

                <div class="form-group" id="adminNotesGroup">
                    <label class="form-label">
                        <i class="bi bi-sticky"></i> Admin Notes
                        <small class="text-muted">(internal note for your reference)</small>
                    </label>
                    <textarea name="admin_notes" class="notes-textarea" id="adminNotesInput"
                              placeholder="Add any internal notes about this application..."></textarea>
                </div>

                <div class="form-group" id="shortlistMsgGroup" style="display:none;">
                    <label class="form-label">
                        <i class="bi bi-envelope-paper"></i> Shortlist Message to Applicant
                        <small class="text-muted">(this will be sent as a notification)</small>
                    </label>
                    <textarea name="shortlist_message" class="notes-textarea" id="shortlistMsgInput"
                              placeholder="e.g. Congratulations! You have been shortlisted. Please check your dashboard for next steps."></textarea>
                </div>

                <div class="form-group" id="rejectionMsgGroup" style="display:none;">
                    <label class="form-label">
                        <i class="bi bi-x-circle"></i> Rejection Message to Applicant
                        <small class="text-muted">(this will be sent as a notification to the applicant)</small>
                    </label>
                    <textarea name="rejection_message" class="notes-textarea" id="rejectionMsgInput"
                              placeholder="e.g. Thank you for your interest. Unfortunately, you have not been selected for this position."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer" style="display:flex;gap:10px;justify-content:flex-end;">
            <button class="btn btn-outline-primary" onclick="closeStatusModal()">Cancel</button>
            <button class="btn btn-primary" onclick="submitStatusUpdate()">
                <i class="bi bi-check-lg"></i> Update Status
            </button>
        </div>
    </div>
</div>

<script>
let pendingAppId = null;
let pendingStatus = null;
let originalStatus = {};

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
}

// Open status update modal
function openStatusModal(select) {
    const appId  = select.dataset.appId;
    const status = select.value;

    // Store current selection for each app
    document.querySelectorAll('.status-select').forEach(s => {
        if (!originalStatus[s.dataset.appId]) {
            originalStatus[s.dataset.appId] = s.value;
        }
    });

    pendingAppId = appId;
    pendingStatus = status;

    document.getElementById('statusAppId').value = appId;
    document.getElementById('statusValue').value = status;

    const labels = {
        pending:     ['Set as Pending', 'Mark this application as pending review.'],
        reviewed:    ['Mark as Reviewed', 'Mark this application as reviewed. The applicant will be notified.'],
        shortlisted: ['Shortlist Applicant', 'Shortlist this applicant. A congratulatory message will be sent.'],
        rejected:    ['Reject Application', 'Reject this application. A notification will be sent to the applicant.']
    };

    const [label, desc] = labels[status] || ['Update Status', ''];
    document.getElementById('statusLabel').textContent = label;
    document.getElementById('statusDesc').textContent = desc;

    // Show/hide shortlist message field
    document.getElementById('shortlistMsgGroup').style.display = status === 'shortlisted' ? 'block' : 'none';
    
    // Show/hide rejection message field
    document.getElementById('rejectionMsgGroup').style.display = status === 'rejected' ? 'block' : 'none';

    // Pre-populate admin notes if available
    document.getElementById('adminNotesInput').value = '';
    document.getElementById('rejectionMsgInput').value = '';

    document.getElementById('statusModal').classList.add('show');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.remove('show');
    // Reset select to original value if cancelled
    if (pendingAppId && originalStatus[pendingAppId]) {
        const sel = document.querySelector(`.status-select[data-app-id="${pendingAppId}"]`);
        if (sel) sel.value = originalStatus[pendingAppId];
    }
    pendingAppId = null;
    pendingStatus = null;
}

// Submit status update via AJAX
function submitStatusUpdate() {
    if (!pendingAppId || !pendingStatus) return;

    const fd = new FormData(document.getElementById('statusForm'));
    fd.append('app_id', pendingAppId);
    fd.append('status', pendingStatus);
    fd.append('admin_notes', document.getElementById('adminNotesInput').value);
    fd.append('shortlist_message', document.getElementById('shortlistMsgInput').value);
    fd.append('rejection_message', document.getElementById('rejectionMsgInput').value);

    fetch('view_applications.php', { method:'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                const badge = document.getElementById('badge-' + pendingAppId);
                const map = {
                    pending:     ['badge-pending',     'Pending'],
                    reviewed:    ['badge-reviewed',    'Reviewed'],
                    shortlisted: ['badge-shortlisted', 'Shortlisted'],
                    rejected:    ['badge-rejected',    'Rejected'],
                };
                const [cls, label] = map[pendingStatus];
                badge.innerHTML = `<span class="status-badge ${cls}">${label}</span>`;
                // Flash row
                const row = document.getElementById('row-' + pendingAppId);
                row.style.background = '#d1e7dd';
                setTimeout(() => row.style.background = '', 1200);

                // Update original status
                originalStatus[pendingAppId] = pendingStatus;

                closeStatusModal();

                // Show success flash
                const flash = document.createElement('div');
                flash.className = 'alert alert-success';
                flash.innerHTML = '<span class="alert-icon">✓</span> Status updated successfully!';
                flash.style.position = 'fixed';
                flash.style.top = '20px';
                flash.style.right = '20px';
                flash.style.zIndex = '9999';
                flash.style.maxWidth = '400px';
                document.body.appendChild(flash);
                setTimeout(() => flash.remove(), 3000);
            } else {
                alert('Failed to update status. Please try again.');
            }
        })
        .catch(() => alert('Network error. Please try again.'));
}

// Show detail modal with full applicant info
function showDetail(data) {
    const cover = data.cover
        ? `<div class="cover-letter-box">${escHtml(data.cover)}</div>`
        : `<em class="text-muted">No cover letter provided.</em>`;

    const qualification = data.qualification ? escHtml(data.qualification) : '<em class="text-muted">N/A</em>';
    const experience = data.experience_years ? escHtml(data.experience_years) + ' years' : '<em class="text-muted">N/A</em>';
    const address = data.address ? escHtml(data.address) : '<em class="text-muted">N/A</em>';
    const adminNotes = data.admin_notes
        ? `<div class="cover-letter-box" style="max-height:120px;">${escHtml(data.admin_notes)}</div>`
        : '<em class="text-muted">No admin notes.</em>';
    const shortlistMsg = data.shortlist_message
        ? `<div class="cover-letter-box" style="max-height:120px;border-color:var(--success);background:#ecf9f1;">${escHtml(data.shortlist_message)}</div>`
        : '<em class="text-muted">No shortlist message.</em>';
    const reviewedDate = data.reviewed_date ? escHtml(data.reviewed_date) : '<em class="text-muted">Not yet reviewed</em>';

    document.getElementById('modalBody').innerHTML = `
        <div class="info-row"><span class="info-label">Applicant:</span> <strong>${escHtml(data.name)}</strong></div>
        <div class="info-row"><span class="info-label">Email:</span> <a href="mailto:${escHtml(data.email)}">${escHtml(data.email)}</a></div>
        <div class="info-row"><span class="info-label">Phone:</span> ${data.phone ? escHtml(data.phone) : '<em class="text-muted">N/A</em>'}</div>
        <div class="info-row"><span class="info-label">Applied For:</span> <strong>${escHtml(data.job)}</strong></div>
        <div class="info-row"><span class="info-label">Applied Date:</span> ${escHtml(data.date)}</div>
        <div class="info-row"><span class="info-label">Status:</span> <span class="status-badge badge-${escHtml(data.status)}">${escHtml(data.status.charAt(0).toUpperCase()+data.status.slice(1))}</span></div>

        <hr class="section-divider">
        <div style="font-weight:700;margin-bottom:10px;color:var(--primary);font-size:.92rem;">
            <i class="bi bi-person-vcard"></i> Additional Applicant Information
        </div>
        <div class="info-row"><span class="info-label">Qualification:</span> ${qualification}</div>
        <div class="info-row"><span class="info-label">Experience:</span> ${experience}</div>
        <div class="info-row"><span class="info-label">Address:</span> ${address}</div>

        <hr class="section-divider">
        <div style="font-weight:700;margin-bottom:6px;color:var(--text);">Cover Letter</div>
        ${cover}

        <hr class="section-divider">
        <div style="font-weight:700;margin-bottom:6px;color:var(--text);">Admin Notes</div>
        ${adminNotes}

        ${data.shortlist_message ? `
        <hr class="section-divider">
        <div style="font-weight:700;margin-bottom:6px;color:var(--success);">Shortlist Message to Applicant</div>
        ${shortlistMsg}
        ` : ''}

        <hr class="section-divider">
        <div class="info-row"><span class="info-label">Last Reviewed:</span> ${reviewedDate}</div>

        <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
            <a href="${escHtml(data.cv)}" target="_blank" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-text"></i> View CV</a>
            <a href="${escHtml(data.cv)}" download class="btn btn-primary btn-sm"><i class="bi bi-download"></i> Download CV</a>
        </div>
    `;
    document.getElementById('detailModal').classList.add('show');
}

function closeModal() {
    document.getElementById('detailModal').classList.remove('show');
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// Close modals on overlay click
document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('statusModal').addEventListener('click', function(e) {
    if (e.target === this) closeStatusModal();
});

// Print table function - opens a printable version of filtered data
function printTable(filterType) {
    let url = 'view_applications.php';
    const params = new URLSearchParams(window.location.search);
    
    if (filterType === 'shortlisted') {
        // Override status filter to shortlisted
        params.set('status', 'shortlisted');
    }
    // For 'all' - use current filters
    
    // Build print URL with print=1 parameter
    params.set('print', '1');
    const printUrl = url + '?' + params.toString();
    
    // Open in new window for printing
    const printWindow = window.open(printUrl, '_blank', 'width=1200,height=800');
    if (printWindow) {
        printWindow.focus();
    } else {
        alert('Please allow pop-ups to print. Your browser blocked the print window.');
    }
}
</script>
</body>
</html>