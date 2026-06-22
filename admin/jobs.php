<?php
/**
 * admin/jobs.php — Manage Job Postings (Full CRUD)
 */
require_once '../config.php';
require_login('admin');

$action  = $_GET['action']  ?? '';
$job_id  = (int)($_GET['id'] ?? 0);
$errors  = [];
$job_data= [];

/* ── HANDLE FORM SUBMISSIONS ── */

// DELETE
if ($action === 'delete' && $job_id > 0) {
    // Delete applications + cv files first
    $apps = $conn->prepare('SELECT cv_path FROM applications WHERE job_id = ?');
    $apps->bind_param('i', $job_id);
    $apps->execute();
    $rows = $apps->get_result()->fetch_all(MYSQLI_ASSOC);
    $apps->close();
    foreach ($rows as $r) { @unlink(__DIR__ . '/../' . $r['cv_path']); }

    $del = $conn->prepare('DELETE FROM jobs WHERE id = ?');
    $del->bind_param('i', $job_id);
    $del->execute();
    $del->close();
    redirect_with_message('jobs.php', 'Job deleted successfully.', 'success');
}

// TOGGLE STATUS
if ($action === 'toggle' && $job_id > 0) {
    $conn->query("UPDATE jobs SET status = IF(status='open','closed','open') WHERE id = $job_id");
    redirect_with_message('jobs.php', 'Job status updated.', 'success');
}

// EDIT — load existing data
if ($action === 'edit' && $job_id > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $estmt = $conn->prepare('SELECT * FROM jobs WHERE id = ? LIMIT 1');
    $estmt->bind_param('i', $job_id);
    $estmt->execute();
    $job_data = $estmt->get_result()->fetch_assoc() ?? [];
    $estmt->close();
    if (!$job_data) redirect_with_message('jobs.php', 'Job not found.', 'danger');
}

// SAVE (new or update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid          = (int)($_POST['job_id'] ?? 0);
    $title        = trim($_POST['title']        ?? '');
    $description  = trim($_POST['description']  ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $location     = trim($_POST['location']     ?? '');
    $type         = trim($_POST['type']         ?? '');
    $status       = trim($_POST['status']       ?? 'open');

    $allowed_types   = ['Full-time','Part-time','Contract','Internship'];
    $allowed_statuses= ['open','closed'];

    if (empty($title))        $errors[] = 'Job title is required.';
    if (empty($description))  $errors[] = 'Description is required.';
    if (empty($requirements)) $errors[] = 'Requirements are required.';
    if (empty($location))     $errors[] = 'Location is required.';
    if (!in_array($type, $allowed_types))   $errors[] = 'Invalid job type.';
    if (!in_array($status, $allowed_statuses)) $errors[] = 'Invalid status.';

    // Re-populate form on error
    $job_data = compact('title','description','requirements','location','type','status');
    $job_data['id'] = $pid;

    if (empty($errors)) {
        if ($pid > 0) {
            // UPDATE
            $stmt = $conn->prepare(
                'UPDATE jobs SET title=?,description=?,requirements=?,location=?,type=?,status=? WHERE id=?'
            );
            $stmt->bind_param('ssssssi', $title, $description, $requirements, $location, $type, $status, $pid);
            $stmt->execute();
            $stmt->close();
            redirect_with_message('jobs.php', 'Job updated successfully.');
        } else {
            // INSERT
            $stmt = $conn->prepare(
                'INSERT INTO jobs (title,description,requirements,location,type,status) VALUES (?,?,?,?,?,?)'
            );
            $stmt->bind_param('ssssss', $title, $description, $requirements, $location, $type, $status);
            $stmt->execute();
            $stmt->close();
            redirect_with_message('jobs.php', 'New job posted successfully.');
        }
    } else {
        // Stay on form
        $action = ($pid > 0) ? 'edit' : 'new';
    }
}

// FETCH all jobs for listing
$all_jobs = $conn->query(
    'SELECT j.*, COUNT(a.id) AS app_count
     FROM jobs j
     LEFT JOIN applications a ON a.job_id = j.id
     GROUP BY j.id ORDER BY j.posted_date DESC'
)->fetch_all(MYSQLI_ASSOC);

