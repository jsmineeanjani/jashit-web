<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
// PERUBAHAN: Hak akses diganti khusus untuk pelanggan
requireRole('pelanggan'); 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konsultasi — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">

    <?php require_once '../includes/sidebar_pelanggan.php'; ?>

    <div class="dashboard-main">
        <?php require_once '../includes/topbar_pelanggan.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            
            <div class="mb-4">
                <h1 class="page-title" style="font-size: 24px; font-weight: 700; color: var(--navy-dark); margin: 0;">Konsultasi Layanan</h1>
                <p class="text-muted" style="font-size: 14px; margin-top: 5px;">Hubungi tim Jashit untuk diskusi lebih lanjut</p>
            </div>

            <div class="row justify-content-center mt-4">
                <div class="col-md-8 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 text-center p-5" style="background-color: #fff;">
                        <div style="width: 80px; height: 80px; background-color: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                            <i class="bi bi-whatsapp" style="font-size: 40px; color: #16a34a;"></i>
                        </div>
                        
                        <h4 style="font-weight: 700; color: var(--navy-dark); margin-bottom: 15px;">Butuh Bantuan atau Konsultasi?</h4>
                        
                        <p style="color: var(--text-muted); line-height: 1.8; margin-bottom: 30px; font-size: 15px;">
                            Untuk konsultasi lebih lanjut mengenai layanan jahit, custom ukuran, pemilihan bahan, model pakaian, atau kerja sama bisnis, silakan hubungi tim Jashit langsung melalui WhatsApp.
                        </p>
                        
                        <a href="https://wa.me/62895422799883" target="_blank" class="btn btn-success" style="padding: 12px 30px; border-radius: 50px; font-weight: 600; font-size: 15px; background-color: #16a34a; border: none; box-shadow: 0 10px 20px rgba(22, 163, 74, 0.2);">
                            <i class="bi bi-whatsapp me-2"></i> Hubungi WhatsApp Jashit
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>