<?php
require_once 'helpers.php';
session_start();
if (isset($_SESSION['user_name'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register • BlockSight - Join Bangladesh's Trusted Investment Platform</title>

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
            content: '';
            position: absolute;
            inset: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><pattern id="grid" width="80" height="80" patternUnits="userSpaceOnUse"><path d="M 80 0 L 0 0 0 80" fill="none" stroke="%23234162" stroke-width="1" opacity="0.15"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
            pointer-events: none;
            z-index: 0;
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; position: relative; z-index: 2; }

        /* Header */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 1.5rem 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            background: rgba(15, 23, 42, 0.7);
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

        /* Main Content - Fixed spacing & no overlap */
        main {
            min-height: calc(100vh - 180px);
            padding: 120px 0 100px;
            display: flex;
            align-items: center;
        }
        .register-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5rem;
            align-items: center;
        }

        .left-side h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.8rem;
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }
        .highlight { color: var(--accent); }
        .left-side p {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 1.5rem;
            max-width: 520px;
        }
        .stats {
            display: flex;
            gap: 3rem;
            margin-top: 3rem;
            flex-wrap: wrap;
        }
        .stat-item h3 {
            font-size: 2.8rem;
            color: var(--accent);
            font-weight: 700;
        }
        .stat-item p { font-size: 1rem; opacity: 0.85; }

        .right-side {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            padding: 3.5rem 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .right-side h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .right-side p.lead {
            text-align: center;
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 3rem;
        }
        .role-cards {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.8rem;
        }
        .role-card {
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid transparent;
            border-radius: 18px;
            padding: 2.2rem;
            text-align: center;
            transition: all 0.4s ease;
        }
        .role-card:hover {
            transform: translateY(-10px);
            background: rgba(0, 212, 255, 0.15);
            border-color: var(--accent);
            box-shadow: 0 20px 40px rgba(0, 212, 255, 0.2);
        }
        .role-card i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--accent), var(--green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .role-card h3 { font-size: 1.7rem; margin-bottom: 1rem; }
        .role-card p { opacity: 0.9; margin-bottom: 1.8rem; font-size: 1.05rem; }
        .role-card .btn {
            background: linear-gradient(135deg, var(--accent), var(--green));
            color: black;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            display: inline-block;
            transition: all 0.3s;
        }
        .role-card .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 30px rgba(0, 212, 255, 0.4);
        }

        .back-link {
            text-align: center;
            margin-top: 2.5rem;
        }
        .back-link a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 1.1rem;
        }
        .back-link a:hover { color: var(--accent); }

        /* Footer */
        footer {
            background: rgba(0, 0, 0, 0.4);
            padding: 2.5rem 0;
            text-align: center;
            font-size: 0.95rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        footer p {
            opacity: 0.8;
        }
        footer a {
            color: var(--accent);
            text-decoration: none;
        }
        footer a:hover { text-decoration: underline; }

        @media (max-width: 992px) {
            .register-wrapper { grid-template-columns: 1fr; gap: 4rem; text-align: center; }
            .left-side h1 { font-size: 3.2rem; }
            .stats { justify-content: center; }
        }
        @media (max-width: 600px) {
            main { padding: 140px 0 80px; }
            .right-side { padding: 2.5rem 2rem; }
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
                <a href="login.php" style="color:white; margin-right:2rem; text-decoration:none;">Login</a>
                <a href="index.php" style="color:var(--accent); text-decoration:none;">Back to Home</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="container">
            <div class="register-wrapper">

                <!-- Left: Hero Message -->
                <div class="left-side">
                    <h1>Join the Future of <span class="highlight">Bangladeshi Investments</span></h1>
                    <p>Bangladesh’s startup funding grew <strong>25×</strong> in just 5 years. BlockSight brings blockchain transparency and AI-powered trust to this historic boom.</p>
                    <p>Be part of the most secure and intelligent investment platform in Bangladesh.</p>

                    <div class="stats">
                        <div class="stat-item">
                            <h3>$2.1B+</h3>
                            <p>Funding in 2025 (est.)</p>
                        </div>
                        <div class="stat-item">
                            <h3>1,200+</h3>
                            <p>Companies Verified</p>
                        </div>
                        <div class="stat-item">
                            <h3>100%</h3>
                            <p>Auditable Trail</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Role Selection -->
                <div class="right-side">
                    <h2>Create Your Account</h2>
                    <p class="lead">Choose your role to begin</p>

                    <div class="role-cards">
                        <div class="role-card">
                            <i class="fas fa-building"></i>
                            <h3>I'm a Company</h3>
                            <p>Raise capital with verified documents, immutable records & AI risk scoring.</p>
                            <a href="register_company.php" class="btn">Register as Company</a>
                        </div>

                        <div class="role-card">
                            <i class="fas fa-chart-line"></i>
                            <h3>I'm an Investor</h3>
                            <p>Access verified deals with full transparency and intelligent risk insights.</p>
                            <a href="register_investor.php" class="btn">Register as Investor</a>
                        </div>
                    </div>

                    <div class="back-link">
                        <a href="index.php">Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Beautiful Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 BlockSight. All rights reserved. | NextTech Limited Bangladesh</p>
            <p>
                <a href="#">Privacy Policy</a> • 
                <a href="#">Terms of Service</a> • 
                <a href="#">Contact</a>
            </p>
        </div>
    </footer>
</body>
</html>