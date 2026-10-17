<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

$success_message = '';
$error_message   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $price        = trim($_POST['price'] ?? '');
    $stock        = (int)($_POST['stock'] ?? 1);
    $category     = trim($_POST['category'] ?? '');
    $image        = $_FILES['product_image'] ?? null;

    $errors = [];
    if ($product_name === '') $errors[] = "Product name is required.";
    if ($price === '' || !is_numeric($price) || $price <= 0) $errors[] = "Price must be a positive number.";
    if ($stock < 1) $errors[] = "Stock must be at least 1.";
    if (empty($category)) $errors[] = "Please select a category.";

    // Image validation
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024;
    $image_name = null;

    if ($image && $image['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($image['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Image upload error (code: " . $image['error'] . ").";
        } elseif (!in_array($image['type'], $allowed_types)) {
            $errors[] = "Only JPG, PNG, GIF, WebP images are allowed.";
        } elseif ($image['size'] > $max_size) {
            $errors[] = "Image size must be less than 5MB.";
        } else {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
            $image_name = 'product_' . time() . '_' . $user_id . '.' . $ext;
            $dest_path = $upload_dir . $image_name;
            if (!move_uploaded_file($image['tmp_name'], $dest_path)) {
                $errors[] = "Failed to save image.";
            }
        }
    } else {
        $errors[] = "Product image is required.";
    }

    if (empty($errors)) {
        $insert = $conn->prepare("INSERT INTO products (seller_id, product_name, description, price, stock, product_image, category, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        if ($insert) {
            $insert->bind_param("issdiss", $user_id, $product_name, $description, $price, $stock, $image_name, $category);
            if ($insert->execute()) {
                $success_message = "Product listed successfully!";
            } else {
                $error_message = "Database error: " . $insert->error;
                if ($image_name) unlink($dest_path);
            }
            $insert->close();
        } else {
            $error_message = "Prepare failed: " . $conn->error;
            if ($image_name) unlink($dest_path);
        }
    } else {
        $error_message = implode("<br>", $errors);
        if ($image_name) unlink($dest_path);
    }
}

$categories = ['Books', 'Food', 'Gadgets', 'Clothing', 'Stationery', 'Others'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sell an Item – CampusCart</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root { --bg-cream: #FDFBF7; --header-bg: #EAE3D8; --header-color: #887968; --text-dark: #2C2C2C; --text-muted: #7A7873; --white: #FFFFFF; --accent: #B8A99A; --border: #E8E5DF; --error: #d9534f; --success: #5cb85c; --font-heading: 'Cormorant Garamond', serif; --font-body: 'Jost', sans-serif; }
        body { background: var(--bg-cream); color: var(--text-dark); font-family: var(--font-body); margin: 0; }
        .top-bar { background: var(--header-bg); color: var(--header-color); text-align: center; padding: 10px; font-size: 13px; }
        nav { background: var(--white); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); }
        .nav-logo { font-family: var(--font-heading); font-size: 28px; text-decoration: none; color: var(--text-dark); font-weight: 600; }
        .nav-links a { margin-left: 25px; text-decoration: none; color: var(--header-color); font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; }
        .container { max-width: 700px; margin: 30px auto; padding: 0 20px; }
        .card { background: var(--white); border: 1px solid var(--border); padding: 30px; border-radius: 4px; }
        h1 { font-family: var(--font-heading); font-size: 28px; margin-top: 0; text-align: center; }
        .msg { padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; }
        .msg.success { background: #dff0d8; color: var(--success); border: 1px solid #d6e9c6; }
        .msg.error { background: #f2dede; color: var(--error); border: 1px solid #ebccd1; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 5px; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: var(--header-color); }
        input[type="text"], input[type="number"], textarea, select { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 2px; font-family: var(--font-body); font-size: 14px; background: var(--bg-cream); }
        textarea { min-height: 100px; resize: vertical; }
        .help { font-size: 11px; color: var(--text-muted); margin-top: 3px; }
        .btn-group { display: flex; gap: 12px; margin-top: 25px; }
        .btn { flex: 1; padding: 12px 20px; border: none; font-family: var(--font-body); font-size: 13px; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; border-radius: 2px; text-align: center; text-decoration: none; }
        .btn-primary { background: var(--accent); color: white; border: 1px solid var(--accent); }
        .btn-secondary { background: transparent; color: var(--header-color); border: 1px solid var(--accent); }
        .btn:hover { opacity: 0.9; }
        .file-upload { border: 2px dashed var(--border); padding: 30px; text-align: center; cursor: pointer; background: var(--bg-cream); }
        .file-upload:hover { border-color: var(--accent); }
        #imagePreview { max-width: 200px; max-height: 200px; margin-top: 10px; display: none; }
    </style>
</head>
<body>
    <div class="top-bar">🇲🇾 MY &nbsp;|&nbsp; 🎓 Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Student'); ?></div>
    <nav>
        <a href="profile.php" class="nav-logo">CampusCart</a>
        <div class="nav-links">
            <a href="marketplace.php">Marketplace</a>
            <a href="profile.php">My Profile</a>
            <a href="add_product.php">Sell Item</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <h1>Sell an Item</h1>
            <?php if ($success_message): ?>
                <div class="msg success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="msg error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" action="">
                <div class="form-group">
                    <label for="product_name">Product Name *</label>
                    <input type="text" id="product_name" name="product_name" required value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    <div class="help">Describe condition, features, etc.</div>
                </div>
                <div class="form-group">
                    <label for="price">Price (RM) *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0.01" required value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="stock">Stock Quantity *</label>
                    <input type="number" id="stock" name="stock" min="1" value="<?php echo $_POST['stock'] ?? 1; ?>" required>
                    <div class="help">How many items you have available.</div>
                </div>
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo (isset($_POST['category']) && $_POST['category'] === $cat) ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Product Image *</label>
                    <div class="file-upload" onclick="document.getElementById('product_image').click()">
                        <div id="uploadText">📷 Click to upload image</div>
                        <input type="file" id="product_image" name="product_image" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;" onchange="previewImage(event)">
                        <img id="imagePreview" src="#" alt="Preview">
                    </div>
                    <div class="help">JPG, PNG, GIF, WebP – max 5MB</div>
                </div>
                <div class="btn-group">
                    <a href="marketplace.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">List Product</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    document.getElementById('uploadText').style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>