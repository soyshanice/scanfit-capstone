<?php
// Include database connection settings and establish a MySQL connection
require_once 'Connectdb.php';

/**
 * Fetch all active products associated with a given display gender label.
 * Maps display labels (Men/Women/Unisex) to internal gender names (Male/Female/Unisex).
 */
function getProductsByGender($genderDisplay) {
    global $conn;

    // Map human-friendly gender labels to stored gender names
    $genderMap = [
        'Men'   => 'Male',
        'Women' => 'Female',
        'Unisex'=> 'Unisex'
    ];

    // Resolve the gender name used in the database
    $genderName = isset($genderMap[$genderDisplay]) ? $genderMap[$genderDisplay] : $genderDisplay;

    // Select distinct active products for the given gender
    $sql = "
        SELECT DISTINCT p.*
        FROM product p
        INNER JOIN productgender pg ON p.product_id = pg.product_id
        INNER JOIN gender g ON pg.gender_id = g.gender_id
        WHERE g.name = ?
          AND p.status = 'ACTIVE'
        ORDER BY p.created_at DESC
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        // Log prepare error and return an empty result set
        error_log("Error preparing statement: " . mysqli_error($conn));
        return mysqli_query($conn, "SELECT * FROM product WHERE 1=0");
    }

    mysqli_stmt_bind_param($stmt, 's', $genderName);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

/**
 * Fetch active products by gender AND size (with stock > 0).
 * This powers: men.php?size=M and womens.php?size=L, etc.
 *
 * It supports size values being stored as:
 * - abbreviation: XS, S, M, L, XL, XXL
 * - name: Small, Medium, Large, etc.
 */
function getProductsByGenderAndSize($genderDisplay, $sizeInput) {
    global $conn;

    // 1) Normalize gender value (DB uses Male/Female; pages use Men/Women)
    $genderMap = [
        'Men'   => 'Male',
        'Women' => 'Female',
        'Unisex'=> 'Unisex',
        'Male'  => 'Male',
        'Female'=> 'Female'
    ];
    $genderName = $genderMap[$genderDisplay] ?? $genderDisplay;

    // 2) Normalize size input (accept "M" or "Medium" etc.)
    $sizeInput = trim($sizeInput);

    // Map abbreviation -> common full names (in case DB stores full names)
    $sizeMap = [
        'XS'  => 'Extra Small',
        'S'   => 'Small',
        'M'   => 'Medium',
        'L'   => 'Large',
        'XL'  => 'Extra Large',
        'XXL' => '2X Large'
    ];

    $sizeAbbr = strtoupper($sizeInput);
    $sizeFull = $sizeMap[$sizeAbbr] ?? $sizeInput;

    /**
     * Query:
     * - product -> productgender -> gender (Men/Women)
     * - product -> productvariant -> size (available sizes)
     * - stock_quantity > 0 ensures size exists and is in stock
     */
    $sql = "
        SELECT DISTINCT p.*
        FROM product p
        INNER JOIN productgender pg ON p.product_id = pg.product_id
        INNER JOIN gender g ON pg.gender_id = g.gender_id
        INNER JOIN productvariant pv ON pv.product_id = p.product_id
        INNER JOIN size s ON s.size_id = pv.size_id
        WHERE g.name = ?
          AND p.status = 'ACTIVE'
          AND pv.stock_quantity > 0
          AND (
                s.abbreviation = ?
                OR s.name = ?
                OR s.name = ?
          )
        ORDER BY p.created_at DESC
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Error preparing size filter statement: " . mysqli_error($conn));
        return mysqli_query($conn, "SELECT * FROM product WHERE 1=0");
    }

    // genderName, sizeAbbr, sizeFull, sizeInput
    mysqli_stmt_bind_param($stmt, 'ssss', $genderName, $sizeAbbr, $sizeFull, $sizeInput);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

/**
 * Fetch all variants (size/color, stock, price adjustment) for a given product.
 */
function getProductVariants($productId) {
    global $conn;

    $sql = "
        SELECT
            pv.variant_id,
            pv.sku AS variant_sku,
            pv.stock_quantity,
            pv.price_adjustment,
            s.name AS size_name,
            c.name AS colour_name
        FROM productvariant pv
        LEFT JOIN size s ON pv.size_id = s.size_id
        LEFT JOIN colour c ON pv.colour_id = c.colour_id
        WHERE pv.product_id = ?
        ORDER BY s.sort_order, c.name
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        // On failure return an empty productvariant result set
        return mysqli_query($conn, "SELECT * FROM productvariant WHERE 1=0");
    }

    mysqli_stmt_bind_param($stmt, 'i', $productId);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

