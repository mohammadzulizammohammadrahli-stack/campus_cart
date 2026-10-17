<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user_id = $_SESSION['user_id'];
if (!isset($_GET['id'])) { header("Location: marketplace.php"); exit(); }

$product_id = (int)$_GET['id'];

// 1. Fetch product info
$query = "SELECT p.*, u.username 
          FROM products p 
          JOIN users u ON p.seller_id = u.user_id 
          WHERE p.product_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) { header("Location: marketplace.php"); exit(); }

// 2. Average product rating (from your ratings table)
$rate_query = "SELECT AVG(stars) AS avg_rating, COUNT(rating_id) AS total_ratings 
               FROM ratings WHERE product_id = ?";
$r_stmt = $conn->prepare($rate_query);
$r_stmt->bind_param("i", $product_id);
$r_stmt->execute();
$rating_data = $r_stmt->get_result()->fetch_assoc();
$r_stmt->close();

$avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
$total_ratings = $rating_data['total_ratings'] ?? 0;

// 3. Seller average rating (average of all ratings on their products)
$seller_rating_query = "SELECT AVG(r.stars) AS avg_seller_rating, COUNT(r.rating_id) AS total_seller_ratings 
                        FROM ratings r 
                        JOIN products p2 ON r.product_id = p2.product_id 
                        WHERE p2.seller_id = ?";
$sr_stmt = $conn->prepare($seller_rating_query);
$sr_stmt->bind_param("i", $product['seller_id']);
$sr_stmt->execute();
$seller_data = $sr_stmt->get_result()->fetch_assoc();
$sr_stmt->close();

$avg_seller_rating = round($seller_data['avg_seller_rating'] ?? 0, 1);
$total_seller_ratings = $seller_data['total_seller_ratings'] ?? 0;

