<?php
require_once 'db.php';
require_once 'helpers.php';
session_start();
require_login();

// FIXED ML CALL - Safe version (no errors even if columns missing)
require_once 'ml_call.php';  // We'll fix this file too below
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invest • BlockSight</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --p:#0f172a;--a:#00d4ff;--g:#00ff88;--s:#1e293b;--l:#f8fafc;
        }
        *{margin:0;padding:0;box-sizing:border-box;}
        body{
            font-family:'Inter',sans-serif;
            background:var(--p);
            color:white;
            min-height:100vh;
            position:relative;
            overflow-x:hidden;
        }
        body::before{
            content:'';
            position:absolute;
            top:0;left:0;right:0;bottom:0;
            background:
                radial-gradient(circle at 20% 80%, rgba(0,212,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0,255,136,0.1) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(251,191,36,0.05) 0%, transparent 60%);
            animation:float 20s infinite linear;
            pointer-events:none;
            z-index:0;
        }
        @keyframes float{
            0%{transform:translate(0,0) rotate(0deg);}
            100%{transform:translate(30px,-30px) rotate(5deg);}
        }

        header{
            background:rgba(15,23,42,0.95);
            backdrop-filter:blur(15px);
            position:fixed;
            top:0;width:100%;z-index:1000;
            padding:1rem 0;
            border-bottom:1px solid rgba(255,255,255,0.1);
        }
        .container{
            max-width:1200px;
            margin:0 auto;
            padding:0 20px;
            position:relative;
            z-index:2;
        }
        .header-content{display:flex;justify-content:space-between;align-items:center;}
        .logo{display:flex;align-items:center;gap:12px;color:white;text-decoration:none;font-size:2rem;font-weight:700;}
        .logo i{color:var(--a);font-size:2.5rem;}
        .logo span{background:linear-gradient(135deg,var(--a),var(--g));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
        nav a{color:white;margin-left:2rem;text-decoration:none;font-weight:500;position:relative;}
        nav a:hover{color:var(--a);}
        nav a::after{content:'';position:absolute;bottom:-8px;left:0;width:0;height:2px;background:var(--a);transition:0.3s;}
        nav a:hover::after{width:100%;}

        main{
            padding-top:120px;
            padding-bottom:100px;
            min-height:100vh;
        }
        h1{
            font-family:'Playfair Display',serif;
            font-size:3.5rem;
            text-align:center;
            margin:3rem 0 4rem;
            background:linear-gradient(135deg,var(--a),var(--g));
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
        }

        .deal-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(380px, 1fr));
            gap:2rem;
            margin-top:2rem;
        }
        .deal-card{
            background:rgba(255,255,255,0.08);
            backdrop-filter:blur(20px);
            border:1px solid rgba(255,255,255,0.1);
            border-radius:24px;
            padding:2rem;
            transition:all 0.4s ease;
            position:relative;
            overflow:hidden;
        }
        .deal-card:hover{
            transform:translateY(-12px);
            box-shadow:0 30px 80px rgba(0,212,255,0.25);
        }
        .risk-badge{
            position:absolute;
            top:16px;
            right:16px;
            padding:0.6rem 1.2rem;
            border-radius:50px;
            font-weight:700;
            font-size:1rem;
            color:white;
        }
        .deal-card h2{
            font-size:1.6rem;
            margin-bottom:1rem;
            color:var(--a);
        }
        .deal-card p{
            margin:0.8rem 0;
            opacity:0.9;
            line-height:1.6;
        }
        .btn{
            background:linear-gradient(135deg,var(--a),var(--g));
            color:black;
            padding:0.9rem 2rem;
            border:none;
            border-radius:50px;
            font-weight:600;
            cursor:pointer;
            text-decoration:none;
            display:inline-block;
            margin-top:1rem;
            transition:0.3s;
        }
        .btn:hover{
            transform:scale(1.05);
            box-shadow:0 15px 30px rgba(0,212,255,0.4);
        }
        .btn-secondary{
            background:#475569;
            color:white;
        }

        .modal{
            display:none;
            position:fixed;
            top:0;left:0;right:0;bottom:0;
            background:rgba(0,0,0,0.9);
            backdrop-filter:blur(10px);
            z-index:2000;
            align-items:center;
            justify-content:center;
            padding:20px;
        }
        .modal.active{display:flex;}
        .modal-content{
            background:rgba(255,255,255,0.1);
            backdrop-filter:blur(20px);
            border:1px solid rgba(255,255,255,0.2);
            border-radius:24px;
            padding:3rem;
            max-width:700px;
            width:100%;
            max-height:90vh;
            overflow-y:auto;
            position:relative;
        }
        .close-modal{
            position:absolute;
            top:15px;
            right:20px;
            font-size:2.5rem;
            cursor:pointer;
            color:#aaa;
        }
        .close-modal:hover{color:white;}

        footer{
            background:rgba(0,0,0,0.5);
            padding:3rem 0;
            text-align:center;
            border-top:1px solid rgba(255,255,255,0.1);
            position:relative;
            z-index:2;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header>
        <div class="container header-content">
            <a href="dashboard.php" class="logo">
                <i class="fas fa-eye"></i>
                <span>BlockSight</span>
            </a>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="fundings.php">Invest</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <h1>Investment Opportunities</h1>

            <div class="deal-grid">
                <?php
                $stmt = $pdo->query("SELECT fr.*, c.company_name, c.industry, c.location, c.description, c.annual_revenue, c.net_profit, c.revenue_growth_yoy, c.profit_margin FROM funding_requests fr JOIN companies c ON fr.company_id = c.id WHERE fr.status = 'open' ORDER BY fr.created_at DESC");
                $deals = $stmt->fetchAll();
                if(empty($deals)) {
                    echo "<p style='text-align:center;grid-column:1/-1;font-size:1.2rem;opacity:0.8;'>No active funding requests at the moment.</p>";
                }
                foreach($deals as $deal):
                    $risk = calculate_risk_score($deal);  // Now 100% safe
                ?>
                <div class="deal-card">
                    <div class="risk-badge" style="background:<?=$risk['color']?>;">
                        <?=$risk['score']?> • <?=$risk['level']?>
                    </div>
                    <h2><?=esc($deal['company_name'])?></h2>
                    <p><strong><?=$deal['title']?></strong></p>
                    <p><strong>Seeking:</strong> $<?=number_format($deal['amount_requested'])?> • <?=$deal['funding_type']?></p>
                    <?php if($deal['equity_offer_percent'] > 0): ?>
                        <p><strong>Equity:</strong> <?=$deal['equity_offer_percent']?>%</p>
                    <?php endif; ?>
                    <p><?=esc(substr($deal['description'] ?: 'Innovative Bangladeshi startup', 0, 120))?>...</p>

                    <div style="margin-top:1.5rem;display:flex;gap:1rem;flex-wrap:wrap;">
                        <a href="invest.php?id=<?=$deal['id']?>" class="btn">Make Offer</a>
                        <button class="btn btn-secondary" onclick="openModal(<?=htmlspecialchars(json_encode($deal), ENT_QUOTES)?>,'<?=$risk['level']?>',<?=$risk['score']?>)">
                            Company Details
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Company Details Modal -->
    <div class="modal" id="companyModal">
        <div class="modal-content">
            <span class="close-modal" onclick="document.getElementById('companyModal').classList.remove('active')">&times;</span>
            <h1 id="modalTitle" style="text-align:center;"></h1>
            <div style="text-align:center;margin:2rem 0;">
                <div id="modalScore" style="font-size:4rem;font-weight:700;"></div>
                <h2 id="modalLevel" style="margin:1rem 0;"></h2>
                <p><strong>Blockchain Verified • Immutable Trail</strong></p>
            </div>
            <div style="line-height:2;">
                <p><strong>Industry:</strong> <span id="modalIndustry"></span></p>
                <p><strong>Location:</strong> <span id="modalLocation"></span></p>
                <p><strong>Revenue:</strong> $<span id="modalRevenue"></span></p>
                <p><strong>Profit:</strong> $<span id="modalProfit"></span></p>
                <p><strong>Growth YoY:</strong> <span id="modalGrowth"></span>%</p>
                <p><strong>Profit Margin:</strong> <span id="modalMargin"></span>%</p>
                <p><strong>Description:</strong></p>
                <p id="modalDesc" style="background:rgba(255,255,255,0.1);padding:1rem;border-radius:12px;"></p>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        function openModal(deal, level, score) {
            document.getElementById('modalTitle').textContent = deal.company_name;
            document.getElementById('modalScore').textContent = score;
            document.getElementById('modalScore').style.color = score >= 85 ? '#10b981' : (score >= 65 ? '#f59e0b' : (score >= 40 ? '#f97316' : '#ef4444'));
            document.getElementById('modalLevel').textContent = level;
            document.getElementById('modalIndustry').textContent = deal.industry || 'Not specified';
            document.getElementById('modalLocation').textContent = deal.location || 'Bangladesh';
            document.getElementById('modalRevenue').textContent = Number(deal.annual_revenue || 0).toLocaleString();
            document.getElementById('modalProfit').textContent = Number(deal.net_profit || 0).toLocaleString();
            document.getElementById('modalGrowth').textContent = deal.revenue_growth_yoy ? (deal.revenue_growth_yoy * 100).toFixed(1) : '0';
            document.getElementById('modalMargin').textContent = deal.profit_margin ? (deal.profit_margin * 100).toFixed(1) : '0';
            document.getElementById('modalDesc').textContent = deal.description || 'No description provided.';
            document.getElementById('companyModal').classList.add('active');
        }
        // Close modal when clicking outside
        document.getElementById('companyModal').addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
    </script>
</body>
</html>