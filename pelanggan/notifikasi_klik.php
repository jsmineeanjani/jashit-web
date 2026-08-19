<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('pelanggan');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Tangkap URL tujuan, jika kosong arahkan ke halaman utama notifikasi
$url = isset($_GET['url']) && !empty($_GET['url']) ? $_GET['url'] : BASE_URL . '/pelanggan/notifikasi.php';

if ($id > 0) {
    $user_id = $_SESSION['user_id'];
    // Update status menjadi sudah dibaca (is_read = 1)
    mysqli_query($koneksi, "UPDATE notifikasi SET is_read = 1 WHERE id = $id AND user_id = $user_id");
}

// Lemparkan user ke halaman yang sesuai
header("Location: " . $url);
exit;