<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

// Auto-detect message column name
$text_column = 'message_text';
$check_cols = $conn->query("SHOW COLUMNS FROM messages");
if ($check_cols) {
    $existing = [];
    while ($col = $check_cols->fetch_assoc()) { $existing[] = $col['Field']; }
    if (in_array('message', $existing)) $text_column = 'message';
    elseif (in_array('msg', $existing)) $text_column = 'msg';
    elseif (in_array('text', $existing)) $text_column = 'text';
}

// Determine active chat
$active_chat = null;
$active_product = null;
$active_other_user = null;
$chat_with_id = 0;
$product_id = 0;

// Open a chat from URL parameters
if (isset($_GET['product_id'])) {
    $product_id = (int)$_GET['product_id'];
    $stmt = $conn->prepare("SELECT seller_id FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($res) { $chat_with_id = $res['seller_id']; }
} elseif (isset($_GET['with_id']) && isset($_GET['prod_id'])) {
    $chat_with_id = (int)$_GET['with_id'];
    $product_id = (int)$_GET['prod_id'];
}

// If there's an active chat, fetch its details
if ($chat_with_id > 0 && $product_id > 0) {
    // Fetch product
    $stmt = $conn->prepare("SELECT product_name, product_image, seller_id FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $active_product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Fetch other user
    $stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $chat_with_id);
    $stmt->execute();
    $active_other_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message_text = trim($_POST['message_text']);
    $msg_product_id = (int)$_POST['product_id'];
    $msg_receiver_id = (int)$_POST['receiver_id'];
    
    if (!empty($message_text) && $msg_product_id > 0 && $msg_receiver_id > 0) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, product_id, $text_column) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $user_id, $msg_receiver_id, $msg_product_id, $message_text);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to same chat to avoid resubmission
        if ($msg_receiver_id == ($active_product['seller_id'] ?? 0)) {
            header("Location: messages.php?product_id=" . $msg_product_id);
        } else {
            header("Location: messages.php?with_id=" . $msg_receiver_id . "&prod_id=" . $msg_product_id);
        }
        exit();
    }
}

// Fetch all conversations
$conv_query = "
    SELECT 
        m.*,
        p.product_name,
        p.product_image,
        CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AS other_user_id,
        u.username AS other_username
    FROM messages m
    JOIN products p ON m.product_id = p.product_id
    JOIN users u ON (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END) = u.user_id
    WHERE m.message_id IN (
        SELECT MAX(message_id) 
        FROM messages 
        WHERE sender_id = ? OR receiver_id = ?
        GROUP BY product_id, 
                 CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END
    )
    ORDER BY m.message_id DESC
";

$stmt = $conn->prepare($conv_query);
$stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$conversations = $stmt->get_result();

// Fetch messages for active chat
$messages_result = null;
if ($chat_with_id > 0 && $product_id > 0) {
    $msg_query = "SELECT * FROM messages WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND product_id = ? ORDER BY message_id ASC";
    $msg_stmt = $conn->prepare($msg_query);
    $msg_stmt->bind_param("iiiii", $user_id, $chat_with_id, $chat_with_id, $user_id, $product_id);
    $msg_stmt->execute();
    $messages_result = $msg_stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages – CampusCart</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #FDFBF7; --header-bg: #EAE3D8; --header-color: #887968;
            --text: #2C2C2C; --muted: #7A7873; --white: #fff;
            --accent: #B8A99A; --border: #E8E5DF;
            --font-h: 'Cormorant Garamond', serif; --font-b: 'Jost', sans-serif;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--bg); color: var(--text); font-family: var(--font-b); height: 100vh; overflow: hidden; }
        .top-bar { background: var(--header-bg); color: var(--header-color); text-align: center; padding: 10px; font-size: 13px; }
        nav { background: var(--white); padding: 12px 25px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); }
        .nav-logo { font-family: var(--font-h); font-size: 26px; text-decoration: none; color: var(--text); font-weight: 600; }
        .nav-links a { margin-left: 20px; text-decoration: none; color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .nav-links a.active { color: var(--text); border-bottom: 2px solid var(--accent); }
        
        /* Main Layout */
        .main-container { display: flex; height: calc(100vh - 105px); }
        
        /* Conversation List (Left Panel) */
        .conv-list { width: 350px; border-right: 1px solid var(--border); background: var(--white); overflow-y: auto; flex-shrink: 0; }
        .conv-list-header { padding: 20px; border-bottom: 1px solid var(--border); }
        .conv-list-header h2 { font-family: var(--font-h); font-size: 22px; color: var(--header-color); }
        .conv-item { display: flex; gap: 12px; padding: 15px 20px; border-bottom: 1px solid var(--border); cursor: pointer; text-decoration: none; color: var(--text); transition: background 0.2s; align-items: center; }
        .conv-item:hover, .conv-item.active { background: var(--bg); }
        .conv-item.active { border-left: 3px solid var(--accent); }
        .conv-avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent); flex-shrink: 0; }
        .conv-details { flex: 1; min-width: 0; }
        .conv-name { font-weight: 500; font-size: 14px; }
        .conv-product { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .conv-preview { font-size: 12px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 3px; }
        .conv-time { font-size: 11px; color: var(--muted); text-align: right; flex-shrink: 0; }
        
        /* Chat Area (Right Panel) */
        .chat-area { flex: 1; display: flex; flex-direction: column; background: var(--white); }
        .chat-empty { flex: 1; display: flex; align-items: center; justify-content: center; color: var(--muted); text-align: center; }
        .chat-header { padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: var(--bg); }
        .chat-header-left { display: flex; align-items: center; gap: 12px; }
        .chat-header img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent); }
        .chat-header-name { font-weight: 500; }
        .chat-header-product { font-size: 12px; color: var(--muted); }
        .chat-messages { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; }
        .bubble-me { max-width: 65%; padding: 10px 16px; border-radius: 16px 16px 4px 16px; background: var(--header-color); color: white; font-size: 14px; align-self: flex-end; word-wrap: break-word; }
        .bubble-other { max-width: 65%; padding: 10px 16px; border-radius: 16px 16px 16px 4px; background: var(--bg); border: 1px solid var(--border); color: var(--text); font-size: 14px; align-self: flex-start; word-wrap: break-word; }
        .chat-input-area { padding: 15px 20px; border-top: 1px solid var(--border); display: flex; gap: 10px; background: var(--white); }
        .chat-input { flex: 1; border: 1px solid var(--border); padding: 10px 15px; font-family: var(--font-b); font-size: 14px; outline: none; background: var(--bg); border-radius: 20px; }
        .chat-input:focus { border-color: var(--accent); }
        .send-btn { background: var(--accent); color: white; border: none; padding: 10px 20px; font-family: var(--font-b); text-transform: uppercase; letter-spacing: 1px; font-size: 11px; cursor: pointer; border-radius: 20px; }
        .send-btn:hover { opacity: 0.9; }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .conv-list { width: 100%; }
            .chat-area { display: none; }
            .chat-area.active { display: flex; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 100; }
            .main-container.show-chat .conv-list { display: none; }
            .main-container.show-chat .chat-area { display: flex; }
        }
    </style>
