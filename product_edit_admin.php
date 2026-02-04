<?php
// product_edit_admin.php
require_once 'functions.php';
requireAdminLogin();
global $conn;

// 1) Get product id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: products_admin.php');
    exit();
}

$error = null;

// 2) Handle POST – perform UPDATE then redirect
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $sku         = trim($_POST['sku'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $base_price  = isset($_POST['base_price']) ? (float)$_POST['base_price'] : 0;
    $status      = $_POST['status'] ?? 'ACTIVE';

    if ($name === '' || $sku === '' || $base_price <= 0) {
        $error = 'Name, SKU and base price are required';
    } else {
        $sql = "UPDATE product
                SET name = ?, sku = ?, description = ?, base_price = ?, status = ?
                WHERE product_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            // IMPORTANT: NO SPACES in 'sssdsi'
            mysqli_stmt_bind_param(
                $stmt,
                'sssdsi',
                $name,
                $sku,
                $description,
                $base_price,
                $status,
                $id
            );
            mysqli_stmt_execute($stmt);

            // On success, go back to products list
            header('Location: products_admin.php');
            exit();
        } else {
            $error = 'Database error: could not prepare statement';
        }
    }
}

// 3) Fetch product to populate the form
$sql = "SELECT * FROM product WHERE product_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res     = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($res);

if (!$product) {
    header('Location: products_admin.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product - Admin</title>
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
        input[type=text],input[type=number],textarea,select{
            width:100%;
            padding:.6rem .7rem;
            border-radius:8px;
            border:1px solid #1f2937;
            background:#020617;
            color:#e5e7eb;
            font-size:.9rem;
        }
        textarea{min-height:100px;resize:vertical}
        .row{display:flex;gap:1rem}
        .row .form-group{flex:1}
        .btn{
            padding:.6rem 1.4rem;
            border:none;
            border-radius:999px;
            font-size:.9rem;
            cursor:pointer;
            font-weight:600;
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
    </style>
</head>
<body>
<div class="nav">
    <a href="products_admin.php">← Back to products</a>
</div>

<h1>Edit Product #<?php echo (int)$product['product_id']; ?></h1>

<div class="card">
    <?php if ($error): ?>
        <div class="msg msg-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name"
                   value="<?php echo htmlspecialchars($product['name']); ?>" required>
        </div>

        <div class="row">
            <div class="form-group">
                <label>SKU</label>
                <input type="text" name="sku"
                       value="<?php echo htmlspecialchars($product['sku']); ?>" required>
            </div>
            <div class="form-group">
                <label>Base price (USD)</label>
                <input type="number" step="0.01" name="base_price"
                       value="<?php echo htmlspecialchars($product['base_price']); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="ACTIVE" <?php echo $product['status']==='ACTIVE'?'selected':''; ?>>ACTIVE</option>
                <option value="INACTIVE" <?php echo $product['status']==='INACTIVE'?'selected':''; ?>>INACTIVE</option>
                <option value="OUT_OF_STOCK" <?php echo $product['status']==='OUT_OF_STOCK'?'selected':''; ?>>OUT OF STOCK</option>
            </select>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description"><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="products_admin.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
