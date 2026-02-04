<?php
// Only load shared functions if isLoggedIn() has not already been defined
if (!function_exists('isLoggedIn')) {
    require_once 'functions.php';
}

// Initialize cart count and customer info defaults
$cartCount = 0;
$customerInfo = null;

// If a customer is logged in, fetch their ID, cart item count, and profile info
if (isLoggedIn()) {
    $customerId = getCustomerId();
    $cartCount = getCartItemCount($customerId);
    $customerInfo = getCustomerInfo($customerId);
}
?>
<nav style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:1rem 0;position:sticky;top:0;z-index:1000;box-shadow:0 4px 12px rgba(0,0,0,.15)">
    <div style="max-width:1400px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;padding:0 2rem">
        <!-- Brand/logo linking back to home page -->
        <a href="index.php" style="font-size:1.4rem;font-weight:800;color:#fff;text-decoration:none;letter-spacing:1px">
            SCANFIT
        </a>

        <!-- Main navigation links, search form, and user/cart controls -->
        <div style="display:flex;gap:2rem;align-items:center">
            <!-- Primary navigation links -->
            <a href="index.php" style="color:#fff;text-decoration:none;font-weight:600;transition:opacity .3s" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">Home</a>
            <a href="men.php" style="color:#fff;text-decoration:none;font-weight:600;transition:opacity .3s" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">Men</a>
            <a href="womens.php" style="color:#fff;text-decoration:none;font-weight:600;transition:opacity .3s" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">Women</a>
            <a href="accessories.php" style="color:#fff;text-decoration:none;font-weight:600;transition:opacity .3s" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">Accessories</a>
            <a href="bmi_calculator.php" style="color:#fff;text-decoration:none;font-weight:600;transition:opacity .3s" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">Size Guide</a>
            <a href="about.php" style="color:#fff;text-decoration:none;font-weight:600;transition:opacity .3s" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">About</a>
            <a href="contact.php" style="color:#fff;text-decoration:none;font-weight:600;transition:opacity .3s" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">Contact</a>

            <!-- Product search form -->
            <form method="GET" action="search.php" style="margin:0">
                <input type="text" name="q" placeholder="Search products..."
                       style="padding:.5rem 1rem;border:none;border-radius:20px;outline:none;min-width:200px"
                       required>
            </form>

            <?php if (isLoggedIn()): ?>
                <!-- Cart link with dynamic item count badge when logged in -->
                <a href="cart.php" style="color:#fff;text-decoration:none;font-weight:600;position:relative">
                    Cart
                    <?php if ($cartCount > 0): ?>
                        <span style="position:absolute;top:-8px;right:-12px;background:#ff4444;color:#fff;border-radius:50%;padding:2px 6px;font-size:.75rem;font-weight:700">
                            <?php echo $cartCount; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <!-- Orders link visible for logged-in customers -->
                <a href="orders.php" style="color:#fff;text-decoration:none;font-weight:600;transition:opacity .3s" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">Orders</a>

                <!-- Logged-in user info pill with icon and customer name -->
                <div style="display:flex;align-items:center;gap:0.8rem;background:rgba(255,255,255,.15);padding:.6rem 1.2rem;border-radius:25px;border:2px solid rgba(255,255,255,.3)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span style="color:#fff;font-weight:700;font-size:.95rem">
                        <?php
                            // Safely display the customer's full name, or "Guest" as a fallback
                            if ($customerInfo) {
                                echo htmlspecialchars($customerInfo['first_name'] . ' ' . $customerInfo['last_name']);
                            } else {
                                echo 'Guest';
                            }
                        ?>
                    </span>
                </div>

                <!-- Logout link for ending the current session -->
                <a href="logout.php" style="color:#fff;text-decoration:none;font-weight:600;transition:opacity .3s" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">Logout</a>
            <?php else: ?>
                <!-- Auth links for visitors who are not logged in -->
                <a href="login.php" style="color:#fff;text-decoration:none;font-weight:600;transition:opacity .3s" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">Login</a>
                <a href="register.php" style="color:#fff;text-decoration:none;font-weight:600;background:rgba(255,255,255,.2);padding:.5rem 1.2rem;border-radius:20px;transition:background .3s" onmouseover="this.style.background='rgba(255,255,255,.3)'" onmouseout="this.style.background='rgba(255,255,255,.2)'">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
