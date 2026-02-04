<?php
// Load shared functions, configuration, and start session/DB connection
require_once 'functions.php';

// Retrieve any error message stored in the session (from previous request)
$error = $_SESSION['error'] ?? null;
// Clear the error from session so it does not persist across page loads
unset($_SESSION['error']);

// If the user is already logged in, redirect them away from the login page
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Handle the form submission when the HTTP method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and trim the submitted email; default to empty string if not set
    $email = trim($_POST['email'] ?? '');
    // Get the submitted password; default to empty string if not set
    $password = $_POST['password'] ?? '';

    // Validate that both email and password have been provided
    if (empty($email) || empty($password)) {
        $error = 'Please provide both email and password';
    } else {
        // Prepare a parameterized query to safely fetch the customer by email
        $sql = "SELECT * FROM customer WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        // Bind the email parameter to the prepared statement as a string
        mysqli_stmt_bind_param($stmt, 's', $email);
        // Execute the prepared statement
        mysqli_stmt_execute($stmt);
        // Get the result set from the executed statement
        $result = mysqli_stmt_get_result($stmt);
        // Fetch a single matching customer row as an associative array
        $customer = mysqli_fetch_assoc($result);

        // If a user is found and the password matches the stored hash, log them in
        if ($customer && password_verify($password, $customer['password_hash'])) {
            // Store key customer data in the session for later use
            $_SESSION['customer_id'] = $customer['customer_id'];
            $_SESSION['customer_name'] = $customer['first_name'] . ' ' . $customer['last_name'];

            // Redirect to the homepage after successful login
            header('Location: index.php');
            exit();
        } else {
            // Show a generic error message on invalid credentials
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Scanfit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Global reset and base layout */
        *{margin:0;padding:0;box-sizing:border-box}
        body{
            font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            min-height:100vh;display:flex;align-items:center;justify-content:center;
            padding:2rem
        }
        /* Centered login card container */
        .login-card{
            background:#fff;border-radius:25px;padding:3rem;
            box-shadow:0 30px 80px rgba(0,0,0,.25);max-width:450px;
            width:100%
        }
        /* Brand logo/title styling */
        .logo{
            text-align:center;font-size:2.5rem;font-weight:800;
            color:#667eea;margin-bottom:2rem;letter-spacing:1px
        }
        /* Main heading styling */
        h1{
            font-size:2rem;margin-bottom:.5rem;color:#2c3e50;
            text-align:center
        }
        /* Subtitle under the heading */
        .subtitle{
            text-align:center;color:#666;margin-bottom:2rem
        }
        /* Form group wrapper spacing */
        .form-group{margin-bottom:1.5rem}
        .form-group label{
            display:block;font-weight:600;margin-bottom:.5rem;
            color:#2c3e50
        }
        /* Input styling for email and password fields */
        .form-group input{
            width:100%;padding:1rem;border:2px solid #e1e4e8;
            border-radius:12px;font-size:1rem;outline:none;
            transition:border-color .3s
        }
        .form-group input:focus{border-color:#667eea}
        /* Error message styling for invalid login feedback */
        .error-msg{
            background:#ff4444;color:#fff;padding:1rem;
            border-radius:12px;margin-bottom:1.5rem;text-align:center
        }
        /* Submit button styling */
        .submit-btn{
            width:100%;padding:1.2rem;border:none;border-radius:15px;
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            color:#fff;font-size:1.1rem;font-weight:700;cursor:pointer;
            transition:transform .2s
        }
        .submit-btn:hover{transform:translateY(-2px)}
        /* Sign-up helper text and link styling */
        .signup-link{
            text-align:center;margin-top:1.5rem;color:#666
        }
        .signup-link a{
            color:#667eea;font-weight:600;text-decoration:none
        }
        .signup-link a:hover{text-decoration:underline}
    </style>
</head>
<body>
    <div class="login-card">
        <!-- Brand logo/title -->
        <div class="logo">SCANFIT</div>
        <!-- Page heading and short description -->
        <h1>Welcome Back</h1>
        <p class="subtitle">Login to continue shopping</p>

        <!-- Display error message if login validation fails -->
        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Login form posting back to the same page -->
        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="your@email.com" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="submit-btn">Login</button>
        </form>

        <!-- Link to registration page for new customers -->
        <div class="signup-link">
    Dont have an account? <a href="register.php">Sign up now</a>
</div>

<div class="signup-link" style="margin-top: 0.75rem;">
    Admin? <a href="admin_login.php">Go to admin login</a>
</div>

    </div>
</body>
</html>
