<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "Group3db";
$port = 3306;

// 1. Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Create database connection
$conn = new mysqli($host, $user, $pass, $dbname, $port);

// 3. Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// 4. Create uploads folder if it doesn't exist
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

// 5. Update user's last_seen (every 60 seconds to reduce DB load)
if (isset($_SESSION['user_id'])) {
    $update_last_seen = $conn->prepare("UPDATE users SET last_seen = NOW() WHERE user_id = ? AND (last_seen IS NULL OR last_seen < DATE_SUB(NOW(), INTERVAL 60 SECOND))");
    if ($update_last_seen) {
        $update_last_seen->bind_param("i", $_SESSION['user_id']);
        $update_last_seen->execute();
        $update_last_seen->close();
    }
}
?>