<?php
// Include database connection and shared helper functions
require_once 'Connectdb.php';
require_once 'functions.php';

// Get product ID from query string, cast to int, or default to 0 if missing
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If ID is invalid or not provided, return 404 and stop script
if ($id <= 0) {
    http_response_code(404);
    echo "Product not found.";
    exit();
}

// Prepare SQL query to fetch a single active product and its associated gender
$sql = "
    SELECT p.*, g.name AS gender_name
    FROM product p
    LEFT JOIN productgender pg ON p.product_id = pg.product_id
    LEFT JOIN gender g        ON pg.gender_id = g.gender_id
    WHERE p.product_id = ?
      AND p.status = 'ACTIVE'
    LIMIT 1
";
// Initialize prepared statement using existing DB connection
$stmt = mysqli_prepare($conn, $sql);
// If statement preparation fails, return 500 error and exit
if (!$stmt) {
    http_response_code(500);
    echo "Failed to load product.";
    exit();
}
// Bind product ID parameter as integer and execute the query
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
// Retrieve result set and fetch the product row as an associative array
$result  = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

// If no matching product found, return 404 and exit
if (!$product) {
    http_response_code(404);
    echo "Product not found.";
    exit();
}

// Fetch variants into an array so we can use them in the form
$variants = [];
// Call helper function to get product variants for this product
$raw = getProductVariants($product['product_id']);
// Loop through each variant row and store in $variants array
while ($v = mysqli_fetch_assoc($raw)) {
    $variants[] = $v;
}

