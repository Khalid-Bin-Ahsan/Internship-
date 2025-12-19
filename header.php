<?php require_once 'helpers.php'; session_start(); require_login(); ?>
<header class="fixed-header">
    <div class="container header-content">
        <a href="dashboard.php" class="logo">
            <i class="fas fa-eye"></i>
            <span>BlockSight</span>
        </a>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <?php if(is_company()): ?>
                <a href="funding_create.php">Raise Capital</a>
            <?php else: ?>
                <a href="fundings.php">Invest</a>
            <?php endif; ?>
            <a href="logout.php">Logout (<?=esc($_SESSION['user_name'])?>)</a>
        </nav>
    </div>
</header>