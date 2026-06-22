<?php
/**
 * update_passwords.php
 * Run this file ONCE to update passwords for the three users.
 * DELETE this file from your server immediately after running it.
 */
require_once 'config.php';

// ─── SET YOUR NEW PASSWORDS HERE ──────────────────────────────────────────────
$new_passwords = [
    1 => 'Admin@1234',        // System Administrator  (admin@jobportal.com)
    2 => 'Applicant@1234',    // Aminu Suleiman        (applicant@test.com)
    
];
// ──────────────────────────────────────────────────────────────────────────────

$results = [];

foreach ($new_passwords as $id => $plain_password) {
    $hashed = password_hash($plain_password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->bind_param('si', $hashed, $id);
    $stmt->execute();

    $results[] = [
        'id'      => $id,
        'affected'=> $stmt->affected_rows,
        'email'   => match($id) {
            1 => 'admin@jobportal.com',
            2 => 'applicant@test.com',
            
        },
        'password'=> $plain_password,
    ];

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Update</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 0 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px 14px; border: 1px solid #ddd; text-align: left; }
        th { background: #f4f4f4; }
        .ok  { color: green; font-weight: bold; }
        .err { color: red;   font-weight: bold; }
        .warn { background: #fff3cd; border: 1px solid #ffc107; padding: 14px; border-radius: 6px; margin-top: 24px; }
    </style>
</head>
<body>
    <h2>Password Update Results</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>New Password</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $r): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['email']) ?></td>
                <td><code><?= htmlspecialchars($r['password']) ?></code></td>
                <td>
                    <?php if ($r['affected'] > 0): ?>
                        <span class="ok">✔ Updated</span>
                    <?php else: ?>
                        <span class="err">✘ Failed (check ID)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="warn">
        ⚠️ <strong>Security Notice:</strong> Delete <code>update_passwords.php</code>
        from your server immediately after confirming the updates above.
    </div>
</body>
</html>