<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('admin');

$error = '';

$today = date('Ymd');
$query_kode = mysqli_query($koneksi, "SELECT MAX(id) as max_id FROM pesanan");
$row_kode = mysqli_fetch_assoc($query_kode);
$next_id = ($row_kode['max_id'] ?? 0) + 1;
$auto_kode = "JSH-" . $today . "-" . str_pad($next_id, 3, '0', STR_PAD_LEFT);

// =========================================================================
// PERBAIKAN: Ambil data 'harga' juga dari database layanan
// =========================================================================
$layanan_res = mysqli_query($koneksi, "SELECT nama_layanan, harga FROM layanan WHERE status = 'Aktif' ORDER BY nama_layanan ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_pesanan     = $_POST['kode_pesanan'];
    $nama_pelanggan   = trim($_POST['nama_pelanggan']); 
    $no_telp          = trim($_POST['no_telp']); 
    
    $cek_user = mysqli_query($koneksi, "SELECT id FROM users WHERE no_hp = '$no_telp' LIMIT 1");
    
    if (mysqli_num_rows($cek_user) > 0) {
        $row_user = mysqli_fetch_assoc($cek_user);
        $user_id  = $row_user['id']; 
    } else {
        $user_id  = $no_telp; 
    }
    
    $jenis_pakaian    = $_POST['jenis_pakaian'];
    $tipe_pesanan     = $_POST['tipe_pesanan'] ?? 'individu';
    
    $ukuran = ''; 
    $jumlah = 1;
    $warna  = trim($_POST['warna'] ?? '');

    // JIKA PESANAN INDIVIDU
    if ($tipe_pesanan === 'individu') {
        $metode_ukuran = $_POST['metode_ukuran_individu'] ?? 'standar';
        $jumlah = (int)($_POST['jumlah_individu'] ?? 1);

        if ($metode_ukuran === 'standar') {
            $ukuran = trim($_POST['ukuran_standar'] ?? '');
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
            
            $ukuran = implode("\n", $detail_ukuran);
            if (empty($ukuran)) {
                $ukuran = 'Custom (Ditulis di catatan)';
            }
        }
        
    // JIKA PRODUKSI MASSAL
    } elseif ($tipe_pesanan === 'massal') {
        $m_ukuran = $_POST['massal_ukuran'] ?? [];
        $m_warna  = $_POST['massal_warna'] ?? [];
        $m_qty    = $_POST['massal_qty'] ?? [];
        
        $detail_massal = [];
        $total_qty = 0;
        
        for ($i = 0; $i < count($m_ukuran); $i++) {
            if (!empty($m_ukuran[$i]) && !empty($m_qty[$i])) {
                $w = !empty($m_warna[$i]) ? $m_warna[$i] : '-';
                $detail_massal[] = "• Ukuran: " . $m_ukuran[$i] . " | Warna: " . $w . " | Qty: " . $m_qty[$i] . " pcs";
                $total_qty += (int)$m_qty[$i];
            }
        }
        
        $ukuran = "RINCIAN MASSAL:\n" . implode("\n", $detail_massal);
        $warna  = "-"; 
        $jumlah = $total_qty > 0 ? $total_qty : 1;
    }

    $bahan            = trim($_POST['bahan'] ?? '');
    $deskripsi        = trim($_POST['deskripsi'] ?? '');
    $deskripsi_final  = "[Pelanggan Walk-in: " . $nama_pelanggan . "]\n" . $deskripsi;
    
    $total_harga      = (int)$_POST['total_harga'];
    $dp_dibayar       = (int)$_POST['dp_dibayar'];
    $sisa_tagihan     = $total_harga - $dp_dibayar;
    $tanggal_deadline = $_POST['tanggal_deadline'];
    $tanggal_pesan    = date('Y-m-d');

    if ($dp_dibayar <= 0) {
        $status_pembayaran = 'belum_bayar';
    } elseif ($sisa_tagihan <= 0) {
        $status_pembayaran = 'lunas';
    } else {
        $status_pembayaran = 'dp';
    }

    if (empty($user_id) || empty($nama_pelanggan) || empty($jenis_pakaian) || $total_harga <= 0 || $jumlah <= 0) {
        $error = 'Isi nama, nomor telepon, jenis pakaian, rincian jumlah, dan total harga dengan benar.';
    } else {
        $query = "INSERT INTO pesanan (
            kode_pesanan, user_id, jenis_pakaian, jumlah, deskripsi, 
            ukuran, warna, bahan, total_harga, dp_dibayar, 
            sisa_tagihan, status_pembayaran, tanggal_pesan, tanggal_deadline, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'menunggu_konfirmasi')";
        
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, 'sssissssiiisss', 
            $kode_pesanan, $user_id, $jenis_pakaian, $jumlah, $deskripsi_final,
            $ukuran, $warna, $bahan, $total_harga, $dp_dibayar,
            $sisa_tagihan, $status_pembayaran, $tanggal_pesan, $tanggal_deadline
        );

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['flash_success'] = 'Pesanan baru berhasil dicatat!';
            header('Location: pesanan.php');
            exit();
        } else {
            $error = 'Gagal menyimpan pesanan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Pesanan Baru — JASHIT Admin</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body style="background-color: #f8f7f5;">
<div class="dashboard-wrapper">
    <?php require_once '../includes/layouts/sidebar_admin.php'; ?>
    <div class="dashboard-main">
        <?php require_once '../includes/topbar_admin.php'; ?>

        <div class="dashboard-content" style="padding: 24px 32px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title" style="font-size: 22px; font-weight: 700; margin: 0;">Buat Pesanan Baru</h1>
                <a href="pesanan.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="font-size: 14px;"><i class="bi bi-exclamation-triangle me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="section-card shadow-sm p-4 mb-4" style="background:#fff; border-radius:8px; border: 1px solid #e2e8f0;">
                            <h5 class="mb-4" style="font-weight:700; color:var(--navy-dark); font-size:16px; border-bottom: 2px solid #f1f5f9; padding-bottom:10px;">Informasi Pakaian</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Kode Pesanan</label>
                                    <input type="text" name="kode_pesanan" class="form-control bg-light fw-bold text-primary" value="<?= $auto_kode ?>" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Nama Pelanggan <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_pelanggan" class="form-control" placeholder="Nama lengkap..." required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">No. Telepon/WA <span class="text-danger">*</span></label>
                                    <input type="text" name="no_telp" class="form-control" placeholder="Contoh: 081234567xx" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Jenis Pakaian / Layanan <span class="text-danger">*</span></label>
                                <select name="jenis_pakaian" id="jenis_pakaian" class="form-select fw-bold border-secondary" required onchange="hitungTotalHargaOtomatis()">
                                    <option value="" data-harga="0">-- Pilih Dari Katalog --</option>
                                    <?php while($l = mysqli_fetch_assoc($layanan_res)): ?>
                                        <option value="<?= htmlspecialchars($l['nama_layanan']) ?>" data-harga="<?= $l['harga'] ?>">
                                            <?= htmlspecialchars($l['nama_layanan']) ?> (Rp <?= number_format($l['harga'], 0, ',', '.') ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <hr class="my-4">

                            <div class="mb-4">
                                <label class="form-label fw-bold small d-block">Pilih Skala Pesanan <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="tipe_pesanan" id="tipe_individu" value="individu" autocomplete="off" checked onchange="toggleSkalaPesanan()">
                                    <label class="btn btn-outline-dark fw-bold" for="tipe_individu"><i class="bi bi-person me-1"></i> Pesanan Individu</label>

                                    <input type="radio" class="btn-check" name="tipe_pesanan" id="tipe_massal" value="massal" autocomplete="off" onchange="toggleSkalaPesanan()">
                                    <label class="btn btn-outline-dark fw-bold" for="tipe_massal"><i class="bi bi-boxes me-1"></i> Produksi Massal</label>
                                </div>
                            </div>

                            <div id="area_tipe_individu" class="p-3 mb-4" style="background-color: #fff; border: 1px solid #e2e8f0; border-radius: 8px;">
                                <h6 class="fw-bold mb-3" style="color: var(--navy-dark); font-size: 14px;">Rincian Pesanan Individu</h6>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">Jumlah (Pcs)</label>
                                        <input type="number" name="jumlah_individu" id="jumlah_individu" class="form-control" value="1" min="1" oninput="hitungTotalHargaOtomatis()">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold small">Metode Ukuran</label>
                                        <select name="metode_ukuran_individu" id="metode_ukuran_individu" class="form-select text-dark border-secondary" onchange="ubahMetodeUkuranIndividu()">
                                            <option value="standar">Ukuran Standar</option>
                                            <option value="custom">Input Detail Ukuran</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Keterangan Ukuran</label>
                                    <div id="area_standar">
                                        <input type="text" name="ukuran_standar" class="form-control" placeholder="Contoh: S, M, L, XL, XXL">
                                    </div>
                                    <div id="area_custom" class="d-none">
                                        <div class="row g-2">
                                            <div class="col-6 col-md-3"><input type="number" name="ukuran_ld" class="form-control" placeholder="L.Dada (cm)" style="font-size: 12px;"></div>
                                            <div class="col-6 col-md-3"><input type="number" name="ukuran_lp" class="form-control" placeholder="L.Pinggang" style="font-size: 12px;"></div>
                                            <div class="col-6 col-md-3"><input type="number" name="ukuran_pb" class="form-control" placeholder="P.Baju (cm)" style="font-size: 12px;"></div>
                                            <div class="col-6 col-md-3"><input type="number" name="ukuran_pl" class="form-control" placeholder="P.Lengan (cm)" style="font-size: 12px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="area_tipe_massal" class="d-none p-3 mb-4" style="background-color: #fff; border: 1px solid #e2e8f0; border-radius: 8px;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0" style="color: var(--navy-dark); font-size: 14px;">Rincian Produksi Massal</h6>
                                    <span class="badge bg-secondary" id="badge_total_massal">Total: 0 Pcs</span>
                                </div>
                                
                                <div id="container_massal">
                                    <div class="row g-1 mb-2 baris-massal">
                                        <div class="col-4"><input type="text" name="massal_ukuran[]" class="form-control form-control-sm" placeholder="Ukuran"></div>
                                        <div class="col-4"><input type="text" name="massal_warna[]" class="form-control form-control-sm" placeholder="Warna"></div>
                                        <div class="col-3"><input type="number" name="massal_qty[]" class="form-control form-control-sm massal-qty" placeholder="Qty" min="1" oninput="kalkulasiQtyMassal()"></div>
                                        <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger w-100" disabled><i class="bi bi-trash"></i></button></div>
                                    </div>
                                </div>
                                
                                <button type="button" class="btn btn-sm btn-outline-secondary fw-bold mt-2" onclick="tambahBarisMassal()">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Rincian
                                </button>
                            </div>

                            <div class="row mb-3" id="area_warna_bahan">
                                <div class="col-md-6 mb-3 mb-md-0" id="bungkus_warna_umum">
                                    <label class="form-label fw-bold small">Warna Kain Umum</label>
                                    <input type="text" name="warna" class="form-control" placeholder="Contoh: Putih, Hitam">
                                </div>
                                <div class="col-md-6" id="bungkus_bahan_kain">
                                    <label class="form-label fw-bold small">Bahan Kain</label>
                                    <input type="text" name="bahan" class="form-control" placeholder="Contoh: Katun, Linen">
                                </div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label fw-bold small">Deskripsi / Detail Request Tambahan</label>
                                <textarea name="deskripsi" class="form-control" rows="3" placeholder="Tuliskan spesifikasi khusus..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="section-card shadow-sm p-4 mb-4" style="background:#fff; border-radius:8px; border: 1px solid #e2e8f0; position: sticky; top: 20px;">
                            <h5 class="mb-4" style="font-weight:700; color:var(--navy-dark); font-size:16px; border-bottom: 2px solid #f1f5f9; padding-bottom:10px;">Pembayaran & Waktu</h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Total Harga Keseluruhan (Rp) <span class="text-danger">*</span></label>
                                <input type="number" name="total_harga" id="total_harga" class="form-control form-control-lg fw-bold text-primary" placeholder="0" required oninput="hitungSisa()">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">DP Dibayar (Rp)</label>
                                <input type="number" name="dp_dibayar" id="dp_dibayar" class="form-control" placeholder="0" value="0" oninput="hitungSisa()">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-danger">Sisa Tagihan (Rp)</label>
                                <input type="number" id="sisa_tagihan" class="form-control bg-light fw-bold" value="0" readonly>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small">Tanggal Deadline <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_deadline" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm" style="background-color: var(--navy-dark); border:none; letter-spacing:0.5px;">
                                <i class="bi bi-save me-1"></i> SIMPAN PESANAN
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const totalInput = document.getElementById('total_harga');
    const dpInput = document.getElementById('dp_dibayar');
    const sisaInput = document.getElementById('sisa_tagihan');
    const jenisSelect = document.getElementById('jenis_pakaian');
    const inputJumlahIndividu = document.getElementById('jumlah_individu');

    // =========================================================================
    // LOGIKA PERHITUNGAN OTOMATIS
    // =========================================================================
    function hitungTotalHargaOtomatis() {
        // Dapatkan elemen option yang saat ini sedang dipilih
        const selectedOption = jenisSelect.options[jenisSelect.selectedIndex];
        
        // Tarik angka dari atribut data-harga (jika kosong, anggap 0)
        const hargaSatuan = parseInt(selectedOption.getAttribute('data-harga')) || 0;
        
        // Cari tahu sedang mode apa (Individu atau Massal)
        const tipe = document.querySelector('input[name="tipe_pesanan"]:checked').value;
        let qtyTotal = 1;

        if (tipe === 'individu') {
            qtyTotal = parseInt(inputJumlahIndividu.value) || 1;
        } else {
            // Jika massal, hitung jumlah dari semua form qty massal
            let totalMassal = 0;
            document.querySelectorAll('.massal-qty').forEach(input => {
                totalMassal += parseInt(input.value) || 0;
            });
            qtyTotal = totalMassal > 0 ? totalMassal : 1;
        }

        // Hitung total dan pasang di input total_harga
        const kalkulasi = hargaSatuan * qtyTotal;
        totalInput.value = kalkulasi;
        
        // Jangan lupa panggil hitungSisa agar kolom sisa tagihan juga otomatis berubah
        hitungSisa();
    }
    // =========================================================================

    function hitungSisa() {
        const total = parseInt(totalInput.value) || 0;
        const dp = parseInt(dpInput.value) || 0;
        const sisa = total - dp;
        sisaInput.value = sisa < 0 ? 0 : sisa;
    }

    function toggleSkalaPesanan() {
        const tipe = document.querySelector('input[name="tipe_pesanan"]:checked').value;
        const areaIndividu = document.getElementById('area_tipe_individu');
        const areaMassal = document.getElementById('area_tipe_massal');
        const bungkusWarna = document.getElementById('bungkus_warna_umum');
        const bungkusBahan = document.getElementById('bungkus_bahan_kain');

        if (tipe === 'individu') {
            areaIndividu.classList.remove('d-none');
            areaMassal.classList.add('d-none');
            bungkusWarna.classList.remove('d-none');
            bungkusBahan.className = 'col-md-6';
            ubahMetodeUkuranIndividu(); 
        } else {
            areaIndividu.classList.add('d-none');
            areaMassal.classList.remove('d-none');
            bungkusWarna.classList.add('d-none');
            bungkusBahan.className = 'col-md-12';
            kalkulasiQtyMassal();
        }
        
        // Trigger perhitungan harga saat ganti tipe
        hitungTotalHargaOtomatis();
    }

    function ubahMetodeUkuranIndividu() {
        const metode = document.getElementById('metode_ukuran_individu').value;
        document.getElementById('area_standar').classList.add('d-none');
        document.getElementById('area_custom').classList.add('d-none');

        if (metode === 'standar') {
            document.getElementById('area_standar').classList.remove('d-none');
        } else if (metode === 'custom') {
            document.getElementById('area_custom').classList.remove('d-none');
        }
    }

    function tambahBarisMassal() {
        const container = document.getElementById('container_massal');
        const barisBaru = document.createElement('div');
        barisBaru.className = 'row g-1 mb-2 baris-massal';
        barisBaru.innerHTML = `
            <div class="col-4"><input type="text" name="massal_ukuran[]" class="form-control form-control-sm" placeholder="Ukuran"></div>
            <div class="col-4"><input type="text" name="massal_warna[]" class="form-control form-control-sm" placeholder="Warna"></div>
            <div class="col-3"><input type="number" name="massal_qty[]" class="form-control form-control-sm massal-qty" placeholder="Qty" min="1" oninput="kalkulasiQtyMassal()"></div>
            <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="hapusBaris(this)"><i class="bi bi-trash"></i></button></div>
        `;
        container.appendChild(barisBaru);
    }

    function hapusBaris(btn) {
        btn.closest('.baris-massal').remove();
        kalkulasiQtyMassal();
    }

    function kalkulasiQtyMassal() {
        let total = 0;
        const inputs = document.querySelectorAll('.massal-qty');
        inputs.forEach(input => {
            total += parseInt(input.value) || 0;
        });
        document.getElementById('badge_total_massal').innerText = "Total: " + total + " Pcs";
        
        // Trigger perhitungan harga setiap kali ngetik Qty massal
        hitungTotalHargaOtomatis();
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>