<?php
// product_add_admin.php
require_once 'functions.php';
requireAdminLogin();
global $conn;

// Fetch categories and genders for dropdowns
$categories = mysqli_query($conn, "SELECT category_id, name FROM category ORDER BY name");
$genders    = mysqli_query($conn, "SELECT gender_id, name FROM gender ORDER BY name");

$error = null;

// Handle POST: insert product + mapping rows
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $sku         = trim($_POST['sku'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $base_price  = isset($_POST['base_price']) ? (float)$_POST['base_price'] : 0;
    $status      = $_POST['status'] ?? 'ACTIVE';
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $gender_id   = isset($_POST['gender_id']) ? (int)$_POST['gender_id'] : 0;

    if ($name === '' || $sku === '' || $base_price <= 0 || $category_id <= 0 || $gender_id <= 0) {
        $error = 'Name, SKU, price, category, and gender are required';
    } else {
        // Insert product
        $sql = "INSERT INTO product (name, sku, description, base_price, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                'sssds',
                $name,
                $sku,
                $description,
                $base_price,
                $status
            );
            if (mysqli_stmt_execute($stmt)) {
                $product_id = mysqli_insert_id($conn);

                // Map to category
                $sqlCat = "INSERT INTO productcategory (product_id, category_id) VALUES (?, ?)";
                $stmtCat = mysqli_prepare($conn, $sqlCat);
                mysqli_stmt_bind_param($stmtCat, 'ii', $product_id, $category_id);
                mysqli_stmt_execute($stmtCat);

                // Map to gender
                $sqlGen = "INSERT INTO productgender (product_id, gender_id) VALUES (?, ?)";
                $stmtGen = mysqli_prepare($conn, $sqlGen);
                mysqli_stmt_bind_param($stmtGen, 'ii', $product_id, $gender_id);
                mysqli_stmt_execute($stmtGen);

                // Redirect back to products list
                header('Location: products_admin.php');
                exit();
            } else {
                $error = 'Failed to insert product (SKU might already exist)';
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
    <title>Add Product - Admin</title>
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
            max-width:650px;
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

<h1>Add Product</h1>

<div class="card">
    <?php if ($error): ?>
        <div class="msg msg-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name"
                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
        </div>

        <div class="row">
            <div class="form-group">
                <label>SKU</label>
                <input type="text" name="sku"
                       value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Base price (USD)</label>
                <input type="number" step="0.01" name="base_price"
                       value="<?php echo htmlspecialchars($_POST['base_price'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="row">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" required>
                    <option value="">Select category</option>
                    <?php while ($c = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?php echo (int)$c['category_id']; ?>"
                            <?php echo (isset($_POST['category_id']) && (int)$_POST['category_id'] === (int)$c['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Gender</label>
                <select name="gender_id" required>
                    <option value="">Select gender</option>
                    <?php while ($g = mysqli_fetch_assoc($genders)): ?>
                        <option value="<?php echo (int)$g['gender_id']; ?>"
                            <?php echo (isset($_POST['gender_id']) && (int)$_POST['gender_id'] === (int)$g['gender_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($g['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="ACTIVE" <?php echo (($_POST['status'] ?? 'ACTIVE')==='ACTIVE')?'selected':''; ?>>ACTIVE</option>
                <option value="INACTIVE" <?php echo (($_POST['status'] ?? '')==='INACTIVE')?'selected':''; ?>>INACTIVE</option>
                <option value="OUT_OF_STOCK" <?php echo (($_POST['status'] ?? '')==='OUT_OF_STOCK')?'selected':''; ?>>OUT OF STOCK</option>
            </select>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Add product</button>
        <a href="products_admin.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
