<?php
// ============================================
// File Koneksi Database
// Sistem Informasi Manajemen Pelanggan Jashit
// ============================================

// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          
define('DB_NAME', 'jashit');
define('DB_CHARSET', 'utf8mb4');

// Buat koneksi menggunakan mysqli
$koneksi = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek apakah koneksi berhasil
if (!$koneksi) {
    // Jika gagal, tampilkan pesan error dan hentikan script
    die("
        <div style='font-family:sans-serif; padding:20px; background:#fee2e2; 
                    border:1px solid #fca5a5; border-radius:8px; margin:20px;'>
            <h3 style='color:#dc2626;'>❌ Koneksi Database Gagal!</h3>
            <p><strong>Error:</strong> " . mysqli_connect_error() . "</p>
            <p>Pastikan:</p>
            <ul>
                <li>Laragon sudah Running</li>
                <li>MySQL sudah aktif</li>
                <li>Database 'jashit' sudah dibuat</li>
            </ul>
        </div>
    ");
}

// Set charset agar karakter Indonesia tampil dengan benar
mysqli_set_charset($koneksi, DB_CHARSET);

// Timezone Indonesia
date_default_timezone_set('Asia/Jakarta');