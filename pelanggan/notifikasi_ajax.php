<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('pelanggan');

header('Content-Type: application/json');

$user_id = (int) $_SESSION['user_id'];

// Hitung total notifikasi yang belum dibaca
$q_count = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM notifikasi WHERE user_id = $user_id AND is_read = 0");
$unread_count = $q_count ? (int) mysqli_fetch_assoc($q_count)['total'] : 0;

// Ambil beberapa notifikasi terbaru untuk dropdown
$q_list = mysqli_query($koneksi, "SELECT * FROM notifikasi WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 10");

$list = [];
if ($q_list) {
    while ($row = mysqli_fetch_assoc($q_list)) {
        $judul_lower = strtolower($row['judul']);
        $target_url  = $row['url'] ?? '';

        // --- LOGIKA PINTAR PENGARAHAN HALAMAN ---
        // Jika di database URL-nya kosong, kita tentukan otomatis dari judulnya
        if (empty($target_url)) {
            if (strpos($judul_lower, 'promo') !== false || strpos($judul_lower, 'diskon') !== false) {
                $target_url = BASE_URL . '/pelanggan/promo.php'; // Arahkan ke halaman diskon
            } elseif (strpos($judul_lower, 'pembayaran') !== false || strpos($judul_lower, 'pesanan') !== false) {
                $target_url = BASE_URL . '/pelanggan/tracking.php'; // Arahkan ke pesanan saya
            } else {
                $target_url = BASE_URL . '/pelanggan/notifikasi.php'; // Default halaman notif
            }
        }

        // Tentukan Icon
        if (strpos($judul_lower, 'promo') !== false || strpos($judul_lower, 'diskon') !== false) {
            $icon_class = 'icon-promo';
            $icon_bi = 'bi-tags-fill';
        } elseif (strpos($judul_lower, 'pembayaran') !== false) {
            $icon_class = 'icon-bayar';
            $icon_bi = 'bi-wallet2';
        } else {
            $icon_class = 'icon-umum';
            $icon_bi = 'bi-bell-fill';
        }

        $list[] = [
            'id'         => (int) $row['id'],
            'judul'      => htmlspecialchars($row['judul']),
            'pesan'      => htmlspecialchars($row['pesan']),
            'is_read'    => (int) $row['is_read'],
            'created_at' => $row['created_at'],
            'url'        => $target_url, // URL yang sudah otomatis terisi
            'icon_class' => $icon_class,
            'icon_bi'    => $icon_bi,
        ];
    }
}

echo json_encode([
    'unread_count' => $unread_count,
    'list'         => $list,
]);
exit;