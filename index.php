<?php
/**
 * index.php — Login Page
 * Iya Abubakar ICT Center — Online Job Application System
 */
require_once 'config.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: applicant/dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        // Fetch user from database
        $stmt = $conn->prepare('SELECT id, full_name, email, password, role FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email']= $user['email'];

            // Role-based redirect
            if ($user['role'] === 'admin') {
                redirect_with_message('admin/dashboard.php', 'Welcome back, ' . $user['full_name'] . '!');
            } else {
                redirect_with_message('applicant/dashboard.php', 'Welcome back, ' . $user['full_name'] . '!');
            }
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="auth-wrapper">
    <!-- Hero Panel -->
    <div class="auth-hero">
        <div class="auth-hero-logo">
            <img src="logo.png" alt="Logo" onerror="this.style.display='none'">
            <div class="brand">
                <div class="brand-title">IYA ABUBAKAR INSTITUTE OF ICT</div>
                <div class="brand-subtitle">AHMADU BELLO UNIVERSITY, ZARIA, KADUNA STATE</div>
            </div>
        </div>
        <h1>Online Job Application System</h1>
        <p>
            A streamlined portal for discovering opportunities and managing applications
            at IYA ABUBAKAR INSTITUTE OF ICT. Apply for roles, track your status, and
            build your career with us.
        </p>
        <div class="auth-hero-stats">
            <?php
            // Fetch live counts for hero stats
            $total_jobs = $conn->query('SELECT COUNT(*) FROM jobs WHERE status="open"')->fetch_row()[0] ?? 0;
            $total_apps = $conn->query('SELECT COUNT(*) FROM applications')->fetch_row()[0] ?? 0;
            $total_users= $conn->query('SELECT COUNT(*) FROM users WHERE role="applicant"')->fetch_row()[0] ?? 0;
            ?>
            <div class="auth-stat">
                <div class="num"><?= $total_jobs ?></div>
                <div class="lbl">Open Jobs</div>
            </div>
            <div class="auth-stat">
                <div class="num"><?= $total_users ?></div>
                <div class="lbl">Registered Applicants</div>
            </div>
            <div class="auth-stat">
                <div class="num"><?= $total_apps ?></div>
                <div class="lbl">Applications</div>
            </div>
        </div>
        <!-- Hero Marquee -->
        <div class="auth-hero-marquee">
            <marquee behavior="scroll" direction="left" onmouseover="this.stop()" onmouseout="this.start()">
                <span><i class="bi bi-telephone"></i> Contact: +234 800 000 0000</span>
                <span class="marquee-sep">|</span>
                <span><i class="bi bi-envelope"></i> Email: info@iabuict.edu.ng</span>
                <span class="marquee-sep">|</span>
                <span><i class="bi bi-globe"></i> Website: www.iabuict.edu.ng</span>
                <span class="marquee-sep">|</span>
                <span><i class="bi bi-geo-alt"></i> Location: Ahmadu Bello University, Zaria, Kaduna State, Nigeria</span>
            </marquee>
        </div>
    </div>

    <!-- Login Panel -->
    <div class="auth-panel">
        <div class="auth-logo-mobile">
            <img src="logo.png" alt="Logo" onerror="this.style.display='none'">
            <div class="brand">IYA ABUBAKAR INSTITUTE OF ICT</div>
        </div>

        <div class="auth-panel-header">
            <h2>Sign In to Your Account</h2>
            <p>Enter your credentials to access the portal</p>
        </div>

        <?= flash_message() ?>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <span class="alert-icon"><i class="bi bi-exclamation-triangle"></i></span>
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="index.php" novalidate>
            <div class="form-group">
                <label class="form-label" for="email"><i class="bi bi-envelope"></i> Email Address</label>
                <div class="input-icon-wrap">
                    <i class="icon bi bi-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="your@email.com"
                           value="<?= e($_POST['email'] ?? '') ?>"
                           required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password"><i class="bi bi-lock"></i> Password</label>
                <div class="input-icon-wrap">
                    <i class="icon bi bi-lock"></i>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Enter your password" required>
                </div>
            </div>

            

            <button type="submit" class="btn btn-primary btn-lg btn-block">
                <i class="bi bi-box-arrow-in-right"></i> Sign In
            </button>
        </form>

        <hr class="divider">
        <p style="text-align:center;font-size:.88rem;color:var(--text-muted);">
            Don't have an account?
            <a href="register.php" style="font-weight:700;">Create one here</a>
        </p>
    </div>
</div>
</body>
</html>
