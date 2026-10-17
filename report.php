<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$reporter_id = $_SESSION['user_id'];
$reported_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$msg = "";
$reported_username = "General Support"; 

if ($reported_user_id > 0) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $reported_user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) { $reported_username = $res['username']; }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_report'])) {
    $reason = trim($_POST['reason']);
    $description = trim($_POST['description']);
    
    // PEMBETULAN: Jika reported_user_id adalah 0, tukar kepada NULL untuk elak error Foreign Key
    $target_user = ($reported_user_id > 0) ? $reported_user_id : NULL;
    
    $stmt = $conn->prepare("INSERT INTO reports (reporter_id, reported_user_id, reason, description, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iiss", $reporter_id, $target_user, $reason, $description);
    
    if ($stmt->execute()) {
        $msg = "Report successfully submitted to CampusCart Admins.";
    } else {
        $msg = "Error submitting report: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CampusCart - Support</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root { --bg-cream: #FDFBF7; --header-bg: #EAE3D8; --header-color: #887968; --text-dark: #2C2C2C; --text-muted: #7A7873; --white: #FFFFFF; --font-heading: 'Cormorant Garamond', serif; --font-body: 'Jost', sans-serif; }
        body { background-color: var(--bg-cream); margin: 0; font-family: var(--font-body); color: var(--text-dark); }
        
        .top-bar { background-color: var(--header-bg); color: var(--header-color); text-align: center; padding: 12px; font-size: 13px; letter-spacing: 1px; border-bottom: 1px solid rgba(136, 121, 104, 0.15); }
        nav { background-color: var(--header-bg); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(136, 121, 104, 0.2); }
        .nav-logo { color: var(--text-dark); font-family: var(--font-heading); font-size: 28px; text-decoration: none; letter-spacing: 2px; font-weight: 600; font-style: italic; }
        .nav-links { display: flex; gap: 30px; }
        .nav-links a { color: var(--header-color); text-decoration: none; font-size: 13px; letter-spacing: 1.5px; text-transform: uppercase; transition: 0.3s; }
        .nav-links a:hover { color: var(--text-dark); }
        
        .container { max-width: 550px; margin: 60px auto; padding: 0 20px; }
        .fb-card { background: var(--white); border: 1px solid #E8E5DF; padding: 40px; box-shadow: 0 15px 40px rgba(0, 0, 0, 0.03); }
        
        .fb-select, .fb-textarea { width: 100%; padding: 15px; font-size: 14px; font-family: var(--font-body); border: 1px solid #E8E5DF; margin-bottom: 25px; background: transparent; color: var(--text-dark); outline: none; transition: 0.3s; box-sizing: border-box; }
        .btn-danger { background: var(--text-dark); color: var(--white); width: 100%; padding: 16px; cursor: pointer; border: none; text-transform: uppercase; letter-spacing: 1.5px; }
        .btn-danger:hover { background: var(--header-color); }
    </style>
</head>
<body>
    
    <nav>
        <a href="profile.php" class="nav-logo">CampusCart</a>
        <div class="nav-links">
            <a href="profile.php">Marketplace</a>
            <a href="wishlist.php">Wishlist</a>
            <a href="purchase.php">History</a>
            <a href="report.php" style="color: #d9534f; font-weight: 700;">Support</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="fb-card">
            <h2 style="font-family: var(--font-heading); font-size: 32px; font-weight: 500; text-align: center; margin-bottom: 10px;">File a Report</h2>
            <p style="text-align: center; font-weight: 300; color: var(--text-muted); margin-bottom: 30px;">
                Reporting: <strong style="color: var(--header-color);"><?php echo htmlspecialchars($reported_username); ?></strong>
            </p>

            <?php if (!empty($msg)): ?>
                <div style="border: 1px solid var(--header-color); padding: 15px; text-align: center; margin-bottom: 25px; background: var(--bg-cream);">
                    <?php echo $msg; ?>
                </div>
            <?php else: ?>
                <form action="report.php?user_id=<?php echo $reported_user_id; ?>" method="POST">
                    <select name="reason" class="fb-select" required>
                        <option value="">Select an issue...</option>
                        <option value="Scam/Fraud">Fraudulent Listing</option>
                        <option value="Inappropriate Behavior">Inappropriate Behavior</option>
                        <option value="Fake Account">Fake Profile</option>
                        <option value="Other">Other</option>
                    </select>
                    <textarea name="description" class="fb-textarea" rows="4" placeholder="Provide context..." required></textarea>
                    <button type="submit" name="submit_report" class="btn-danger">Submit Report</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>