<?php
// register_company.php
require 'db.php';
$config = require 'config.php';
$upload_dir = $config['upload_dir'];

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $company_name = trim($_POST['company_name'] ?? '');
    $regno = $_POST['registration_number'] ?? '';
    $industry = $_POST['industry'] ?? '';
    $location = $_POST['location'] ?? '';
    $annual_revenue = floatval($_POST['annual_revenue'] ?? 0);
    $net_profit = floatval($_POST['net_profit'] ?? 0);
    $revenue_growth_yoy = floatval($_POST['revenue_growth_yoy'] ?? 0);
    $profit_margin = floatval($_POST['profit_margin'] ?? 0);

    $pdf_path = null;
    if (!empty($_FILES['company_pdf']['name'])) {
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $fn = basename($_FILES['company_pdf']['name']);
        $ext = pathinfo($fn, PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'pdf') {
            $error = "Only PDF files are allowed.";
        } elseif ($_FILES['company_pdf']['size'] > 10_000_000) { // 10MB limit
            $error = "PDF file is too large (max 10MB).";
        } else {
            $target = $upload_dir . '/' . time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $fn);
            if (move_uploaded_file($_FILES['company_pdf']['tmp_name'], $target)) {
                $pdf_path = str_replace(__DIR__ . '/', '', $target);
            } else {
                $error = "Failed to upload PDF.";
            }
        }
    }

    if (!$name || !$email || !$password || !$company_name) {
        $error = "Please fill all required fields.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        // Check if email already exists
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = "This email is already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'company')");
            $stmt->execute([$name, $email, $hash]);
            $uid = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO companies 
                (user_id, company_name, registration_number, industry, location, 
                 annual_revenue, net_profit, revenue_growth_yoy, profit_margin, document_pdf, documents_count)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$uid, $company_name, $regno, $industry, $location,
                $annual_revenue, $net_profit, $revenue_growth_yoy, $profit_margin, $pdf_path, ($pdf_path?1:0)]);

            session_start();
            $_SESSION['user_id'] = $uid;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'company';
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
    <title>Register Company • BlockSight</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary: #0f172a;
            --accent: #00d4ff;
            --green: #00ff88;
            --gold: #fbbf24;
            --gray: #64748b;
            --light: #f8fafc;
            --error: #ef4444;
            --success: #10b981;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, #1e3a8a 50%, #0f4c81 100%);
            color: white;
            min-height: 100vh;
            position: relative;
        }
        body::before {
            content: ''; position: absolute; inset: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><pattern id="grid" width="80" height="80" patternUnits="userSpaceOnUse"><path d="M 80 0 L 0 0 0 80" fill="none" stroke="%23234162" stroke-width="1" opacity="0.15"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
            pointer-events: none;
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; position: relative; z-index: 2; }

        header {
            position: fixed; top: 0; left: 0; right: 0;
            padding: 1.5rem 0; z-index: 1000;
            backdrop-filter: blur(10px);
            background: rgba(15, 23, 42, 0.7);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .header-content {
            display: flex; justify-content: space-between; align-items: center;
            max-width: 1200px; margin: 0 auto; padding: 0 20px;
        }
        .logo {
            display: flex; align-items: center; gap: 12px; color: white; text-decoration: none;
            font-size: 2rem; font-weight: 700;
        }
        .logo i { color: var(--accent); font-size: 2.5rem; }
        .logo span {
            background: linear-gradient(135deg, #00d4ff, #00ff88);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        main {
            flex: 1; padding: 120px 20px 100px;
            display: flex; align-items: center; justify-content: center;
        }

        .form-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 24px;
            padding: 3rem 3.5rem;
            width: 100%; max-width: 700px;
            box-shadow: 0 25px 70px rgba(0,0,0,0.4);
        }

        .form-header {
            text-align: center; margin-bottom: 2.5rem;
        }
        .form-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem; margin-bottom: 0.5rem;
        }
        .form-header p {
            font-size: 1.2rem; opacity: 0.9;
        }

        .section-title {
            font-size: 1.6rem; margin: 2.5rem 0 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid var(--accent);
            display: inline-block;
        }

        .form-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group.full { grid-column: 1 / -1; }
        label {
            display: block; margin-bottom: 0.6rem; font-weight: 500; font-size: 1.05rem;
        }
        input, select {
            width: 100%; padding: 1rem 1.2rem;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px; color: white; font-size: 1rem;
            transition: all 0.3s;
        }
        input::placeholder { color: rgba(255,255,255,0.6); }
        input:focus, select:focus {
            outline: none; border-color: var(--accent);
            background: rgba(255,255,255,0.18);
            box-shadow: 0 0 0 3px rgba(0,212,255,0.2);
        }

        .file-input {
            padding: 1.5rem;
            background: rgba(255,255,255,0.08);
            border: 2px dashed rgba(255,255,255,0.3);
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-input:hover {
            border-color: var(--accent);
            background: rgba(0,212,255,0.1);
        }
        .file-input input { display: none; }

        .btn-submit {
            width: 100%; margin-top: 2rem;
            background: linear-gradient(135deg, var(--accent), var(--green));
            color: black; padding: 1.2rem;
            border: none; border-radius: 50px;
            font-size: 1.3rem; font-weight: 600;
            cursor: pointer; transition: all 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,212,255,0.4);
        }

        .alert {
            padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 1rem;
        }
        .alert-error { background: rgba(239,68,68,0.2); border: 1px solid var(--error); color: #fca5a5; }
        .alert-success { background: rgba(16,185,129,0.2); border: 1px solid var(--success); color: #a7f3d0; }

        .back-link {
            text-align: center; margin-top: 2rem;
        }
        .back-link a { color: var(--accent); text-decoration: none; font-weight: 500; }
        .back-link a:hover { text-decoration: underline; }

        footer {
            background: rgba(0,0,0,0.4); padding: 2.5rem 0; text-align: center;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.95rem;
        }
        footer a { color: var(--accent); text-decoration: none; }
        footer a:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-card { padding: 2.5rem 2rem; }
            .form-header h1 { font-size: 2.5rem; }
        }
    </style>
</head>
<body>

    <header>
        <div class="header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-eye"></i>
                <span>BlockSight</span>
            </a>
            <div>
                <a href="register.php" style="color:var(--accent); margin-left:2rem;">Back to Roles</a>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="form-card">
                <div class="form-header">
                    <h1>Company Registration</h1>
                    <p>Join BlockSight and raise capital with full transparency</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success">
                        Registration successful! Redirecting to your dashboard...
                        <script>setTimeout(() => location.href='dashboard.php', 2000);</script>
                    </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                <form method="post" enctype="multipart/form-data">
                    <h3 class="section-title">Account Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Your Full Name *</label>
                            <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="form-group full">
                            <label>Password (min 8 chars) *</label>
                            <input type="password" name="password" required minlength="8">
                        </div>
                    </div>

                    <h3 class="section-title">Company Details</h3>
                    <div class="form-grid">
                        <div class="form-group full">
                            <label>Company Legal Name *</label>
                            <input type="text" name="company_name" required value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Registration Number</label>
                            <input type="text" name="registration_number" value="<?= htmlspecialchars($_POST['registration_number'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Industry</label>
                            <input type="text" name="industry" value="<?= htmlspecialchars($_POST['industry'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Location (City, Country)</label>
                            <input type="text" name="location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Annual Revenue (USD)</label>
                            <input type="number" step="0.01" name="annual_revenue" value="<?= htmlspecialchars($_POST['annual_revenue'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Net Profit (USD)</label>
                            <input type="number" step="0.01" name="net_profit" value="<?= htmlspecialchars($_POST['net_profit'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Revenue Growth YoY (e.g. 0.45 = 45%)</label>
                            <input type="number" step="0.01" name="revenue_growth_yoy" value="<?= htmlspecialchars($_POST['revenue_growth_yoy'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Profit Margin (e.g. 0.18 = 18%)</label>
                            <input type="number" step="0.01" name="profit_margin" value="<?= htmlspecialchars($_POST['profit_margin'] ?? '') ?>">
                        </div>
                    </div>

                    <h3 class="section-title">Upload Financial Document (PDF)</h3>
                    <div class="form-group full">
                        <label class="file-input">
                            <i class="fas fa-cloud-upload-alt" style="font-size:2rem; margin-bottom:1rem; display:block;"></i>
                            <strong>Click to upload company PDF</strong><br>
                            <span style="font-size:0.9rem; opacity:0.8;">Max 10MB • Financial statements, pitch deck, etc.</span>
                            <input type="file" name="company_pdf" accept="application/pdf">
                        </label>
                    </div>

                    <button type="submit" class="btn-submit">
                        Register & Create Company Profile
                    </button>
                </form>
                <?php endif; ?>

                <div class="back-link">
                    <a href="register.php">Back to role selection</a>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>© 2025 BlockSight • Transparent. Intelligent. Bangladesh-Powered.</p>
            <p><a href="#">Privacy</a> • <a href="#">Terms</a> • <a href="#">Support</a></p>
        </div>
    </footer>
</body>
</html>