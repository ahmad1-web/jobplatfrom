<?php
/**
 * register.php — Applicant Registration
 * Iya Abubakar ICT Center — Online Job Application System
 */
require_once 'config.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['user_role']==='admin' ? 'admin/dashboard.php' : 'applicant/dashboard.php'));
    exit;
}

$errors = [];
$values = ['full_name'=>'','email'=>'','phone'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $password  = $_POST['password']       ?? '';
    $confirm   = $_POST['confirm_password']?? '';

    // Store for repopulation
    $values = compact('full_name','email','phone');

    // Validation
    if (empty($full_name))       $errors[] = 'Full name is required.';
    elseif (strlen($full_name)<3) $errors[] = 'Full name must be at least 3 characters.';

    if (empty($email))           $errors[] = 'Email address is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';

    if (empty($password))        $errors[] = 'Password is required.';
    elseif (strlen($password)<6) $errors[] = 'Password must be at least 6 characters.';

    if ($password !== $confirm)  $errors[] = 'Passwords do not match.';

    // Phone validation: supports +234XXXXXXXXXX (13 digits) or 0XXXXXXXXXX (11 digits)
    if (!empty($phone)) {
        // Strip spaces, dashes, parentheses
        $clean_phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        if (!preg_match('/^(\+234\d{10}|0\d{10})$/', $clean_phone)) {
            $errors[] = 'Please enter a valid Nigerian phone number (e.g. +2348012345678 or 08012345678).';
        }
    }

    if (empty($errors)) {
        // Check if email already exists
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'This email address is already registered. <a href="index.php">Sign in</a>.';
        } else {
            $stmt->close();

            // Hash password and insert
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $role   = 'applicant';

            $stmt2 = $conn->prepare(
                'INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt2->bind_param('sssss', $full_name, $email, $phone, $hashed, $role);

            if ($stmt2->execute()) {
                $stmt2->close();
                redirect_with_message('index.php', 'Registration successful! You can now sign in.');
            } else {
                $errors[] = 'Registration failed. Please try again later.';
                $stmt2->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — <?= SITE_NAME ?></title>
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
        <h1>Join Our Talent Community</h1>
        <p>
            Create your applicant profile and gain access to open positions at
            IYA ABUBAKAR INSTITUTE OF ICT. Track your applications and stay updated
            through our portal.
        </p>
        <div class="auth-hero-stats">
            <div class="auth-stat">
                <div class="num">2</div>
                <div class="lbl">Open Positions</div>
            </div>
            <div class="auth-stat">
                <div class="num">Free</div>
                <div class="lbl">To Apply</div>
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

    <!-- Registration Panel -->
    <div class="auth-panel">
        <div class="auth-logo-mobile">
            <img src="logo.png" alt="Logo" onerror="this.style.display='none'">
            <div class="brand">IYA ABUBAKAR INSTITUTE OF ICT</div>
        </div>

        <div class="auth-panel-header">
            <h2>Create an Account</h2>
            <p>Fill in the form below to register as an applicant</p>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <span class="alert-icon"><i class="bi bi-exclamation-triangle"></i></span>
            <div>
                <?php foreach ($errors as $err): ?>
                    <div><?= $err ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate>
            <div class="form-group">
                <label class="form-label" for="full_name">Full Name *</label>
                <div class="input-icon-wrap">
                    <i class="icon bi bi-person"></i>
                    <input type="text" id="full_name" name="full_name" class="form-control"
                           placeholder="e.g. Aminu Suleiman"
                           value="<?= e($values['full_name']) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address *</label>
                    <div class="input-icon-wrap">
                        <i class="icon bi bi-envelope"></i>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="your@email.com"
                               value="<?= e($values['email']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <div class="input-icon-wrap">
                        <i class="icon bi bi-telephone"></i>
                        <input type="tel" id="phone" name="phone" class="form-control"
                               placeholder="+234 800 000 0000"
                               value="<?= e($values['phone']) ?>">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Password *</label>
                    <div class="input-icon-wrap">
                        <i class="icon bi bi-lock"></i>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Min. 6 characters" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password *</label>
                    <div class="input-icon-wrap">
                        <i class="icon bi bi-lock-fill"></i>
                        <input type="password" id="confirm_password" name="confirm_password"
                               class="form-control" placeholder="Repeat password" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top:6px;">
                <i class="bi bi-person-plus"></i> Create Account
            </button>
        </form>

        <hr class="divider">
        <p style="text-align:center;font-size:.88rem;color:var(--text-muted);">
            Already have an account?
            <a href="index.php" style="font-weight:700;">Sign in</a>
        </p>
    </div>
</div>

<script>
// Client-side password match check
document.querySelector('form').addEventListener('submit', function(e) {
    const p1 = document.getElementById('password').value;
    const p2 = document.getElementById('confirm_password').value;
    if (p1 !== p2) {
        e.preventDefault();
        alert('Passwords do not match!');
        document.getElementById('confirm_password').focus();
    }
});
</script>
</body>
</html>