$active_page = 'admin_jobs';
$page_title  = 'Manage Jobs';
$show_form   = ($action === 'new' || $action === 'edit');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs — <?= SITE_NAME ?></title>
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

            <?php if ($show_form): ?>
            <!-- ─── JOB FORM ─── -->
            <div class="page-header">
                <div class="page-header-left">
                    <div class="breadcrumb">
                        <a href="jobs.php">Jobs</a>
                        <i class="bi bi-chevron-right"></i>
                        <?= $job_data['id'] ?? 0 ? 'Edit Job' : 'New Job' ?>
                    </div>
                    <h2><?= $job_data['id'] ?? 0 ? 'Edit Job Posting' : 'Add New Job' ?></h2>
                </div>
                <a href="jobs.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Jobs
                </a>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <span class="alert-icon"><i class="bi bi-exclamation-triangle"></i></span>
                <div><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-briefcase-fill"></i> Job Details</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="jobs.php">
                        <input type="hidden" name="job_id" value="<?= (int)($job_data['id'] ?? 0) ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Job Title *</label>
                                <input type="text" name="title" class="form-control"
                                       placeholder="e.g. Web Developer"
                                       value="<?= e($job_data['title'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Location *</label>
                                <input type="text" name="location" class="form-control"
                                       placeholder="e.g. Zaria, Kaduna State"
                                       value="<?= e($job_data['location'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Job Type *</label>
                                <select name="type" class="form-select" required>
                                    <?php foreach (['Full-time','Part-time','Contract','Internship'] as $t): ?>
                                    <option value="<?= $t ?>" <?= ($job_data['type']??'')===$t?'selected':'' ?>><?= $t ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-select" required>
                                    <option value="open"   <?= ($job_data['status']??'')==='open'  ?'selected':'' ?>>Open</option>
                                    <option value="closed" <?= ($job_data['status']??'')==='closed'?'selected':'' ?>>Closed</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Job Description *</label>
                            <textarea name="description" class="form-control" rows="7" required
                                placeholder="Provide a detailed description of the role and responsibilities..."><?= e($job_data['description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Requirements *</label>
                            <textarea name="requirements" class="form-control" rows="7" required
                                placeholder="List the qualifications, skills, and experience required..."><?= e($job_data['requirements'] ?? '') ?></textarea>
                        </div>

                        <div style="display:flex;gap:12px;margin-top:8px;">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle"></i>
                                <?= ($job_data['id'] ?? 0) ? 'Update Job' : 'Post Job' ?>
                            </button>
                            <a href="jobs.php" class="btn btn-outline-primary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php else: ?>
            <!-- ─── JOBS TABLE ─── -->
            <div class="page-header">
                <div class="page-header-left">
                    <div class="breadcrumb"><i class="bi bi-house"></i> Admin <i class="bi bi-chevron-right"></i> Jobs</div>
                    <h2>Job Postings</h2>
                    <p>Manage all job listings — add, edit, or remove positions.</p>
                </div>
                <a href="jobs.php?action=new" class="btn btn-accent">
                    <i class="bi bi-plus-circle"></i> Add New Job
                </a>
            </div>

            <div class="card">
                <?php if (empty($all_jobs)): ?>
                <div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-briefcase"></i>
                        <p>No jobs posted yet. <a href="jobs.php?action=new">Post the first job</a>.</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Job Title</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Applications</th>
                                <th>Status</th>
                                <th>Posted Date</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_jobs as $i => $job): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><strong><?= e($job['title']) ?></strong></td>
                                <td><?= e($job['type']) ?></td>
                                <td><?= e($job['location']) ?></td>
                                <td>
                                    <a href="view_applications.php?job_id=<?= $job['id'] ?>"
                                       style="font-weight:700;color:var(--primary);">
                                        <?= $job['app_count'] ?> application<?= $job['app_count'] != 1 ? 's':'' ?>
                                    </a>
                                </td>
                                <td><?= status_badge($job['status']) ?></td>
                                <td style="font-size:.8rem;color:var(--text-muted);"><?= fmt_date($job['posted_date']) ?></td>
                                <td style="text-align:center;">
                                    <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                                        <a href="jobs.php?action=edit&id=<?= $job['id'] ?>"
                                           class="btn btn-sm btn-info" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="jobs.php?action=toggle&id=<?= $job['id'] ?>"
                                           class="btn btn-sm btn-warning"
                                           title="Toggle Status"
                                           onclick="return confirm('Toggle status for this job?')">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </a>
                                        <a href="jobs.php?action=delete&id=<?= $job['id'] ?>"
                                           class="btn btn-sm btn-danger"
                                           title="Delete"
                                           onclick="return confirmDelete(event, '<?= e(addslashes($job['title'])) ?>', <?= $job['app_count'] ?>)">
                                            <i class="bi bi-trash"></i>
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

function confirmDelete(e, title, appCount) {
    let msg = 'Are you sure you want to DELETE "' + title + '"?';
    if (appCount > 0) {
        msg += '\n\nWARNING: This will also delete ' + appCount + ' application(s) and their uploaded CVs.';
    }
    if (!confirm(msg)) {
        e.preventDefault();
        return false;
    }
    return true;
}
</script>
</body>
</html>
