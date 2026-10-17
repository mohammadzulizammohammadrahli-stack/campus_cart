<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

if (isset($_GET['add'])) {
    $prod_id = (int)$_GET['add'];
    $check = $conn->prepare("SELECT * FROM wishlists WHERE user_id = ? AND product_id = ?");
    $check->bind_param("ii", $user_id, $prod_id);
    $check->execute();
    if ($check->get_result()->num_rows == 0) {
        $insert = $conn->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)");
        $insert->bind_param("ii", $user_id, $prod_id);
        $insert->execute();
        $insert->close();
    }
    $check->close();
    header("Location: wishlist.php");
    exit();
}

$query = "SELECT p.*, w.added_at FROM products p JOIN wishlists w ON p.product_id = w.product_id WHERE w.user_id = ? ORDER BY w.wishlist_id DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$wishlist_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CampusCart - Wishlist</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg-cream: #FDFBF7; 
            --header-bg: #EAE3D8; 
            --header-color: #887968; 
            --text-dark: #2C2C2C; 
            --text-muted: #7A7873; 
            --white: #FFFFFF; 
            --font-heading: 'Cormorant Garamond', serif; 
            --font-body: 'Jost', sans-serif; 
        }
        body { background-color: var(--bg-cream); color: var(--text-dark); font-family: var(--font-body); margin: 0; }
        
        .top-bar { background-color: var(--header-bg); color: var(--header-color); text-align: center; padding: 12px; font-size: 13px; font-weight: 400; letter-spacing: 1px; border-bottom: 1px solid rgba(136, 121, 104, 0.15); font-family: var(--font-body); }
        nav { background-color: var(--header-bg); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(136, 121, 104, 0.2); }
        .nav-logo { color: var(--text-dark); font-family: var(--font-heading); font-size: 28px; text-decoration: none; letter-spacing: 2px; font-weight: 600; font-style: italic; }
        .nav-links { display: flex; gap: 30px; }
        .nav-links a { color: var(--header-color); text-decoration: none; font-size: 13px; letter-spacing: 1.5px; text-transform: uppercase; transition: 0.3s; }
        .nav-links a:hover { color: var(--text-dark); }
        
        .container { max-width: 1100px; margin: 60px auto; padding: 0 40px; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 30px; }
        .card { background: var(--white); border: 1px solid #E8E5DF; padding: 20px; transition: 0.3s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.03); }
        .card img { width: 100%; aspect-ratio: 1/1; object-fit: cover; border: 1px solid #E8E5DF; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="top-bar">
        MY &nbsp;&nbsp;|&nbsp;&nbsp; 🎓 Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Student'); ?>!
    </div>
    
    <nav>
        <a href="profile.php" class="nav-logo">CampusCart</a>
        <div class="nav-links">
            <a href="profile.php">Marketplace</a>
            <a href="wishlist.php" style="color: var(--text-dark); border-bottom: 1px solid var(--text-dark);">Wishlist</a>
            <a href="purchase.php">History</a>
            <a href="report.php" style="color: #d9534f; font-weight: 700;">Support</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <h1 style="font-family: var(--font-heading); font-size: 48px; font-weight: 500; color: var(--header-color); border-bottom: 1px solid rgba(136, 121, 104, 0.3); padding-bottom: 15px; margin-bottom: 40px;">Saved Items</h1>
        
        <div class="grid">
            <?php if ($wishlist_result && $wishlist_result->num_rows > 0): ?>
                <?php while($row = $wishlist_result->fetch_assoc()): ?>
                    <div class="card">
                        <a href="view_product.php?id=<?php echo $row['product_id']; ?>" style="text-decoration: none; color: inherit;">
                            <img src="uploads/<?php echo htmlspecialchars($row['product_image']); ?>" alt="Product">
                            <h3 style="font-family: var(--font-heading); font-size: 22px; font-weight: 500; margin: 0 0 5px 0; color: var(--text-dark);"><?php echo htmlspecialchars($row['product_name']); ?></h3>
                            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 5px;">Added: <?php echo date("d M Y", strtotime($row['added_at'])); ?></div>
                            <div style="color: var(--header-color); font-weight: 400; font-size: 18px;">RM <?php echo number_format($row['price'], 2); ?></div>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; padding: 40px; border: 1px solid #E8E5DF; background: var(--bg-cream); text-align: center;">
                    <p style="color: var(--text-muted); font-size: 16px; font-weight: 300;">Your wishlist is currently empty.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>