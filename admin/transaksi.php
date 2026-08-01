<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin');

// --- AKSI VERIFIKASI PEMBAYARAN ---
if (isset($_GET['terima'])) {
    $id_trx = (int)$_GET['terima'];
    
    // Cek apakah transaksi ini masih "Menunggu" agar tidak diverifikasi 2 kali
    $cek = mysqli_query($koneksi, "SELECT * FROM transaksi WHERE id = $id_trx AND status_verifikasi = 'Menunggu'");
    if (mysqli_num_rows($cek) > 0) {
        $trx = mysqli_fetch_assoc($cek);
        $pesanan_id = $trx['pesanan_id'];
        $nominal_bayar = $trx['nominal'];

        // 1. Ubah status transaksi jadi Terverifikasi (KITA SIMPAN KE VARIABEL $update)
        $update = mysqli_query($koneksi, "UPDATE transaksi SET status_verifikasi = 'Terverifikasi' WHERE id = $id_trx");
        
        // --- LOGIKA NOTIFIKASI ---
        if ($update) {
            // Ambil user_id dan kode pesanan dari transaksi ini
            $q_info = mysqli_query($koneksi, "SELECT p.user_id, p.kode_pesanan FROM transaksi t JOIN pesanan p ON t.pesanan_id = p.id WHERE t.id = $id_trx");
            if ($info = mysqli_fetch_assoc($q_info)) {
                $uid_pelanggan = $info['user_id'];
                $kode = $info['kode_pesanan'];
                
                // Kirim Notifikasi
                $judul_notif = "Pembayaran Dikonfirmasi!";
                $pesan_notif = "Hore! Pembayaran sebesar Rp " . number_format($nominal_bayar, 0, ',', '.') . " untuk pesanan $kode telah kami terima. Admin sedang memprosesnya!";
                mysqli_query($koneksi, "INSERT INTO notifikasi (user_id, judul, pesan) VALUES ($uid_pelanggan, '$judul_notif', '$pesan_notif')");
            }
        }

        // 2. Update Otomatis ke Tabel Pesanan (DP & Sisa Tagihan)
        $q_pesanan = mysqli_query($koneksi, "SELECT total_harga, dp_dibayar FROM pesanan WHERE id = $pesanan_id");
        if ($pesanan = mysqli_fetch_assoc($q_pesanan)) {
            $dp_baru = $pesanan['dp_dibayar'] + $nominal_bayar;
            
            // Mencegah DP lebih dari total harga
            if ($dp_baru > $pesanan['total_harga']) {
                $dp_baru = $pesanan['total_harga'];
            }
            
            $sisa_baru = $pesanan['total_harga'] - $dp_baru;
            $status_bayar = ($sisa_baru <= 0) ? 'lunas' : 'dp';

            mysqli_query($koneksi, "UPDATE pesanan SET dp_dibayar = $dp_baru, sisa_tagihan = $sisa_baru, status_pembayaran = '$status_bayar' WHERE id = $pesanan_id");
        }
        $_SESSION['flash_success'] = 'Pembayaran berhasil diverifikasi!';
    }
    header("Location: transaksi.php");
    exit;
}

// --- AKSI TOLAK PEMBAYARAN ---
if (isset($_GET['tolak'])) {
    $id_trx = (int)$_GET['tolak'];
    mysqli_query($koneksi, "UPDATE transaksi SET status_verifikasi = 'Ditolak' WHERE id = $id_trx AND status_verifikasi = 'Menunggu'");
    $_SESSION['flash_error'] = 'Bukti pembayaran ditolak.';
    header("Location: transaksi.php");
    exit;
}

// Ambil data transaksi beserta kode pesanan dan nama pelanggan
$query = "SELECT t.*, p.kode_pesanan, u.nama AS nama_pelanggan, u.no_hp 
          FROM transaksi t
          JOIN pesanan p ON t.pesanan_id = p.id
          JOIN users u ON p.user_id = u.id
          ORDER BY t.status_verifikasi ASC, t.tanggal_bayar DESC"; // Yang "Menunggu" ditaruh paling atas
