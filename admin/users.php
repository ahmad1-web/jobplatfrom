<?php
/**
 * admin/users.php — User Management
 * Manage all registered users (applicants)
 */
require_once '../config.php';
require_login('admin');

$admin_id = $_SESSION['user_id'];

/* ── AJAX: Update user status ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user_status'])) {
    $user_id    = (int)$_POST['user_id'];
    $new_status = trim($_POST['status']);
    $allowed    = ['active', 'suspended', 'inactive'];

    if ($user_id > 0 && in_array($new_status, $allowed)) {
        $upd = $conn->prepare('UPDATE users SET status=? WHERE id=? AND role="applicant"');
        $upd->bind_param('si', $new_status, $user_id);
        $upd->execute();
        $upd->close();
        log_activity($conn, $admin_id, 'Updated user status', "User #{$user_id} set to {$new_status}");
        echo json_encode(['ok'=>true]);
    } else {
        echo json_encode(['ok'=>false]);
    }
    exit;
}

/* ── AJAX: Delete user ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    if ($user_id > 0) {
        // Get user info for logging
        $uq = $conn->prepare('SELECT full_name, email FROM users WHERE id=?');
        $uq->bind_param('i', $user_id);
        $uq->execute();
        $uinfo = $uq->get_result()->fetch_assoc();
        $uq->close();

        // Delete user (applications will cascade)
        $del = $conn->prepare('DELETE FROM users WHERE id=? AND role="applicant"');
        $del->bind_param('i', $user_id);
        $del->execute();
        $del->close();

        log_activity($conn, $admin_id, 'Deleted user', "Deleted {$uinfo['full_name']} ({$uinfo['email']})");
        echo json_encode(['ok'=>true]);
    } else {
        echo json_encode(['ok'=>false]);
    }
    exit;
}

// Search
$search = trim($_GET['search'] ?? '');

// Build query
$sql = 'SELECT u.*,
               (SELECT COUNT(*) FROM applications WHERE user_id = u.id) AS application_count,
               (SELECT MAX(applied_date) FROM applications WHERE user_id = u.id) AS last_application_date
        FROM users u
        WHERE u.role = "applicant"';

$params = [];
$types  = '';

if ($search !== '') {
    $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
    $searchTerm = "%{$search}%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    $types  = 'sss';
}

$sql .= ' ORDER BY u.created_at DESC';

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total counts
$total_applicants = $conn->query('SELECT COUNT(*) FROM users WHERE role="applicant"')->fetch_row()[0] ?? 0;
$active_users     = $conn->query('SELECT COUNT(*) FROM users WHERE role="applicant" AND status="active"')->fetch_row()[0] ?? 0;
$suspended_users  = $conn->query('SELECT COUNT(*) FROM users WHERE role="applicant" AND status="suspended"')->fetch_row()[0] ?? 0;

$active_page = 'admin_users';
$page_title  = 'User Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .user-avatar-sm {
            width: 38px; height: 38px; border-radius: 50%;
            background: var(--primary); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .85rem; flex-shrink: 0;
        }
        .user-status-select {
            padding: 4px 8px; border-radius: 6px;
            border: 1px solid var(--border); font-size: .8rem;
            cursor: pointer;
        }
        .user-status-select:focus { outline: 2px solid var(--primary); outline-offset: -1px; }
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
                    <div class="breadcrumb"><i class="bi bi-house"></i> Admin <i class="bi bi-chevron-right"></i> Users</div>
                    <h2>User Management</h2>
                    <p>Manage all registered applicants (<?= $total_applicants ?> total).</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid" style="margin-bottom:22px;">
                <div class="stat-card card-blue">
                    <div class="stat-icon"><i class="bi bi-people"></i></div>
                    <div class="stat-body">
                        <div class="label">Total Applicants</div>
                        <div class="value"><?= $total_applicants ?></div>
                    </div>
                </div>
                <div class="stat-card card-green">
                    <div class="stat-icon"><i class="bi bi-person-check"></i></div>
                    <div class="stat-body">
                        <div class="label">Active</div>
                        <div class="value"><?= $active_users ?></div>
                    </div>
                </div>
                <div class="stat-card card-red">
                    <div class="stat-icon"><i class="bi bi-person-slash"></i></div>
                    <div class="stat-body">
                        <div class="label">Suspended</div>
                        <div class="value"><?= $suspended_users ?></div>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="card" style="margin-bottom:18px;">
                <div class="card-body" style="padding:16px 22px;">
                    <form method="GET" action="users.php" style="display:flex;gap:12px;align-items:flex-end;">
                        <div style="flex:1;">
                            <label class="form-label" style="font-size:.78rem;">Search Users</label>
                            <div class="input-icon-wrap">
                                <i class="icon bi bi-search"></i>
                                <input type="text" name="search" class="form-control"
                                       placeholder="Search by name, email, or phone..."
                                       value="<?= e($search) ?>">
                            </div>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary" style="margin-bottom:1px;">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <?php if ($search): ?>
                            <a href="users.php" class="btn btn-outline-primary" style="margin-bottom:1px;">
                                <i class="bi bi-x"></i> Clear
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <?php if (empty($users)): ?>
                <div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-people"></i>
                        <p>No users found<?= $search ? ' matching your search.' : '.' ?></p>
                    </div>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Qualification</th>
                                <th>Experience</th>
                                <th>Applications</th>
                                <th>Last Activity</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $i => $user): ?>
                            <tr id="user-row-<?= $user['id'] ?>">
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div class="user-avatar-sm"><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></div>
                                        <div>
                                            <div style="font-weight:700;font-size:.87rem;"><?= e($user['full_name']) ?></div>
                                            <div style="font-size:.73rem;color:var(--text-muted);">ID: #<?= $user['id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-size:.82rem;">
                                    <div><i class="bi bi-envelope" style="color:var(--text-muted);"></i> <?= e($user['email']) ?></div>
                                    <?php if ($user['phone']): ?>
                                    <div style="font-size:.77rem;margin-top:2px;"><i class="bi bi-telephone" style="color:var(--text-muted);"></i> <?= e($user['phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:.82rem;">
                                    <?= $user['qualification'] ? e($user['qualification']) : '<em class="text-muted">N/A</em>' ?>
                                </td>
                                <td style="text-align:center;font-size:.88rem;">
                                    <?= $user['experience_years'] ? e($user['experience_years']) . ' yrs' : '-' ?>
                                </td>
                                <td style="text-align:center;">
                                    <span style="font-weight:700;color:var(--primary);font-size:.95rem;">
                                        <?= $user['application_count'] ?>
                                    </span>
                                </td>
                                <td style="font-size:.78rem;color:var(--text-muted);">
                                    <?= $user['last_application_date'] ? date('d M Y', strtotime($user['last_application_date'])) : 'Never' ?>
                                </td>
                                <td>
                                    <select class="user-status-select" data-user-id="<?= $user['id'] ?>"
                                            onchange="updateUserStatus(this)"
                                            style="background:<?= $user['status']==='active' ? '#ecf9f1' : ($user['status']==='suspended' ? '#fde8e8' : '#fef3e2') ?>;">
                                        <option value="active" <?= $user['status']==='active'?'selected':'' ?>>Active</option>
                                        <option value="suspended" <?= $user['status']==='suspended'?'selected':'' ?>>Suspended</option>
                                        <option value="inactive" <?= $user['status']==='inactive'?'selected':'' ?>>Inactive</option>
                                    </select>
                                </td>
                                <td style="font-size:.78rem;color:var(--text-muted);">
                                    <?= date('d M Y', strtotime($user['created_at'])) ?>
                                </td>
                                <td style="text-align:center;">
                                    <div style="display:flex;gap:5px;justify-content:center;">
                                        <button class="btn btn-sm btn-outline-info"
                                                onclick='viewUser(<?= json_encode([
                                                    "id" => $user['id'],
                                                    "name" => $user['full_name'],
                                                    "email" => $user['email'],
                                                    "phone" => $user['phone'] ?? 'N/A',
                                                    "qualification" => $user['qualification'] ?? 'N/A',
                                                    "experience_years" => $user['experience_years'] ?? '0',
                                                    "address" => $user['address'] ?? 'N/A',
                                                    "status" => $user['status'],
                                                    "created_at" => date('d M Y, g:i A', strtotime($user['created_at'])),
                                                    "apps" => $user['application_count'],
                                                ]) ?>)'
                                                title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger"
                                                onclick="deleteUser(<?= $user['id'] ?>, '<?= e(addslashes($user['full_name'])) ?>')"
                                                title="Delete User">
                                            <i class="bi bi-trash"></i>
                                        </button>
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

<!-- User Detail Modal -->
<div class="modal-overlay" id="userModal">
    <div class="modal-box" style="max-width:550px;">
        <div class="modal-header">
            <h4><i class="bi bi-person-badge"></i> User Details</h4>
            <button class="modal-close" onclick="closeUserModal()">&times;</button>
        </div>
        <div class="modal-body" id="userModalBody"></div>
        <div class="modal-footer">
            <button class="btn btn-outline-primary" onclick="closeUserModal()">Close</button>
        </div>
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

// Update user status via AJAX
function updateUserStatus(select) {
    const userId = select.dataset.userId;
    const status = select.value;

    if (!confirm('Are you sure you want to change this user\'s status to "' + status + '"?')) {
        // Revert the select
        select.value = select.querySelector(`option[value="${select.dataset.original || 'active'}"]`).value;
        return;
    }

    select.dataset.original = status;

    const fd = new FormData();
    fd.append('update_user_status', '1');
    fd.append('user_id', userId);
    fd.append('status', status);

    fetch('users.php', { method:'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                const row = document.getElementById('user-row-' + userId);
                row.style.background = status === 'active' ? '#d1e7dd' : (status === 'suspended' ? '#f8d7da' : '#fff3cd');
                setTimeout(() => row.style.background = '', 1500);
                // Update select background
                if (status === 'active') select.style.background = '#ecf9f1';
                else if (status === 'suspended') select.style.background = '#fde8e8';
                else select.style.background = '#fef3e2';
            } else {
                alert('Failed to update user status.');
                select.value = select.dataset.original === 'active' ? 'active' : (select.dataset.original === 'suspended' ? 'suspended' : 'inactive');
            }
        })
        .catch(() => alert('Network error.'));
}

// View user details
function viewUser(data) {
    document.getElementById('userModalBody').innerHTML = `
        <div style="text-align:center;margin-bottom:18px;">
            <div style="width:60px;height:60px;border-radius:50%;background:var(--primary);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:1.5rem;">
                ${escHtml(data.name.charAt(0).toUpperCase())}
            </div>
            <div style="font-weight:700;font-size:1.1rem;margin-top:8px;">${escHtml(data.name)}</div>
            <div><span class="status-badge badge-${data.status}">${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</span></div>
        </div>
        <hr style="border-color:var(--border);margin:12px 0;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:.85rem;">
            <div><strong>User ID:</strong> #${escHtml(data.id)}</div>
            <div><strong>Total Applications:</strong> ${escHtml(data.apps)}</div>
            <div><strong>Email:</strong> <a href="mailto:${escHtml(data.email)}">${escHtml(data.email)}</a></div>
            <div><strong>Phone:</strong> ${escHtml(data.phone)}</div>
            <div><strong>Qualification:</strong> ${escHtml(data.qualification)}</div>
            <div><strong>Experience:</strong> ${escHtml(data.experience_years)} years</div>
            <div style="grid-column:1/-1;"><strong>Address:</strong> ${escHtml(data.address)}</div>
            <div style="grid-column:1/-1;"><strong>Registered:</strong> ${escHtml(data.created_at)}</div>
        </div>
    `;
    document.getElementById('userModal').classList.add('show');
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('show');
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// Delete user with confirmation
function deleteUser(userId, userName) {
    if (!confirm('Are you sure you want to permanently delete "' + userName + '"?\n\nThis will also delete all their applications and notifications. This action CANNOT be undone!')) {
        return;
    }

    const fd = new FormData();
    fd.append('delete_user', '1');
    fd.append('user_id', userId);

    fetch('users.php', { method:'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                const row = document.getElementById('user-row-' + userId);
                row.style.background = '#f8d7da';
                setTimeout(() => {
                    row.remove();
                    // Show success
                    const flash = document.createElement('div');
                    flash.className = 'alert alert-success';
                    flash.innerHTML = '<span class="alert-icon">✓</span> User "' + escHtml(userName) + '" has been deleted.';
                    flash.style.position = 'fixed';
                    flash.style.top = '20px';
                    flash.style.right = '20px';
                    flash.style.zIndex = '9999';
                    flash.style.maxWidth = '400px';
                    document.body.appendChild(flash);
                    setTimeout(() => flash.remove(), 3000);
                }, 500);
            } else {
                alert('Failed to delete user. Please try again.');
            }
        })
        .catch(() => alert('Network error.'));
}

// Close modal on overlay click
document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) closeUserModal();
});
</script>
</body>
</html>