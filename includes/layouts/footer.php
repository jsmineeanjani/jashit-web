<!-- ════ FOOTER ════ -->
<footer class="footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-brand">JASHIT.</div>
                <p class="footer-desc">Jashit menghadirkan kualitas jahitan yang rapi, kuat, dan detail. Menyatukan estetika dan kualitas dalam setiap jahitan.</p>
            </div>
            <div class="col-lg-2 offset-lg-1">
                <div class="footer-heading">Navigasi</div>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/public/index.php">Home</a></li>
                    <li><a href="<?= BASE_URL ?>/public/index.php#tentang">Profil Usaha</a></li>
                    <li><a href="<?= BASE_URL ?>/public/index.php#layanan">Layanan</a></li>
                    <li><a href="<?= BASE_URL ?>/public/index.php#alur">Alur Pemesanan</a></li>
                </ul>
            </div>
            <div class="col-lg-2">
                <div class="footer-heading">Akses Sistem</div>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/auth/login.php">Login</a></li>
                    <li><a href="<?= BASE_URL ?>/auth/register.php">Daftar Akun</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <div class="footer-heading">Lokasi & Kontak</div>
                <div class="footer-contact">
                    <div class="mb-2">
                        <i class="bi bi-geo-alt me-2 text-white"></i>
                        <a href="https://www.google.com/maps/place/Jasa+Jahit+-+Jahitra/@-6.3077511,106.7480927,17z/data=!3m1!4b1!4m6!3m5!1s0x2e69ef5cb27a5d71:0x9f29dce99f3d6565!8m2!3d-6.3077511!4d106.7480927!16s%2Fg%2F11x6gbff1l?entry=ttu&g_ep=EgoyMDI2MDQyOS4wIKXMDSoASAFQAw%3D%3D" 
                           target="_blank" 
                           style="color: inherit; text-decoration: none;"
                           onmouseover="this.style.color='#f4d3c2'" 
                           onmouseout="this.style.color='inherit'">
                           Gg. Nurul Huda III No.3, RT.001/RW.015, Ciputat, Kota Tangerang Selatan
                        </a>
                    </div>
                    <div class="mb-2">
                        <i class="bi bi-whatsapp me-2 text-white"></i>
                        <a href="https://wa.me/6285781539128" 
                           target="_blank" 
                           style="color: inherit; text-decoration: none;"
                           onmouseover="this.style.color='#f4d3c2'" 
                           onmouseout="this.style.color='inherit'">
                           0857-8153-9128
                        </a>
                    </div>
                    <div><i class="bi bi-clock me-2 text-white"></i>Senin - Sabtu (09.00 - 22.00 WIB)</div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?= date('Y') ?> JASHIT. Semua hak dilindungi.
        </div>
    </div>
</footer>

<!-- Bootstrap JS & Custom JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>