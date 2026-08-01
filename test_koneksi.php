<?php
// File ini HANYA untuk testing, akan dihapus nanti
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Test Koneksi - Jashit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container" style="max-width:600px">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h4 class="mb-4">🔌 Test Koneksi Database Jashit</h4>

            <?php
            // Test 1: Cek koneksi
            if ($koneksi) {
                echo '<div class="alert alert-success">
                    <strong>Koneksi database BERHASIL!</strong><br>
                    Terhubung ke database: <code>jashit_db</code>
                </div>';
            }

            // Test 2: Hitung jumlah user
            $result = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users");
            $row = mysqli_fetch_assoc($result);
            echo '<div class="alert alert-info">
                Jumlah user di database: <strong>' . $row['total'] . ' user</strong>
            </div>';

            // Test 3: Tampilkan semua user
            $result_users = mysqli_query($koneksi, "SELECT nama, email, role FROM users");
            echo '<h6 class="mt-3">Daftar User:</h6>';
            echo '<table class="table table-bordered table-sm">';
            echo '<thead><tr><th>Nama</th><th>Email</th><th>Role</th></tr></thead>';
            echo '<tbody>';
            while ($user = mysqli_fetch_assoc($result_users)) {
                $badge = match($user['role']) {
                    'admin' => 'danger',
                    'owner' => 'warning',
                    default => 'primary'
                };
                echo '<tr>
                    <td>' . htmlspecialchars($user['nama']) . '</td>
                    <td>' . htmlspecialchars($user['email']) . '</td>
                    <td><span class="badge bg-' . $badge . '">' . $user['role'] . '</span></td>
                </tr>';
            }
            echo '</tbody></table>';

            // Test 4: Hitung pesanan
            $result_pesanan = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan");
            $row_pesanan = mysqli_fetch_assoc($result_pesanan);
            echo '<div class="alert alert-success mt-2">
                Jumlah pesanan di database: <strong>' . $row_pesanan['total'] . ' pesanan</strong>
            </div>';

            echo '<div class="alert alert-secondary">
                PHP Version: <strong>' . phpversion() . '</strong><br>
                MySQL Version: <strong>' . mysqli_get_server_info($koneksi) . '</strong><br>
                Waktu Server: <strong>' . date('d F Y, H:i:s') . '</strong>
            </div>';
            ?>

            <a href="index.php" class="btn btn-dark btn-sm">← Kembali ke Beranda</a>
        </div>
    </div>
</div>
</body>
</html>