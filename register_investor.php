<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once 'db.php';
$config = require 'config.php';
$upload_dir = $config['upload_dir'];

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $affiliations = trim($_POST['affiliations'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    $pic_path = null;

    // Upload profile picture
    if (!empty($_FILES['picture']['name'])) {
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = "Only JPG, PNG, WebP, or GIF images are allowed.";
        } elseif ($_FILES['picture']['size'] > 5_000_000) {
            $error = "Image must be under 5MB.";
        } else {
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES['picture']['name']));
            $target = $upload_dir . '/' . $filename;
            if (move_uploaded_file($_FILES['picture']['tmp_name'], $target)) {
                $pic_path = 'uploads/' . $filename;
            } else {
                $error = "Failed to upload image.";
            }
        }
    }

    if (!$name || !$email || !$password) {
        $error = "Name, email, and password are required.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = "Email already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, picture) VALUES (?, ?, ?, 'investor', ?)");
            $stmt->execute([$name, $email, $hash, $pic_path]);

            $uid = $pdo->lastInsertId();

            $_SESSION['user_id'] = $uid;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'investor';
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register as Investor • BlockSight</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {--primary:#0f172a;--accent:#00d4ff;--green:#00ff88;--error:#ef4444;--success:#10b981;}
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,var(--primary) 0%,#1e3a8a 50%,#0f4c81 100%);color:white;min-height:100vh;position:relative;}
        body::before{content:'';position:absolute;inset:0;background:url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><pattern id="grid" width="80" height="80" patternUnits="userSpaceOnUse"><path d="M 80 0 L 0 0 0 80" fill="none" stroke="%23234162" stroke-width="1" opacity="0.15"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');pointer-events:none;}
        .container{max-width:1200px;margin:0 auto;padding:0 20px;position:relative;z-index:2;}
        header{position:fixed;top:0;left:0;right:0;padding:1.5rem 0;z-index:1000;backdrop-filter:blur(10px);background:rgba(15,23,42,0.7);}
        .header-content{display:flex;justify-content:space-between;align-items:center;max-width:1200px;margin:0 auto;padding:0 20px;}
        .logo{display:flex;align-items:center;gap:12px;color:white;text-decoration:none;font-size:2rem;font-weight:700;}
        .logo i{color:var(--accent);font-size:2.5rem;}
        .logo span{background:linear-gradient(135deg,#00d4ff,#00ff88);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
        main{flex:1;padding:120px 20px 100px;display:flex;align-items:center;justify-content:center;}
        .form-card{background:rgba(255,255,255,0.1);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.15);border-radius:24px;padding:3rem 3.5rem;width:100%;max-width:600px;box-shadow:0 25px 70px rgba(0,0,0,0.4);}
        .form-header{text-align:center;margin-bottom:2.5rem;}
        .form-header h1{font-family:'Playfair Display',serif;font-size:3rem;margin-bottom:0.5rem;}
        .form-group{margin-bottom:1.8rem;}
        label{display:block;margin-bottom:0.6rem;font-weight:500;}
        input,textarea{width:100%;padding:1rem 1.2rem;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);border-radius:12px;color:white;transition:all .3s;}
        input:focus,textarea:focus{outline:none;border-color:var(--accent);background:rgba(255,255,255,0.18);box-shadow:0 0 0 3px rgba(0,212,255,0.2);}
        .file-input{padding:2rem;background:rgba(255,255,255,0.08);border:2px dashed rgba(255,255,255,0.3);border-radius:16px;text-align:center;cursor:pointer;transition:all .3s;}
        .file-input:hover{border-color:var(--accent);background:rgba(0,212,255,0.1);}
        .file-input input{display:none;}
        .btn-submit{width:100%;margin-top:1.5rem;background:linear-gradient(135deg,var(--accent),var(--green));color:black;padding:1.3rem;border:none;border-radius:50px;font-size:1.3rem;font-weight:600;cursor:pointer;transition:all .3s;}
        .btn-submit:hover{transform:translateY(-5px);box-shadow:0 20px 40px rgba(0,212,255,0.4);}
        .alert{padding:1rem 1.5rem;border-radius:12px;margin-bottom:1.5rem;}
        .alert-error{background:rgba(239,68,68,0.2);border:1px solid var(--error);color:#fca5a5;}
        .alert-success{background:rgba(16,185,129,0.2);border:1px solid var(--success);color:#a7f3d0;}
        .back-link{text-align:center;margin-top:2rem;}
        .back-link a{color:var(--accent);text-decoration:none;font-weight:500;}
        footer{background:rgba(0,0,0,0.4);padding:2.5rem 0;text-align:center;border-top:1px solid rgba(255,255,255,0.1);font-size:0.95rem;}
        footer a{color:var(--accent);text-decoration:none;}
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <a href="index.php" class="logo"><i class="fas fa-eye"></i><span>BlockSight</span></a>
            <a href="register.php" style="color:var(--accent);">Back</a>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="form-card">
                <div class="form-header">
                    <h1>Join as Investor</h1>
                    <p>Access verified startups with blockchain transparency</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success">
                        Success! Redirecting to dashboard...
                        <script>setTimeout(() => location.href='dashboard.php', 2000);</script>
                    </div>
                <?php else: ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group"><label>Full Name *</label><input type="text" name="name" required value="<?= htmlspecialchars($_POST['name']??'') ?>"></div>
                    <div class="form-group"><label>Email *</label><input type="email" name="email" required value="<?= htmlspecialchars($_POST['email']??'') ?>"></div>
                    <div class="form-group"><label>Password *</label><input type="password" name="password" required minlength="8"></div>
                    <div class="form-group"><label>Affiliations (optional)</label><input type="text" name="affiliations" value="<?= htmlspecialchars($_POST['affiliations']??'') ?>"></div>
                    <div class="form-group"><label>Short Bio</label><textarea name="bio"><?= htmlspecialchars($_POST['bio']??'') ?></textarea></div>
                    <div class="form-group">
                        <label class="file-input">
                            <i class="fas fa-user-circle" style="font-size:3rem;margin-bottom:1rem;display:block;"></i>
                            <strong>Upload Picture</strong><br><span style="opacity:0.8;font-size:0.9rem;">Max 5MB</span>
                            <input type="file" name="picture" accept="image/*">
                        </label>
                    </div>
                    <button type="submit" class="btn-submit">Register as Investor</button>
                </form>
                <?php endif; ?>

                <div class="back-link"><a href="register.php">Back to Roles</a></div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>© 2025 BlockSight • Bangladesh’s Trusted Investment Platform</p>
        </div>
    </footer>
</body>
</html>