$result = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Pembayaran — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .card-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 24px; }
        table.dataTable thead th { background-color: #f8fafc; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid #e2e8f0 !important; padding: 14px 15px; }
        table.dataTable tbody td { vertical-align: middle; font-size: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; padding: 16px 15px; }
        .dataTables_wrapper .row:first-child { align-items: center; margin-bottom: 20px; }
        div.dataTables_filter { text-align: left !important; }
        div.dataTables_filter input { width: 250px; display: inline-block; margin-left: 0; margin-right: 10px; }
        div.dataTables_length { text-align: right !important; }
        div.dataTables_length select { width: auto; display: inline-block; margin: 0 8px; }
        
        .bukti-img { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; cursor: pointer; transition: 0.2s; }
        .bukti-img:hover { transform: scale(1.1); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_admin.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="mb-4">
                <h1 class="page-title" style="font-size: 22px; font-weight: 700; margin: 0;">Verifikasi Pembayaran</h1>
                <p class="text-muted" style="font-size: 13px; margin-top: 5px; margin-bottom: 0;">Cek dan validasi bukti transfer/QRIS yang diunggah pelanggan.</p>
            </div>

            <?php if(isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size: 14px;">
                    <i class="bi bi-check-circle me-2"></i> <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="font-size: 14px;">
                    <i class="bi bi-x-circle me-2"></i> <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card-table-wrap shadow-sm">
                <table id="tabelTransaksi" class="table table-hover w-100 mb-0">
                    <thead>
                        <tr>
                            <th width="15%">WAKTU BAYAR</th>
                            <th width="20%">INFO PESANAN</th>
                            <th width="20%">DETAIL PEMBAYARAN</th>
                            <th width="10%" class="text-center">BUKTI</th>
                            <th width="15%" class="text-center">STATUS</th>
                            <th width="20%" class="text-center">AKSI VERIFIKASI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result && mysqli_num_rows($result) > 0): 
                            while ($row = mysqli_fetch_assoc($result)): 
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: #1e293b; font-size: 13px;">
                                        <?= date('d M Y', strtotime($row['tanggal_bayar'])) ?>
                                    </div>
                                    <div style="font-size: 11px; color: #64748b;">
                                        <?= date('H:i', strtotime($row['tanggal_bayar'])) ?> WIB
                                    </div>
                                </td>
                                <td>
                                    <a href="pesanan_detail.php?id=<?= $row['pesanan_id'] ?>" style="font-weight: 700; color: var(--navy-dark); font-family: monospace; font-size: 14px; text-decoration: none;">
                                        <?= htmlspecialchars($row['kode_pesanan']) ?>
                                    </a>
                                    <div style="font-size: 12px; color: #475569; margin-top: 2px;">
                                        <?= htmlspecialchars($row['nama_pelanggan']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #10b981; font-size: 15px;">
                                        Rp <?= number_format($row['nominal'], 0, ',', '.') ?>
                                    </div>
                                    <div style="font-size: 11px; margin-top: 3px;">
                                        <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($row['jenis_pembayaran']) ?></span>
                                        <span style="color: #64748b;"><i class="bi bi-wallet2"></i> <?= htmlspecialchars($row['metode_pembayaran']) ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if(!empty($row['bukti_pembayaran'])): ?>
                                        <img src="<?= BASE_URL ?>/assets/img/bukti/<?= $row['bukti_pembayaran'] ?>" class="bukti-img shadow-sm" onclick="lihatBukti('<?= BASE_URL ?>/assets/img/bukti/<?= $row['bukti_pembayaran'] ?>')">
                                    <?php else: ?>
                                        <span class="badge bg-secondary" style="font-size: 10px;">TANPA BUKTI</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php 
                                    if($row['status_verifikasi'] == 'Menunggu') {
                                        echo '<span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-hourglass-split"></i> MENUNGGU</span>';
                                    } elseif($row['status_verifikasi'] == 'Terverifikasi') {
                                        echo '<span class="badge bg-success px-2 py-1"><i class="bi bi-check-circle"></i> VALID</span>';
                                    } else {
                                        echo '<span class="badge bg-danger px-2 py-1"><i class="bi bi-x-circle"></i> DITOLAK</span>';
                                    }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php if($row['status_verifikasi'] == 'Menunggu'): ?>
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="transaksi.php?terima=<?= $row['id'] ?>" class="btn btn-sm btn-success btn-terima" style="font-size: 11px; font-weight: 600;">
                                                <i class="bi bi-check2"></i> TERIMA
                                            </a>
                                            <a href="transaksi.php?tolak=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger btn-tolak" style="font-size: 11px; font-weight: 600;">
                                                <i class="bi bi-x-lg"></i> TOLAK
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: #94a3b8; font-style: italic;">Selesai diverifikasi</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<script>
    $(document).ready(function() {
        $('#tabelTransaksi').DataTable({
            "dom": "<'row mb-3'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            "language": { "search": "", "searchPlaceholder": "Cari kode / nama..." },
            "ordering": false // Dimatikan agar urutan SQL (Menunggu di atas) tidak rusak
        });

        // Konfirmasi Terima
        $(document).on('click', '.btn-terima', function(e) {
            e.preventDefault();
            const href = $(this).attr('href');
            Swal.fire({
                title: 'Verifikasi Pembayaran?',
                text: "Apakah anda yakin bukti ini sudah valid?.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Verifikasi!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = href;
            });
        });

        // Konfirmasi Tolak
        $(document).on('click', '.btn-tolak', function(e) {
            e.preventDefault();
            const href = $(this).attr('href');
            Swal.fire({
                title: 'Tolak Pembayaran?',
                text: "Pelanggan harus mengunggah ulang bukti pembayaran yang valid.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Tolak!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = href;
            });
        });
    });

    // Fungsi popup gambar bukti bayar
    function lihatBukti(urlGambar) {
        Swal.fire({
            imageUrl: urlGambar,
            imageAlt: 'Bukti Pembayaran',
            showConfirmButton: false,
            showCloseButton: true,
            customClass: {
                popup: 'p-2'
            }
        });
    }
</script>
</body>
</html>