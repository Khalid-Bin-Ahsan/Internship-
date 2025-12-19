<?php
require_once 'helpers.php';
session_start();
$user = $_SESSION['user_name'] ?? null;
$role = $_SESSION['user_role'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlockSight - Transparent Investments for Bangladesh</title>
    
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
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--light); color: #333; line-height: 1.6; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }

        /* Header */
        header {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            padding: 1rem 0;
        }
        .header-content { display: flex; justify-content: space-between; align-items: center; }
        .logo { display: flex; align-items: center; gap: 12px; color: white; text-decoration: none; font-size: 2rem; font-weight: 700; }
        .logo i { color: var(--accent); font-size: 2.5rem; }
        .logo span { background: linear-gradient(135deg, #00d4ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        nav a { color: white; text-decoration: none; margin-left: 2rem; font-weight: 500; position: relative; transition: 0.3s; }
        nav a:hover { color: var(--accent); }
        nav a::after { content: ''; position: absolute; width: 0; height: 2px; bottom: -8px; left: 0; background: var(--accent); transition: 0.3s; }
        nav a:hover::after { width: 100%; }

        /* Hero */
        .hero { background: linear-gradient(135deg, var(--primary) 0%, #1e3a8a 50%, #0f4c81 100%); color: white; padding: 180px 0 120px; position: relative; overflow: hidden; }
        .hero::before { content: ''; position: absolute; inset: 0; background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><pattern id="grid" width="80" height="80" patternUnits="userSpaceOnUse"><path d="M 80 0 L 0 0 0 80" fill="none" stroke="%23234162" stroke-width="1" opacity="0.2"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>'); opacity: 0.3; }
        .hero-content { display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; position: relative; z-index: 2; }
        .hero h1 { font-family: 'Playfair Display', serif; font-size: 4.5rem; line-height: 1.1; margin-bottom: 1.5rem; }
        .hero .highlight { color: var(--accent); }
        .hero p { font-size: 1.3rem; margin-bottom: 2rem; opacity: 0.9; max-width: 500px; }
        .btn-primary { background: linear-gradient(135deg, var(--accent), #00ff88); color: black; padding: 1rem 2.5rem; border: none; border-radius: 50px; font-weight: 600; font-size: 1.1rem; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .btn-primary:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,212,255,0.3); }
        .btn-outline { border: 2px solid var(--accent); color: var(--accent); background: transparent; margin-left: 1rem; }
        .btn-outline:hover { background: var(--accent); color: black; }

        /* Bangladesh Growth Chart Section */
        .growth-section {
            padding: 120px 0;
            background: white;
            text-align: center;
        }
        .chart-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 30px;
            background: #f8fafc;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        }

        /* How It Works Modal */
        .modal { display: none; position: fixed; z-index: 2000; inset: 0; background: rgba(0,0,0,0.9); backdrop-filter: blur(10px); align-items: center; justify-content: center; padding: 20px; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 20px; max-width: 900px; width: 100%; padding: 40px; position: relative; }
        .close-modal { position: absolute; top: 15px; right: 20px; font-size: 2.5rem; cursor: pointer; color: #aaa; }
        .close-modal:hover { color: #000; }

        /* Features, Stats, CTA, Footer - same beautiful style */
        .features { padding: 100px 0; background: #f8fafc; }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 4rem; }
        .feature-card { background: white; padding: 2.5rem; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; transition: 0.3s; }
        .feature-card:hover { transform: translateY(-10px); }
        .feature-card i { font-size: 3.5rem; background: linear-gradient(135deg, var(--accent), #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 1.5rem; }
        .stats { padding: 80px 0; background: var(--secondary); color: white; text-align: center; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 3rem; margin-top: 3rem; }
        .stat-number { font-size: 3.5rem; font-weight: 700; color: var(--accent); }
        .cta-footer { background: linear-gradient(135deg, var(--primary), #1e40af); color: white; text-align: center; padding: 100px 0; }
        footer { background: var(--primary); color: #94a3b8; text-align: center; padding: 40px 0; font-size: 0.9rem; }
    </style>
</head>
<body>

    <!-- Header -->
    <header>
        <div class="container header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-eye"></i>
                <span>BlockSight</span>
            </a>
            <nav>
                <a href="index.php">Home</a>
                <a href="#features">Features</a>
                <a href="#" id="howItWorks">How It Works</a>
                <?php if($user): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="logout.php">Logout (<?=esc($user)?>)</a>
                <?php else: ?>
                    <a href="register.php">Get Started</a>
                    <a href="login.php">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-content">
            <div>
                <h1>Transparent <span class="highlight">Investments</span><br>Meet Intelligent Risk</h1>
                <p>The first platform combining immutable blockchain audit trails with machine learning-powered risk assessment — now empowering Bangladeshi startups & investors.</p>
                <div style="margin-top: 2rem;">
                    <?php if(!$user): ?>
                        <a href="register.php" class="btn-primary">Start</a>
                        <a href="#" class="btn-primary btn-outline">Request Demo</a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn-primary">Go to Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>
            <div style="text-align: center;">
                <i class="fas fa-shield-alt" style="font-size: 20rem; opacity: 0.15;"></i>
            </div>
        </div>
    </section>

    <!-- Bangladesh Startup Investment Growth Chart -->
    <section class="growth-section" id="growth">
        <div class="container">
            <h2 style="font-size: 2.8rem; margin-bottom: 1rem;">Bangladesh Startup Funding Boom (2020–2025)</h2>
            <p style="font-size: 1.2rem; color: var(--gray); max-width: 800px; margin: 0 auto 2rem;">
                Funding has grown <strong>25×</strong> in 5 years. BlockSight brings transparency and trust to this explosive growth.
            </p>
            <div class="chart-container">
                <canvas id="bdGrowthChart"></canvas>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features" id="features">
        <div class="container">
            <h2 style="text-align: center; font-size: 3rem; margin-bottom: 1rem;">Built for Trust & Performance</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <i class="fas fa-lock"></i>
                    <h3>Immutable Records</h3>
                    <p>Every document and transaction permanently recorded on blockchain.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-robot"></i>
                    <h3>AI Risk Engine</h3>
                    <p>Real-time fraud detection and credibility scoring using ML.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-search-dollar"></i>
                    <h3>Smart Matching</h3>
                    <p>Connect verified companies with the right investors.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats, CTA, Footer - unchanged -->
    <section class="stats">
        <div class="container">
            <h2 style="font-size: 2.8rem; margin-bottom: 1rem;">Trusted by Forward-Thinking Investors</h2>
            <div class="stat-grid">
                <div><div class="stat-number">$250M+</div><p>Capital Facilitated</p></div>
                <div><div class="stat-number">1,200+</div><p>Verified Companies</p></div>
                <div><div class="stat-number">98.2%</div><p>Risk Prediction Accuracy</p></div>
                <div><div class="stat-number">100%</div><p>Audit Transparency</p></div>
            </div>
        </div>
    </section>

    <section class="cta-footer">
        <div class="container">
            <h2 style="font-size: 3.5rem;">Ready to Invest with Confidence?</h2>
            <p style="font-size: 1.4rem; margin: 2rem 0; max-width: 700px; margin-left: auto; margin-right: auto;">
                Join hundreds of Bangladeshi companies and investors already using BlockSight.
            </p>
            <?php if(!$user): ?>
                <a href="register.php" class="btn-primary" style="font-size: 1.3rem; padding: 1.2rem 3rem;">Get Started Free</a>
            <?php else: ?>
                <a href="dashboard.php" class="btn-primary" style="font-size: 1.3rem; padding: 1.2rem 3rem;">Enter Dashboard</a>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2025 BlockSight. All rights reserved. | NextTech Limited Bangladesh</p>
        </div>
    </footer>

    <!-- How It Works Flow Diagram Modal -->
    <div class="modal" id="howModal">
        <div class="modal-content">
            <span class="close-modal" id="closeModal">×</span>
            <h2 style="text-align:center; margin-bottom:2rem; color:var(--primary);">How BlockSight Works</h2>
            <svg viewBox="0 0 1000 650" style="width:100%; height:auto;">
                <defs>
                    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#00d4ff"/>
                        <stop offset="100%" stop-color="#00ff88"/>
                    </linearGradient>
                    <marker id="arrow" markerWidth="10" markerHeight="10" refX="8" refY="3" orient="auto">
                        <path d="M0,0 L0,6 L9,3 z" fill="#83f816ff"/>
                    </marker>
                </defs>

                <rect x="50" y="100" width="200" height="100" rx="20" fill="url(#grad)" stroke="#000" stroke-width="3"/>
                <text x="150" y="150" text-anchor="middle" fill="white" font-size="18" font-weight="bold">Company Onboarding</text>
                <text x="150" y="175" text-anchor="middle" fill="white" font-size="14">Upload financial documents</text>

                <rect x="400" y="100" width="200" height="100" rx="20" fill="#3b82f6" stroke="#000" stroke-width="3"/>
                <text x="500" y="150" text-anchor="middle" fill="white" font-size="18" font-weight="bold">ML Risk Analysis</text>
                <text x="500" y="175" text-anchor="middle" fill="white" font-size="14">AI scores credibility</text>

                <rect x="750" y="100" width="200" height="100" rx="20" fill="#10b981" stroke="#000" stroke-width="3"/>
                <text x="850" y="150" text-anchor="middle" fill="white" font-size="18" font-weight="bold">Blockchain Trail</text>
                <text x="850" y="175" text-anchor="middle" fill="white" font-size="14">Immutable audit record</text>

                <rect x="275" y="380" width="450" height="140" rx="25" fill="#1f2937" stroke="#fbbf24" stroke-width="5"/>
                <text x="500" y="450" text-anchor="middle" fill="#fbbf24" font-size="26" font-weight="bold">Smart Investor Matching</text>
                <text x="500" y="485" text-anchor="middle" fill="white" font-size="16">Investors see verified, risk-scored opportunities</text>

                <line x1="250" y1="150" x2="400" y2="150" stroke="#00d4ff" stroke-width="6" marker-end="url(#arrow)"/>
                <line x1="600" y1="150" x2="750" y2="150" stroke="#00d4ff" stroke-width="6" marker-end="url(#arrow)"/>
                <line x1="500" y1="200" x2="500" y2="380" stroke="#fbbf24" stroke-width="6" marker-end="url(#arrow)"/>
            </svg>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Bangladesh Growth Chart
        new Chart(document.getElementById('bdGrowthChart'), {
            type: 'line',
            data: {
                labels: ['2020', '2021', '2022', '2023', '2024', '2025'],
                datasets: [{
                    label: 'Funding (USD Million)',
                    data: [85, 225, 480, 890, 1450, 2100],
                    borderColor: '#00d4ff',
                    backgroundColor: 'rgba(0,212,255,0.1)',
                    borderWidth: 5,
                    pointBackgroundColor: '#00ff88',
                    pointRadius: 8,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Modal Controls
        document.getElementById('howItWorks').onclick = e => { e.preventDefault(); document.getElementById('howModal').classList.add('active'); };
        document.getElementById('closeModal').onclick = () => document.getElementById('howModal').classList.remove('active');
        document.getElementById('howModal').onclick = e => { if (e.target === document.getElementById('howModal')) document.getElementById('howModal').classList.remove('active'); };
    </script>
</body>
</html>