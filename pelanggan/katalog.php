<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('pelanggan');

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// 1. Cek apakah ini pengguna baru (untuk diskon otomatis)
$q_cek_baru = mysqli_query($koneksi, "SELECT id FROM pesanan WHERE user_id = $user_id LIMIT 1");
$is_pengguna_baru = (mysqli_num_rows($q_cek_baru) == 0);

// 2. Ambil daftar voucher yang sedang aktif
$sekarang = date('Y-m-d');
$q_promo_aktif = mysqli_query($koneksi, "
    SELECT id, judul 
    FROM informasi_diskon 
    WHERE status = 'aktif' 
    AND (target_pelanggan = 'semua' OR target_pelanggan IS NULL)
    AND (tgl_selesai IS NULL OR tgl_selesai >= '$sekarang')
");

$promo_list = [];
if ($q_promo_aktif) {
    while ($p = mysqli_fetch_assoc($q_promo_aktif)) {
        $promo_list[] = $p;
    }
}

// PROSES SAAT PELANGGAN MENGIRIM FORM PESANAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_pesanan'])) {
    $jenis_pakaian = trim($_POST['jenis_pakaian']); 
    $kategori_pesanan = $_POST['kategori_pesanan'] ?? 'layanan'; 
    
    $ukuran = ''; 
    $jumlah = 1; 
    $warna  = '';
    $bahan  = trim($_POST['bahan'] ?? '');

    // LOGIKA KHUSUS JIKA PELANGGAN HANYA MEMBELI BAHAN ATAU AKSESORIS
    if ($kategori_pesanan === 'bahan') {
        $jumlah = (int)($_POST['jumlah_meter'] ?? 1);
        $warna  = trim($_POST['warna_bahan'] ?? '');
        $ukuran = "Pembelian Bahan/Kain ($jumlah Meter)";
        $bahan  = $jenis_pakaian; 
        $tipe_pesanan = 'bahan';
    } 
    elseif ($kategori_pesanan === 'aksesoris') {
        $jumlah = (int)($_POST['jumlah_aksesoris'] ?? 1);
        $warna  = trim($_POST['warna_aksesoris'] ?? '');
        $ukuran = "Pembelian Aksesoris ($jumlah Pcs)";
        $bahan  = $jenis_pakaian; 
        $tipe_pesanan = 'aksesoris';
    }
    // LOGIKA JIKA PELANGGAN MEMESAN LAYANAN JAHIT
    else {
        $tipe_pesanan  = $_POST['tipe_pesanan'] ?? 'individu';
        $warna  = trim($_POST['warna'] ?? '');
        
        if ($tipe_pesanan === 'individu') {
            $metode_ukuran = $_POST['metode_ukuran_individu'] ?? 'standar';
            $jumlah = (int)($_POST['jumlah_individu'] ?? 1);

            if ($metode_ukuran === 'standar') {
                $ukuran = trim($_POST['ukuran_standar'] ?? '');
            } elseif ($metode_ukuran === 'wa') {
                $ukuran = 'Konsultasi via WA (Belum tahu ukuran)';
            } elseif ($metode_ukuran === 'custom') {
                $ld = trim($_POST['ukuran_ld'] ?? '');
                $lp = trim($_POST['ukuran_lp'] ?? '');
                $pb = trim($_POST['ukuran_pb'] ?? '');
                $pl = trim($_POST['ukuran_pl'] ?? '');
                
                $detail_ukuran = [];
                if (!empty($ld)) $detail_ukuran[] = "LD: {$ld}cm";
                if (!empty($lp)) $detail_ukuran[] = "L.Pinggang: {$lp}cm";
                if (!empty($pb)) $detail_ukuran[] = "Pjg Baju: {$pb}cm";
                if (!empty($pl)) $detail_ukuran[] = "Pjg Lengan: {$pl}cm";
                
                $ukuran = implode(", ", $detail_ukuran);
                if (empty($ukuran)) {
                    $ukuran = 'Custom (Ditulis di catatan)';
                }
            }
        } elseif ($tipe_pesanan === 'massal') {
            $m_ukuran = $_POST['massal_ukuran'] ?? [];
            $m_warna  = $_POST['massal_warna'] ?? [];
            $m_qty    = $_POST['massal_qty'] ?? [];
            
            $detail_massal = [];
            $total_qty = 0;
            $kumpulan_warna = [];
            
            // Loop berdasarkan jumlah input (Qty) yang masuk, bukan berdasarkan ukuran
            for ($i = 0; $i < count($m_qty); $i++) {
                
                // Pastikan QTY diisi minimal 1, baru proses baris ini
                if (!empty($m_qty[$i]) && (int)$m_qty[$i] > 0) {
                    
                    $uk_val = !empty(trim($m_ukuran[$i] ?? '')) ? trim($m_ukuran[$i]) : 'Campur/Custom';
                    $wr_val = !empty(trim($m_warna[$i] ?? '')) ? trim($m_warna[$i]) : '';
                    
                    if ($wr_val !== '') {
                        $detail_massal[] = $uk_val . " (" . $wr_val . ") = " . $m_qty[$i] . "pcs";
                        $kumpulan_warna[] = $wr_val; // Tangkap warnanya di sini
                    } else {
                        $detail_massal[] = $uk_val . " = " . $m_qty[$i] . "pcs";
                    }
                    
                    $total_qty += (int)$m_qty[$i];
                }
            }
            
            $ukuran = "[PRODUKSI MASSAL] " . implode(" | ", $detail_massal);
            $jumlah = $total_qty > 0 ? $total_qty : 1;
            
            // PROSES SIMPAN WARNA KE DATABASE (Mencegah strip masuk)
            if (!empty($kumpulan_warna)) {
                $warna_unik = array_unique($kumpulan_warna);
                $warna = implode(", ", $warna_unik); 
            } else {
                // Jika pelanggan sama sekali tidak mengetik warna di tabel massal,
                // ambil dari input warna individu (jaga-jaga jika mereka mengetik di sana)
                $warna_umum = trim($_POST['warna'] ?? '');
                $warna = !empty($warna_umum) ? $warna_umum : "Tidak ada spesifikasi warna"; 
            }
        }
    }

    $deskripsi       = trim($_POST['deskripsi'] ?? '');
    $tanggal_selesai = !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : NULL;
    
    // KODE PESANAN
    $hari_ini_db = date('Y-m-d');
    $format_tanggal = date('Ymd');
    
    $q_cek_urutan = mysqli_query($koneksi, "SELECT kode_pesanan FROM pesanan WHERE DATE(tanggal_pesan) = '$hari_ini_db' ORDER BY id DESC LIMIT 1");
    if (mysqli_num_rows($q_cek_urutan) > 0) {
        $row_urutan = mysqli_fetch_assoc($q_cek_urutan);
        $last_kode = $row_urutan['kode_pesanan'];
        $parts = explode('-', $last_kode);
        $last_urutan = (int)end($parts);
        $urutan_baru = $last_urutan + 1;
    } else {
        $urutan_baru = 1;
    }
    $kode_pesanan = 'JSH-' . $format_tanggal . '-' . str_pad($urutan_baru, 3, '0', STR_PAD_LEFT);
    
    // AMBIL HARGA DASAR
    $q_harga = mysqli_prepare($koneksi, "SELECT harga FROM layanan WHERE nama_layanan = ?");
    mysqli_stmt_bind_param($q_harga, 's', $jenis_pakaian);
    mysqli_stmt_execute($q_harga);
    $res_harga = mysqli_stmt_get_result($q_harga);
    
    $harga_per_pcs = 0;
    if ($row_harga = mysqli_fetch_assoc($res_harga)) {
        $harga_per_pcs = $row_harga['harga'];
    }
    
    // ========================================================
    // KALKULASI HARGA GROSIR (Diubah menjadi minimal 30 Pcs)
    // ========================================================
    if (isset($tipe_pesanan) && $tipe_pesanan === 'massal' && $jumlah > 0) {
        if ($jumlah >= 200)      { $harga_per_pcs = $harga_per_pcs * 0.70; } // Diskon 30%
        elseif ($jumlah >= 100)  { $harga_per_pcs = $harga_per_pcs * 0.80; } // Diskon 20%
        elseif ($jumlah >= 50)   { $harga_per_pcs = $harga_per_pcs * 0.90; } // Diskon 10%
        elseif ($jumlah >= 30)   { $harga_per_pcs = $harga_per_pcs * 0.95; } // Diskon 5%
        // 1-29 pcs tetap harga normal (1.00x)
    }

    $total_harga_awal = $harga_per_pcs * $jumlah;
    $total_harga      = $total_harga_awal;
    $promo_dipakai    = NULL;

    // Kalkulasi Voucher (Hanya jika dipakai)
    $voucher_diskon = 0;
    $voucher_label  = '';
    $id_voucher_dipilih = $_POST['id_promo'] ?? '';

    if ($id_voucher_dipilih === 'new_user' && $is_pengguna_baru) {
        $voucher_diskon = 10;
        $voucher_label  = 'Voucher Pengguna Baru';
    } elseif (!empty($id_voucher_dipilih) && $id_voucher_dipilih !== 'new_user') {
        $id_promo_db         = (int)$id_voucher_dipilih;
        $judul_promo_dipilih = 'Voucher Spesial';
        foreach ($promo_list as $pr) {
            if ($pr['id'] == $id_promo_db) { $judul_promo_dipilih = $pr['judul']; break; }
        }
        $voucher_diskon = 10;
        $voucher_label  = "Voucher '{$judul_promo_dipilih}'";
    }

    // Terapkan Diskon Voucher ke Total Harga
    if ($voucher_diskon > 0) {
        $total_harga = $total_harga_awal - ($total_harga_awal * ($voucher_diskon / 100));
        $promo_dipakai = "{$voucher_label} [Total: {$voucher_diskon}%]";
    }

    $dp_dibayar        = 0;
    $sisa_tagihan      = $total_harga;
    $status_pembayaran = 'belum_bayar';
    $status            = 'menunggu_konfirmasi';

    if (empty($jenis_pakaian)) {
        $error = 'Jenis pakaian / bahan tidak valid.';
    } else {
        $query = "INSERT INTO pesanan (user_id, kode_pesanan, jenis_pakaian, jumlah, ukuran, warna, bahan, deskripsi, promo_dipakai, total_harga, dp_dibayar, sisa_tagihan, status_pembayaran, status, tanggal_pesan, tanggal_deadline) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
                  
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, 'ississsssiiisss', 
            $user_id, $kode_pesanan, $jenis_pakaian, $jumlah, $ukuran, $warna, $bahan, $deskripsi, $promo_dipakai, 
            $total_harga, $dp_dibayar, $sisa_tagihan, $status_pembayaran, $status, $tanggal_selesai
        );

        if (mysqli_stmt_execute($stmt)) {
            $satuan = ($kategori_pesanan === 'bahan') ? 'Meter' : 'Pcs';
            $_SESSION['flash_success'] = "Pesanan <b>$kode_pesanan</b> berhasil dibuat! Total pesanan: $jumlah $satuan.";
            header('Location: tracking.php'); 
            exit();
        } else {
            $error = 'Terjadi kesalahan sistem. Gagal membuat pesanan.';
        }
    }
}

