<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('pelanggan');

// Cek apakah parameter id dan url tersedia di link
if (isset($_GET['id']) && isset($_GET['url'])) {
    $id_notif = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
    $target_url = urldecode($_GET['url']);

    // Ubah status notifikasi menjadi sudah dibaca (is_read = 1)
    mysqli_query($koneksi, "UPDATE notifikasi SET is_read = 1 WHERE id = $id_notif AND user_id = $user_id");

    // Lemparkan pelanggan ke halaman tujuan (tracking / info promo)
    header("Location: " . $target_url);
    exit;
} else {
    // Jika tidak ada parameter, kembalikan ke beranda pelanggan
    header("Location: index.php");
    exit;
}
?>