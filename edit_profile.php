<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

// Fetch current user data
$query = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$user_data = $query->get_result()->fetch_assoc();
$query->close();

$success_message = '';
$error_message   = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $bio       = trim($_POST['bio'] ?? '');
    $location  = trim($_POST['location'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $bank_no   = trim($_POST['bank_no'] ?? '');

    // Validation
    $errors = [];

    if ($username === '') {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters.";
    }

    if ($email === '') {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Check unique email (exclude current user)
    $check = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $check->bind_param("si", $email, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $errors[] = "Email already in use by another account.";
    }
    $check->close();

    // Check unique username
    $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
    $check->bind_param("si", $username, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $errors[] = "Username already taken.";
    }
    $check->close();

    // Simple phone validation
    if (!empty($phone) && !preg_match('/^[0-9+\-\s()]+$/', $phone)) {
        $errors[] = "Phone number contains invalid characters.";
    }

    // Bank account number – only digits (optional)
    if (!empty($bank_no) && !ctype_digit($bank_no)) {
        $errors[] = "Bank account number must contain only digits.";
    }

    if (empty($errors)) {
        // Update user – WITHOUT updated_at
        $update = $conn->prepare("UPDATE users SET 
            username = ?, 
            email = ?, 
            phone = ?, 
            bio = ?, 
            location = ?, 
            bank_name = ?, 
            bank_no = ? 
            WHERE user_id = ?");

        if ($update) {
            $update->bind_param("sssssssi", $username, $email, $phone, $bio, $location, $bank_name, $bank_no, $user_id);
            if ($update->execute()) {
                $_SESSION['username'] = $username;
                $success_message = "Profile updated successfully!";

                // Refresh user data
                $refresh = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
                $refresh->bind_param("i", $user_id);
                $refresh->execute();
                $user_data = $refresh->get_result()->fetch_assoc();
                $refresh->close();
            } else {
                $error_message = "Database update failed: " . $update->error;
            }
            $update->close();
        } else {
            $error_message = "Prepare failed: " . $conn->error;
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
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
        .nav-links a { margin-left: 25px; text-decoration: none; color: var(--muted); font-size: 13px; text-transform: uppercase; letter-spacing: 1px; }
        .container { max-width: 650px; margin: 30px auto; padding: 0 20px; }
        .card { background: var(--white); border: 1px solid var(--border); padding: 30px; border-radius: 4px; }
        h1 { font-family: var(--font-head); font-size: 28px; margin-top: 0; text-align: center; }
        .msg { padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; }
        .msg.success { background: #dff0d8; color: var(--success); border: 1px solid #d6e9c6; }
        .msg.error { background: #f2dede; color: var(--error); border: 1px solid #ebccd1; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 5px; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); }
        input[type="text"], input[type="email"], input[type="tel"], textarea {
            width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 2px;
            font-family: var(--font-body); font-size: 14px; background: var(--bg);
        }
        textarea { min-height: 90px; resize: vertical; }
        .help { font-size: 11px; color: var(--muted); margin-top: 3px; }
        .btn-group { display: flex; gap: 12px; margin-top: 25px; }
        .btn { flex: 1; padding: 12px 20px; border: none; font-family: var(--font-body); font-size: 13px; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; border-radius: 2px; text-align: center; text-decoration: none; }
        .btn-primary { background: var(--accent); color: white; border: 1px solid var(--accent); }
        .btn-secondary { background: transparent; color: var(--muted); border: 1px solid var(--accent); }
        .btn:hover { opacity: 0.9; }
        .profile-pic-section { text-align: center; margin-bottom: 25px; }
        .profile-pic-section img { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid var(--accent); }
        .change-pic { font-size: 12px; color: var(--muted); margin-top: 8px; display: inline-block; text-decoration: none; }
        hr { border: none; border-top: 1px solid var(--border); margin: 20px 0; }
    </style>
</head>
<body>
    <div class="top-bar">🇲🇾 MY &nbsp;|&nbsp; 🎓 Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Student'); ?></div>
    <nav>
        <a href="profile.php" class="nav-logo">CampusCart</a>
        <div class="nav-links">
            <a href="marketplace.php">Marketplace</a>
            <a href="profile.php">My Profile</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <h1>Edit Profile</h1>

            <?php if ($success_message): ?>
                <div class="msg success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="msg error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Profile picture -->
            <div class="profile-pic-section">
                <?php $pic = $user_data['profile_picture'] ?? 'default.png'; ?>
                <img src="uploads/<?php echo htmlspecialchars($pic); ?>" onerror="this.src='uploads/default.png'" alt="Profile">
                <br>
                <a href="upload_profile.php" class="change-pic">Change Profile Picture</a>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" required minlength="3">
                    <div class="help">Min. 3 characters</div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" placeholder="+60 12-3456789">
                </div>

                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" maxlength="500" placeholder="Tell others about yourself..."><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
                    <div class="help">Brief description (max 500 characters)</div>
                </div>

                <hr>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user_data['location'] ?? ''); ?>" placeholder="City, Campus">
                    <div class="help">Where are you based?</div>
                </div>

                <div class="form-group">
                    <label for="bank_name">Bank Name</label>
                    <input type="text" id="bank_name" name="bank_name" value="<?php echo htmlspecialchars($user_data['bank_name'] ?? ''); ?>" placeholder="e.g., Maybank">
                </div>

                <div class="form-group">
                    <label for="bank_no">Bank Account Number</label>
                    <input type="text" id="bank_no" name="bank_no" value="<?php echo htmlspecialchars($user_data['bank_no'] ?? ''); ?>" placeholder="Account number (digits only)" pattern="[0-9]+">
                    <div class="help">Only numbers allowed</div>
                </div>

                <div class="btn-group">
                    <a href="profile.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>