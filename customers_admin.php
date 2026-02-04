<?php
require_once 'functions.php';
requireAdminLogin();
global $conn;

$sql = "SELECT * FROM customer ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Customers</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Segoe UI',sans-serif;background:#0f172a;color:#e5e7eb;padding:2rem}
        a{text-decoration:none;color:#38bdf8}
        a:hover{text-decoration:underline}
        .nav{margin-bottom:1rem;font-size:.85rem;color:#9ca3af}
        .nav a{color:#38bdf8}
        h1{font-size:1.8rem;margin-bottom:1rem}
        table{width:100%;border-collapse:collapse;background:#020617;border-radius:12px;overflow:hidden}
        th,td{padding:.75rem 1rem;font-size:.9rem;border-bottom:1px solid #111827;text-align:left}
        th{background:#020617;font-weight:600;color:#9ca3af}
        tr:nth-child(even){background:#020617}
    </style>
</head>
<body>
<div class="nav">
    <a href="admin_dashboard.php">← Dashboard</a>
</div>

<h1>Customers</h1>

<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Created</th>
    </tr>
    </thead>
    <tbody>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?php echo (int)$row['customer_id']; ?></td>
            <td><?php echo htmlspecialchars($row['first_name'].' '.$row['last_name']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['phone']); ?></td>
            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</body>
</html>
