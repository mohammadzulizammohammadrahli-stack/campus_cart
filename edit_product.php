<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

$product_id = (int)($_GET['id'] ?? 0);
if ($product_id <= 0) { header("Location: my_shop.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ? AND seller_id = ?");
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$product) { header("Location: my_shop.php"); exit(); }

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['product_name'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $price    = trim($_POST['price'] ?? '');
    $stock    = (int)($_POST['stock'] ?? 1);
    $category = trim($_POST['category'] ?? '');

    $errors = [];
    if ($name === '') $errors[] = "Product name required.";
    if ($price === '' || !is_numeric($price) || $price <= 0) $errors[] = "Valid price required.";
    if ($stock < 1) $errors[] = "Stock must be at least 1.";
    if ($category === '') $errors[] = "Category required.";

    // Image handling
    $image_name = $product['product_image'];
    if (!empty($_FILES['product_image']['tmp_name'])) {
        $file = $_FILES['product_image'];
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($file['type'], $allowed)) $errors[] = "Invalid image type.";
        elseif ($file['size'] > 5*1024*1024) $errors[] = "Image too large (max 5MB).";
        else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $new_name = 'product_' . time() . '_' . $user_id . '.' . $ext;
            $dest = 'uploads/' . $new_name;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                if ($product['product_image'] && file_exists('uploads/'.$product['product_image'])) {
                    unlink('uploads/'.$product['product_image']);
                }
                $image_name = $new_name;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    if (empty($errors)) {
        $upd = $conn->prepare("UPDATE products SET product_name=?, description=?, price=?, stock=?, category=?, product_image=? WHERE product_id=? AND seller_id=?");
        $upd->bind_param("ssdissii", $name, $desc, $price, $stock, $category, $image_name, $product_id, $user_id);
        if ($upd->execute()) {
            $_SESSION['flash_success'] = "Product updated!";
            header("Location: my_shop.php");
            exit();
        } else {
            $error = "Update failed: " . $upd->error;
        }
        $upd->close();
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product – CampusCart</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #FDFBF7; --header-bg: #EAE3D8; --header-color: #887968; --text: #2C2C2C; --muted: #7A7873; --white: #fff; --accent: #B8A99A; --border: #E8E5DF; --font-h: 'Cormorant Garamond', serif; --font-b: 'Jost', sans-serif; }
        body { background: var(--bg); color: var(--text); font-family: var(--font-b); margin:0; }
        .top-bar { background: var(--header-bg); color: var(--header-color); text-align:center; padding:10px; font-size:13px; }
        nav { background: var(--white); padding:15px 30px; display:flex; justify-content:space-between; border-bottom:1px solid var(--border); }
        .nav-logo { font-family:var(--font-h); font-size:28px; text-decoration:none; color:var(--text); }
        .container { max-width:600px; margin:30px auto; padding:0 20px; }
        .card { background:var(--white); border:1px solid var(--border); padding:25px; border-radius:4px; }
        h1 { font-family:var(--font-h); font-size:28px; margin-top:0; }
        .msg { padding:10px; margin-bottom:15px; border-radius:4px; }
        .msg.error { background:#f2dede; color:#d9534f; }
        label { display:block; margin:10px 0 5px; font-size:13px; text-transform:uppercase; color:var(--header-color); }
        input, textarea, select { width:100%; padding:8px; border:1px solid var(--border); background:var(--bg); font-family:var(--font-b); }
        .btn { padding:12px 25px; background:var(--header-bg); color:var(--header-color); border:1px solid var(--accent); text-transform:uppercase; cursor:pointer; margin-top:15px; }
        .btn:hover { background:var(--accent); color:white; }
        .back { display:inline-block; margin-top:10px; color:var(--muted); }
    </style>
</head>
<body>
<div class="top-bar">✏️ Edit Product</div>
<nav><a href="my_shop.php" class="nav-logo">CampusCart</a></nav>
<div class="container">
<div class="card">
    <h1>Edit Product</h1>
    <?php if ($error): ?><div class="msg error"><?= $error ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <label>Product Name</label>
        <input type="text" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required>
        <label>Description</label>
        <textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea>
        <label>Price (RM)</label>
        <input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required>
        <label>Stock Quantity</label>
        <input type="number" name="stock" min="1" value="<?= $product['stock'] ?>" required>
        <label>Category</label>
        <select name="category">
            <?php foreach (['Books','Food','Gadgets','Clothing','Stationery','Others'] as $cat): ?>
                <option value="<?= $cat ?>" <?= $product['category'] == $cat ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
        </select>
        <label>Current Image</label>
        <img src="uploads/<?= htmlspecialchars($product['product_image'] ?? 'default.png') ?>" style="width:100px; display:block; margin-bottom:10px;">
        <label>New Image (optional)</label>
        <input type="file" name="product_image" accept="image/*">
        <button type="submit" class="btn">Update Product</button>
    </form>
    <a href="my_shop.php" class="back">← Back to My Shop</a>
</div>
</div>
</body>
</html>