// AMBIL DATA LAYANAN
$q_layanan = "SELECT * FROM layanan WHERE status = 'Aktif' ORDER BY id DESC";
$r_layanan = mysqli_query($koneksi, $q_layanan);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Katalog Layanan — JASHIT</title>
<link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
<style>
/* ===== KARTU LAYANAN ===== */
.service-card {
    border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;
    background: #fff; transition: all 0.3s ease;
    height: 100%; display: flex; flex-direction: column;
}
.service-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.08); }
.service-img { width:100%; height:200px; object-fit:cover; background:#f1f5f9; }
.service-body { padding:20px; flex-grow:1; display:flex; flex-direction:column; }
.service-price { font-size:18px; font-weight:800; color:#10b981; margin:8px 0 4px; }
.service-meta  { font-size:12px; color:#64748b; margin-bottom:14px; display:flex; gap:14px; }
.btn-pesan { margin-top:auto; background:var(--navy-dark); color:#fff; font-weight:600; border:none; border-radius:6px; padding:10px; transition:.2s; }
.btn-pesan:hover { background:#1e293b; color:#fff; }

/* ===== SEARCH ===== */
.search-container { position:relative; max-width:450px; }
.search-input { padding:10px 14px 10px 38px; border-radius:50px; border:1px solid #cbd5e1; font-size:14px; transition:.3s; }
.search-input:focus { border-color:var(--navy-dark); box-shadow:0 0 0 .25rem rgba(15,23,42,.1); }
.search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:14px; }
.no-result-msg { display:none; text-align:center; padding:40px 20px; background:#fff; border-radius:12px; border:1px dashed #cbd5e1; margin-top:20px; }

/* ===== TABEL GROSIR ===== */
.tier-table td, .tier-table th { padding: 10px 12px; font-size: 14px; vertical-align: middle; }

/* ===== PREVIEW ESTIMASI MASSAL ===== */
#preview_estimasi {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    border-radius: 10px; color: #fff; padding: 16px 20px;
    font-size: 13px; transition: .3s;
}
#preview_estimasi .est-label { color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; }
#preview_estimasi .est-value { font-weight: 700; font-size: 18px; }
</style>
</head>
<body style="background:#f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/sidebar_pelanggan.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_pelanggan.php'; ?>
        <div class="dashboard-content" style="padding:24px 32px;">

            <!-- Header -->
            <div class="row align-items-center mb-4 g-3">
                <div class="col-md-6">
                    <h1 class="page-title" style="font-size:24px;font-weight:700;color:var(--navy-dark);margin:0;">
                        Katalog Layanan Jashit
                    </h1>
                    <p class="text-muted" style="font-size:14px;margin-top:5px;margin-bottom:0;">
                        Pilih layanan jahitan atau bahan yang Anda butuhkan
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="search-container ms-md-auto">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" id="inputCari" class="form-control search-input"
                               placeholder="Cari layanan, bahan..." onkeyup="cariLayanan()">
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Kartu Layanan -->
            <div class="row g-4" id="katalogContainer">
            <?php if ($r_layanan && mysqli_num_rows($r_layanan) > 0):
                while ($lyr = mysqli_fetch_assoc($r_layanan)): ?>
                <div class="col-md-6 col-lg-4 col-xl-3 kartu-layanan">
                    <div class="service-card shadow-sm">
                        <?php if (!empty($lyr['gambar'])): ?>
                            <img src="<?= BASE_URL ?>/assets/img/layanan/<?= $lyr['gambar'] ?>"
                                 class="service-img" alt="<?= htmlspecialchars($lyr['nama_layanan']) ?>">
                        <?php else: ?>
                            <div class="service-img d-flex align-items-center justify-content-center" style="color:#94a3b8;">
                                <i class="bi bi-scissors" style="font-size:40px;"></i>
                            </div>
                        <?php endif; ?>

                        <div class="service-body">
                            <span class="badge bg-light text-dark border mb-2 txt-kategori" style="width:fit-content;">
                                <?= htmlspecialchars($lyr['kategori']) ?>
                            </span>
                            <h5 class="txt-nama" style="font-size:16px;font-weight:700;color:#1e293b;margin-bottom:4px;line-height:1.4;">
                                <?= htmlspecialchars($lyr['nama_layanan']) ?>
                            </h5>

                            <div class="service-price">
                                Rp <?= number_format($lyr['harga'], 0, ',', '.') ?>
                                <span style="font-size:12px;font-weight:600;color:#64748b;">
                                    <?= $lyr['kategori'] === 'Bahan/Material' ? '/ Meter' : '/ Pcs' ?>
                                </span>
                            </div>

                            <?php if (!in_array($lyr['kategori'], ['Bahan/Material','Aksesoris'])): ?>
                            <div class="service-meta">
                                <span><i class="bi bi-clock"></i> Est. <?= htmlspecialchars($lyr['estimasi_hari']) ?> Hari</span>
                            </div>
                            <?php else: ?>
                            <div class="service-meta"><span style="opacity:0;">-</span></div>
                            <?php endif; ?>

                            <p style="font-size:13px;color:#475569;margin-bottom:18px;line-height:1.5;flex-grow:1;">
                                <?= htmlspecialchars(mb_substr($lyr['deskripsi'], 0, 80)) ?>...
                            </p>

                            <button type="button" class="btn btn-pesan w-100"
                                onclick="bukaModalPesan(
                                    '<?= htmlspecialchars(addslashes($lyr['nama_layanan'])) ?>',
                                    '<?= htmlspecialchars(addslashes($lyr['kategori'])) ?>',
                                    <?= (int)$lyr['harga'] ?>
                                )">
                                <i class="bi bi-cart-plus me-1"></i> Pilih & Pesan
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center py-4">
                        <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                        Saat ini belum ada layanan yang tersedia.
                    </div>
                </div>
            <?php endif; ?>
            </div>

            <div id="pesanKosong" class="no-result-msg">
                <i class="bi bi-search fs-1 text-muted d-block mb-3"></i>
                <h6 class="fw-bold text-dark mb-1">Layanan tidak ditemukan</h6>
                <p class="text-muted small mb-0">Coba kata kunci lain seperti "Kemeja", "Abaya", atau "Katun".</p>
            </div>

        </div>
    </div>
</div>

<!-- MODAL PESAN -->
<div class="modal fade" id="modalPesan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content" style="border-radius:14px;border:none;">
      <div class="modal-header" style="background:var(--navy-dark);color:#fff;border-radius:14px 14px 0 0;">
        <h5 class="modal-title fw-bold"><i class="bi bi-bag-plus me-2"></i>Buat Pesanan</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4" style="background:#f8fafc;">
        <form action="" method="POST">
          <input type="hidden" name="buat_pesanan"    value="1">
          <input type="hidden" name="kategori_pesanan" id="kategori_pesanan" value="layanan">

          <!-- Item Terpilih -->
          <div class="mb-4">
            <label class="form-label fw-bold small text-muted text-uppercase" style="letter-spacing:.5px;">Item Dipilih</label>
            <input type="text" name="jenis_pakaian" id="inputJenisPakaian"
                   class="form-control form-control-lg fw-bold" readonly
                   style="background:#e2e8f0;font-size:16px;">
          </div>

          <!-- AREA LAYANAN JAHIT -->
          <div id="area_layanan_jahit">

            <div class="mb-4">
              <label class="form-label fw-bold small d-block">Skala Pesanan <span class="text-danger">*</span></label>
              <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="tipe_pesanan" id="tipe_individu" value="individu" checked onchange="toggleSkala()">
                <label class="btn btn-outline-dark fw-bold" for="tipe_individu">
                  <i class="bi bi-person me-1"></i> Pesanan Individu
                </label>
                <input type="radio" class="btn-check" name="tipe_pesanan" id="tipe_massal" value="massal" onchange="toggleSkala()">
                <label class="btn btn-outline-dark fw-bold" for="tipe_massal">
                  <i class="bi bi-boxes me-1"></i> Produksi Massal / Grosir
                </label>
              </div>
            </div>

            <!-- INDIVIDU -->
            <div id="area_individu" class="p-3 mb-4" style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;">
              <h6 class="fw-bold mb-3" style="color:var(--navy-dark);font-size:14px;">Rincian Pesanan Individu</h6>
              <div class="row mb-3">
                <div class="col-md-4">
                  <label class="form-label fw-bold small">Jumlah (Pcs)</label>
                  <input type="number" name="jumlah_individu" class="form-control" value="1" min="1">
                </div>
                <div class="col-md-8">
                  <label class="form-label fw-bold small">Warna Kain</label>
                  <input type="text" name="warna" class="form-control" placeholder="Contoh: Hitam, Navy">
                </div>
              </div>
              <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                  <label class="form-label fw-bold small">Metode Ukuran</label>
                  <select name="metode_ukuran_individu" id="metode_ukuran" class="form-select border-secondary" onchange="ubahMetodeUkuran()">
                    <option value="standar">Ukuran Standar (S/M/L…)</option>
                    <option value="custom">Input Detail Ukuran</option>
                    <option value="wa">Konsultasi via WA</option>
                  </select>
                </div>
                <div class="col-md-8">
                  <label class="form-label fw-bold small">Keterangan Ukuran</label>
                  <div id="area_standar">
                    <input type="text" name="ukuran_standar" class="form-control" placeholder="S, M, L, XL, XXL">
                  </div>
                  <div id="area_custom" class="d-none">
                    <div class="row g-2">
                      <div class="col-6 col-md-3"><input type="number" name="ukuran_ld" class="form-control" placeholder="L.Dada (cm)" style="font-size:12px;"></div>
                      <div class="col-6 col-md-3"><input type="number" name="ukuran_lp" class="form-control" placeholder="L.Pinggang" style="font-size:12px;"></div>
                      <div class="col-6 col-md-3"><input type="number" name="ukuran_pb" class="form-control" placeholder="P.Baju (cm)" style="font-size:12px;"></div>
                      <div class="col-6 col-md-3"><input type="number" name="ukuran_pl" class="form-control" placeholder="P.Lengan (cm)" style="font-size:12px;"></div>
                    </div>
                  </div>
                  <div id="area_wa" class="d-none">
                    <div class="alert py-2 mb-0" style="font-size:13px;background:#f1f5f9;border:1px solid #cbd5e1;">
                      <i class="bi bi-whatsapp text-success me-1"></i> Konsultasikan detail ukuran via WhatsApp setelah memesan.
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- PRODUKSI MASSAL -->
            <div id="area_massal" class="d-none mb-4" style="border:2px solid #0f172a;border-radius:12px;overflow:hidden;">
              <div style="background:var(--navy-dark);padding:14px 20px;color:#fff;">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <span class="fw-bold" style="font-size:15px;"><i class="bi bi-boxes me-2"></i>Produksi Massal / Grosir</span>
                    <div style="font-size:12px;color:#94a3b8;margin-top:2px;">Tingkatkan jumlah kuantitas untuk keperluan Bisnis Anda!</div>
                  </div>
                </div>
              </div>

              <div class="p-3" style="background:#fff;">
                <div class="mb-4">
                  <p class="fw-bold small text-muted mb-2 text-uppercase" style="letter-spacing:.5px;">
                    <i class="bi bi-tag me-1"></i> Daftar Harga per Kuantitas
                  </p>
                  <div class="table-responsive">
                    <table class="table table-bordered tier-table mb-0 text-center">
                      <thead class="table-light">
                        <tr><th width="50%">Rentang Jumlah</th><th width="50%">Harga Per Pcs</th></tr>
                      </thead>
                      <tbody id="tabel_tier_body">
                          <!-- Digenerate oleh JavaScript -->
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="mb-3">
                  <p class="fw-bold small text-muted mb-2 text-uppercase" style="letter-spacing:.5px;">
                    <i class="bi bi-list-columns me-1"></i> Rincian Ukuran & Warna
                  </p>
                  <div id="container_massal">
                    <div class="row g-1 mb-2 baris-massal">
                      <div class="col-4"><input type="text" name="massal_ukuran[]" class="form-control form-control-sm" placeholder="Ukuran (S/M/L/XL)"></div>
                      <div class="col-4"><input type="text" name="massal_warna[]"  class="form-control form-control-sm" placeholder="Warna"></div>
                      <div class="col-3"><input type="number" name="massal_qty[]" class="form-control form-control-sm qty-massal" placeholder="Qty" min="1" oninput="updateEstimasi()"></div>
                      <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger w-100" disabled><i class="bi bi-trash"></i></button></div>
                    </div>
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-secondary fw-bold mt-1" onclick="tambahBarisMassal()">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Baris
                  </button>
                </div>

                <div id="preview_estimasi">
                  <div class="row g-3">
                    <div class="col-6 col-md-4">
                      <div class="est-label">Total Qty</div>
                      <div class="est-value" id="est_qty">0 pcs</div>
                    </div>
                    <div class="col-6 col-md-4">
                      <div class="est-label">Harga per Pcs</div>
                      <div class="est-value" id="est_harga_pcs">—</div>
                    </div>
                    <div class="col-12 col-md-4">
                      <div class="est-label">Subtotal Harga</div>
                      <div class="est-value text-warning" id="est_total">—</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div><!-- end area_layanan_jahit -->

          <!-- AREA BAHAN -->
          <div id="area_bahan" class="d-none p-3 mb-4" style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;">
            <h6 class="fw-bold mb-3" style="color:var(--navy-dark);font-size:14px;">Pembelian Bahan Kain</h6>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label fw-bold small">Jumlah (Meter) <span class="text-danger">*</span></label>
                <input type="number" name="jumlah_meter" class="form-control" value="1" min="1">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-bold small">Warna / Corak</label>
                <input type="text" name="warna_bahan" class="form-control" placeholder="Contoh: Merah Marun, Motif Kotak">
              </div>
            </div>
          </div>

          <!-- AREA AKSESORIS -->
          <div id="area_aksesoris" class="d-none p-3 mb-4" style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;">
            <h6 class="fw-bold mb-3" style="color:var(--navy-dark);font-size:14px;">Pembelian Aksesoris</h6>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label fw-bold small">Jumlah (Pcs) <span class="text-danger">*</span></label>
                <input type="number" name="jumlah_aksesoris" class="form-control" value="1" min="1">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-bold small">Warna / Varian</label>
                <input type="text" name="warna_aksesoris" class="form-control" placeholder="Contoh: Hitam, Polos">
              </div>
            </div>
          </div>

          <!-- BARIS BAWAH: VOUCHER + BAHAN + TANGGAL + CATATAN -->
          <div class="row mb-3">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold small">
                <i class="bi bi-ticket-perforated me-1"></i> Gunakan Voucher (Opsional)
              </label>
              <select name="id_promo" id="selectVoucher" class="form-select border-secondary" onchange="updateEstimasi()">
                <?php if ($is_pengguna_baru): ?>
                  <option value="new_user" selected>Voucher Pengguna Baru — Diskon 10%</option>
                <?php endif; ?>
                <option value="" <?= $is_pengguna_baru ? '' : 'selected' ?>>Tanpa Voucher</option>
                <?php foreach ($promo_list as $pr): ?>
                  <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['judul']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3" id="area_input_bahan">
              <label class="form-label fw-bold small">Bahan Kain Keseluruhan</label>
              <input type="text" name="bahan" class="form-control" placeholder="Katun, Linen, Sifon, dll.">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold small">Tanggal Diinginkan Selesai <span class="text-muted">(Opsional)</span></label>
            <input type="date" name="tanggal_selesai" class="form-control" min="<?= date('Y-m-d') ?>">
          </div>

          <div class="mb-4">
            <label class="form-label fw-bold small">Catatan / Detail Spesifik</label>
            <textarea name="deskripsi" class="form-control" rows="3"
                      placeholder="Model, referensi gambar, warna detail, alamat kirim, dll..."></textarea>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm"
                  style="background:var(--navy-dark);border:none;font-size:15px;border-radius:8px;">
            <i class="bi bi-send me-2"></i> KIRIM PESANAN & TERBITKAN TAGIHAN
          </button>

        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Struktur Data Tier Harga (Multiplier / Faktor Pengali)
// Mulai grosir ditarik mundur menjadi min 30 pcs
const TIER_GROSIR = [
    { min: 1,   max: 29,  multiplier: 1.00 },
    { min: 30,  max: 49,  multiplier: 0.95 },
    { min: 50,  max: 99,  multiplier: 0.90 },
    { min: 100, max: 199, multiplier: 0.80 },
    { min: 200, max: '∞', multiplier: 0.70 }
];

let hargaAktif = 0;

function formatRp(n) {
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}

function generateTabelGrosir(hargaBase) {
    let tableHTML = '';
    // Loop untuk menampilkan dari kuantitas terkecil hingga terbesar
    TIER_GROSIR.forEach(t => { 
        let range = t.max === '∞' ? `≥ ${t.min} Pcs` : `${t.min} - ${t.max} Pcs`;
        let price = hargaBase * t.multiplier;
        
        // Cek apakah ini harga normal atau harga grosir
        let labelHarga = t.multiplier === 1.00 
            ? `<span class="text-secondary">${formatRp(price)} / pcs (Harga Normal)</span>` 
            : `<span class="fw-bold text-success">${formatRp(price)} / pcs</span>`;
            
        tableHTML += `<tr><td>${range}</td><td>${labelHarga}</td></tr>`;
    });
    document.getElementById('tabel_tier_body').innerHTML = tableHTML;
}

function getHargaPerPcs(qty, hargaBase) {
    // Balik array untuk mencari dari kuantitas terbesar ke terkecil
    const sortedTiers = [...TIER_GROSIR].reverse();
    for (const t of sortedTiers) {
        if (qty >= t.min) return hargaBase * t.multiplier;
    }
    return hargaBase;
}

function bukaModalPesan(nama, kategori, harga) {
    hargaAktif = parseInt(harga) || 0;
    document.getElementById('inputJenisPakaian').value = nama;

    const isBahan     = kategori === 'Bahan/Material';
    const isAksesoris = kategori === 'Aksesoris';

    document.getElementById('kategori_pesanan').value =
        isBahan ? 'bahan' : isAksesoris ? 'aksesoris' : 'layanan';

    ['area_layanan_jahit','area_bahan','area_aksesoris','area_input_bahan'].forEach(id => {
        document.getElementById(id).classList.add('d-none');
    });

    if (isBahan) {
        document.getElementById('area_bahan').classList.remove('d-none');
    } else if (isAksesoris) {
        document.getElementById('area_aksesoris').classList.remove('d-none');
    } else {
        document.getElementById('area_layanan_jahit').classList.remove('d-none');
        document.getElementById('area_input_bahan').classList.remove('d-none');
        document.getElementById('tipe_individu').checked = true;
        
        generateTabelGrosir(hargaAktif); // Buat tabel grosir dinamis
        toggleSkala();
    }

    resetEstimasi();
    new bootstrap.Modal(document.getElementById('modalPesan')).show();
}

function toggleSkala() {
    const tipe = document.querySelector('input[name="tipe_pesanan"]:checked').value;
    document.getElementById('area_individu').classList.toggle('d-none', tipe !== 'individu');
    document.getElementById('area_massal').classList.toggle('d-none',   tipe !== 'massal');
    if (tipe === 'individu') ubahMetodeUkuran();
}

function ubahMetodeUkuran() {
    const m = document.getElementById('metode_ukuran').value;
    ['area_standar','area_custom','area_wa'].forEach(id =>
        document.getElementById(id).classList.add('d-none'));
    document.getElementById('area_' + m).classList.remove('d-none');
}

function tambahBarisMassal() {
    const c   = document.getElementById('container_massal');
    const row = document.createElement('div');
    row.className = 'row g-1 mb-2 baris-massal';
    row.innerHTML = `
        <div class="col-4"><input type="text"   name="massal_ukuran[]" class="form-control form-control-sm" placeholder="Ukuran"></div>
        <div class="col-4"><input type="text"   name="massal_warna[]"  class="form-control form-control-sm" placeholder="Warna"></div>
        <div class="col-3"><input type="number" name="massal_qty[]"    class="form-control form-control-sm qty-massal" placeholder="Qty" min="1" oninput="updateEstimasi()"></div>
        <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="hapusBarisMassal(this)"><i class="bi bi-trash"></i></button></div>
    `;
    c.appendChild(row);
}

function hapusBarisMassal(btn) {
    btn.closest('.baris-massal').remove();
    updateEstimasi();
}

function updateEstimasi() {
    let totalQty = 0;
    document.querySelectorAll('.qty-massal').forEach(i => totalQty += parseInt(i.value) || 0);

    const hargaPerPcs = getHargaPerPcs(totalQty, hargaAktif);
    const subTotal    = hargaPerPcs * totalQty;

    // Panel estimasi
    document.getElementById('est_qty').textContent       = totalQty + ' pcs';
    document.getElementById('est_harga_pcs').textContent = totalQty > 0 ? formatRp(hargaPerPcs) : '—';
    document.getElementById('est_total').textContent     = totalQty > 0 ? formatRp(subTotal) : '—';
}

function resetEstimasi() {
    document.getElementById('est_qty').textContent       = '0 pcs';
    document.getElementById('est_harga_pcs').textContent = '—';
    document.getElementById('est_total').textContent     = '—';
}

function cariLayanan() {
    const q     = document.getElementById('inputCari').value.toLowerCase();
    const cards = document.getElementsByClassName('kartu-layanan');
    let ada     = false;
    for (const c of cards) {
        const nama = c.querySelector('.txt-nama').innerText.toLowerCase();
        const kat  = c.querySelector('.txt-kategori').innerText.toLowerCase();
        const show = nama.includes(q) || kat.includes(q);
        c.style.display = show ? '' : 'none';
        if (show) ada = true;
    }
    document.getElementById('pesanKosong').style.display = ada ? 'none' : 'block';
}
</script>
</body>
</html>