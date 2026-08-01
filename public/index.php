<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

if (isLoggedIn()) {
    redirectByRole();
}

$page_title = 'Beranda';

// AMBIL DATA FEEDBACK UNTUK LANDING PAGE
$query_fb = mysqli_query($koneksi, "
    SELECT f.rating, f.komentar, f.created_at, u.nama AS nama_pelanggan, u.foto 
    FROM feedback f 
    JOIN users u ON f.user_id = u.id 
    WHERE f.status = 'ditampilkan' 
    ORDER BY f.id DESC 
    LIMIT 6
");

// 1. Panggil Header dari Layouts
require_once '../includes/layouts/header.php'; 
?>

<section class="hero-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-7">
                <div class="hero-label">Usaha Jahit Terpercaya</div>
                <h1 class="hero-title">Seni Jahitan<br>Estetika & <span>Kualitas</span></h1>
                <p class="hero-desc">Jashit siap menjadi mitra andalan dalam setiap karya busana Anda. Kualitas bukan hanya sekadar janji, tapi sudah terbukti oleh berbagai brand ternama nasional.</p>
                <div class="hero-actions">
                    <a href="<?= BASE_URL ?>/auth/register.php" class="btn-elegant-dark" style="background:var(--peach-soft); color:var(--navy-dark);">MULAI PESAN</a>
                    <a href="<?= BASE_URL ?>/public/index.php#layanan" class="btn-elegant-dark" style="background:transparent; border:1px solid rgba(255,255,255,0.4);">LIHAT LAYANAN</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="tentang" class="tentang-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-5 mb-lg-0">
                <div class="tentang-image-wrap">
                    <img src="<?= BASE_URL ?>/assets/img/tentang-kami.png" alt="Penjahit Profesional" class="tentang-image">
                    <div class="tentang-accent"></div>
                </div>
            </div>
            <div class="col-lg-6 offset-lg-1">
                <div class="section-label dark">Profil Usaha</div>
                <h2 class="section-title mb-4">Menyatukan Estetika dan Kualitas dalam Setiap Jahitan</h2>
                <p class="section-desc" style="text-align: justify;">Jashit merupakan usaha jahit yang telah melayani berbagai macam pesanan sejak 2010. Berlokasi di Ciputat, Tangerang Selatan, kami mengutamakan kualitas jahitan, ketepatan waktu, dan komunikasi yang baik dengan pelanggan sebagai prinsip utama dalam setiap karya.</p>
                <p class="section-desc" style="text-align: justify;">Didukung oleh tim penjahit yang berpengalaman dan terampil, <strong>Jashit</strong> mampu menangani berbagai jenis pesanan mulai dari gamis, mukena, seragam kantor, hingga produk fashion lokal dan custom.</p>
                <p class="section-desc" style="text-align: justify;">Visi kami adalah menjadi mitra andalan dalam setiap karya busana. Pilih Jashit, karena kualitas bukan hanya janji tapi sudah terbukti!</p>
                
                <div class="mt-4">
                    <span style="font-size:12px; font-weight:700; color:var(--navy-dark); text-transform:uppercase; letter-spacing:1px;">Dipercaya Oleh Brand:</span>
                    <div class="client-logos">
                        <span class="client-tag">Gudang Garam</span>
                        <span class="client-tag">Maheera</span>
                        <span class="client-tag">Khanaan</span>
                        <span class="client-tag">Event Organizer</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="promo-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="promo-box">
                    <div class="promo-badge">PROMO SPESIAL</div>
                    <h3 class="promo-title">Diskon 10% Khusus Pelanggan Baru!</h3>
                    <p class="promo-desc">Dapatkan potongan harga eksklusif sekarang.</p>
                    <a href="<?= BASE_URL ?>/register.php" class="btn-elegant-dark" style="background:var(--peach-soft); color:var(--navy-dark);">KLAIM PROMO SEKARANG</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="layanan" class="layanan-section">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6">
                <div class="section-label dark">Layanan Kami</div>
                <h2 class="section-title">Standar Tinggi,<br>Harga Bersahabat</h2>
            </div>
            <div class="col-lg-5 offset-lg-1">
                <p class="section-desc">Kami melayani berbagai jenis kebutuhan jahit dengan standar tinggi. Mulai dari perorangan hingga produksi skala besar.</p>
            </div>
        </div>
        <div class="row g-4">
            
            <div class="col-md-4">
                <div class="layanan-card">
                    <img src="<?= BASE_URL ?>/assets/img/busana-muslim.jpg" alt="Busana Muslimah" class="layanan-img">
                    <div class="layanan-content">
                        <h5>Busana Muslimah</h5>
                        <p>Mulai dari jahit gamis hingga busana muslimah yang rapi, sopan, dan sangat elegan dipakai.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="layanan-card">
                    <img src="<?= BASE_URL ?>/assets/img/brand-lokal.jpg" alt="Brand Fashion Lokal" class="layanan-img">
                    <div class="layanan-content">
                        <h5>Brand Fashion Lokal</h5>
                        <p>Menerima pesanan produksi untuk brand fashion lokal yang sangat sesuai dengan desain dan detail Anda.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="layanan-card">
                    <img src="https://images.unsplash.com/photo-1617137984095-74e4e5e3613f?auto=format&fit=crop&w=600&q=80" alt="Pakaian Kantor & Seragam" class="layanan-img">
                    <div class="layanan-content">
                        <h5>Pakaian Kantor & Seragam</h5>
                        <p>Jasa pembuatan seragam kantor dan instansi yang tampil profesional serta nyaman dipakai harian.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="layanan-card">
                    <img src="<?= BASE_URL ?>/assets/img/aksesoris.jpeg" alt="Aksesoris & Rompi" class="layanan-img">
                    <div class="layanan-content">
                        <h5>Aksesoris & Rompi</h5>
                        <p>Kami juga bisa membuatkan aksesoris pelengkap seperti tas mukena, pouch, hingga rompi custom.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="layanan-card">
                    <img src="<?= BASE_URL ?>/assets/img/manekin.jpg" alt="Modifikasi Pakaian" class="layanan-img">
                    <div class="layanan-content">
                        <h5>Modifikasi Pakaian</h5>
                        <p>Menyediakan jasa modifikasi (permak) pakaian agar lebih pas dan nyaman sesuai dengan bentuk tubuh Anda.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="layanan-card">
                    <img src="<?= BASE_URL ?>/assets/img/pakaian-khusus.png" alt="Produksi Skala Besar" class="layanan-img">
                    <div class="layanan-content">
                        <h5>Produksi Skala Besar</h5>
                        <p>Tidak hanya individu, kami berpengalaman menangani pesanan skala besar untuk Event Organizer (EO).</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-5 pt-3">
            <a href="<?= BASE_URL ?>/public/layanan.php" class="btn-elegant-dark" style="background:var(--navy-dark); color:#fff; padding:12px 32px; font-size:14px; letter-spacing:1px; text-transform:uppercase;">
                LIHAT DETAIL LAYANAN & HARGA
            </a>
        </div>
    </div>
</section>

<section id="alur" class="alur-section">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label dark" style="justify-content:center;">Cara Kerja Kami</div>
            <h2 class="section-title">Alur Pemesanan Jashit</h2>
            <p class="section-desc mx-auto" style="max-width:600px;">Proses yang transparan dan mudah dipantau langsung dari device Anda.</p>
        </div>
        <div class="row g-4 position-relative">
            <div class="alur-line d-none d-md-block"></div>
            
            <div class="col-md-3">
                <div class="alur-item">
                    <div class="alur-icon"><i class="bi bi-chat-dots"></i></div>
                    <h5>1. Konsultasi & Diskusi</h5>
                    <p>Mulai dari diskusi desain, pemilihan bahan berkualitas, hingga penentuan ukuran yang paling pas untuk Anda.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alur-item">
                    <div class="alur-icon"><i class="bi bi-clipboard-check"></i></div>
                    <h5>2. Kesepakatan & DP</h5>
                    <p>Setelah bahan dan harga disepakati, Anda bisa melakukan pembayaran uang muka (DP) agar pesanan segera masuk antrean.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alur-item">
                    <div class="alur-icon"><i class="bi bi-scissors"></i></div>
                    <h5>3. Proses Produksi</h5>
                    <p>Tim terampil kami memulai proses cutting, penjahitan, hingga tahap finishing & Quality Control (QC).</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alur-item">
                    <div class="alur-icon"><i class="bi bi-box-seam"></i></div>
                    <h5>4. Selesai</h5>
                    <p>Jahitan selesai tepat waktu!</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="testimoni" class="py-5" style="background-color: #f8f9fa;">
    <div class="container">
        <h2 class="text-center mb-5" style="font-weight: 700; color: var(--navy-dark);">Apa Kata Pelanggan Kami?</h2>
        
        <div class="row g-4 justify-content-center">
            <?php if (mysqli_num_rows($query_fb) > 0): ?>
                <?php while ($fb = mysqli_fetch_assoc($query_fb)): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; padding: 20px;">
                            
                            <div style="color: #f59e0b; font-size: 16px; margin-bottom: 15px;">
                                <?php 
                                $rating = (int)$fb['rating'];
                                for($i=1; $i<=5; $i++) {
                                    echo $i <= $rating ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                                }
                                ?>
                            </div>
                            
                            <p class="card-text flex-grow-1" style="color: #475569; font-size: 14.5px; line-height: 1.6; font-style: italic;">
                                "<?= htmlspecialchars($fb['komentar']) ?>"
                            </p>
                            
                            <div class="mt-3 pt-3 border-top d-flex align-items-center">
                                <?php if(!empty($fb['foto']) && file_exists('../assets/img/uploads/profil/' . $fb['foto'])): ?>
                                    <img src="<?= BASE_URL ?>/assets/img/uploads/profil/<?= $fb['foto'] ?>" alt="User" class="rounded-circle me-3 border" style="width: 45px; height: 45px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3 text-white" style="width: 45px; height: 45px; font-weight: bold; font-size: 16px;">
                                        <?= strtoupper(mb_substr($fb['nama_pelanggan'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div>
                                    <div style="font-weight: 700; color: var(--navy-dark); font-size: 14px;">
                                        <?= htmlspecialchars($fb['nama_pelanggan']) ?>
                                    </div>
                                    <div style="font-size: 11px; color: #94a3b8;">
                                        <?= date('d M Y', strtotime($fb['created_at'])) ?>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted">
                    <p>Belum ada ulasan yang ditampilkan.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>


<?php require_once '../includes/layouts/footer.php'; ?>

<!-- Eksternal Script JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>