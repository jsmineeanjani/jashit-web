<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' — JASHIT' : 'JASHIT · Jasa Jahit Konveksi' ?></title>
    
    <!-- Favicon JASHIT -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS Utama -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <!-- Custom CSS Landing Page -->
    <link href="<?= BASE_URL ?>/assets/css/landing.css" rel="stylesheet">

    <!-- AOS Animation CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom" style="position:sticky;top:0;z-index:999;">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>/public/index.php">
            <img src="<?= BASE_URL ?>/assets/img/logo_jashit.png" alt="Logo JASHIT" style="height: 55px; width: auto;">
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'index.php') ? 'active-menu' : '' ?>" href="<?= BASE_URL ?>/public/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'tentang.php') ? 'active-menu' : '' ?>" href="<?= BASE_URL ?>/public/tentang.php">Tentang</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'layanan.php') ? 'active-menu' : '' ?>" href="<?= BASE_URL ?>/public/layanan.php">Layanan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/public/index.php#testimoni">Feedback</a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <a href="<?= BASE_URL ?>/auth/login.php" class="nav-link-login">LOGIN</a>
                <a href="<?= BASE_URL ?>/auth/register.php" class="btn-elegant-dark">DAFTAR</a>
            </div>
        </div>
    </div>
</nav>