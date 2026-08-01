<?php
// Naik satu folder untuk memanggil config
require_once '../config/config.php';
require_once '../includes/auth.php';

if (isLoggedIn()) {
    redirectByRole();
}

// Dapatkan nama file yang sedang dibuka untuk header
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan — JASHIT</title>
    <!-- Favicon JASHIT -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    
    <!-- Eksternal Library -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Panggil File CSS (Gabungan dari Tentang karena menunya sama, plus Layanan) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tentang.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/layanan.css">
</head>
<body>

    <!-- Panggil header -->
    <?php require_once '../includes/layouts/header.php'; ?>

    <div class="page-header" data-aos="fade-down" data-aos-duration="1000">
        <div class="container">
            <h1 class="page-title">Layanan Kami</h1>
            <div class="breadcrumb-custom">
                <a href="<?= BASE_URL ?>/public/index.php">Home</a> <span>/</span> Layanan
            </div>
        </div>
    </div>

    <section class="layanan-section">
        <div class="container">
            <div class="row g-4">
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="layanan-card">
                        <img src="../assets/img/busana-muslim.jpg" alt="Busana Muslimah" class="layanan-img">
                        <div class="layanan-content">
                            <h5>Busana Muslimah</h5>
                            <p>Mulai dari jahit gamis hingga busana muslimah yang rapi, sopan, dan sangat elegan dipakai.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="layanan-card">
                        <img src="../assets/img/brand-lokal.jpg" alt="Brand Fashion Lokal" class="layanan-img">
                        <div class="layanan-content">
                            <h5>Brand Fashion Lokal</h5>
                            <p>Menerima pesanan produksi untuk brand fashion lokal yang sangat sesuai dengan desain dan detail Anda.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="layanan-card">
                        <img src="https://images.unsplash.com/photo-1617137984095-74e4e5e3613f?auto=format&fit=crop&w=600&q=80" alt="Pakaian Kantor & Seragam" class="layanan-img">
                        <div class="layanan-content">
                            <h5>Pakaian Kantor & Seragam</h5>
                            <p>Jasa pembuatan seragam kantor dan instansi yang tampil profesional serta nyaman dipakai harian.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="layanan-card">
                        <img src="../assets/img/aksesoris.jpeg" alt="Aksesoris & Rompi" class="layanan-img">
                        <div class="layanan-content">
                            <h5>Aksesoris & Rompi</h5>
                            <p>Kami juga bisa membuatkan aksesoris pelengkap seperti tas mukena, pouch, hingga rompi custom.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="500">
                    <div class="layanan-card">
                        <img src="../assets/img/manekin.jpg" alt="Modifikasi Pakaian" class="layanan-img">
                        <div class="layanan-content">
                            <h5>Modifikasi Pakaian</h5>
                            <p>Menyediakan jasa modifikasi (permak) pakaian agar lebih pas dan nyaman sesuai dengan bentuk tubuh Anda.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="600">
                    <div class="layanan-card">
                        <img src="../assets/img/pakaian-khusus.png" alt="Produksi Skala Besar" class="layanan-img">
                        <div class="layanan-content">
                            <h5>Produksi Skala Besar</h5>
                            <p>Tidak hanya individu, kami berpengalaman menangani pesanan skala besar untuk Event Organizer (EO).</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="harga-section">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <div style="font-size:12px; font-weight:700; letter-spacing:3px; color:var(--text-muted); text-transform:uppercase; margin-bottom:10px;">
                    Estimasi Biaya 
                </div>
                <h2 style="font-size:30px; font-weight:700; color:var(--navy-dark);">
                    Kisaran Harga
                </h2>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="harga-card featured">
                        <h5>Pembuatan Aksesoris & Rompi</h5>
                        <div class="price">Mulai 65rb</div>
                        <div class="price-sub">Desain serta jahitan rapih, dan nyaman digunakan untuk berbagai kebutuhan acara.</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="harga-card featured">
                        <h5>Pakaian Kantor & Seragam</h5>
                        <div class="price">Mulai 150rb</div>
                        <div class="price-sub">Dengan bahan standar American/Japan Drill. Termasuk bordir.</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="harga-card featured">
                        <h5>Gamis / Dress</h5>
                        <div class="price">Hubungi Kami</div>
                        <div class="price-sub">Silahkan hubungi kami untuk detail harga lengkap</div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5" data-aos="zoom-in" data-aos-offset="-50">
                <a href="<?= BASE_URL ?>/auth/register.php" class="btn-elegant-dark" btn-konsul-costum;>
                    KONSULTASI & PESAN SEKARANG
                </a>
            </div>
        </div>
    </section>

    <!-- Panggil Footer -->
    <?php require_once '../includes/layouts/footer.php'; ?>
    
    <!-- Eksternal Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS Animation JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            offset: 80,
            duration: 800,
            easing: 'ease-out-cubic'
        });
    </script>
</body>
</html>