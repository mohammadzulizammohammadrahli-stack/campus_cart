<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

// Flash messages
$success = $_SESSION['flash_success'] ?? null;
$error   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Fetch purchases with product details and check if buyer has already rated the product
$query = "SELECT p.*, pr.product_name, pr.product_image,
                 r.rating_id AS has_rating
          FROM purchases p 
          JOIN products pr ON p.product_id = pr.product_id 
          LEFT JOIN ratings r ON p.product_id = r.product_id AND r.user_id = p.buyer_id
          WHERE p.buyer_id = ? 
          ORDER BY p.purchased_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase History – CampusCart</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg: #FDFBF7; --header-bg: #EAE3D8; --header-color: #887968;
            --text: #2C2C2C; --muted: #7A7873; --white: #fff;
            --accent: #B8A99A; --border: #E8E5DF; --success: #5cb85c; --error: #d9534f;
            --font-h: 'Cormorant Garamond', serif; --font-b: 'Jost', sans-serif; 
        }
        body { background: var(--bg); color: var(--text); font-family: var(--font-b); margin: 0; }
        .top-bar { background: var(--header-bg); color: var(--header-color); text-align: center; padding: 10px; font-size: 13px; }
        nav { background: var(--white); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); }
        .nav-logo { font-family: var(--font-h); font-size: 28px; text-decoration: none; color: var(--text); font-weight: 600; }
        .nav-links a { margin-left: 25px; text-decoration: none; color: var(--header-color); font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; }
        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .msg { padding: 15px; border-radius: 4px; margin-bottom: 25px; text-align: center; }
        .msg.success { background: #dff0d8; color: var(--success); border: 1px solid #d6e9c6; }
        .msg.error { background: #f2dede; color: var(--error); border: 1px solid #ebccd1; }
        h1 { font-family: var(--font-h); font-size: 36px; color: var(--header-color); border-bottom: 1px solid rgba(136, 121, 104, 0.3); padding-bottom: 15px; margin-bottom: 30px; }
        
        .history-card { background: var(--white); border: 1px solid var(--border); padding: 20px; margin-bottom: 15px; display: flex; gap: 20px; align-items: center; border-radius: 4px; }
        .product-img { width: 100px; height: 100px; object-fit: cover; border: 1px solid var(--border); border-radius: 2px; }
        .card-info { flex: 1; }
        .card-meta { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; }
        .card-name { font-family: var(--font-h); font-size: 22px; margin: 5px 0; }
        .card-price { color: var(--header-color); font-size: 20px; font-weight: 500; }
        .card-actions { display: flex; flex-direction: column; gap: 10px; }
        .btn { padding: 8px 18px; border: 1px solid var(--accent); color: var(--header-color); text-transform: uppercase; font-size: 11px; text-decoration: none; text-align: center; letter-spacing: 1px; background: var(--header-bg); display: inline-block; }
        .btn:hover { background: var(--accent); color: white; }
        .btn.rated { background: var(--bg); color: var(--muted); border-color: var(--border); cursor: default; }
        .btn.rated:hover { background: var(--bg); color: var(--muted); }
        .empty { text-align: center; padding: 60px; color: var(--muted); }
    </style>
</head>
<body>
    <div class="top-bar">MY &nbsp;|&nbsp; 🎓 Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Student'); ?></div>
    <nav>
        <a href="profile.php" class="nav-logo">CampusCart</a>
        <div class="nav-links">
            <a href="marketplace.php">Marketplace</a>
            <a href="profile.php">My Profile</a>
            <a href="wishlist.php">Wishlist</a>
            <a href="purchase.php">History</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if ($success): ?>
            <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <h1>Order History</h1>

        <?php if ($history_result && $history_result->num_rows > 0): ?>
            <?php while($row = $history_result->fetch_assoc()): ?>
                <div class="history-card">
                    <img src="uploads/<?php echo htmlspecialchars($row['product_image']); ?>" class="product-img" onerror="this.src='uploads/default.png'">
                    <div class="card-info">
                        <div class="card-meta">Order #<?php echo $row['purchase_id']; ?> • <?php echo date("d M Y", strtotime($row['purchased_at'])); ?></div>
                        <h3 class="card-name"><?php echo htmlspecialchars($row['product_name']); ?></h3>
                        <div class="card-amount">RM <?php echo number_format($row['amount'], 2); ?></div>
                    </div>
                    <div class="card-actions">
                        <a href="view_product.php?id=<?php echo $row['product_id']; ?>" class="btn">View Product</a>
                        <?php if ($row['has_rating']): ?>
                            <span class="btn rated">✓ Rated</span>
                        <?php else: ?>
                            <a href="rate.php?product_id=<?php echo $row['product_id']; ?>" class="btn">⭐ Rate Product</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty">
                <p>No purchases yet.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>