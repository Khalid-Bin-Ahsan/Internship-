<?php
function esc($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
function redirect($url) { header("Location: $url"); exit; }
function is_logged_in() { return isset($_SESSION['user_id']); }
function require_login() { if(!is_logged_in()) redirect('login.php'); }
function is_company() { return $_SESSION['user_role'] === 'company'; }
function is_investor() { return $_SESSION['user_role'] === 'investor'; }

// Generate fake blockchain hash
function generate_blockchain_hash() {
    return '0x' . bin2hex(random_bytes(32));
}
?>