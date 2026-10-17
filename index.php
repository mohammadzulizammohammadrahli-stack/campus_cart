<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';
$error = '';
if (isset($_SESSION['user_id'])) { header("Location: profile.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            header("Location: profile.php");
            exit();
        } else { 
            $error = "The password you've entered is incorrect."; 
        }
    } else { 
        header("Location: register.php"); 
        exit(); 
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CampusCart- Log in</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg-cream: #FDFBF7; 
            --header-bg: #EAE3D8; /* New soft beige/brownish background for the header */
            --header-color: #887968; 
            --btn-beige: #EBE6DF;    
            --text-dark: #2C2C2C; 
            --text-muted: #7A7873; 
            --white: #FFFFFF; 
            --font-heading: 'Cormorant Garamond', serif; 
            --font-body: 'Jost', sans-serif; 
        }
        body { background-color: var(--bg-cream); color: var(--text-dark); font-family: var(--font-body); margin: 0; }
        
        /* The New Header Styling with Beige Background */
        .top-bar { background-color: var(--header-bg); color: var(--header-color); text-align: center; padding: 12px; font-size: 13px; font-weight: 400; letter-spacing: 1px; border-bottom: 1px solid rgba(136, 121, 104, 0.15); font-family: var(--font-body); }
        
        nav { background-color: var(--header-bg); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(136, 121, 104, 0.2); }
        .nav-left { display: flex; align-items: center; gap: 40px; }
        .nav-logo { color: var(--text-dark); font-family: var(--font-heading); font-size: 32px; text-decoration: none; letter-spacing: 2px; font-weight: 600; font-style: italic; }
        .nav-links { display: flex; gap: 25px; font-weight: 400; font-size: 14px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--header-color); }
        .nav-icons { display: flex; align-items: center; gap: 20px; color: var(--header-color); font-size: 20px; }
        
        .split-layout { display: flex; max-width: 1200px; margin: 60px auto; padding: 0 40px; align-items: center; justify-content: space-between; gap: 80px; }
        .split-left { flex: 1; }
        .split-right { width: 420px; flex-shrink: 0; }
        
        .fb-heading { font-family: var(--font-heading); font-size: 64px; line-height: 1.1; color: var(--header-color); font-weight: 500; margin: 0 0 15px 0; letter-spacing: 1px; }
        .hero-image { width: 100%; max-width: 420px; aspect-ratio: 3/4; object-fit: cover; border-radius: 200px 200px 0 0; border: 1px solid #E8E5DF; margin-top: 20px; }
        
        .fb-card { background-color: var(--white); border: 1px solid #E8E5DF; padding: 40px; width: 100%; box-sizing: border-box; }
        .fb-input { width: 100%; padding: 15px 0; font-size: 14px; font-family: var(--font-body); border: none; border-bottom: 1px solid var(--text-muted); margin-bottom: 25px; background: transparent; color: var(--text-dark); outline: none; }
        .fb-input:focus { border-bottom-color: var(--header-color); }
        
        .fb-btn-primary { background: var(--btn-beige); color: var(--header-color); font-size: 13px; font-family: var(--font-body); font-weight: 500; letter-spacing: 1.5px; text-transform: uppercase; border: 1px solid var(--header-color); width: 100%; padding: 16px; cursor: pointer; transition: 0.3s; }
        .fb-btn-primary:hover { background: var(--header-color); color: var(--bg-cream); }
        
        .fb-btn-secondary { display: block; color: var(--header-color); font-size: 13px; text-decoration: underline; text-underline-offset: 4px; text-align: center; margin-top: 20px; transition: 0.3s; }
        .fb-btn-secondary:hover { color: var(--text-dark); }
    </style>
</head>
<body>
    <div class="top-bar">
         MY &nbsp;&nbsp;|&nbsp;&nbsp; 🎓 Exclusive marketplace for UMS Students.
    </div>
    <nav>
        <div class="nav-left">
            <a href="index.php" class="nav-logo">CampusCart</a>
   
</div>
            </div>
        </div>
    </nav>
    <div class="split-layout">
        <div class="split-left">
            <h1 class="fb-heading">Explore the<br>campus marketplace<br>you love.</h1>
            <p style="font-size: 18px; font-weight: 300; color: var(--text-dark);">Buy, sell, and trade essentials elegantly within the university.</p>
            <img src="depop likes.jpg" alt="Aesthetic Collection" class="hero-image">
        </div>
        <div class="split-right">
            <div class="fb-card">
                <h2 style="font-family: var(--font-heading); font-size: 32px; font-weight: 500; margin-top: 0; color: var(--header-color); text-align: center;">Log In</h2>
                
                <?php if (!empty($error)): ?>
                    <div style="color: #A94442; border: 1px solid #A94442; padding: 10px; margin-bottom: 20px; font-size: 13px; text-align: center;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form action="index.php" method="POST">
                    <input type="email" name="email" class="fb-input" placeholder="UMS Email address" required>
                    
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" class="fb-input" placeholder="Password" required>
                        <span id="togglePassword" style="position: absolute; right: 0; top: 15px; cursor: pointer; color: var(--header-color);">👁️</span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 25px; font-size: 13px; font-weight: 300;">
                        <input type="checkbox" name="remember" id="remember" style="accent-color: var(--header-color);">
                        <label for="remember">Remember me</label>
                    </div>
                    
                    <button type="submit" class="fb-btn-primary">Log in</button>
                    <a href="register.php" class="fb-btn-secondary">Create new account</a>
                </form>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    </script>
</body>
</html>