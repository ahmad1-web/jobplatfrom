<?php
/**
 * applicant/apply.php — Job Application Submission
 */
require_once '../config.php';
require_login('applicant');

$user_id = $_SESSION['user_id'];

// Validate job_id parameter
$job_id = (int)($_GET['job_id'] ?? $_POST['job_id'] ?? 0);
if ($job_id < 1) {
    redirect_with_message('dashboard.php', 'Invalid job reference.', 'danger');
}

// Fetch job
$jstmt = $conn->prepare('SELECT * FROM jobs WHERE id = ? AND status = "open" LIMIT 1');
$jstmt->bind_param('i', $job_id);
$jstmt->execute();
$job = $jstmt->get_result()->fetch_assoc();
$jstmt->close();

if (!$job) {
    redirect_with_message('dashboard.php', 'This job is no longer available.', 'warning');
}

// Check for duplicate application
$dup = $conn->prepare('SELECT id FROM applications WHERE user_id=? AND job_id=? LIMIT 1');
$dup->bind_param('ii', $user_id, $job_id);
$dup->execute();
$dup->store_result();
if ($dup->num_rows > 0) {
    $dup->close();
    redirect_with_message('dashboard.php', 'You have already applied for this position.', 'warning');
}
$dup->close();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cover_letter     = trim($_POST['cover_letter'] ?? '');
    $qualification    = trim($_POST['qualification'] ?? '');
    $experience_years = (int)($_POST['experience_years'] ?? 0);
    $address          = trim($_POST['address'] ?? '');

    // Validate additional fields
    if (empty($qualification)) {
        $errors[] = 'Please enter your highest qualification.';
    }
    if ($experience_years < 0) {
        $errors[] = 'Please enter valid years of experience.';
    }
    if (empty($address)) {
        $errors[] = 'Please enter your current address.';
    }

    // File validation
    if (empty($_FILES['cv']['name'])) {
        $errors[] = 'Please upload your CV.';
    } else {
        $file     = $_FILES['cv'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['pdf', 'doc', 'docx'];
        $max_size = MAX_FILE_SIZE; // 2MB

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error. Please try again.';
        } elseif (!in_array($ext, $allowed)) {
            $errors[] = 'Only PDF, DOC, and DOCX files are allowed.';
        } elseif ($file['size'] > $max_size) {
            $errors[] = 'File size must not exceed 2MB.';
        } else {
            // Generate unique filename and move file
            $unique_name = 'cv_' . $user_id . '_' . $job_id . '_' . time() . '.' . $ext;
            $upload_path = UPLOAD_DIR . $unique_name;

            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $cv_path = UPLOAD_URL . $unique_name;

                // Insert application with all fields
                $stmt = $conn->prepare(
                    'INSERT INTO applications (user_id, job_id, cv_path, cover_letter, qualification, experience_years, address) VALUES (?,?,?,?,?,?,?)'
                );
                $stmt->bind_param('iisssis', $user_id, $job_id, $cv_path, $cover_letter, $qualification, $experience_years, $address);

                if ($stmt->execute()) {
                    $stmt->close();
                    $success = true;
                } else {
                    @unlink($upload_path);
                    $errors[] = 'Could not save your application. Please try again.';
                    $stmt->close();
                }
            } else {
                $errors[] = 'Could not save the uploaded file. Check server permissions.';
            }
        }
    }
}

