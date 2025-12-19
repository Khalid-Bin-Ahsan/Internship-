<?php
// company_profile.php
require_once 'db.php';
require_once 'helpers.php';
require_once 'ml_call.php';
session_start();
require_login();

$company_id = $_GET['id'] ?? 0;
$role = $_SESSION['user_role'];

// Fetch company details
$stmt = $pdo->prepare("
    SELECT c.*, u.name as founder, u.email as contact_email
    FROM companies c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.id = ?
");
$stmt->execute([$company_id]);
$company = $stmt->fetch();

if (!$company) {
    redirect('dashboard.php');
}

// Calculate risk score
$risk = calculate_risk_score($company);

// Fetch funding requests
$stmt = $pdo->prepare("SELECT * FROM funding_requests WHERE company_id = ? AND status = 'open'");
$stmt->execute([$company_id]);
$funding_requests = $stmt->fetchAll();

// Fetch risk history
$stmt = $pdo->prepare("
    SELECT * FROM company_risk_scores 
    WHERE company_id = ? 
    ORDER BY calculation_date DESC 
    LIMIT 10
");
$stmt->execute([$company_id]);
$risk_history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?=esc($company['company_name'])?> • BlockSight</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Add styles from your existing dashboard */
        .risk-chart {
            height: 300px;
            background: rgba(255,255,255,0.05);
            border-radius: 20px;
            padding: 2rem;
            margin: 2rem 0;
        }
        .metric-card {
            background: rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <div class="container">
            <div class="card">
                <h1 style="font-size: 2.8rem;"><?=esc($company['company_name'])?></h1>
                <p style="opacity: 0.8; margin-bottom: 2rem;">
                    Founded by <?=esc($company['founder'])?> • <?=esc($company['industry'])?> • <?=esc($company['location'])?>
                </p>
                
                <!-- ML Risk Score Section -->
                <div style="text-align: center; padding: 3rem; background: var(--s); border-radius: 24px; margin: 2rem 0;">
                    <h2 style="margin-bottom: 2rem;">
                        <i class="fas fa-robot"></i> AI Risk Assessment
                    </h2>
                    
                    <div style="display: flex; align-items: center; justify-content: center; gap: 4rem; flex-wrap: wrap;">
                        <div>
                            <div class="risk-score-circle" style="
                                width: 180px;
                                height: 180px;
                                border-radius: 50%;
                                background: <?=$risk['color']?>20;
                                border: 5px solid <?=$risk['color']?>;
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                justify-content: center;
                                margin: 0 auto;
                            ">
                                <span style="font-size: 4rem; font-weight: 800; color: <?=$risk['color']?>;">
                                    <?=$risk['score']?>
                                </span>
                                <small style="font-size: 1rem; color: <?=$risk['color']?>;">
                                    Risk Score
                                </small>
                            </div>
                            
                            <div class="risk-level-badge" style="
                                display: inline-block;
                                padding: 0.8rem 2rem;
                                border-radius: 50px;
                                background: <?=$risk['color']?>20;
                                color: <?=$risk['color']?>;
                                font-weight: 700;
                                border: 2px solid <?=$risk['color']?>;
                                margin: 1.5rem 0;
                                font-size: 1.2rem;
                            ">
                                <?=$risk['level']?>
                            </div>
                        </div>
                        
                        <div style="text-align: left; max-width: 400px;">
                            <h3>Risk Breakdown</h3>
                            <?php if(isset($risk['breakdown'])): ?>
                            <div style="margin-top: 1rem;">
                                <?php foreach($risk['breakdown'] as $key => $value): ?>
                                <div style="margin: 1rem 0;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <span><?=ucfirst(str_replace('_', ' ', $key))?></span>
                                        <span><?=round($value, 1)?>/100</span>
                                    </div>
                                    <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px;">
                                        <div style="height: 100%; width: <?=$value?>%; background: <?=$risk['color']?>; border-radius: 4px;"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Financial Metrics -->
                <div class="form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
                    <div class="metric-card">
                        <h4><i class="fas fa-money-bill-wave"></i> Annual Revenue</h4>
                        <p style="font-size: 2rem; font-weight: 700;">$<?=number_format($company['annual_revenue'], 2)?></p>
                    </div>
                    <div class="metric-card">
                        <h4><i class="fas fa-chart-line"></i> Revenue Growth</h4>
                        <p style="font-size: 2rem; font-weight: 700;"><?=round($company['revenue_growth_yoy'] * 100, 1)?>%</p>
                    </div>
                    <div class="metric-card">
                        <h4><i class="fas fa-percentage"></i> Profit Margin</h4>
                        <p style="font-size: 2rem; font-weight: 700;"><?=round($company['profit_margin'] * 100, 1)?>%</p>
                    </div>
                    <div class="metric-card">
                        <h4><i class="fas fa-file-pdf"></i> Documents</h4>
                        <p style="font-size: 2rem; font-weight: 700;"><?=$company['documents_count']?> Files</p>
                    </div>
                </div>
                
                <!-- Open Funding Requests -->
                <?php if($funding_requests): ?>
                <div style="margin: 3rem 0;">
                    <h2>Open Funding Requests</h2>
                    <?php foreach($funding_requests as $funding): ?>
                    <div style="background: rgba(0,212,255,0.1); padding: 1.5rem; border-radius: 16px; margin: 1rem 0;">
                        <h3><?=esc($funding['title'])?></h3>
                        <p>Amount: $<?=number_format($funding['amount_requested'], 2)?></p>
                        <p>Type: <?=ucfirst($funding['funding_type'])?></p>
                        <?php if($funding['funding_type'] === 'equity'): ?>
                            <p>Equity Offered: <?=$funding['equity_offer_percent']?>%</p>
                        <?php endif; ?>
                        <button class="btn btn-meet" onclick="openModal('meeting',<?=$company['user_id']?>,'<?=addslashes($company['company_name'])?>')">
                            Invest Now
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Risk History Chart -->
                <?php if($risk_history): ?>
                <div style="margin: 3rem 0;">
                    <h2>Risk History</h2>
                    <div class="risk-chart">
                        <!-- This would be implemented with Chart.js -->
                        <canvas id="riskHistoryChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 3rem;">
                    <button class="btn btn-meet" onclick="openModal('meeting',<?=$company['user_id']?>,'<?=addslashes($company['company_name'])?>')">
                        <i class="fas fa-calendar-check"></i> Request Meeting
                    </button>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'footer.php'; ?>
    
    <script>
        // Chart.js implementation for risk history
        const ctx = document.getElementById('riskHistoryChart');
        if (ctx) {
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: [<?php 
                        echo implode(',', array_map(function($item) {
                            return "'" . date('M d', strtotime($item['calculation_date'])) . "'";
                        }, $risk_history));
                    ?>],
                    datasets: [{
                        label: 'Risk Score',
                        data: [<?php 
                            echo implode(',', array_map(function($item) {
                                return $item['risk_score'];
                            }, $risk_history));
                        ?>],
                        borderColor: '<?=$risk['color']?>',
                        backgroundColor: '<?=$risk['color']?>20',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>