/**
 * Retrieve a single active product by its ID.
 */
function getProductById($productId) {
    global $conn;

    $sql = "SELECT * FROM product WHERE product_id = ? AND status = 'ACTIVE' LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

/**
 * Check if a customer is currently logged in via session.
 */
function isLoggedIn() {
    return isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);
}

/**
 * Require a logged-in customer; if not logged in, redirect to login page.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // Save intended URL so user can be redirected back after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
}

// ---- ADMIN AUTH HELPERS ----

function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: admin_login.php');
        exit();
    }
}

// OPTIONAL: get current admin record
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    global $conn;
    $adminId = (int)$_SESSION['admin_id'];
    $sql = "SELECT * FROM admin WHERE admin_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $adminId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($res);
}

/**
 * Get the current logged-in customer ID from session, or null if not set.
 */
function getCustomerId() {
    return $_SESSION['customer_id'] ?? null;
}

/**
 * Fetch a customer's full record by their ID.
 */
function getCustomerInfo($customerId) {
    global $conn;

    $sql = "SELECT * FROM customer WHERE customer_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $customerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

/**
 * Locate an existing ACTIVE cart for the customer, or create a new one.
 */
function createOrGetCart($customerId) {
    global $conn;

    // Try to find active cart for this customer
    $sql = "SELECT cart_id FROM cart WHERE customer_id = ? AND status = 'ACTIVE' LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $customerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $cart = mysqli_fetch_assoc($result);

    if ($cart) {
        return $cart['cart_id'];
    }

    // No active cart found, create a new one
    $sql = "INSERT INTO cart (customer_id, created_at, status) VALUES (?, NOW(), 'ACTIVE')";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $customerId);
    mysqli_stmt_execute($stmt);
    return mysqli_insert_id($conn);
}

// Ensure a session is started before using session-based helpers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection configuration (duplicate of Connectdb, kept as-is for now)
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'capstonestoredb';

// Establish a mysqli connection using the above credentials
$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die('Database Connection Error: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');

function updateCustomerRecommendedSize($customerId, $gender, $recommendedSize) {
    global $conn;

    $sql = "
        UPDATE customer
        SET recommended_size = ?, recommended_gender = ?, size_updated_at = NOW()
        WHERE customer_id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'ssi', $recommendedSize, $gender, $customerId);
    return mysqli_stmt_execute($stmt);
}

function getCustomerRecommendedProfile($customerId) {
    global $conn;

    $sql = "
        SELECT recommended_size, recommended_gender, size_updated_at
        FROM customer
        WHERE customer_id = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $customerId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

/**
 * Get or create an ACTIVE cart and return its ID for a customer.
 */
function getOrCreateActiveCartId($customerId) {
    global $conn;

    // 1) Try to find existing ACTIVE cart
    $sql = "
        SELECT cart_id
        FROM cart
        WHERE customer_id = ?
          AND status = 'ACTIVE'
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 'i', $customerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        return (int)$row['cart_id'];
    }

    // 2) Create new cart if none exist
    $insert = "
        INSERT INTO cart (customer_id, status, created_at, updated_at)
        VALUES (?, 'ACTIVE', NOW(), NOW())
    ";
    $stmt2 = mysqli_prepare($conn, $insert);
    if (!$stmt2) {
        return null;
    }
    mysqli_stmt_bind_param($stmt2, 'i', $customerId);
    if (!mysqli_stmt_execute($stmt2)) {
        return null;
    }
    return mysqli_insert_id($conn);
}

/**
 * Add a product variant to the customer's cart, or increase quantity if it already exists.
 */
