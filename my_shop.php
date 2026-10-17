<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

// Fetch seller's products with sales stats (using amount)
$query = "SELECT p.*, 
                 COUNT(pur.purchase_id) AS sold_count,
                 COALESCE(SUM(pur.amount), 0) AS total_revenue
          FROM products p
          LEFT JOIN purchases pur ON p.product_id = pur.product_id
          WHERE p.seller_id = ?
          GROUP BY p.product_id
          ORDER BY p.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$products = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Shop – CampusCart</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #FDFBF7; --header-bg: #EAE3D8; --header-color: #887968; --text: #2C2C2C; --muted: #7A7873; --white: #fff; --accent: #B8A99A; --border: #E8E5DF; --font-h: 'Cormorant Garamond', serif; --font-b: 'Jost', sans-serif; }
        body { background: var(--bg); color: var(--text); font-family: var(--font-b); margin:0; }
        .top-bar { background: var(--header-bg); color: var(--header-color); text-align:center; padding:10px; font-size:13px; }
        nav { background: var(--white); padding:15px 30px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); }
        .nav-logo { font-family:var(--font-h); font-size:28px; text-decoration:none; color:var(--text); }
        .nav-links a { margin-left:25px; text-decoration:none; color:var(--header-color); font-size:13px; text-transform:uppercase; letter-spacing:1px; }
        .container { max-width:1000px; margin:30px auto; padding:0 20px; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; }
        h1 { font-family:var(--font-h); font-size:32px; color:var(--header-color); }
        .btn { padding:10px 20px; background:var(--header-bg); color:var(--header-color); border:1px solid var(--accent); text-transform:uppercase; font-size:12px; letter-spacing:1px; text-decoration:none; cursor:pointer; }
        .btn:hover { background:var(--accent); color:white; }
        .product-card { background:var(--white); border:1px solid var(--border); padding:15px; margin-bottom:15px; display:flex; gap:20px; align-items:center; border-radius:4px; }
        .product-img { width:100px; height:100px; object-fit:cover; border:1px solid var(--border); }
        .product-info { flex:1; }
        .product-name { font-family:var(--font-h); font-size:22px; margin:0 0 5px; }
        .meta { font-size:13px; color:var(--muted); }
        .stats { display:flex; gap:25px; margin-top:8px; }
        .stat-box { text-align:center; }
        .stat-num { font-family:var(--font-h); font-size:22px; color:var(--accent); }
        .stat-label { font-size:11px; text-transform:uppercase; color:var(--muted); }
        .actions { display:flex; gap:10px; }
        .btn-sm { padding:8px 15px; background:var(--header-bg); color:var(--header-color); border:1px solid var(--accent); font-size:11px; text-transform:uppercase; text-decoration:none; }
        .btn-sm.danger { background:transparent; color:#d9534f; border-color:#d9534f; }
        .btn-sm.danger:hover { background:#d9534f; color:white; }
        .empty { text-align:center; padding:60px; color:var(--muted); }
    </style>
</head>
<body>
    <div class="top-bar">MY &nbsp;|&nbsp; 🎓 Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Student'); ?></div>
    <nav>
        <a href="profile.php" class="nav-logo">CampusCart</a>
        <div class="nav-links">
            <a href="marketplace.php">Marketplace</a>
            <a href="profile.php">Profile</a>
            <a href="my_shop.php">My Shop</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <h1>My Shop</h1>
            <a href="add_product.php" class="btn">+ Add New Product</a>
        </div>

        <?php if ($products->num_rows > 0): ?>
            <?php while($p = $products->fetch_assoc()): ?>
                <div class="product-card">
                    <img src="uploads/<?= htmlspecialchars($p['product_image'] ?? 'default.png') ?>" class="product-img">
                    <div class="product-info">
                        <h3 class="product-name"><?= htmlspecialchars($p['product_name']) ?></h3>
                        <div class="meta">RM <?= number_format($p['price'], 2) ?> &nbsp;|&nbsp; <?= htmlspecialchars($p['category'] ?? 'Others') ?></div>
                        <div class="stats">
                            <div class="stat-box">
                                <div class="stat-num"><?= $p['stock'] ?></div>
                                <div class="stat-label">In Stock</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-num"><?= $p['sold_count'] ?></div>
                                <div class="stat-label">Sold</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-num">RM <?= number_format($p['total_revenue'], 2) ?></div>
                                <div class="stat-label">Revenue</div>
                            </div>
                        </div>
                    </div>
                    <div class="actions">
                        <a href="edit_product.php?id=<?= $p['product_id'] ?>" class="btn-sm">Edit</a>
                        <a href="delete_product.php?id=<?= $p['product_id'] ?>" class="btn-sm danger" onclick="return confirm('Delete this product?')">Delete</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty">
                <p>You haven’t listed any products yet.</p>
                <a href="add_product.php" class="btn">Sell Your First Item</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>