// 4. Quick message handler
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['quick_message'])) {
    $msg_text = trim($_POST['message_text']);
    if (!empty($msg_text)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, product_id, message_text) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $user_id, $product['seller_id'], $product_id, $msg_text);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: chat.php?product_id=" . $product_id);
            exit();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['product_name']); ?> – CampusCart</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-cream: #FDFBF7; --header-bg: #EAE3D8; --header-color: #887968;
            --text-dark: #2C2C2C; --text-muted: #7A7873; --white: #FFFFFF;
            --accent: #B8A99A; --border: #E8E5DF; --star: #FFD700;
            --font-heading: 'Cormorant Garamond', serif; --font-body: 'Jost', sans-serif;
        }
        body {
            margin: 0; font-family: var(--font-body); background-color: var(--bg-cream);
            height: 100vh; overflow: hidden; display: flex; flex-direction: column;
        }
        .top-bar {
            background-color: var(--header-bg); color: var(--header-color);
            padding: 15px 40px; display: flex; justify-content: space-between;
            align-items: center; border-bottom: 1px solid rgba(136, 121, 104, 0.2); z-index: 10;
        }
        .nav-logo {
            color: var(--text-dark); font-family: var(--font-heading); font-size: 28px;
            text-decoration: none; font-weight: 600; font-style: italic;
        }
        .close-btn {
            color: var(--header-color); text-decoration: none; font-size: 13px;
            text-transform: uppercase; letter-spacing: 1px; border: 1px solid var(--header-color);
            padding: 6px 15px; border-radius: 20px; transition: 0.3s;
        }
        .close-btn:hover { background: var(--header-color); color: var(--white); }

        .split-wrapper { display: flex; flex-grow: 1; overflow: hidden; }
        .left-image-section {
            flex: 2; position: relative; background-color: var(--header-bg);
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .main-product-image {
            max-width: 85%; max-height: 85%; z-index: 2; object-fit: contain;
            box-shadow: 0 20px 50px rgba(0,0,0,0.05); border: 1px solid rgba(136, 121, 104, 0.1);
        }

        .right-details-section {
            width: 450px; background-color: var(--white); display: flex;
            flex-direction: column; border-left: 1px solid var(--border); z-index: 5;
        }
        .details-scroll { flex-grow: 1; overflow-y: auto; padding: 40px; }
        .product-title {
            font-family: var(--font-heading); font-size: 38px; color: var(--header-color);
            font-weight: 500; margin: 0 0 10px 0; line-height: 1.1;
        }
        .product-price {
            font-size: 24px; font-weight: 500; color: var(--text-dark);
            margin-bottom: 20px; letter-spacing: 1px;
        }
        .stock-info { margin: 10px 0; }
        .detail-label {
            font-size: 11px; text-transform: uppercase; letter-spacing: 2px;
            color: var(--text-muted); margin-bottom: 10px; display: block;
            border-bottom: 1px solid var(--border); padding-bottom: 5px;
        }
        .description-text {
            font-size: 14px; font-weight: 300; color: var(--text-dark);
            line-height: 1.6; margin-bottom: 30px;
        }
        .seller-info {
            display: flex; align-items: center; gap: 15px; padding: 20px;
            background: var(--bg-cream); border: 1px solid var(--border); margin-bottom: 30px;
        }
        .seller-avatar {
            width: 45px; height: 45px; border-radius: 50%; object-fit: cover;
            border: 1px solid var(--header-color);
        }
        .rating-box {
            margin: 15px 0; font-size: 14px;
        }
        .stars {
            color: var(--star); font-size: 20px;
        }
        .message-footer {
            padding: 30px 40px; background: var(--bg-cream); border-top: 1px solid var(--border);
        }
        .msg-input {
            width: 100%; padding: 15px 0; font-size: 14px; font-family: var(--font-body);
            border: none; border-bottom: 1px solid var(--text-muted); margin-bottom: 20px;
            background: transparent; color: var(--text-dark); outline: none;
        }
        .msg-input:focus { border-bottom-color: var(--header-color); }
        .send-btn {
            background: var(--header-color); color: var(--white); font-size: 12px;
            font-family: var(--font-body); font-weight: 500; letter-spacing: 1.5px;
            text-transform: uppercase; border: 1px solid var(--header-color);
            width: 100%; padding: 15px; cursor: pointer; transition: 0.3s;
            text-align: center; text-decoration: none; display: block;
        }
        .send-btn:hover { background: transparent; color: var(--header-color); }
        .checkout-link {
            display: block; text-align: center; margin-top: 15px;
            color: var(--header-color); text-decoration: underline;
            font-size: 13px; letter-spacing: 1px; text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <a href="marketplace.php" class="nav-logo">CampusCart</a>
        <a href="marketplace.php" class="close-btn">Back to Marketplace</a>
    </div>

    <div class="split-wrapper">
        <div class="left-image-section">
            <img src="uploads/<?php echo htmlspecialchars($product['product_image']); ?>" class="main-product-image" alt="Product Image">
        </div>

        <div class="right-details-section">
            <div class="details-scroll">
                <h1 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                <div class="product-price">RM <?php echo number_format($product['price'], 2); ?></div>

                <!-- Stock Status -->
                <div class="stock-info">
                    <?php if ($product['stock'] > 0): ?>
                        <span style="color:#5cb85c;">✓ In Stock (<?php echo $product['stock']; ?> available)</span>
                    <?php else: ?>
                        <span style="color:#d9534f; font-weight:bold;">✕ Sold Out</span>
                    <?php endif; ?>
                </div>

                <!-- Product Rating (from ratings table) -->
                <div class="rating-box">
                    <span class="detail-label">Product Rating</span>
                    <div class="stars">
                        <?php echo str_repeat('★', floor($avg_rating)) . str_repeat('☆', 5 - floor($avg_rating)); ?>
                    </div>
                    <span style="color: var(--text-muted);">
                        <?php echo $avg_rating; ?>/5 (<?php echo $total_ratings; ?> ratings)
                    </span>
                </div>

                <!-- Seller Rating (average of all their products) -->
                <div class="rating-box">
                    <span class="detail-label">Seller Rating</span>
                    <div class="stars">
                        <?php echo str_repeat('★', floor($avg_seller_rating)) . str_repeat('☆', 5 - floor($avg_seller_rating)); ?>
                    </div>
                    <span style="color: var(--text-muted);">
                        <?php echo $avg_seller_rating; ?>/5 (<?php echo $total_seller_ratings; ?> ratings)
                    </span>
                </div>

                <span class="detail-label">Description</span>
                <div class="description-text">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>

                <?php if (!empty($product['location'])): ?>
                    <span class="detail-label">Location</span>
                    <div class="description-text" style="font-style: italic;">
                        📍 <?php echo htmlspecialchars($product['location']); ?>
                    </div>
                <?php endif; ?>

                <div class="seller-info">
                    <img src="uploads/default.png" class="seller-avatar" alt="Seller">
                    <div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($product['username']); ?></div>
                        <div style="font-size: 12px; color: var(--text-muted);">Verified UMS Student</div>
                    </div>
                </div>
            </div>

            <div class="message-footer">
                <?php if ($product['seller_id'] != $user_id): ?>
                    <form method="POST">
                        <input type="text" name="message_text" class="msg-input" placeholder="Hi, is this available?" required>
                        <button type="submit" name="quick_message" class="send-btn">Message Seller</button>
                    </form>
                    <a href="checkout.php?product_id=<?php echo $product_id; ?>" class="checkout-link">
                        Proceed to Checkout
                    </a>
                <?php else: ?>
                    <a href="my_shop.php" class="send-btn" style="background: transparent; color: var(--header-color);">
                        Manage Your Listing
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>