function addToCart($customerId, $productId, $variantId, $quantity) {
    global $conn;

    // Validate IDs and quantity before proceeding
    if ($customerId <= 0 || $productId <= 0 || $variantId <= 0 || $quantity <= 0) {
        return false;
    }

    // Verify that the variant belongs to the product, and fetch its stock
    $checkSql = "
        SELECT pv.stock_quantity
        FROM productvariant pv
        WHERE pv.variant_id = ?
          AND pv.product_id = ?
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $checkSql);
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'ii', $variantId, $productId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $variant = mysqli_fetch_assoc($res);
    if (!$variant) {
        return false;
    }

    // Get the active cart for this customer (or create one)
    $cartId = getOrCreateActiveCartId($customerId);
    if (!$cartId) {
        return false;
    }

    // Check if the variant is already in the cart
    $existingSql = "
        SELECT cart_item_id, quantity
        FROM cartitem
        WHERE cart_id = ?
          AND variant_id = ?
        LIMIT 1
    ";
    $stmt2 = mysqli_prepare($conn, $existingSql);
    if (!$stmt2) {
        return false;
    }
    mysqli_stmt_bind_param($stmt2, 'ii', $cartId, $variantId);
    mysqli_stmt_execute($stmt2);
    $existingRes = mysqli_stmt_get_result($stmt2);
    if ($row = mysqli_fetch_assoc($existingRes)) {
        // Item exists: update the quantity
        $newQty = $row['quantity'] + $quantity;
        $updateSql = "
            UPDATE cartitem
            SET quantity = ?
            WHERE cart_item_id = ?
        ";
        $stmt3 = mysqli_prepare($conn, $updateSql);
        if (!$stmt3) {
            return false;
        }
        mysqli_stmt_bind_param($stmt3, 'ii', $newQty, $row['cart_item_id']);
        return mysqli_stmt_execute($stmt3);
    } else {
        // Item not in cart: insert a new cartitem row
        $insertSql = "
            INSERT INTO cartitem (cart_id, variant_id, quantity, added_at)
            VALUES (?, ?, ?, NOW())
        ";
        $stmt4 = mysqli_prepare($conn, $insertSql);
        if (!$stmt4) {
            return false;
        }
        mysqli_stmt_bind_param($stmt4, 'iii', $cartId, $variantId, $quantity);
        return mysqli_stmt_execute($stmt4);
    }
}

/**
 * Retrieve all active cart items for a customer, including product and variant details.
 */
function getCartItems($customerId) {
    global $conn;

    $sql = "
        SELECT
            ci.cart_item_id,
            ci.quantity,
            ci.variant_id,
            ca.cart_id,
            pv.stock_quantity,
            pv.price_adjustment,
            p.name  AS product_name,
            p.sku   AS product_sku,
            p.base_price,
            s.name  AS size_name,
            c.name  AS colour_name
        FROM cart ca
        INNER JOIN cartitem ci       ON ca.cart_id = ci.cart_id
        INNER JOIN productvariant pv ON ci.variant_id = pv.variant_id
        INNER JOIN product p         ON pv.product_id = p.product_id
        LEFT JOIN size   s           ON pv.size_id   = s.size_id
        LEFT JOIN colour c           ON pv.colour_id = c.colour_id
        WHERE ca.customer_id = ?
          AND ca.status = 'ACTIVE'
        ORDER BY ci.added_at DESC
    ";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        // Fallback to empty result if prepare fails
        return mysqli_query($conn, "SELECT * FROM cartitem WHERE 1=0");
    }
    mysqli_stmt_bind_param($stmt, 'i', $customerId);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

/**
 * Calculate the monetary total of the customer's active cart.
 */
function getCartTotal($customerId) {
    global $conn;

    $sql = "
        SELECT
            IFNULL(SUM( (p.base_price + IFNULL(pv.price_adjustment,0)) * ci.quantity ), 0) AS total
        FROM cart ca
        INNER JOIN cartitem ci ON ca.cart_id = ci.cart_id
        INNER JOIN productvariant pv ON ci.variant_id = pv.variant_id
        INNER JOIN product p ON pv.product_id = p.product_id
        WHERE ca.customer_id = ?
          AND ca.status = 'ACTIVE'
    ";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return 0;
    }
    mysqli_stmt_bind_param($stmt, 'i', $customerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['total'] ?? 0;
}

/**
 * Remove a specific cart item that belongs to the given customer.
 */
function removeFromCart($cartItemId, $customerId) {
    global $conn;

    $sql = "
        DELETE ci FROM cartitem ci
        INNER JOIN cart ca ON ci.cart_id = ca.cart_id
        WHERE ci.cart_item_id = ? AND ca.customer_id = ?
    ";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'ii', $cartItemId, $customerId);
    return mysqli_stmt_execute($stmt);
}

/**
 * Update the quantity for a cart item, or remove it if quantity is zero or less.
 */
function updateCartItemQuantity($cartItemId, $customerId, $quantity) {
    global $conn;

    // If quantity is zero or negative, treat it as a remove operation
    if ($quantity <= 0) {
        return removeFromCart($cartItemId, $customerId);
    }

    $sql = "
        UPDATE cartitem ci
        INNER JOIN cart ca ON ci.cart_id = ca.cart_id
        SET ci.quantity = ?
        WHERE ci.cart_item_id = ? AND ca.customer_id = ?
    ";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'iii', $quantity, $cartItemId, $customerId);
    return mysqli_stmt_execute($stmt);
}

/**
 * Return the total count of items (sum of quantities) in the customer's active cart.
 */
