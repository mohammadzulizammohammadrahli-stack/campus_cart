<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!preg_match('/@(student\.)?ums\.edu\.my$/i', $email)) {
        $error = "Registration requires a valid UMS student email (@student.ums.edu.my).";
    } else {
        $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows > 0) {
            $error = "This email is already registered!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $username, $email, $hashed_password);
            
            if ($stmt_insert->execute()) {
                header("Location: index.php");
                exit();
            } else {
                $error = "Something went wrong. Try again!";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CampusCart - Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg-cream: #FDFBF7; 
            --header-color: #887968; /* The new earthy taupe color */
            --btn-beige: #EBE6DF;    /* Soft beige for the button */
            --text-dark: #2C2C2C; 
            --text-muted: #7A7873; 
            --white: #FFFFFF; 
            --font-heading: 'Cormorant Garamond', serif; 
            --font-body: 'Jost', sans-serif; 
        }
        body { background-color: var(--bg-cream); color: var(--text-dark); font-family: var(--font-body); margin: 0; }
        
        .top-bar { background-color: var(--bg-cream); color: var(--header-color); text-align: center; padding: 12px; font-size: 13px; font-weight: 400; letter-spacing: 1px; border-bottom: 1px solid #E8E5DF; font-family: var(--font-body); }
        
        nav { background-color: transparent; padding: 20px 40px; text-align: center; border-bottom: 1px solid #E8E5DF; }
        .nav-logo { color: var(--text-dark); font-family: var(--font-heading); font-size: 32px; text-decoration: none; letter-spacing: 2px; font-weight: 600; font-style: italic; }
        
        .fb-card { background-color: var(--white); border: 1px solid #E8E5DF; padding: 50px; width: 100%; box-sizing: border-box; }
        .fb-input { width: 100%; padding: 15px 0; font-size: 14px; font-family: var(--font-body); border: none; border-bottom: 1px solid var(--text-muted); margin-bottom: 25px; background: transparent; color: var(--text-dark); outline: none; }
        .fb-input:focus { border-bottom-color: var(--header-color); }
        
        /* The Beige Button */
        .fb-btn-primary { background: var(--btn-beige); color: var(--header-color); font-size: 13px; font-family: var(--font-body); font-weight: 500; letter-spacing: 1.5px; text-transform: uppercase; border: 1px solid var(--header-color); width: 100%; padding: 16px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .fb-btn-primary:hover { background: var(--header-color); color: var(--bg-cream); }
    </style>
</head>
<body>
    <div class="top-bar">
        🇲🇾 MY &nbsp;&nbsp;|&nbsp;&nbsp; 🎓 Exclusive marketplace for UMS Students.
    </div>
    <nav>
        <a href="index.php" class="nav-logo">CampusCart</a>
    </nav>
    <div style="display: flex; align-items: center; justify-content: center; min-height: 80vh; padding: 40px 20px;">
        <div class="fb-card" style="max-width: 480px;">
            <h2 style="font-family: var(--font-heading); font-size: 36px; font-weight: 500; text-align: center; margin-top: 0; color: var(--header-color);">Join the Collection</h2>
            
            <?php if (!empty($error)): ?>
                <div style="color: #A94442; border: 1px solid #A94442; padding: 10px; margin-bottom: 20px; font-size: 13px; text-align: center;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <input type="text" name="username" class="fb-input" placeholder="Display Name" required>
                <input type="email" name="email" class="fb-input" placeholder="UMS Email (@ums.edu.my)" required>
                
                <div style="position: relative;">
                    <input type="password" id="password_reg" name="password" class="fb-input" placeholder="New password" required>
                    <span id="togglePasswordReg" style="position: absolute; right: 0; top: 15px; cursor: pointer; color: var(--header-color);">👁️</span>
                </div>
                
                <p style="font-size: 12px; font-weight: 300; margin-bottom: 25px; color: var(--text-muted); text-align: center; line-height: 1.5;">
                    By clicking Sign Up, you agree to our Terms and that you are a verified student of Universiti Malaysia Sabah.
                </p>
                <button type="submit" class="fb-btn-primary">Sign Up</button>
            </form>
            <div style="text-align: center; margin-top: 25px;">
                <a href="index.php" style="color: var(--header-color); font-size: 14px; text-decoration: underline; font-weight: 400; text-underline-offset: 4px;">Already have an account? Log in</a>
            </div>
        </div>
    </div>
    <script>
        const togglePasswordReg = document.querySelector('#togglePasswordReg');
        const passwordReg = document.querySelector('#password_reg');
        togglePasswordReg.addEventListener('click', function () {
            const type = passwordReg.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordReg.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    </script>
</body>
</html>