// flash messages from add_to_cart.php
// Read success and error flash messages from session if set
$successMsg = $_SESSION['success'] ?? null;
$errorMsg   = $_SESSION['error']   ?? null;
// Clear flash message values so they are shown only once
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Dynamic page title using product name -->
    <title><?php echo htmlspecialchars($product['name']); ?> - Scanfit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Reset margin, padding and set box sizing */
        *{margin:0;padding:0;box-sizing:border-box}

        /* Global body styles including background gradient and typography */
        body{
            font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
            background:linear-gradient(135deg,#f5f7fa 0%,#c3cfe2 100%);
            color:#333;
            min-height:100vh;
        }

        /* Hero header section with background image and overlay gradient */
        .hero-section{
            background:linear-gradient(rgba(102,126,234,.9),rgba(118,75,162,.9)),
                       url('https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=2000&q=80');
            background-size:cover;
            background-position:center;
            height:45vh;
            display:flex;
            align-items:center;
            justify-content:center;
            text-align:center;
            color:#fff;
        }

        /* Container for hero text content */
        .hero-content{
            max-width:800px;
            padding:0 1.5rem;
        }

        /* Main hero heading styling */
        .hero-content h1{
            font-size:clamp(2rem,4.5vw,3.2rem);
            font-weight:800;
            margin-bottom:.8rem;
            text-shadow:0 4px 20px rgba(0,0,0,.5);
        }

        /* Hero subtitle text styling */
        .hero-content p{
            font-size:clamp(1rem,2.3vw,1.3rem);
            opacity:.95;
        }

        /* Wrapper for main content area */
        .page-wrapper{
            max-width:1200px;
            margin:0 auto;
            padding:3rem 1.5rem 4rem;
        }

        /* Flash message base styling */
        .flash-msg{
            margin-bottom:1.5rem;
            padding:1rem 1.2rem;
            border-radius:12px;
            font-weight:600;
            text-align:center;
        }
        /* Success flash background and text color */
        .flash-success{
            background:#28a745;
            color:#fff;
        }
        /* Error flash background and text color */
        .flash-error{
            background:#ff4444;
            color:#fff;
        }

        /* Grid layout for product image and info columns */
        .product-layout{
            display:grid;
            grid-template-columns:minmax(0,1.1fr) minmax(0,1fr);
            gap:2.5rem;
            align-items:flex-start;
        }

        /* Card container for product image */
        .product-image-card{
            background:#fff;
            border-radius:24px;
            overflow:hidden;
            box-shadow:0 18px 40px rgba(0,0,0,.12);
        }

        /* Wrapper controlling image height and centering */
        .product-image-wrapper{
            background:#f0f4f8;
            height:420px;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        /* Product image sizing and object-fit */
        .product-image-wrapper img{
            width:100%;
            height:100%;
            object-fit:cover;
        }

        /* Pill-style label (e.g., Product Detail) */
        .pill{
            display:inline-flex;
            align-items:center;
            padding:.3rem .9rem;
            border-radius:999px;
            font-size:.8rem;
            font-weight:600;
            letter-spacing:.08em;
            text-transform:uppercase;
            background:#e8ecff;
            color:#4b5cd7;
        }

        /* Card container for product information and form */
        .product-info-card{
            background:#fff;
            border-radius:24px;
            padding:2rem;
            box-shadow:0 18px 40px rgba(0,0,0,.08);
        }

        /* Product title typography */
        .product-title{
            font-size:1.9rem;
            font-weight:800;
            color:#2c3e50;
            margin-bottom:.4rem;
        }

        /* SKU text styling */
        .sku-text{
            font-size:.9rem;
            color:#8b95b1;
            margin-bottom:1rem;
        }

        /* Row for price and supporting text */
        .price-row{
            display:flex;
            align-items:baseline;
            gap:.8rem;
            margin-bottom:1.4rem;
        }

        /* Main price styling */
        .price-main{
            font-size:2rem;
            font-weight:800;
            color:#667eea;
        }

        /* Price note (tax/shipping) styling */
        .price-note{
            font-size:.9rem;
            color:#8b95b1;
        }

        /* Row of meta chips below price */
        .meta-row{
            display:flex;
            flex-wrap:wrap;
            gap:.6rem;
            margin-bottom:1.5rem;
        }

        /* Individual pill-like meta chip (returns, checkout, etc.) */
        .meta-chip{
            background:#f8f9fa;
            border-radius:999px;
            padding:.35rem .9rem;
            font-size:.85rem;
            color:#555;
            border:1px solid #e1e4e8;
        }

        /* Product description text styling */
        .product-description{
            font-size:.98rem;
            line-height:1.8;
            color:#555;
            margin-bottom:1.8rem;
        }

        /* Form group container spacing */
        .form-group{
            margin-bottom:1.3rem;
        }

        /* Form label styling */
        .form-group label{
            display:block;
            font-weight:600;
            margin-bottom:.5rem;
            color:#2c3e50;
            font-size:.95rem;
        }

        /* Base styles for select and numeric input fields */
        select,
        input[type="number"]{
            width:100%;
            padding:.9rem 1rem;
            border-radius:12px;
            border:2px solid #e1e4e8;
            font-size:.95rem;
            outline:none;
            transition:border-color .25s, box-shadow .25s;
            background:#fff;
        }

        /* Focus state styles for form controls */
        select:focus,
        input[type="number"]:focus{
            border-color:#667eea;
            box-shadow:0 0 0 3px rgba(102,126,234,.2);
        }

        /* Layout for quantity row: input and helper text */
        .qty-row{
            display:flex;
            gap:1rem;
            align-items:center;
        }

        /* Quantity helper text styling */
        .qty-row small{
            color:#8b95b1;
            font-size:.8rem;
        }

        /* Add to Cart button styling */
        .add-to-cart-btn{
            width:100%;
            padding:1rem 1.2rem;
            margin-top:1rem;
            border:none;
            border-radius:16px;
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            color:#fff;
            font-size:1rem;
            font-weight:700;
            cursor:pointer;
            box-shadow:0 14px 30px rgba(102,126,234,.35);
            transition:transform .18s ease, box-shadow .18s ease;
        }

        /* Add to Cart hover effect */
        .add-to-cart-btn:hover{
            transform:translateY(-2px);
            box-shadow:0 18px 40px rgba(102,126,234,.45);
        }

        /* Badge shown when variants are limited or unavailable */
        .badge-out{
            display:inline-flex;
            align-items:center;
            gap:.4rem;
            padding:.35rem .8rem;
            border-radius:999px;
            background:#fff4f4;
            color:#e55353;
            font-size:.8rem;
            margin-top:.6rem;
        }

        /* Small helper note below variants dropdown */
        .variant-note{
            font-size:.85rem;
            color:#8b95b1;
            margin-top:.3rem;
        }

        /* Box shown when product has no variants configured */
        .no-variants-box{
            padding:1rem 1.2rem;
            border-radius:14px;
            background:#fff4e6;
            color:#b55a00;
            font-size:.9rem;
            margin:1rem 0 1.5rem;
        }

        /* Section heading label above product info */
        .section-heading{
            font-size:1rem;
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:.12em;
            color:#9ca3af;
            margin-bottom:.6rem;
        }

        /* Highlighted span inside section heading */
        .section-heading span{
            color:#667eea;
        }

        /* Responsive layout adjustments for tablets */
        @media (max-width: 900px){
            .product-layout{
                grid-template-columns:1fr;
            }
            .product-image-wrapper{
                height:320px;
            }
        }

        /* Responsive layout adjustments for small screens */
        @media (max-width: 600px){
            .page-wrapper{
                padding:2rem 1rem 3rem;
            }
            .product-info-card{
                padding:1.6rem;
            }
        }
    </style>
</head>
<body>

<!-- Include main navigation bar -->
<?php include 'navbar.php'; ?>

<!-- Top hero section introducing the product detail view -->
<section class="hero-section">
    <div class="hero-content">
        <p class="pill">Product Detail</p>
        <h1><?php echo htmlspecialchars($product['name']); ?></h1>
        <p>Explore fit, style, and sizing before adding this item to your Scanfit cart.</p>
    </div>
</section>

<!-- Main page container for product content and messages -->
<div class="page-wrapper">

    <!-- Render success message if present -->
    <?php if ($successMsg): ?>
        <div class="flash-msg flash-success">
            <?php echo htmlspecialchars($successMsg); ?>
        </div>
    <?php endif; ?>

    <!-- Render error message if present -->
    <?php if ($errorMsg): ?>
        <div class="flash-msg flash-error">
            <?php echo htmlspecialchars($errorMsg); ?>
        </div>
    <?php endif; ?>

    <!-- Two-column layout: product image (left) and info/form (right) -->
    <div class="product-layout">

        <!-- LEFT: IMAGE -->
        <div class="product-image-card">
            <div class="product-image-wrapper">
                <img
                    src="images/<?php echo htmlspecialchars($product['sku']); ?>.jpg"
                    alt="<?php echo htmlspecialchars($product['name']); ?>"
                    onerror="this.src='https://via.placeholder.com/700x450/f8f9fa/999?text=Product+Image';"
                >
            </div>
        </div>

        <!-- RIGHT: INFO + FORM -->
        <div class="product-info-card">
            <!-- Section label showing context (Scanfit Product) -->
            <div class="section-heading"><span>Scanfit</span> Product</div>

            <!-- Product name as main heading -->
            <h2 class="product-title">
                <?php echo htmlspecialchars($product['name']); ?>
            </h2>

            <!-- SKU line, optionally including gender if available -->
            <div class="sku-text">
                SKU: <?php echo htmlspecialchars($product['sku']); ?>
                <?php if (!empty($product['gender_name'])): ?>
                    • <?php echo htmlspecialchars($product['gender_name']); ?>
                <?php endif; ?>
            </div>

            <!-- Display base product price and explanatory note -->
            <div class="price-row">
                <div class="price-main">
                    $<?php echo number_format($product['base_price'], 2); ?>
                </div>
                <div class="price-note">Tax and shipping calculated at checkout</div>
            </div>

            <!-- Meta chips for reassurance (returns, checkout, processing) -->
            <div class="meta-row">
                <span class="meta-chip">Easy returns</span>
                <span class="meta-chip">Secure checkout</span>
                <span class="meta-chip">Fast processing</span>
            </div>

            <!-- Product description block; fallback text if description is empty -->
            <?php if (!empty($product['description'])): ?>
                <p class="product-description">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </p>
            <?php else: ?>
                <p class="product-description">
                    Discover a versatile wardrobe essential designed for comfort and everyday wear.
                </p>
            <?php endif; ?>

            <!-- Add to Cart form posting to cart handler -->
            <form method="POST" action="add_to_cart.php">
                <!-- Hidden field with product ID used by add_to_cart.php -->
                <input type="hidden" name="product_id"
                       value="<?php echo (int)$product['product_id']; ?>">

                <!-- Variant selector shown only when variants exist -->
                <?php if (!empty($variants)): ?>
                    <div class="form-group">
                        <label for="variant_id">Select size &amp; colour</label>
                        <select name="variant_id" id="variant_id" required>
                            <?php foreach ($variants as $v): ?>
                                <option value="<?php echo (int)$v['variant_id']; ?>">
                                    <?php
                                    // Build a human-readable label using size and colour names
                                    $parts = [];
                                    if (!empty($v['size_name']))   $parts[]   = 'Size: '.$v['size_name'];
                                    if (!empty($v['colour_name'])) $parts[]   = 'Color: '.$v['colour_name'];
                                    echo htmlspecialchars(implode(' • ', $parts) ?: 'Standard');
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="variant-note">
                            Sizes are recommended based on your BMI profile in Scanfit.
                        </p>
                    </div>
                <?php else: ?>
                    <!-- Message block when no variants are configured -->
                    <div class="no-variants-box">
                        This product is currently available in one standard option. Variants will be added soon.
                    </div>
                <?php endif; ?>

                <!-- Quantity selector for the product -->
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <div class="qty-row">
                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            value="1"
                            min="1"
                        >
                        <small>Limit applies based on available stock.</small>
                    </div>
                </div>

                <!-- Primary submit button to add item to cart -->
                <button type="submit" class="add-to-cart-btn">
                    Add to Cart
                </button>

                <!-- Badge hinting that configuration options are limited -->
                <?php if (empty($variants)): ?>
                    <div class="badge-out">
                        ⚠ Limited configuration available
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

</body>
</html>