</head>
<body>
    <div class="top-bar">🇲🇾 MY &nbsp;|&nbsp; 🎓 Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Student'); ?></div>
    <nav>
        <a href="profile.php" class="nav-logo">CampusCart</a>
        <div class="nav-links">
            <a href="marketplace.php">Marketplace</a>
            <a href="profile.php">My Profile</a>
            <a href="messages.php" class="active">Messages</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="main-container">
        <!-- Conversation List (Left Panel) -->
        <div class="conv-list" id="convList">
            <div class="conv-list-header">
                <h2>Messages</h2>
            </div>
            <?php if ($conversations && $conversations->num_rows > 0): ?>
                <?php while ($conv = $conversations->fetch_assoc()): 
                    $is_active = ($conv['other_user_id'] == $chat_with_id && $conv['product_id'] == $product_id);
                    $preview = $conv[$text_column] ?? '';
                    if (strlen($preview) > 40) $preview = substr($preview, 0, 40) . '...';
                    $link = "messages.php?with_id=" . $conv['other_user_id'] . "&prod_id=" . $conv['product_id'];
                ?>
                    <a href="<?php echo $link; ?>" class="conv-item <?php echo $is_active ? 'active' : ''; ?>">
                        <img src="uploads/<?php echo htmlspecialchars($conv['product_image'] ?? 'default.png'); ?>" class="conv-avatar" onerror="this.src='uploads/default.png'">
                        <div class="conv-details">
                            <div class="conv-name"><?php echo htmlspecialchars($conv['other_username']); ?></div>
                            <div class="conv-product"><?php echo htmlspecialchars($conv['product_name']); ?></div>
                            <div class="conv-preview"><?php echo htmlspecialchars($preview); ?></div>
                        </div>
                        <div class="conv-time"><?php echo date('d/m', strtotime($conv['created_at'] ?? 'now')); ?></div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; color: var(--muted);">
                    <p>No conversations yet.</p>
                    <p style="font-size: 13px;">Start chatting from a product page.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Chat Area (Right Panel) -->
        <div class="chat-area" id="chatArea">
            <?php if ($active_product && $active_other_user): ?>
                <!-- Active Chat Header -->
                <div class="chat-header">
                    <div class="chat-header-left">
                        <img src="uploads/<?php echo htmlspecialchars($active_product['product_image'] ?? 'default.png'); ?>" onerror="this.src='uploads/default.png'">
                        <div>
                            <div class="chat-header-name"><?php echo htmlspecialchars($active_other_user['username']); ?></div>
                            <div class="chat-header-product"><?php echo htmlspecialchars($active_product['product_name']); ?></div>
                        </div>
                    </div>
                    <?php if (isset($active_product['seller_id']) && $active_product['seller_id'] != $user_id): ?>
                        <a href="checkout.php?product_id=<?php echo $product_id; ?>" style="padding: 8px 15px; background: var(--accent); color: white; text-decoration: none; font-size: 11px; text-transform: uppercase; border-radius: 4px;">Buy Now</a>
                    <?php endif; ?>
                </div>

                <!-- Messages -->
                <div class="chat-messages" id="chatMessages">
                    <?php if ($messages_result && $messages_result->num_rows > 0): ?>
                        <?php while($msg = $messages_result->fetch_assoc()): ?>
                            <div class="<?php echo ($msg['sender_id'] == $user_id) ? 'bubble-me' : 'bubble-other'; ?>">
                                <?php echo htmlspecialchars($msg[$text_column] ?? ''); ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; color: var(--muted); margin-top: 60px;">
                            No messages yet. Say hello! 👋
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Input Area -->
                <form method="POST" class="chat-input-area">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    <input type="hidden" name="receiver_id" value="<?php echo $chat_with_id; ?>">
                    <input type="text" name="message_text" class="chat-input" placeholder="Type a message..." required autocomplete="off">
                    <button type="submit" name="send_message" class="send-btn">Send</button>
                </form>
            <?php else: ?>
                <!-- Empty State -->
                <div class="chat-empty">
                    <div>
                        <div style="font-size: 48px; margin-bottom: 15px;">💬</div>
                        <h3 style="font-family: var(--font-h); color: var(--header-color);">Your Messages</h3>
                        <p style="font-size: 14px;">Select a conversation to start chatting</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-scroll chat to bottom
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>