$active_page = 'app_jobs';
$page_title  = 'Apply for Job';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply — <?= e($job['title']) ?> — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="app-shell">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>

        <main class="page-content">
            <div class="page-header">
                <div class="page-header-left">
                    <div class="breadcrumb">
                        <i class="bi bi-house"></i>
                        <a href="dashboard.php">Dashboard</a>
                        <i class="bi bi-chevron-right"></i> Apply
                    </div>
                    <h2>Job Application</h2>
                    <p>Complete the form below to apply for this position.</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success" style="font-size:1rem;">
                <span class="alert-icon"><i class="bi bi-check-circle-fill"></i></span>
                <div>
                    <strong>Application Submitted Successfully!</strong><br>
                    Your application for <em><?= e($job['title']) ?></em> has been received. We'll review it and update your status.
                    <br><br>
                    <a href="dashboard.php" class="btn btn-success btn-sm">
                        <i class="bi bi-house"></i> Go to Dashboard
                    </a>
                </div>
            </div>
            <?php else: ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <span class="alert-icon"><i class="bi bi-exclamation-triangle"></i></span>
                <div><?php foreach ($errors as $e_msg): ?><div><?= e($e_msg) ?></div><?php endforeach; ?></div>
            </div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 360px;gap:22px;align-items:start;">
                <!-- Application Form -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="bi bi-file-earmark-person"></i> Application Form</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="apply.php" enctype="multipart/form-data">
                            <input type="hidden" name="job_id" value="<?= $job_id ?>">

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="bi bi-upload"></i> Upload CV
                                    <small class="text-muted">(PDF, DOC, DOCX — Max 2MB)</small>
                                </label>
                                <div class="file-upload-area" id="dropArea" onclick="document.getElementById('cv').click()">
                                    <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx" onchange="updateFileName(this)">
                                    <i class="bi bi-cloud-upload"></i>
                                    <p>
                                        <strong>Click to browse</strong> or drag & drop your CV here
                                    </p>
                                    <p style="font-size:.78rem;">PDF, DOC, DOCX • Max 2MB</p>
                                    <div id="file-name-display"></div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="bi bi-chat-text"></i> Cover Letter
                                    <small class="text-muted">(optional but recommended)</small>
                                </label>
                                <textarea name="cover_letter" class="form-control" rows="8"
                                    placeholder="Dear Hiring Manager,&#10;&#10;I am writing to express my interest in the <?= e($job['title']) ?> position at Iya Abubakar ICT Center...&#10;&#10;[Continue your cover letter here]"><?= e($_POST['cover_letter'] ?? '') ?></textarea>
                                <small class="text-muted">A compelling cover letter increases your chances significantly.</small>
                            </div>

                            <!-- Additional Information Section -->
                            <div style="margin:18px 0 8px;padding:14px;background:var(--bg);border-radius:8px;border:1px solid var(--border);">
                                <div style="font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:8px;color:var(--primary);">
                                    <i class="bi bi-person-vcard"></i> Additional Information
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-mortarboard"></i> Highest Qualification *
                                    </label>
                                    <input type="text" name="qualification" class="form-control"
                                           placeholder="e.g. B.Sc Computer Science, HND Electrical Engineering"
                                           value="<?= e($_POST['qualification'] ?? '') ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-briefcase"></i> Years of Experience *
                                    </label>
                                    <input type="number" name="experience_years" class="form-control"
                                           placeholder="e.g. 3"
                                           min="0" max="50"
                                           value="<?= e($_POST['experience_years'] ?? '') ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-geo-alt"></i> Current Address *
                                    </label>
                                    <textarea name="address" class="form-control" rows="2"
                                              placeholder="e.g. No. 123, Ahmadu Bello Way, Zaria, Kaduna State"
                                              required><?= e($_POST['address'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div style="display:flex;gap:12px;margin-top:8px;">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send"></i> Submit Application
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-primary btn-lg">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Job Details Sidebar -->
                <div>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="bi bi-briefcase"></i> Job Details</h3>
                        </div>
                        <div class="card-body">
                            <div style="font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:12px;">
                                <?= e($job['title']) ?>
                            </div>
                            <?= status_badge($job['status']) ?>
                            <hr class="divider">
                            <div style="display:flex;flex-direction:column;gap:10px;font-size:.88rem;">
                                <div><i class="bi bi-geo-alt" style="color:var(--accent);"></i> <strong>Location:</strong> <?= e($job['location']) ?></div>
                                <div><i class="bi bi-clock" style="color:var(--accent);"></i> <strong>Type:</strong> <?= e($job['type']) ?></div>
                                <div><i class="bi bi-calendar3" style="color:var(--accent);"></i> <strong>Posted:</strong> <?= fmt_date($job['posted_date']) ?></div>
                            </div>
                            <hr class="divider">
                            <div style="font-size:.82rem;color:var(--text-muted);line-height:1.7;">
                                <?= nl2br(e(substr($job['description'], 0, 350))) ?>…
                            </div>
                        </div>
                    </div>

                    <div class="card" style="margin-top:16px;background:var(--primary);border:none;">
                        <div class="card-body" style="color:rgba(255,255,255,.85);font-size:.84rem;">
                            <div style="color:#fff;font-weight:700;margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                                <i class="bi bi-info-circle"></i> Tips for a Strong Application
                            </div>
                            <ul style="padding-left:16px;line-height:1.9;margin:0;">
                                <li>Tailor your CV to match the job requirements.</li>
                                <li>Write a personalized cover letter.</li>
                                <li>Ensure your CV is updated and error-free.</li>
                                <li>Highlight relevant skills and experience.</li>
                            </ul>
                        </div>
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

function updateFileName(input) {
    const display = document.getElementById('file-name-display');
    if (input.files && input.files[0]) {
        const f = input.files[0];
        const size = (f.size / 1024).toFixed(0);
        display.innerHTML = '<i class="bi bi-file-earmark-check"></i> ' + f.name + ' (' + size + ' KB)';
        document.getElementById('dropArea').style.borderColor = 'var(--success)';
    }
}

// Drag and drop
const dropArea = document.getElementById('dropArea');
dropArea.addEventListener('dragover', e => { e.preventDefault(); dropArea.classList.add('dragover'); });
dropArea.addEventListener('dragleave', () => dropArea.classList.remove('dragover'));
dropArea.addEventListener('drop', e => {
    e.preventDefault();
    dropArea.classList.remove('dragover');
    const dt = e.dataTransfer;
    if (dt.files.length) {
        document.getElementById('cv').files = dt.files;
        updateFileName(document.getElementById('cv'));
    }
});
</script>
</body>
</html>