function getCartItemCount($customerId) {
    global $conn;

    $sql = "
        SELECT IFNULL(SUM(ci.quantity), 0) AS total_items
        FROM cart ca
        INNER JOIN cartitem ci ON ca.cart_id = ci.cart_id
        WHERE ca.customer_id = ? AND ca.status = 'ACTIVE'
    ";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return 0;
    }
    mysqli_stmt_bind_param($stmt, 'i', $customerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['total_items'] ?? 0;
}

/**
 * Search active products by name, description or SKU (basic keyword search).
 */
function searchProducts($query) {
    global $conn;

    $searchTerm = '%' . $query . '%';
    $sql = "
        SELECT DISTINCT p.*
        FROM product p
        WHERE p.status = 'ACTIVE'
          AND (p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)
        ORDER BY p.name
        LIMIT 50
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return mysqli_query($conn, "SELECT * FROM product WHERE 1=0");
    }

    mysqli_stmt_bind_param($stmt, 'sss', $searchTerm, $searchTerm, $searchTerm);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

/**
 * Fetch all orders for a customer, including associated payment method (if any).
 */
function getCustomerOrders($customerId) {
    global $conn;

    $sql = "
        SELECT o.*, p.method_name AS payment_method
        FROM `order` o
        LEFT JOIN payment p ON o.order_id = p.order_id
        WHERE o.customer_id = ?
        ORDER BY o.created_at DESC
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return mysqli_query($conn, "SELECT * FROM `order` WHERE 1=0");
    }

    mysqli_stmt_bind_param($stmt, 'i', $customerId);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

/**
 * Attempt to cancel an order for a customer, respecting status rules.
 */
function cancelOrder($orderId, $customerId) {
    global $conn;

    if ($orderId <= 0 || $customerId <= 0) {
        return false;
    }

    // Only allow cancelling own order that is not already delivered or cancelled
    $sql = "
        SELECT order_id, status
        FROM `order`
        WHERE order_id = ? AND customer_id = ?
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'ii', $orderId, $customerId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($res);

    if (!$order) {
        return false;
    }

    if (in_array($order['status'], ['DELIVERED', 'CANCELLED'], true)) {
        // Prevent cancelling orders that are already delivered or cancelled
        return false;
    }

    // Update order status to CANCELLED for this customer
    $update = "
        UPDATE `order`
        SET status = 'CANCELLED', updated_at = NOW()
        WHERE order_id = ? AND customer_id = ?
        LIMIT 1
    ";
    $stmt2 = mysqli_prepare($conn, $update);
    if (!$stmt2) {
        return false;
    }
    mysqli_stmt_bind_param($stmt2, 'ii', $orderId, $customerId);
    return mysqli_stmt_execute($stmt2);
}

/**
 * Get all line items belonging to a specific order, with product and variant details.
 */
function getOrderItems($orderId) {
    global $conn;

    $sql = "
        SELECT
            oi.*,
            p.name AS product_name,
            p.sku AS product_sku,
            s.name AS size_name,
            c.name AS colour_name
        FROM orderitem oi
        INNER JOIN productvariant pv ON oi.variant_id = pv.variant_id
        INNER JOIN product p ON pv.product_id = p.product_id
        LEFT JOIN size s ON pv.size_id = s.size_id
        LEFT JOIN colour c ON pv.colour_id = c.colour_id
        WHERE oi.order_id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return mysqli_query($conn, "SELECT * FROM orderitem WHERE 1=0");
    }

    mysqli_stmt_bind_param($stmt, 'i', $orderId);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

/**
 * Fetch all available sizes, ordered by display sort order.
 */
function getAllSizes() {
    global $conn;

    $sql = "SELECT * FROM size ORDER BY sort_order";
    return mysqli_query($conn, $sql);
}

/**
 * Fetch all body type definitions from reference table.
 */
function getAllBodyTypes() {
    global $conn;

    $sql = "SELECT * FROM bodytype ORDER BY bodytype_id";
    return mysqli_query($conn, $sql);
}

/**
 * Save a new body measurement snapshot for a given customer.
 */
function saveBodyMeasurement($customerId, $height, $weight, $bodytypeId = null, $chest = null, $waist = null, $hips = null) {
    global $conn;

    $sql = "
        INSERT INTO bodymeasurement
        (customer_id, height_cm, weight_kg, bodytype_id, chest_cm, waist_cm, hips_cm, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'iddiddd', $customerId, $height, $weight, $bodytypeId, $chest, $waist, $hips);
    return mysqli_stmt_execute($stmt);
}

/**
 * Retrieve the most recent body measurement entry for a customer.
 */
function getLatestBodyMeasurement($customerId) {
    global $conn;

    $sql = "
        SELECT * FROM bodymeasurement
        WHERE customer_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $customerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}
?>
