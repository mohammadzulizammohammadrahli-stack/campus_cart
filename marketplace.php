<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

// Build query with filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$base_query = "SELECT * FROM products WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $base_query .= " AND product_name LIKE ?";
    $params[] = '%' . $search . '%';
    $types .= 's';
}

if (!empty($category) && $category !== 'all') {
    $base_query .= " AND category = ?";
    $params[] = $category;
    $types .= 's';
}

$base_query .= " ORDER BY product_id DESC";

$stmt = $conn->prepare($base_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products_result = $stmt->get_result();

// Predefined categories (you can also fetch distinct from DB if preferred)
$categories = ['Books', 'Food', 'Gadgets', 'Clothing', 'Stationery', 'Others'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CampusCart – Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-cream: #FDFBF7;
            --header-bg: #EAE3D8;
            --header-color: #887968;
            --text-dark: #2C2C2C;
            --text-muted: #7A7873;
            --white: #FFFFFF;
            --accent: #B8A99A;
            --border: #E8E5DF;
            --font-heading: 'Cormorant Garamond', serif;
            --font-body: 'Jost', sans-serif;
            --shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        body { background: var(--bg-cream); color: var(--text-dark); font-family: var(--font-body); margin: 0; }
        .top-bar { background: var(--header-bg); color: var(--header-color); text-align: center; padding: 10px; font-size: 13px; }
        nav { background: var(--white); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); }
        .nav-logo { font-family: var(--font-heading); font-size: 28px; text-decoration: none; color: var(--text-dark); font-weight: 600; }
        .nav-links a { margin-left: 25px; text-decoration: none; color: var(--header-color); font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }

        /* Search & Filters */
        .filters-bar { display: flex; flex-wrap: wrap; gap: 15px; align-items: center; margin-bottom: 30px; }
        .search-form { flex: 1; min-width: 250px; display: flex; }
        .search-input { flex: 1; padding: 10px 15px; border: 1px solid var(--border); font-family: var(--font-body); background: var(--white); }
        .search-btn { padding: 10px 20px; background: var(--accent); color: white; border: 1px solid var(--accent); font-family: var(--font-body); text-transform: uppercase; letter-spacing: 1px; cursor: pointer; }
        .category-filters { display: flex; flex-wrap: wrap; gap: 8px; }
        .cat-btn { padding: 8px 16px; border: 1px solid var(--border); background: var(--white); color: var(--header-color); text-transform: uppercase; font-size: 12px; letter-spacing: 1px; cursor: pointer; text-decoration: none; border-radius: 2px; transition: all 0.3s; }
        .cat-btn.active, .cat-btn:hover { background: var(--header-bg); color: var(--text-dark); border-color: var(--accent); }

        /* Product Grid */
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 25px; }
        .card { background: var(--white); border: 1px solid var(--border); padding: 15px; border-radius: 4px; transition: box-shadow 0.3s; }
        .card:hover { box-shadow: var(--shadow); }
        .card img { width: 100%; aspect-ratio: 1/1; object-fit: cover; border: 1px solid var(--border); margin-bottom: 12px; border-radius: 2px; }
        .card h3 { font-family: var(--font-heading); font-size: 18px; margin: 0 0 5px; }
        .card .rating { color: #D4AF37; font-size: 14px; margin-bottom: 5px; }
        .card .price { color: var(--header-color); font-size: 18px; font-weight: 500; }
        .card .category-badge { display: inline-block; font-size: 10px; text-transform: uppercase; background: var(--header-bg); color: var(--header-color); padding: 2px 8px; border-radius: 2px; margin-top: 5px; }
        .save-btn { display: block; text-align: center; border: 1px solid var(--accent); color: var(--header-color); padding: 10px; text-decoration: none; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-top: 12px; border-radius: 2px; transition: background 0.3s; }
        .save-btn:hover { background: var(--header-bg); color: var(--text-dark); }
        .no-products { text-align: center; padding: 60px; color: var(--text-muted); }
    </style>
</head>
<body>
    <div class="top-bar">MY &nbsp;|&nbsp; 🎓 Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Student'); ?>!</div>
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
        <!-- Search & Category Filters -->
        <div class="filters-bar">
            <form class="search-form" method="GET" action="marketplace.php">
                <input type="text" name="search" class="search-input" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                <?php if (!empty($category)): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                <?php endif; ?>
                <button type="submit" class="search-btn">Search</button>
            </form>

            <div class="category-filters">
                <a href="marketplace.php?search=<?php echo urlencode($search); ?>" class="cat-btn <?php echo ($category === '' || $category === 'all') ? 'active' : ''; ?>">All</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="marketplace.php?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($cat); ?>" 
                       class="cat-btn <?php echo ($category === $cat) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Products -->
        <?php if ($products_result->num_rows > 0): ?>
            <div class="grid">
                <?php while ($row = $products_result->fetch_assoc()):
                    $prod_id = $row['product_id'];
                    // Get rating
                    $rate_stmt = $conn->prepare("SELECT AVG(stars) as avg_rating, COUNT(rating_id) as total FROM ratings WHERE product_id = ?");
                    $rate_stmt->bind_param("i", $prod_id);
                    $rate_stmt->execute();
                    $rate_data = $rate_stmt->get_result()->fetch_assoc();
                    $rate_stmt->close();
                ?>
                    <div class="card">
                        <a href="view_product.php?id=<?php echo $prod_id; ?>" style="text-decoration: none; color: inherit;">
                            <img src="uploads/<?php echo htmlspecialchars($row['product_image']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                            <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
                            <div class="rating">
                                <?php echo $rate_data['total'] > 0 ? round($rate_data['avg_rating'],1) . " ★" : "No ratings"; ?>
                            </div>
                            <div class="price">RM <?php echo number_format($row['price'], 2); ?></div>
                            <?php if (!empty($row['category'])): ?>
                                <span class="category-badge"><?php echo htmlspecialchars($row['category']); ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="wishlist.php?add=<?php echo $prod_id; ?>" class="save-btn">Save Item</a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-products">
                <h2 style="font-family: var(--font-heading);">No products found</h2>
                <p>Try adjusting your search or filter.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>