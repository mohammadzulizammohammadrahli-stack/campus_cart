<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

$product_id = (int)($_GET['product_id'] ?? 0);
if ($product_id <= 0) { header("Location: purchase.php"); exit(); }

// Check if user has already rated this product
$check = $conn->prepare("SELECT rating_id FROM ratings WHERE user_id = ? AND product_id = ?");
$check->bind_param("ii", $user_id, $product_id);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    $_SESSION['flash_error'] = "You have already rated this product.";
    header("Location: purchase.php");
    exit();
}
$check->close();

// Fetch product name for display
$product_name = '';
$stmt = $conn->prepare("SELECT product_name, product_image FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
if ($prod) {
    $product_name = $prod['product_name'];
    $product_image = $prod['product_image'];
}
$stmt->close();

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stars = (int)($_POST['stars'] ?? 0);
    if ($stars < 1 || $stars > 5) {
        $error_msg = "Please select a rating (1-5 stars).";
    } else {
        $ins = $conn->prepare("INSERT INTO ratings (user_id, product_id, stars, created_at) VALUES (?, ?, ?, NOW())");
        $ins->bind_param("iii", $user_id, $product_id, $stars);
        if ($ins->execute()) {
            $_SESSION['flash_success'] = "Thank you for rating!";
            header("Location: purchase.php");
            exit();
        } else {
            $error_msg = "Error saving rating. Please try again.";
        }
        $ins->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rate Product – CampusCart</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #FDFBF7; --header-bg: #EAE3D8; --header-color: #887968;
            --text: #2C2C2C; --muted: #7A7873; --white: #fff;
            --accent: #B8A99A; --border: #E8E5DF; --error: #d9534f;
            --star: #FFD700; --star-empty: #ddd;
            --font-h: 'Cormorant Garamond', serif; --font-b: 'Jost', sans-serif;
        }
        body { background: var(--bg); color: var(--text); font-family: var(--font-b); margin: 0; }
        .top-bar { background: var(--header-bg); color: var(--header-color); text-align: center; padding: 10px; font-size: 13px; }
        nav { background: var(--white); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); }
        .nav-logo { font-family: var(--font-h); font-size: 28px; text-decoration: none; color: var(--text); font-weight: 600; }
        .container { max-width: 500px; margin: 30px auto; padding: 0 20px; }
        .card { background: var(--white); border: 1px solid var(--border); padding: 30px; border-radius: 4px; text-align: center; }
        h1 { font-family: var(--font-h); font-size: 28px; margin: 0 0 10px; }
        .product-img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border); }
        .product-name { font-family: var(--font-h); font-size: 20px; margin: 10px 0; }
        .stars { display: flex; gap: 8px; flex-direction: row-reverse; justify-content: center; margin: 20px 0; }
        .stars input { display: none; }
        .stars label { font-size: 36px; color: var(--star-empty); cursor: pointer; transition: color 0.2s; }
        .stars input:checked ~ label,
        .stars label:hover,
        .stars label:hover ~ label { color: var(--star); }
        .btn-submit { width: 100%; padding: 14px; background: var(--accent); color: white; border: none; text-transform: uppercase; letter-spacing: 1px; font-size: 13px; cursor: pointer; border-radius: 4px; margin-top: 15px; }
        .btn-submit:hover { opacity: 0.9; }
        .btn-back { display: block; text-align: center; margin-top: 10px; color: var(--muted); font-size: 13px; text-decoration: none; }
        .error-msg { background: #f2dede; color: var(--error); padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="top-bar">⭐ Rate Product</div>
    <nav><a href="purchase.php" class="nav-logo">CampusCart</a></nav>

    <div class="container">
        <div class="card">
            <h1>Rate This Product</h1>
            <?php if (!empty($product_image)): ?>
                <img src="uploads/<?php echo htmlspecialchars($product_image); ?>" class="product-img" onerror="this.src='uploads/default.png'">
            <?php endif; ?>
            <div class="product-name"><?php echo htmlspecialchars($product_name); ?></div>

            <?php if ($error_msg): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="stars">
                    <input type="radio" id="s5" name="stars" value="5"><label for="s5">★</label>
                    <input type="radio" id="s4" name="stars" value="4"><label for="s4">★</label>
                    <input type="radio" id="s3" name="stars" value="3"><label for="s3">★</label>
                    <input type="radio" id="s2" name="stars" value="2"><label for="s2">★</label>
                    <input type="radio" id="s1" name="stars" value="1"><label for="s1">★</label>
                </div>
                <button type="submit" class="btn-submit">Submit Rating</button>
            </form>
            <a href="purchase.php" class="btn-back">← Back to History</a>
        </div>
    </div>
</body>
</html>