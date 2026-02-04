<?php
// admin_profile.php
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireAdminLogin();
global $conn;

$admin = getCurrentAdmin();
if (!$admin) {
    header('Location: admin_login.php');
    exit();
}

$error   = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');

    if ($username === '' || $email === '') {
        $error = 'Username and email are required';
    } elseif ($password !== '' && $password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        // Start with base update (username + email)
        $params = [$username, $email, $admin['admin_id']];
        $types  = 'ssi';
        $sql    = "UPDATE admin SET username = ?, email = ?";

        // If password provided, include password_hash
        if ($password !== '') {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql   .= ", password_hash = ?";
            $params = [$username, $email, $password_hash, $admin['admin_id']];
            $types  = 'sssi';
        }

        $sql .= " WHERE admin_id = ?";

        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);

            if (mysqli_stmt_execute($stmt)) {
                $success = 'Profile updated successfully';

                // Security: refresh session ID after profile update (especially password update)
                session_regenerate_id(true);

                // Refresh admin data + session username
                $admin = getCurrentAdmin();
                $_SESSION['admin_name'] = $admin['username'];
            } else {
                $error = 'Update failed (username or email may already be in use)';
            }
        } else {
            $error = 'Database error: could not prepare statement';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile - Scanfit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{
            font-family:'Segoe UI',sans-serif;
            background:#0f172a;
            color:#e5e7eb;
            padding:2rem;
        }
        a{text-decoration:none;color:#38bdf8}
        a:hover{text-decoration:underline}
        .nav{margin-bottom:1rem;font-size:.85rem;color:#9ca3af}
        h1{font-size:1.8rem;margin-bottom:1rem}
        .card{
            max-width:600px;
            background:#020617;
            border-radius:16px;
            padding:1.8rem;
            border:1px solid #111827;
        }
        .form-group{margin-bottom:1rem}
        label{
            display:block;
            margin-bottom:.25rem;
            font-size:.9rem;
            color:#9ca3af;
        }
        input[type=text],input[type=email],input[type=password]{
            width:100%;
            padding:.6rem .7rem;
            border-radius:8px;
            border:1px solid #1f2937;
            background:#020617;
            color:#e5e7eb;
            font-size:.9rem;
        }
        .btn{
            padding:.6rem 1.4rem;
            border:none;
            border-radius:999px;
            font-size:.9rem;
            cursor:pointer;
            font-weight:600;
            display:inline-block;
        }
        .btn-primary{background:#38bdf8;color:#0f172a}
        .btn-secondary{background:#111827;color:#e5e7eb;margin-left:.6rem}
        .msg{
            margin-bottom:1rem;
            padding:.7rem .9rem;
            border-radius:10px;
            font-size:.85rem;
        }
        .msg-error{background:#b91c1c;color:#fee2e2}
        .msg-success{background:#14532d;color:#bbf7d0}
        .hint{font-size:.8rem;color:#6b7280;margin-top:.25rem}
    </style>
</head>
<body>
<div class="nav">
    <a href="admin_dashboard.php">← Back to dashboard</a>
</div>

<h1>Admin Profile</h1>

<div class="card">
    <?php if ($error): ?>
        <div class="msg msg-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="msg msg-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username"
                   value="<?php echo htmlspecialchars($admin['username']); ?>" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email"
                   value="<?php echo htmlspecialchars($admin['email']); ?>" required>
        </div>

        <div class="form-group">
            <label>New password (optional)</label>
            <input type="password" name="password" autocomplete="new-password">
            <div class="hint">Leave blank to keep current password</div>
        </div>

        <div class="form-group">
            <label>Confirm new password</label>
            <input type="password" name="confirm_password" autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary">Save profile</button>
        <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
