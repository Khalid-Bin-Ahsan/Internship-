<?php
// ml_call.php - ML API Integration
require_once 'db.php';
require_once 'helpers.php';

function calculate_risk_score($company) {
    global $pdo;
    
    // Prepare data for ML API
    $data = [
        'annual_revenue' => floatval($company['annual_revenue'] ?? 0),
        'net_profit' => floatval($company['net_profit'] ?? 0),
        'revenue_growth_yoy' => floatval($company['revenue_growth_yoy'] ?? 0),
        'profit_margin' => floatval($company['profit_margin'] ?? 0),
        'documents_count' => intval($company['documents_count'] ?? 0),
        'company_age' => calculate_company_age($company['created_at'] ?? date('Y-m-d')),
        'funding_rounds' => count_funding_rounds($company['id'] ?? 0)
    ];
    
    // Call ML API (with fallback if API is down)
    $ml_result = call_ml_api($data);
    
    if ($ml_result) {
        // Store in database
        store_risk_score($company['id'], $ml_result);
        
        return [
            'score' => $ml_result['risk_score'],
            'level' => $ml_result['risk_level'],
            'color' => $ml_result['color'],
            'breakdown' => $ml_result['breakdown'] ?? null
        ];
    } else {
        // Fallback calculation
        return calculate_fallback_risk($company);
    }
}

function call_ml_api($data) {
    $api_url = 'http://localhost:5000/predict'; // Your Flask ML API
    
    try {
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // 3 second timeout
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200 && $response) {
            $result = json_decode($response, true);
            if (isset($result['success']) && $result['success']) {
                return $result;
            }
        }
    } catch (Exception $e) {
        // Log error if needed
        error_log("ML API Error: " . $e->getMessage());
    }
    
    return false; // API call failed
}

function calculate_company_age($created_date) {
    try {
        $created = new DateTime($created_date);
        $now = new DateTime();
        $interval = $created->diff($now);
        return max(1, $interval->y);
    } catch (Exception $e) {
        return 1; // Default age
    }
}

function count_funding_rounds($company_id) {
    global $pdo;
    if (!$company_id) return 0;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM funding_requests WHERE company_id = ?");
        $stmt->execute([$company_id]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function store_risk_score($company_id, $risk_data) {
    global $pdo;
    
    if (!$company_id) return;
    
    try {
        // Check if risk_calculated_at column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM companies LIKE 'risk_calculated_at'");
        $stmt->execute();
        $column_exists = $stmt->fetch();
        
        if ($column_exists) {
            // Update companies table with all columns
            $stmt = $pdo->prepare("
                UPDATE companies 
                SET current_risk_score = ?, risk_level = ?, risk_calculated_at = NOW() 
                WHERE id = ?
            ");
        } else {
            // Update only existing columns
            $stmt = $pdo->prepare("
                UPDATE companies 
                SET current_risk_score = ?, risk_level = ? 
                WHERE id = ?
            ");
        }
        
        $stmt->execute([
            $risk_data['risk_score'],
            $risk_data['risk_level'],
            $company_id
        ]);
        
        // Check if company_risk_scores table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'company_risk_scores'");
        $stmt->execute();
        $table_exists = $stmt->fetch();
        
        if ($table_exists) {
            // Insert into risk_scores history
            $stmt = $pdo->prepare("
                INSERT INTO company_risk_scores 
                (company_id, risk_score, risk_level, financial_health, growth_potential, market_risk, operational_risk)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $company_id,
                $risk_data['risk_score'],
                $risk_data['risk_level'],
                $risk_data['breakdown']['financial_health'] ?? 0,
                $risk_data['breakdown']['growth_potential'] ?? 0,
                $risk_data['breakdown']['market_risk'] ?? 0,
                $risk_data['breakdown']['operational_risk'] ?? 0
            ]);
        }
    } catch (Exception $e) {
        // Silently fail - don't break the page if database update fails
        error_log("Database update error: " . $e->getMessage());
    }
}

function calculate_fallback_risk($company) {
    $score = 50; // Base score
    
    // Adjust based on financial metrics
    $net_profit = floatval($company['net_profit'] ?? 0);
    $revenue_growth = floatval($company['revenue_growth_yoy'] ?? 0);
    $profit_margin = floatval($company['profit_margin'] ?? 0);
    $documents_count = intval($company['documents_count'] ?? 0);
    
    if ($net_profit > 0) $score -= 15;
    if ($revenue_growth > 0.2) $score -= 10;
    if ($profit_margin > 0.1) $score -= 10;
    if ($documents_count > 0) $score -= 5;
    
    // Ensure score is between 0-100
    $score = max(0, min(100, $score));
    
    // Determine level and color
    $color = get_risk_color($score);
    $level = get_risk_level($score);
    
    return [
        'score' => round($score, 2),
        'level' => $level,
        'color' => $color
    ];
}

function get_risk_color($score) {
    if ($score >= 80) return '#ef4444';
    if ($score >= 60) return '#f97316';
    if ($score >= 40) return '#fbbf24';
    if ($score >= 20) return '#22c55e';
    return '#10b981';
}

function get_risk_level($score) {
    if ($score >= 80) return 'Very High Risk';
    if ($score >= 60) return 'High Risk';
    if ($score >= 40) return 'Moderate Risk';
    if ($score >= 20) return 'Low Risk';
    return 'Very Low Risk';
}

// For testing - simulate ML API response if Flask is not running
function simulate_ml_response($company_data) {
    // Generate a realistic risk score based on company data
    $score = 50;
    
    if ($company_data['net_profit'] > 0) $score -= 20;
    if ($company_data['revenue_growth_yoy'] > 0.25) $score -= 15;
    if ($company_data['profit_margin'] > 0.15) $score -= 10;
    if ($company_data['documents_count'] > 2) $score -= 5;
    
    $score = max(0, min(100, $score + rand(-10, 10)));
    
    $color = get_risk_color($score);
    $level = get_risk_level($score);
    
    return [
        'success' => true,
        'risk_score' => round($score, 2),
        'risk_level' => $level,
        'color' => $color,
        'breakdown' => [
            'financial_health' => round(100 - ($score * 0.7), 2),
            'growth_potential' => round((100 - $score) * 1.2, 2),
            'market_risk' => round($score * 0.8, 2),
            'operational_risk' => round($score * 0.6, 2)
        ]
    ];
}
?>