<?php
/**
 * config.php
 * Database connection configuration
 * Iya Abubakar ICT Center — Job Portal
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Change for production
define('DB_NAME', 'job_platform');
define('SITE_NAME', 'Iya Abubakar Institute of ICT');
define('SITE_SUBTITLE', 'Online Job Application System');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2 MB

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create MySQLi connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;color:#c0392b;">
        <h2>Database Connection Error</h2>
        <p>Could not connect to the database. Please check your configuration in <code>config.php</code>.</p>
        <pre>' . htmlspecialchars($conn->connect_error) . '</pre>
    </div>');
}

$conn->set_charset('utf8mb4');

/**
 * Helper: redirect with a flash message
 */
function redirect_with_message(string $url, string $message, string $type = 'success'): void {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type']    = $type;
    header('Location: ' . $url);
    exit;
}

/**
 * Helper: display and clear flash message
 */
function flash_message(): string {
    if (!empty($_SESSION['flash_message'])) {
        $msg  = htmlspecialchars($_SESSION['flash_message']);
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        $icon = $type === 'success' ? '✓' : ($type === 'danger' ? '✗' : 'ℹ');
        return "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">
                    <span class=\"alert-icon\">{$icon}</span> {$msg}
                    <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
                </div>";
    }
    return '';
}

/**
 * Helper: require login; redirect if not
 */
function require_login(string $role = ''): void {
    if (empty($_SESSION['user_id'])) {
        redirect_with_message('../index.php', 'Please log in to continue.', 'warning');
    }
    if ($role && ($_SESSION['user_role'] ?? '') !== $role) {
        redirect_with_message('../index.php', 'Access denied.', 'danger');
    }
}

/**
 * Sanitize string output
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Format a date nicely
 */
function fmt_date(string $date): string {
    return date('d M Y, g:i A', strtotime($date));
}

/**
 * Status badge HTML
 */
function status_badge(string $status): string {
    $map = [
        'pending'     => 'badge-pending',
        'reviewed'    => 'badge-reviewed',
        'shortlisted' => 'badge-shortlisted',
        'rejected'    => 'badge-rejected',
        'open'        => 'badge-open',
        'closed'      => 'badge-closed',
        'active'      => 'badge-active',
        'suspended'   => 'badge-suspended',
        'inactive'    => 'badge-inactive',
    ];
    $cls = $map[$status] ?? 'badge-secondary';
    return "<span class=\"status-badge {$cls}\">" . ucfirst(e($status)) . "</span>";
}

/**
 * Create a notification for a user
 */
function create_notification(mysqli $conn, int $user_id, ?int $application_id, string $title, string $message, string $type = 'general'): bool {
    $stmt = $conn->prepare(
        'INSERT INTO notifications (user_id, application_id, title, message, type) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('iisss', $user_id, $application_id, $title, $message, $type);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Log admin activity
 */
function log_activity(mysqli $conn, int $admin_id, string $action, ?string $details = null): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $conn->prepare(
        'INSERT INTO activity_log (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('isss', $admin_id, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

/**
 * Get unread notification count for a user
 */
function unread_notification_count(mysqli $conn, int $user_id): int {
    $stmt = $conn->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $count;
}
