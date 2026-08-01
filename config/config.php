<?php
// ============================================
// File Konfigurasi Global
// ============================================

// URL dasar project (sesuaikan jika nama folder berbeda)
define('BASE_URL', 'http://localhost/jashit');

// Nama aplikasi
define('APP_NAME', 'Jashit');
define('APP_TAGLINE', 'Sistem Informasi Manajemen Pelanggan');
define('APP_VERSION', '1.0.0');

// Path folder upload
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/jashit/assets/img/uploads/');
define('UPLOAD_URL', BASE_URL . '/assets/img/uploads/');

// Ukuran maksimal upload file (2MB)
define('MAX_FILE_SIZE', 2 * 1024 * 1024);

// Ekstensi file yang diizinkan untuk upload
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Jumlah data per halaman (pagination)
define('DATA_PER_PAGE', 10);

// Mulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include file koneksi database
require_once __DIR__ . '/koneksi.php';