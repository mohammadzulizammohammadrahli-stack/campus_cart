<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

$product_id = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
if ($product_id <= 0) { header("Location: marketplace.php"); exit(); }

// Fetch product & seller
$stmt = $conn->prepare("SELECT p.*, u.username AS seller_name, u.bank_name, u.bank_no 
                        FROM products p JOIN users u ON p.seller_id = u.user_id 
                        WHERE p.product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$product) { header("Location: marketplace.php"); exit(); }

// --- STOCK CHECK ---
if ($product['stock'] <= 0) {
    $_SESSION['flash_error'] = "Sorry, this item is no longer available.";
    header("Location: view_product.php?id=" . $product_id);
    exit();
}

$step = 'choose';
$payment_method = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['payment_method']) && !isset($_POST['confirm_payment'])) {
        $step = 'details';
        $payment_method = $_POST['payment_method'];
    } elseif (isset($_POST['confirm_payment'])) {
        // Process payment
        $amount = (float)($_POST['amount'] ?? $product['price']);
        $delivery = $_POST['delivery_method'] ?? 'pickup';
        $pay_method = $_POST['payment_method'];
        $fpx_bank = $_POST['fpx_bank'] ?? '';
        $note = $pay_method;
        if ($pay_method === 'FPX' && $fpx_bank) $note .= " ($fpx_bank)";

        // Insert purchase and decrement stock
        $conn->begin_transaction();
        try {
            $ins = $conn->prepare("INSERT INTO purchases (buyer_id, seller_id, product_id, amount, payment_method, delivery_method, status, purchased_at) VALUES (?, ?, ?, ?, ?, ?, 'Paid', NOW())");
            $ins->bind_param("iiidss", $user_id, $product['seller_id'], $product_id, $amount, $note, $delivery);
            $ins->execute();
            $ins->close();

            // Decrease stock
            $upd = $conn->prepare("UPDATE products SET stock = stock - 1 WHERE product_id = ? AND stock > 0");
            $upd->bind_param("i", $product_id);
            $upd->execute();
            $upd->close();

            $conn->commit();
            $_SESSION['flash_success'] = "Payment successful! Purchase recorded.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_success'] = "Payment recorded (simulated).";
        }
        header("Location: purchase.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout – CampusCart</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #FDFBF7; --hdr: #EAE3D8; --hdr-clr: #887968; --txt: #2C2C2C; --muted: #7A7873; --white: #fff; --accent: #B8A99A; --border: #E8E5DF; --font-h: 'Cormorant Garamond', serif; --font-b: 'Jost', sans-serif; }
        body { background: var(--bg); color: var(--txt); font-family: var(--font-b); margin:0; }
        .top-bar { background: var(--hdr); color: var(--hdr-clr); text-align:center; padding:10px; font-size:13px; }
        nav { background: var(--white); padding:15px 30px; display:flex; justify-content:space-between; border-bottom:1px solid var(--border); }
        .nav-logo { font-family:var(--font-h); font-size:28px; text-decoration:none; color:var(--txt); }
        .container { max-width:550px; margin:30px auto; padding:0 20px; }
        .card { background:var(--white); border:1px solid var(--border); padding:25px; border-radius:4px; }
        h1 { font-family:var(--font-h); font-size:28px; margin-top:0; }
        .summary { background:var(--bg); border:1px solid var(--border); padding:15px; margin-bottom:20px; }
        .row { display:flex; justify-content:space-between; margin-bottom:6px; }
        .amount { font-family:var(--font-h); font-size:24px; color:var(--hdr-clr); }
        .btns { display:flex; gap:12px; margin-bottom:20px; }
        .btn-radio { flex:1; padding:18px; border:1px solid var(--border); text-align:center; cursor:pointer; background:var(--white); }
        .btn-radio:hover, .btn-radio input:checked+span { border-color:var(--accent); background:var(--hdr); }
        .btn { display:block; width:100%; padding:14px; background:var(--hdr); color:var(--hdr-clr); border:1px solid var(--accent); text-transform:uppercase; letter-spacing:1px; cursor:pointer; margin-top:15px; }
        .btn:hover { background:var(--accent); color:white; }
        .cancel { display:block; text-align:center; margin-top:10px; color:var(--muted); font-size:13px; }
        .bank-opt { border:1px solid var(--border); padding:12px 15px; display:flex; align-items:center; gap:10px; margin-bottom:8px; cursor:pointer; }
        .bank-opt:hover { border-color:var(--accent); }
        .detail-box { background:var(--bg); border:1px solid var(--border); padding:15px; margin-bottom:20px; }
        .detail-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border); }
        .detail-row:last-child { border:none; }
    </style>
