<?php
// Naik satu folder untuk memanggil config
require_once '../config/config.php';
require_once '../includes/auth.php';

if (isLoggedIn()) {
    redirectByRole();
}

// Dapatkan nama file yang sedang dibuka
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami — JASHIT</title>
    <!-- Favicon JASHIT -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <!-- Eksternal Library -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- File CSS Kustom -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tentang.css">
</head>
<body>

    <!-- Panggil header -->
    <?php require_once '../includes/layouts/header.php'; ?>

    <!-- Banner Judul Halaman -->
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">Tentang Kami</h1>
            <p style="color: rgba(255,255,255,0.7); font-size: 14.5px;">Mengenal lebih dekat perjalanan panjang dan dedikasi Jashit.</p>
        </div>
    </div>

    <!-- Konten Tentang -->
    <section class="tentang-section">
        <div class="container">
            <div class="row align-items-center">
                <!-- Sisi Kiri: Gambar -->
                <div class="col-lg-5">
                    <div class="tentang-image-wrap">
                        <img src="../assets/img/tentang-jashit.png" alt="Perjalanan Jashit" class="tentang-image">
                        <div class="tentang-accent"></div>
                    </div>
                </div>

                <!-- Sisi Kanan: Teks -->
                <div class="col-lg-6 offset-lg-1">
                    <div class="section-label">Perjalanan Kami</div>
                    <h2 class="section-title">Dedikasi Sebuah Karya Sejak 2010</h2>
                    
                    <div class="tentang-text">
                        <p>
                            <strong>Jashit</strong> bermula pada saat Ibu Ninik memutuskan purna tugas dari pekerjaannya dan merintis usaha bersama seorang rekannya. Berawal dari keberanian melangkah, Jashit perlahan tumbuh menjadi usaha konveksi jahit terpercaya yang berlokasi di kawasan Ciputat, Tangerang Selatan.
                        </p>
                        
                        <p>
                            Pada masa-masa awal berdirinya, fokus utama kami adalah memproduksi pesanan konveksi berskala besar. Namun, seiring berjalannya waktu dan tingginya permintaan akan detail yang lebih personal, kini Jashit lebih memfokuskan diri pada layanan jahit <em>custom</em> meskipun kapasitas produksi skala besar (konveksi) tetap menjadi salah satu keahlian utama kami.
                        </p>

                        <p>
                            Kini, sebagai usaha jahit keluarga yang matang, Jashit bangga telah berkolaborasi dan dipercaya menjadi mitra produksi oleh berbagai <em>brand</em> butik terkemuka. Didukung oleh tim penjahit yang berpengalaman dan terampil, kami siap menangani pesanan mulai dari gamis, mukena, seragam kantor, hingga busana <em>fashion</em> lokal yang menuntut ketelitian tinggi.
                        </p>

                        <!-- Blok Kutipan Visi -->
                        <div class="custom-quote">
                            <p>"Visi kami sederhana: Menjadi mitra andalan dalam setiap perjalanan busana Anda. Pilih Jashit, karena kualitas bukan hanya sekadar janji, tapi sebuah pembuktian."</p>
                        </div>
                    </div>
                    
                    <!-- Klien/Brand -->
                    <div class="mt-4 pt-2">
                        <span style="font-size:12px; font-weight:700; color:var(--navy-dark); text-transform:uppercase; letter-spacing:1px;">Telah Berkolaborasi Bersama:</span>
                        <div class="client-logos">
                            <span class="client-tag">Brand Butik Lokal</span>
                            <span class="client-tag">Gudang Garam</span>
                            <span class="client-tag">Maheera</span>
                            <span class="client-tag">Khanaan</span>
                            <span class="client-tag">Event Organizer (EO)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Panggil Footer -->
    <?php require_once '../includes/layouts/footer.php'; ?>

    <!-- Eksternal Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>