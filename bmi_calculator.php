<?php
// Load shared functions (authentication, DB helpers, etc.)
require_once 'functions.php';

// Ensure session is active (only if your project doesn't already do this inside functions.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize result and helper variables for BMI calculation and size recommendation
$result = null;
$bmi = null;
$category = '';
$recommendedSize = '';
$saveSuccess = false;

// Handle form submission when the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read gender and numeric height/weight from submitted form
    $gender = $_POST['gender'] ?? '';
    $heightCm = (float)($_POST['height'] ?? 0);
    $weightKg = (float)($_POST['weight'] ?? 0);

    // Only proceed if both height and weight are valid positive numbers
    if ($heightCm > 0 && $weightKg > 0 && ($gender === 'Male' || $gender === 'Female')) {

        // Convert height to meters and compute BMI
        $heightM = $heightCm / 100;
        $bmi = $weightKg / ($heightM * $heightM);

        // Classify BMI into standard weight categories
        if ($bmi < 18.5) {
            $category = 'Underweight';
        } elseif ($bmi < 25) {
            $category = 'Normal weight';
        } elseif ($bmi < 30) {
            $category = 'Overweight';
        } else {
            $category = 'Obese';
        }

        // Size recommendation logic for male users based on height and BMI ranges
        if ($gender === 'Male') {
            if ($heightCm < 165) {
                $recommendedSize = $bmi < 20 ? 'XS' : ($bmi < 23 ? 'S' : ($bmi < 27 ? 'M' : 'L'));
            } elseif ($heightCm < 175) {
                $recommendedSize = $bmi < 22 ? 'S' : ($bmi < 25 ? 'M' : ($bmi < 28 ? 'L' : 'XL'));
            } elseif ($heightCm < 185) {
                $recommendedSize = $bmi < 23 ? 'M' : ($bmi < 26 ? 'L' : ($bmi < 29 ? 'XL' : 'XXL'));
            } else {
                $recommendedSize = $bmi < 24 ? 'L' : ($bmi < 27 ? 'XL' : 'XXL');
            }
        } else {
            // Size recommendation logic for female users based on height and BMI ranges
            if ($heightCm < 155) {
                $recommendedSize = $bmi < 20 ? 'XS' : ($bmi < 23 ? 'S' : ($bmi < 27 ? 'M' : 'L'));
            } elseif ($heightCm < 165) {
                $recommendedSize = $bmi < 21 ? 'S' : ($bmi < 24 ? 'M' : ($bmi < 28 ? 'L' : 'XL'));
            } elseif ($heightCm < 175) {
                $recommendedSize = $bmi < 22 ? 'M' : ($bmi < 25 ? 'L' : ($bmi < 29 ? 'XL' : 'XXL'));
            } else {
                $recommendedSize = $bmi < 23 ? 'L' : ($bmi < 26 ? 'XL' : 'XXL');
            }
        }

        // If user is logged in, persist body measurements to their profile
        if (isLoggedIn()) {
            $customerId = getCustomerId();
            if (saveBodyMeasurement($customerId, $heightCm, $weightKg)) {
                $saveSuccess = true;
            }

            // Save recommended size to customer table (Step 2A + Step 2B)
            if (function_exists('updateCustomerRecommendedSize')) {
                updateCustomerRecommendedSize($customerId, $gender, $recommendedSize);
            }
        }

        // Save results to session (works for guest or logged in)
        $_SESSION['recommended_size'] = $recommendedSize;
        $_SESSION['recommended_gender'] = $gender;
        $_SESSION['bmi_value'] = $bmi;
        $_SESSION['bmi_category'] = $category;

        // Prepare result array for use in the view/template section below
        $result = [
            'gender' => $gender,
            'height' => $heightCm,
            'weight' => $weightKg,
            'bmi' => number_format($bmi, 2),
            'category' => $category,
            'size' => $recommendedSize
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BMI Calculator & Size Guide - Scanfit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{
            font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
            background:linear-gradient(135deg,#f5f7fa 0%,#c3cfe2 100%);
            color:#333;min-height:100vh
        }
        .hero-section{
            background:linear-gradient(rgba(102,126,234,.9),rgba(118,75,162,.9));
            height:40vh;display:flex;align-items:center;justify-content:center;
            text-align:center;color:#fff
        }
        .hero-content h1{
            font-size:clamp(2rem,5vw,3.5rem);font-weight:800;margin-bottom:1rem
        }
        .hero-content p{
            font-size:clamp(1rem,2vw,1.3rem);opacity:.95
        }
        .container{
            max-width:900px;margin:0 auto;padding:3rem 2rem
        }
        .calculator-card{
            background:#fff;border-radius:25px;padding:3rem;
            box-shadow:0 20px 60px rgba(0,0,0,.1);margin-bottom:2rem
        }
        .form-group{margin-bottom:1.5rem}
        .form-group label{
            display:block;font-weight:600;margin-bottom:.5rem;
            color:#2c3e50;font-size:1.1rem
        }
        .form-group select,
        .form-group input{
            width:100%;padding:1rem;border:2px solid #e1e4e8;
            border-radius:12px;font-size:1rem;outline:none;
            transition:border-color .3s
        }
        .form-group select:focus,
        .form-group input:focus{border-color:#667eea}
        .form-row{
            display:grid;grid-template-columns:1fr 1fr;gap:1.5rem
        }
        .submit-btn{
            width:100%;padding:1.2rem;border:none;border-radius:15px;
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            color:#fff;font-size:1.1rem;font-weight:700;cursor:pointer;
            transition:transform .2s;margin-top:1rem
        }
        .submit-btn:hover{transform:translateY(-2px)}
        .result-card{
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            border-radius:25px;padding:3rem;color:#fff;text-align:center;
            box-shadow:0 20px 60px rgba(102,126,234,.3)
        }
        .result-card h2{font-size:2rem;margin-bottom:2rem}
        .result-grid{
            display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1.5rem;margin-top:2rem
        }
        .result-item{
            background:rgba(255,255,255,.15);padding:1.5rem;border-radius:15px;backdrop-filter:blur(10px)
        }
        .result-item-label{font-size:.9rem;opacity:.9;margin-bottom:.5rem}
        .result-item-value{font-size:1.8rem;font-weight:800}
        .size-recommendation{
            font-size:3rem;font-weight:900;margin:2rem 0;
            text-shadow:0 4px 20px rgba(0,0,0,.3)
        }
        .info-section{
            background:#fff;border-radius:25px;padding:2.5rem;
            box-shadow:0 15px 40px rgba(0,0,0,.08);margin-top:2rem
        }
        .info-section h3{font-size:1.6rem;margin-bottom:1rem;color:#2c3e50}
        .info-section p{line-height:1.8;color:#555;margin-bottom:1rem}
        .size-chart{
            display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));
            gap:1rem;margin-top:1.5rem
        }
        .size-box{
            background:#f8f9fa;padding:1.5rem;border-radius:15px;
            text-align:center;border:2px solid #e1e4e8
        }
        .size-box-label{
            font-weight:700;font-size:1.3rem;color:#667eea;margin-bottom:.5rem
        }
        .size-box-range{font-size:.85rem;color:#666}
        .success-msg{
            background:#28a745;color:#fff;padding:1rem;
            border-radius:12px;margin-bottom:1.5rem;text-align:center;
            font-weight:600
        }
        .action-buttons{
            margin-top:2rem;
            display:flex;
            justify-content:center;
            gap:1rem;
            flex-wrap:wrap;
        }
        .btn-primary-like{
            padding:12px 18px;border-radius:12px;background:#fff;color:#5b4aa2;
            font-weight:800;text-decoration:none;display:inline-block
        }
        .btn-secondary-like{
            padding:12px 18px;border-radius:12px;background:rgba(255,255,255,.2);color:#fff;
            font-weight:800;text-decoration:none;display:inline-block;border:2px solid rgba(255,255,255,.35)
        }
        @media(max-width:768px){
            .form-row{grid-template-columns:1fr}
            .calculator-card,.result-card,.info-section{padding:2rem}
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<section class="hero-section">
    <div class="hero-content">
        <h1>BMI Calculator & Size Guide</h1>
        <p>Find your perfect fit based on your body measurements</p>
    </div>
</section>

<div class="container">
    <div class="calculator-card">
        <h2 style="font-size:2rem;margin-bottom:2rem;color:#2c3e50">Calculate Your Size</h2>

        <form method="POST">
            <div class="form-group">
                <label>Gender</label>
                <select name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Height (cm)</label>
                    <input type="number" name="height" step="0.1" min="100" max="250"
                           placeholder="e.g., 175"
                           value="<?php echo htmlspecialchars($_POST['height'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Weight (kg)</label>
                    <input type="number" name="weight" step="0.1" min="30" max="300"
                           placeholder="e.g., 70"
                           value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>" required>
                </div>
            </div>

            <button type="submit" class="submit-btn">Calculate BMI & Get Size Recommendation</button>
        </form>
    </div>

    <?php if ($result): ?>
        <?php if ($saveSuccess): ?>
            <div class="success-msg">Your measurements have been saved to your profile</div>
        <?php endif; ?>

        <div class="result-card">
            <h2>Your Results</h2>

            <div class="size-recommendation">Recommended Size: <?php echo htmlspecialchars($result['size']); ?></div>

            <div class="result-grid">
                <div class="result-item">
                    <div class="result-item-label">BMI</div>
                    <div class="result-item-value"><?php echo $result['bmi']; ?></div>
                </div>

                <div class="result-item">
                    <div class="result-item-label">Category</div>
                    <div class="result-item-value" style="font-size:1.3rem"><?php echo htmlspecialchars($result['category']); ?></div>
                </div>

                <div class="result-item">
                    <div class="result-item-label">Height</div>
                    <div class="result-item-value"><?php echo $result['height']; ?><span style="font-size:1rem">cm</span></div>
                </div>

                <div class="result-item">
                    <div class="result-item-label">Weight</div>
                    <div class="result-item-value"><?php echo $result['weight']; ?><span style="font-size:1rem">kg</span></div>
                </div>
            </div>

            <p style="margin-top:2rem;opacity:.95;font-size:1.1rem">
                Based on your measurements, we recommend size <strong><?php echo htmlspecialchars($result['size']); ?></strong>
                for the best fit in our <?php echo htmlspecialchars($result['gender']); ?> collection.
            </p>

            <div class="action-buttons">
                <a class="btn-primary-like"
                   href="<?php echo ($result['gender'] === 'Male')
                       ? 'men.php?size=' . urlencode($result['size'])
                       : 'womens.php?size=' . urlencode($result['size']); ?>">
                    Shop my recommended size
                </a>

                <a class="btn-secondary-like"
                   href="<?php echo ($result['gender'] === 'Male') ? 'men.php' : 'womens.php'; ?>">
                    Shop and choose my own size
                </a>
            </div>

            <p style="margin-top:1rem;opacity:.95;font-size:0.95rem">
                Tip: You can use the recommended size as a guide, but you can still choose a different size while shopping.
            </p>
        </div>
    <?php endif; ?>

    <div class="info-section">
        <h3>Understanding BMI</h3>
        <p>
            BMI (Body Mass Index) is a measure of body fat based on height and weight. It's calculated by dividing
            your weight in kilograms by the square of your height in meters. While BMI is a useful general indicator,
            it doesn't account for muscle mass, bone density, or body composition.
        </p>

        <h3 style="margin-top:2rem">Size Chart Reference</h3>
        <div class="size-chart">
            <div class="size-box">
                <div class="size-box-label">XS</div>
                <div class="size-box-range">Extra Small<br>BMI &lt; 20</div>
            </div>
            <div class="size-box">
                <div class="size-box-label">S</div>
                <div class="size-box-range">Small<br>BMI 20-22</div>
            </div>
            <div class="size-box">
                <div class="size-box-label">M</div>
                <div class="size-box-range">Medium<br>BMI 22-25</div>
            </div>
            <div class="size-box">
                <div class="size-box-label">L</div>
                <div class="size-box-range">Large<br>BMI 25-28</div>
            </div>
            <div class="size-box">
                <div class="size-box-label">XL</div>
                <div class="size-box-range">Extra Large<br>BMI 28-30</div>
            </div>
            <div class="size-box">
                <div class="size-box-label">XXL</div>
                <div class="size-box-range">2X Large<br>BMI &gt; 30</div>
            </div>
        </div>

        <p style="margin-top:2rem;font-style:italic;color:#666">
            Note: These are general recommendations. Individual fit preferences may vary. We recommend checking
            specific product measurements for the most accurate fit.
        </p>
    </div>
</div>
</body>
</html>