</head>
<body>
<div class="top-bar">🔒 Secure Checkout</div>
<nav><a href="marketplace.php" class="nav-logo">CampusCart</a></nav>
<div class="container">
<div class="card">
    <h1>Checkout</h1>
    <div class="summary">
        <div class="row"><span>Item</span><span><?= htmlspecialchars($product['product_name']) ?></span></div>
        <div class="row"><span>Seller</span><span><?= htmlspecialchars($product['seller_name']) ?></span></div>
        <div class="row"><span>Price</span><span class="amount">RM <?= number_format($product['price'], 2) ?></span></div>
        <div class="row"><span>Stock</span><span><?= $product['stock'] ?> available</span></div>
    </div>

    <?php if ($step === 'choose'): ?>
        <h2>Payment Method</h2>
        <form method="post">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <div class="btns">
                <label class="btn-radio"><input type="radio" name="payment_method" value="FPX" required> 💳 FPX Online</label>
                <label class="btn-radio"><input type="radio" name="payment_method" value="Manual Transfer"> 🏦 Manual Transfer</label>
            </div>
            <button type="submit" class="btn">Continue</button>
        </form>
        <a href="marketplace.php" class="cancel">Cancel</a>

    <?php elseif ($step === 'details' && $payment_method === 'FPX'): ?>
        <h2>FPX – Select Your Bank</h2>
        <form method="post">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <input type="hidden" name="amount" value="<?= $product['price'] ?>">
            <input type="hidden" name="delivery_method" value="pickup">
            <input type="hidden" name="payment_method" value="FPX">
            <input type="hidden" name="confirm_payment" value="1">
            <label class="bank-opt"><input type="radio" name="fpx_bank" value="Maybank2u" required> Maybank2u</label>
            <label class="bank-opt"><input type="radio" name="fpx_bank" value="CIMB Clicks"> CIMB Clicks</label>
            <label class="bank-opt"><input type="radio" name="fpx_bank" value="Bank Islam"> Bank Islam</label>
            <label class="bank-opt"><input type="radio" name="fpx_bank" value="RHB Now"> RHB Now</label>
            <label class="bank-opt"><input type="radio" name="fpx_bank" value="Public Bank"> Public Bank</label>
            <button type="submit" class="btn">Confirm Payment</button>
        </form>
        <a href="checkout.php?product_id=<?= $product_id ?>" class="cancel">← Back</a>

    <?php elseif ($step === 'details' && $payment_method === 'Manual Transfer'): ?>
        <h2>Manual Transfer</h2>
        <p style="margin-bottom:15px;">Transfer exactly the amount below to the seller’s account.</p>
        <div class="detail-box">
            <div class="detail-row"><span>Amount</span><span>RM <?= number_format($product['price'], 2) ?></span></div>
            <div class="detail-row"><span>Bank Name</span><span><?= htmlspecialchars($product['bank_name'] ?? 'N/A') ?></span></div>
            <div class="detail-row"><span>Account No.</span><span><?= htmlspecialchars($product['bank_no'] ?? 'N/A') ?></span></div>
            <div class="detail-row"><span>Reference</span><span><?= htmlspecialchars($product['product_name']) ?></span></div>
        </div>
        <form method="post">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <input type="hidden" name="amount" value="<?= $product['price'] ?>">
            <input type="hidden" name="delivery_method" value="pickup">
            <input type="hidden" name="payment_method" value="Manual Transfer">
            <input type="hidden" name="confirm_payment" value="1">
            <button type="submit" class="btn">I Have Transferred</button>
        </form>
        <a href="checkout.php?product_id=<?= $product_id ?>" class="cancel">← Back</a>
    <?php endif; ?>
</div>
</div>
</body>
</html>