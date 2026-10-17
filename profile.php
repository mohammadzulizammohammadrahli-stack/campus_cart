<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

// Flash messages from upload or other actions
$success = $_SESSION['flash_success'] ?? null;
$error   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Fetch user data
$query = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$user = $query->get_result()->fetch_assoc();
$query->close();

// Fallback for missing fields
$username = $user['username'] ?? 'User';
$email = $user['email'] ?? '';
$profile_picture = $user['profile_picture'] ?? 'default.png';
$created_at = $user['created_at'] ?? date('Y-m-d');

// Stats
$products_count = 0; $wishlist_count = 0; $purchases_count = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM products WHERE seller_id = ?")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $products_count = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
}
if ($stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM wishlists WHERE user_id = ?")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $wishlist_count = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
}
if ($stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM purchases WHERE buyer_id = ?")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $purchases_count = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
}

// ---- Handle Report Submission ----
$report_success = $report_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $reported_id = (int)($_POST['reported_user_id'] ?? 0);
    $reason = trim($_POST['report_reason'] ?? '');
    $desc = trim($_POST['report_description'] ?? '');

    if ($reported_id <= 0) {
        $report_error = "Please enter a valid User ID to report.";
    } elseif ($reported_id == $user_id) {
        $report_error = "You cannot report yourself.";
    } elseif ($reason === '' || $desc === '') {
        $report_error = "Please fill in both reason and description.";
    } else {
        // Check if reported user exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $check->bind_param("i", $reported_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows == 0) {
            $report_error = "User ID not found.";
        } else {
            $ins = $conn->prepare("INSERT INTO reports (reporter_id, reported_user_id, reason, description, status) VALUES (?, ?, ?, ?, 'pending')");
            $ins->bind_param("iiss", $user_id, $reported_id, $reason, $desc);
            if ($ins->execute()) {
                $report_success = "Report submitted successfully. The admin team will review it.";
            } else {
                $report_error = "Error submitting report.";
            }
            $ins->close();
        }
        $check->close();
    }
}

