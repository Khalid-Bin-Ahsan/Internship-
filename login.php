<?php
// login.php
require 'db.php';
session_start();

if (isset($_SESSION['user_name'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = "Please fill in all fields";
    } else {
        $stmt = $pdo->prepare("SELECT id, name, password_hash, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && password_verify($password, $u['password_hash'])) {
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['user_name'] = $u['name'];
            $_SESSION['user_role'] = $u['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login • BlockSight - Secure Access</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary: #0f172a;
            --secondary: #1e293b;
            --accent: #00d4ff;
            --gold: #fbbf24;
            --light: #f8fafc;
            --gray: #64748b;
            --green: #00ff88;
            --error: #ef4444;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, #1e3a8a 50%, #0f4c81 100%);
            color: white;
            min-height: 100vh;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><pattern id="grid" width="80" height="80" patternUnits="userSpaceOnUse"><path d="M 80 0 L 0 0 0 80" fill="none" stroke="%23234162" stroke-width="1" opacity="0.15"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
            pointer-events: none;
            z-index: 0;
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; position: relative; z-index: 2; }

        /* Fixed Header */
        header {
            position: fixed;
            top: 0; left: 0; right: 0;
            padding: 1.5rem 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            background: rgba(15, 23, 42, 0.7);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
            font-size: 2rem;
            font-weight: 700;
        }
        .logo i { color: var(--accent); font-size: 2.5rem; }
        .logo span {
            background: linear-gradient(135deg, #00d4ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Main Content */
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 20px 80px;
        }

        .login-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            padding: 3.5rem 3rem;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4);
            text-align: center;
        }

        .login-box h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        .login-box .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 500;
            font-size: 1.05rem;
        }
        .form-group input {
            width: 100%;
            padding: 1rem 1.2rem;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .form-group input::placeholder { color: rgba(255,255,255,0.6); }
        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.18);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2);
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--accent), var(--green));
            color: black;
            padding: 1.1rem;
            border: none;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
        }
        .btn-login:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(0, 212, 255, 0.4);
        }

        .error-alert {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid var(--error);
            color: #fca5a5;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .login-footer {
            margin-top: 2rem;
            font-size: 1rem;
        }
        .login-footer a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }

        /* Footer */
        footer {
            background: rgba(0, 0, 0, 0.4);
            padding: 2.5rem 0;
            text-align: center;
            font-size: 0.95rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        footer p { opacity: 0.8; margin-bottom: 0.5rem; }
        footer a { color: var(--accent); text-decoration: none; }
        footer a:hover { text-decoration: underline; }

        @media (max-width: 480px) {
            .login-box { padding: 2.5rem 2rem; }
            .login-box h1 { font-size: 2.5rem; }
        }
    </style>
</head>
<body>

    <!-- Fixed Header -->
    <header>
        <div class="header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-eye"></i>
                <span>BlockSight</span>
            </a>
            <div>
                <a href="register.php" style="color:var(--accent); text-decoration:none; margin-left:2rem;">Register</a>
                <a href="index.php" style="color:white; margin-left:2rem; text-decoration:none;">Home</a>
            </div>
        </div>
    </header>

    <!-- Main Login Form -->
    <main>
        <div class="container">
            <div class="login-box">
                <h1>Welcome Back</h1>
                <p class="subtitle">Log in to your BlockSight dashboard</p>

                <?php if (!empty($error)): ?>
                    <div class="error-alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" required placeholder="you@company.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn-login">
                        Login Securely
                    </button>
                </form>

                <div class="login-footer">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 BlockSight. All rights reserved. | Secure • Transparent • Intelligent</p>
            <p>
                <a href="#">Privacy Policy</a> • 
                <a href="#">Terms of Service</a> • 
                <a href="#">Support</a>
            </p>
        </div>
    </footer>
</body>
</html>