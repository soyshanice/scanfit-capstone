<?php
// men.php

// Include database connection and shared helper functions
require_once 'Connectdb.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// OPTIONAL: quick fix to hide shoes if you don’t want to sell shoes
$HIDE_SHOES = true;

// Read size filter from URL (ex: men.php?size=M)
$sizeFilter = trim($_GET['size'] ?? '');

// Fetch products
$sizeUnavailable = false;

if ($sizeFilter !== '') {
    // You must have added this function in functions.php in Step 2D:
    // getProductsByGenderAndSize('Men', $sizeFilter)
    $products_result = getProductsByGenderAndSize('Men', $sizeFilter);

    // If no products are found for that size, show message and fallback to all men products
    if ($products_result && mysqli_num_rows($products_result) === 0) {
        $sizeUnavailable = true;
        $products_result = getProductsByGender('Men');
    }
} else {
    $products_result = getProductsByGender('Men');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Men's Collection - Scanfit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{
            font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
            background:#f8f9fa;color:#333;line-height:1.6
        }
        .hero-section {
            height:40vh;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            background:
                linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),
                url('images/category_mens') center/cover no-repeat;
            color:#fff;
            text-align:center;
        }
        .hero-section h1{font-size:2.5rem;margin-bottom:.5rem}
        .hero-section p{opacity:.9}

        .products-section{
            max-width:1400px;margin:0 auto;padding:3rem 2rem
        }

        /* Notice box */
        .notice{
            max-width:1400px;
            margin: 0 auto 1.5rem auto;
            background:#fff3cd;
            color:#856404;
            border:1px solid #ffeeba;
            padding:14px 16px;
            border-radius:14px;
            text-align:center;
            font-weight:600;
        }
        .notice a{
            color:#5b4aa2;
            font-weight:800;
            text-decoration:none;
            margin: 0 10px;
        }

        .subhead{
            max-width:1400px;
            margin: 0 auto 1.5rem auto;
            color:#555;
            padding: 0 2rem;
        }

        .products-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
            gap:2rem
        }
        .product-card{
            background:#fff;border-radius:20px;overflow:hidden;
            box-shadow:0 10px 30px rgba(0,0,0,.1);
            transition:all .3s ease
        }
        .product-card:hover{
            transform:translateY(-8px);
            box-shadow:0 20px 40px rgba(0,0,0,.15)
        }
        .product-image{
            height:260px;background:#f0f2f5;overflow:hidden
        }
        .product-image img{
            width:100%;height:100%;object-fit:cover;
            transition:transform .4s ease
        }
        .product-card:hover .product-image img{transform:scale(1.08)}
        .product-info{padding:1.5rem}
        .product-name{font-size:1.3rem;font-weight:600;margin-bottom:.5rem}
        .product-price{font-size:1.4rem;font-weight:700;color:#667eea;margin-bottom:.8rem}
        .product-sizes{margin-bottom:1rem}
        .size-tag{
            display:inline-block;background:#f8f9fa;color:#555;
            padding:.3rem .7rem;border-radius:999px;
            border:1px solid #e1e4e8;font-size:.85rem;margin-right:.3rem;
            margin-bottom:.3rem
        }
        .add-to-cart-btn{
            width:100%;
            padding:.9rem;
            border-radius:12px;
            border:none;
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            color:#fff;
            font-weight:600;
            cursor:pointer;
            text-decoration:none;
            display:inline-block;
            text-align:center;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<section class="hero-section">
    <h1>Men's Collection</h1>
    <p>Premium quality clothing for the modern man</p>
</section>

<!-- Messages -->
<?php if ($sizeUnavailable): ?>
    <div class="notice">
        Sorry! Your recommended size (<strong><?php echo htmlspecialchars($sizeFilter); ?></strong>) is currently unavailable.
        Please select a different size or check back later as we restock.
        <div style="margin-top:10px;">
            <a href="men.php">View all sizes</a>
            <a href="bmi_calculator.php">Recalculate BMI</a>
        </div>
    </div>
<?php elseif ($sizeFilter !== ''): ?>
    <div class="subhead">
        Showing items available in size <strong><?php echo htmlspecialchars($sizeFilter); ?></strong>.
    </div>
<?php else: ?>
    <div class="subhead">
        Browse all available items.
    </div>
<?php endif; ?>

<section class="products-section">
    <?php if ($products_result && mysqli_num_rows($products_result) > 0): ?>
        <div class="products-grid">
            <?php while ($product = mysqli_fetch_assoc($products_result)): ?>

                <?php
                // OPTIONAL: hide shoes quickly (until you remove them from DB)
                if ($HIDE_SHOES) {
                    $nameLower = strtolower($product['name'] ?? '');
                    if (strpos($nameLower, 'shoe') !== false || strpos($nameLower, 'sneaker') !== false || strpos($nameLower, 'boot') !== false) {
                        continue;
                    }
                }

                // Fetch all size variants for the current product
                $variants_result = getProductVariants($product['product_id']);
                $sizes = [];

                while ($variant = mysqli_fetch_assoc($variants_result)) {
                    if (!empty($variant['size_name'])) {
                        $sizes[] = $variant['size_name'];
                    }
                }

                // If we are filtering by size, optionally hide products that do not show the filtered size in their size badges
                // (This is a visual consistency check; your SQL filter should already handle it.)
                if ($sizeFilter !== '' && !in_array($sizeFilter, $sizes)) {
                    // If your size tags are "Medium" instead of "M", comment this out.
                    // continue;
                }
                ?>

                <div class="product-card">
                    <div class="product-image">
                        <img src="images/<?php echo htmlspecialchars($product['sku']); ?>.jpg"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='https://via.placeholder.com/300x260/f8f9fa/999?text=👕'">
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-price">
                            $<?php echo number_format((float)$product['base_price'], 2); ?>
                        </div>

                        <?php if (!empty($sizes)): ?>
                            <div class="product-sizes">
                                <?php foreach (array_unique($sizes) as $size): ?>
                                    <span class="size-tag"><?php echo htmlspecialchars($size); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <a href="product.php?id=<?php echo (int)$product['product_id']; ?>" class="add-to-cart-btn">
                            View Details
                        </a>
                    </div>
                </div>

            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>No Men's products available yet.</p>
    <?php endif; ?>
</section>
</body>
</html>