// ---- Handle Contact Admin Submission ----
$contact_success = $contact_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $subject = trim($_POST['contact_subject'] ?? '');
    $message = trim($_POST['contact_message'] ?? '');

    if ($subject === '' || $message === '') {
        $contact_error = "Subject and message are required.";
    } else {
        $ins = $conn->prepare("INSERT INTO contact_messages (sender_id, subject, message) VALUES (?, ?, ?)");
        $ins->bind_param("iss", $user_id, $subject, $message);
        if ($ins->execute()) {
            $contact_success = "Message sent to admin. We'll get back to you soon.";
        } else {
            $contact_error = "Failed to send message.";
        }
        $ins->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile – CampusCart</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #FDFBF7; --header-bg: #EAE3D8; --text: #2C2C2C;
            --muted: #7A7873; --white: #fff; --accent: #B8A99A;
            --border: #E8E5DF; --error: #d9534f; --success: #5cb85c;
            --font-head: 'Cormorant Garamond', serif; --font-body: 'Jost', sans-serif;
        }
        body { background: var(--bg); color: var(--text); font-family: var(--font-body); margin: 0; }
        .top-bar { background: var(--header-bg); color: var(--muted); text-align: center; padding: 10px; font-size: 13px; }
        nav { background: var(--white); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); }
        .nav-logo { font-family: var(--font-head); font-size: 28px; text-decoration: none; color: var(--text); font-weight: 600; }
        .nav-links a { margin-left: 25px; text-decoration: none; color: var(--muted); font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; }
        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        .flash { padding: 15px; border-radius: 4px; margin-bottom: 25px; text-align: center; }
        .flash.success { background: #dff0d8; color: var(--success); border: 1px solid #d6e9c6; }
        .flash.error { background: #f2dede; color: var(--error); border: 1px solid #ebccd1; }
        .profile-header { background: var(--white); border: 1px solid var(--border); padding: 30px; display: flex; gap: 30px; align-items: center; border-radius: 4px; margin-bottom: 25px; }
        .profile-pic { width: 130px; height: 130px; border-radius: 50%; object-fit: cover; border: 4px solid var(--accent); }
        .profile-details h1 { font-family: var(--font-head); font-size: 32px; margin: 0 0 5px; }
        .profile-details p { color: var(--muted); margin: 5px 0; font-size: 14px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat { background: var(--white); border: 1px solid var(--border); padding: 20px; text-align: center; border-radius: 4px; }
        .stat .number { font-family: var(--font-head); font-size: 36px; color: var(--accent); }
        .stat .label { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); }
        .actions { background: var(--white); border: 1px solid var(--border); padding: 25px; border-radius: 4px; margin-bottom: 25px; }
        .actions h2 { font-family: var(--font-head); font-size: 22px; margin-top: 0; }
        .action-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 20px; }
        .action-item { border: 1px solid var(--border); padding: 15px; text-align: center; font-size: 13px; cursor: pointer; color: var(--muted); text-transform: uppercase; text-decoration: none; display: block; }
        .action-item:hover { background: var(--header-bg); color: var(--text); }
        .settings { background: var(--white); border: 1px solid var(--border); padding: 25px; border-radius: 4px; margin-bottom: 25px; }
        .settings h2 { font-family: var(--font-head); font-size: 22px; }
        .setting-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid var(--border); }
        .setting-row:last-child { border-bottom: none; }
        .setting-label { font-size: 14px; }
        .setting-desc { font-size: 12px; color: var(--muted); }
        .btn { padding: 8px 20px; border: 1px solid var(--accent); color: var(--muted); text-transform: uppercase; font-size: 12px; text-decoration: none; background: transparent; cursor: pointer; display: inline-block; }
        .btn:hover { background: var(--header-bg); color: var(--text); }
        .btn.danger { border-color: var(--error); color: var(--error); }
        .btn.danger:hover { background: var(--error); color: white; }
        /* Form styles inside cards */
        .form-card { background: var(--white); border: 1px solid var(--border); padding: 25px; border-radius: 4px; margin-bottom: 25px; }
        .form-card h2 { font-family: var(--font-head); font-size: 22px; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; text-transform: uppercase; color: var(--muted); }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid var(--border); font-family: var(--font-body); background: var(--bg); font-size: 14px; }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .btn-submit { width: 100%; padding: 12px; background: var(--accent); color: white; border: none; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; font-size: 13px; }
        .btn-submit:hover { opacity: 0.9; }
        .msg { padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .msg.success { background: #dff0d8; color: var(--success); border: 1px solid #d6e9c6; }
        .msg.error { background: #f2dede; color: var(--error); border: 1px solid #ebccd1; }
    </style>
</head>
<body>
    <div class="top-bar">MY &nbsp;|&nbsp; 🎓 Welcome, <?php echo htmlspecialchars($username); ?></div>
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
        <!-- Flash messages (from other pages) -->
        <?php if ($success): ?>
            <div class="flash success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="flash error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <img src="uploads/<?php echo htmlspecialchars($profile_picture); ?>" 
                 class="profile-pic" 
                 onerror="this.src='uploads/default.png'">
            <div class="profile-details">
                <h1><?php echo htmlspecialchars($username); ?></h1>
                <p>Email: <?php echo htmlspecialchars($email); ?></p>
                <p>Member since <?php echo date('F Y', strtotime($created_at)); ?></p>
                <a href="upload_profile.php" class="btn" style="margin-top:10px;">Change Photo</a>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat">
                <div class="number"><?php echo $products_count; ?></div>
                <div class="label">Products Listed</div>
            </div>
            <div class="stat">
                <div class="number"><?php echo $wishlist_count; ?></div>
                <div class="label">Wishlist Items</div>
            </div>
            <div class="stat">
                <div class="number"><?php echo $purchases_count; ?></div>
                <div class="label">Purchases</div>
            </div>
        </div>

     <!-- Quick Actions -->
<div class="actions">
    <h2>Quick Actions</h2>
    <div class="action-grid">
        <a href="my_shop.php" class="action-item">
            <span class="action-icon">📦</span>
            Manage Listings
            <span class="action-sub">Products</span>
        </a>
        <a href="purchase.php" class="action-item">
            <span class="action-icon">📋</span>
            Order History
            <span class="action-sub">Purchases</span>
        </a>
        <a href="report.php" class="action-item">
            <span class="action-icon">🛡️</span>
            Support Center
            <span class="action-sub">Reports & Help</span>
        </a>
        <a href="messages.php" class="action-item">
            <span class="action-icon">✉️</span>
            Inbox
            <span class="action-sub">Messages</span>
        </a>
    </div>
</div>

        <!-- Account Settings -->
        <div class="settings">
            <h2>Account Settings</h2>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Edit Profile</div>
                    <div class="setting-desc">Change name, email, bio, phone</div>
                </div>
                <a href="edit_profile.php" class="btn">Edit</a>
            </div>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Change Password</div>
                    <div class="setting-desc">Update your account password</div>
                </div>
                <a href="change_password.php" class="btn">Update</a>
            </div>
        </div>

        <!-- Report a User Section -->
        <div class="form-card">
            <h2>Report a User</h2>
            <?php if ($report_success): ?>
                <div class="msg success"><?php echo htmlspecialchars($report_success); ?></div>
            <?php else: ?>
                <?php if ($report_error): ?>
                    <div class="msg error"><?php echo htmlspecialchars($report_error); ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="form-group">
                        <label for="reported_user_id">User ID to Report *</label>
                        <input type="number" id="reported_user_id" name="reported_user_id" required placeholder="Enter the user's ID (found on their profile)">
                    </div>
                    <div class="form-group">
                        <label for="report_reason">Reason *</label>
                        <select id="report_reason" name="report_reason" required>
                            <option value="">-- Select issue --</option>
                            <option value="Scam/Fraud">Scam / Fraud</option>
                            <option value="Inappropriate Behavior">Inappropriate Behavior</option>
                            <option value="Fake Account">Fake Account</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="report_description">Description *</label>
                        <textarea id="report_description" name="report_description" required placeholder="Provide details..."></textarea>
                    </div>
                    <button type="submit" name="submit_report" class="btn-submit">Submit Report</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Contact Admin Section -->
        <div class="form-card">
            <h2>Contact Admin</h2>
            <?php if ($contact_success): ?>
                <div class="msg success"><?php echo htmlspecialchars($contact_success); ?></div>
            <?php else: ?>
                <?php if ($contact_error): ?>
                    <div class="msg error"><?php echo htmlspecialchars($contact_error); ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="form-group">
                        <label for="contact_subject">Subject *</label>
                        <input type="text" id="contact_subject" name="contact_subject" required placeholder="e.g., Payment issue, Account problem">
                    </div>
                    <div class="form-group">
                        <label for="contact_message">Message *</label>
                        <textarea id="contact_message" name="contact_message" required placeholder="Describe your problem in detail..."></textarea>
                    </div>
                    <button type="submit" name="submit_contact" class="btn-submit">Send Message</button>
                </form>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>