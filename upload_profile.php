<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

$error_message = '';
$success_message = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $file = $_FILES['profile_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_message = "Upload error code: " . $file['error'];
    } elseif (!in_array($file['type'], $allowed_types)) {
        $error_message = "Only JPG, PNG, GIF, WebP files are allowed.";
    } elseif ($file['size'] > $max_size) {
        $error_message = "File is too large. Max 5MB.";
    } else {
        $upload_dir = 'uploads/profile/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_name = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $dest_path = $upload_dir . $new_name;

        // Remove old picture (skip default.png)
        $old_query = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
        $old_query->bind_param("i", $user_id);
        $old_query->execute();
        $old_result = $old_query->get_result();
        $old_row = $old_result->fetch_assoc();
        $old_query->close();

        if ($old_row && !empty($old_row['profile_picture']) && $old_row['profile_picture'] !== 'default.png') {
            $old_file = 'uploads/' . $old_row['profile_picture'];
            if (file_exists($old_file)) unlink($old_file);
        }

        if (move_uploaded_file($file['tmp_name'], $dest_path)) {
            $db_path = 'profile/' . $new_name;

            $update = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
            if ($update) {
                $update->bind_param("si", $db_path, $user_id);
                if ($update->execute()) {
                    $_SESSION['flash_success'] = "Profile picture updated successfully!";
                    header("Location: profile.php");
                    exit();
                } else {
                    $error_message = "Database update failed.";
                    unlink($dest_path);
                }
                $update->close();
            } else {
                $error_message = "Database error: " . $conn->error;
                unlink($dest_path);
            }
        } else {
            $error_message = "Failed to save file. Check folder permissions.";
        }
    }
}

// Fetch current picture for display
$user_query = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$current_user = $user_result->fetch_assoc();
$current_picture = $current_user['profile_picture'] ?? 'default.png';
$user_query->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Profile Picture</title>
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
        .container { max-width: 600px; margin: 30px auto; padding: 0 20px; }
        .card { background: var(--white); border: 1px solid var(--border); padding: 30px; border-radius: 4px; text-align: center; }
        h1 { font-family: var(--font-head); font-size: 28px; margin-bottom: 20px; }
        .msg { padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; }
        .msg.error { background: #f2dede; color: var(--error); border: 1px solid #ebccd1; }
        .current-img { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid var(--accent); margin-bottom: 10px; }
        .upload-area { border: 2px dashed var(--border); padding: 30px; margin: 20px 0; border-radius: 4px; background: var(--bg); cursor: pointer; }
        .upload-area:hover, .upload-area.dragover { border-color: var(--accent); background: var(--header-bg); }
        #fileInput { display: none; }
        .preview-container { display: none; margin: 20px 0; }
        .preview-container img { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid var(--accent); }
        .btn-group { display: flex; gap: 10px; justify-content: center; margin-top: 20px; }
        .btn { padding: 10px 25px; border: none; font-family: var(--font-body); font-size: 13px; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; border-radius: 2px; }
        .btn-primary { background: var(--accent); color: white; border: 1px solid var(--accent); }
        .btn-secondary { background: transparent; color: var(--muted); border: 1px solid var(--accent); }
        .btn-back { display: inline-block; margin-top: 20px; color: var(--muted); text-decoration: none; font-size: 12px; }
    </style>
</head>
<body>
    <div class="top-bar">MY &nbsp;|&nbsp; 🎓 Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Student'); ?></div>
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
            <h1>Update Profile Picture</h1>

            <?php if ($error_message): ?>
                <div class="msg error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <img src="uploads/<?php echo htmlspecialchars($current_picture); ?>" 
                 class="current-img" id="currentImg" 
                 onerror="this.src='uploads/default.png'">

            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="file" id="fileInput" name="profile_image" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewFile()">
                
                <div class="upload-area" id="uploadArea" onclick="document.getElementById('fileInput').click()">
                    <div style="font-size: 36px;">📷</div>
                    <div>Click or drag & drop a photo</div>
                    <small style="color: var(--muted);">JPG, PNG, GIF, WebP – max 5MB</small>
                </div>

                <div class="preview-container" id="previewContainer">
                    <img id="previewImg" src="" alt="Preview">
                    <p id="fileName" style="font-size: 12px; color: var(--muted); margin-top: 5px;"></p>
                </div>

                <div class="btn-group" id="btnGroup" style="display: none;">
                    <button type="button" class="btn btn-secondary" onclick="cancelUpload()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Picture</button>
                </div>
            </form>

            <a href="profile.php" class="btn-back">← Back to Profile</a>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('fileInput');
        const uploadArea = document.getElementById('uploadArea');
        const previewContainer = document.getElementById('previewContainer');
        const previewImg = document.getElementById('previewImg');
        const fileName = document.getElementById('fileName');
        const btnGroup = document.getElementById('btnGroup');
        const currentImg = document.getElementById('currentImg');

        function previewFile() {
            const file = fileInput.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) { alert('Please select an image file.'); return; }
            if (file.size > 5 * 1024 * 1024) { alert('File too large (max 5MB).'); return; }

            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                fileName.textContent = file.name;
                previewContainer.style.display = 'block';
                uploadArea.style.display = 'none';
                btnGroup.style.display = 'flex';
                currentImg.style.opacity = '0.5';
            };
            reader.readAsDataURL(file);
        }

        function cancelUpload() {
            fileInput.value = '';
            previewContainer.style.display = 'none';
            uploadArea.style.display = 'block';
            btnGroup.style.display = 'none';
            currentImg.style.opacity = '1';
        }

        // Drag & drop
        uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.classList.add('dragover'); });
        uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length) {
                fileInput.files = files;
                previewFile();
            }
        });
    </script>
</body>
</html>