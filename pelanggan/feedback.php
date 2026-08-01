<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireRole('pelanggan');

$user_id = $_SESSION['user_id'];
$error   = '';

// Ambil pesanan selesai milik pelanggan ini (untuk dropdown)
$stmt_p = mysqli_prepare($koneksi,
    "SELECT p.id, p.kode_pesanan, p.jenis_pakaian
     FROM pesanan p
     WHERE p.user_id = ? AND p.status = 'selesai'
     ORDER BY p.updated_at DESC"
);
mysqli_stmt_bind_param($stmt_p, 'i', $user_id);
mysqli_stmt_execute($stmt_p);
$result_p       = mysqli_stmt_get_result($stmt_p);
$pesanan_selesai = [];
while ($row = mysqli_fetch_assoc($result_p)) {
    $pesanan_selesai[] = $row;
}
mysqli_stmt_close($stmt_p);

// Simpan feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating     = (int)($_POST['rating'] ?? 0);
    $komentar   = trim($_POST['komentar'] ?? '');
    $pesanan_id = (int)($_POST['pesanan_id'] ?? 0);

    if ($rating < 1 || $rating > 5) {
        $error = 'Pilih rating antara 1 sampai 5.';
    } elseif (empty($komentar)) {
        $error = 'Komentar tidak boleh kosong.';
    } else {
        $pid = $pesanan_id ?: null;
        $stmt = mysqli_prepare($koneksi,
            "INSERT INTO feedback
             (user_id, pesanan_id, rating, komentar, status)
             VALUES (?, ?, ?, ?, 'menunggu')"
        );
        mysqli_stmt_bind_param($stmt, 'iiis',
            $user_id, $pid, $rating, $komentar
        );
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['flash_success'] =
                'Terima kasih! Feedback Anda telah terkirim.';
            mysqli_stmt_close($stmt);
            header('Location: ' . BASE_URL . '/pelanggan/feedback.php');
            exit();
        } else {
            $error = 'Gagal mengirim feedback. Coba lagi.';
            mysqli_stmt_close($stmt);
        }
    }
}

// Riwayat feedback milik pelanggan ini
$stmt_f = mysqli_prepare($koneksi,
    "SELECT f.*, p.kode_pesanan
     FROM feedback f
     LEFT JOIN pesanan p ON f.pesanan_id = p.id
     WHERE f.user_id = ?
     ORDER BY f.created_at DESC"
);
mysqli_stmt_bind_param($stmt_f, 'i', $user_id);
mysqli_stmt_execute($stmt_f);
$result_f       = mysqli_stmt_get_result($stmt_f);
$feedback_list  = [];
while ($row = mysqli_fetch_assoc($result_f)) {
    $feedback_list[] = $row;
}
mysqli_stmt_close($stmt_f);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback — JASHIT</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .star-group { display: flex; gap: 6px; flex-direction: row-reverse;
                      justify-content: flex-end; }
        .star-group input { display: none; }
        .star-group label {
            font-size: 28px; cursor: pointer;
            color: #e2e8f0; transition: color 0.15s;
        }
        .star-group input:checked ~ label,
        .star-group label:hover,
        .star-group label:hover ~ label {
            color: #f59e0b;
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <?php require_once '../includes/sidebar_pelanggan.php'; ?>
    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <h1 class="page-title">Feedback & Ulasan</h1>
        </div>
        <div class="dashboard-content">
            <?= showFlash() ?>

            <div class="row g-4">

                <div class="col-lg-5">
                    <div class="section-card">
                        <div class="section-card-header">
                            <h5>Tulis Ulasan</h5>
                        </div>
                        <div class="section-card-body">

                            <?php if ($error): ?>
                                <div class="alert-flash alert-flash-error">
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST">

                                <div class="mb-4">
                                    <label class="form-label-elegant">
                                        Pesanan (opsional)
                                    </label>
                                    <select name="pesanan_id"
                                            class="form-control-elegant">
                                        <option value="">
                                            — Ulasan Umum —
                                        </option>
                                        <?php foreach ($pesanan_selesai as $ps): ?>
                                            <option value="<?= $ps['id'] ?>"
                                                <?= ($_POST['pesanan_id'] ?? '') == $ps['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($ps['kode_pesanan']) ?>
                                                — <?= htmlspecialchars($ps['jenis_pakaian']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small style="font-size:12px;color:var(--text-muted);">
                                        Pilih pesanan spesifik atau biarkan kosong
                                        untuk ulasan umum.
                                    </small>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label-elegant">
                                        Rating *
                                    </label>
                                    <div class="star-group">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio" name="rating"
                                                   id="star<?= $i ?>"
                                                   value="<?= $i ?>"
                                                <?= ($_POST['rating'] ?? '') == $i ? 'checked' : '' ?>>
                                            <label for="star<?= $i ?>">★</label>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label-elegant">
                                        Komentar *
                                    </label>
                                    <textarea name="komentar"
                                              class="form-control-elegant"
                                              rows="5" style="resize:none;"
                                              placeholder="Ceritakan pengalaman Anda memesan di Jashit..."
                                              required
                                    ><?= htmlspecialchars($_POST['komentar'] ?? '') ?></textarea>
                                </div>

                                <button type="submit" class="btn-elegant-dark"
                                        style="width:100%;">
                                    KIRIM ULASAN
                                </button>

                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="section-card">
                        <div class="section-card-header">
                            <h5>Ulasan Saya</h5>
                            <span style="font-size:13px;color:var(--text-muted);">
                                <?= count($feedback_list) ?> ulasan
                            </span>
                        </div>
                        <div class="section-card-body">

                            <?php if (empty($feedback_list)): ?>
                                <div style="text-align:center;padding:32px 0;
                                            color:var(--text-muted);">
                                    <i class="bi bi-star"
                                       style="font-size:40px;display:block;
                                              margin-bottom:12px;opacity:0.3;"></i>
                                    <p style="font-size:14px;">
                                        Belum ada ulasan. Bagikan pengalaman Anda!
                                    </p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($feedback_list as $fb): ?>
                                <div style="padding:16px 0;
                                            border-bottom:1px solid var(--border);">
                                    <div style="display:flex;justify-content:space-between;
                                                align-items:flex-start;
                                                margin-bottom:8px;flex-wrap:wrap;gap:8px;">
                                        <div>
                                            <div style="color:#f59e0b;font-size:16px;
                                                        letter-spacing:2px;margin-bottom:4px;">
                                                <?= str_repeat('★',$fb['rating'])
                                                  . str_repeat('☆',5-$fb['rating']) ?>
                                            </div>
                                            <?php if ($fb['kode_pesanan']): ?>
                                                <span style="font-size:11px;
                                                             color:var(--text-muted);">
                                                    <?= htmlspecialchars($fb['kode_pesanan']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="text-align:right;">
                                            <div style="font-size:11px;color:var(--text-muted);">
                                                <?= date('d M Y', strtotime($fb['created_at'])) ?>
                                            </div>
                                            <?php
                                            $fb_style = [
                                                'menunggu'      => 'status-menunggu',
                                                'ditampilkan'   => 'status-selesai',
                                                'disembunyikan' => 'status-dibatalkan',
                                            ];
                                            $fbc = $fb_style[$fb['status']] ?? 'status-menunggu';
                                            ?>
                                            <span class="status-badge <?= $fbc ?>"
                                                  style="font-size:10px;margin-top:4px;
                                                         display:inline-block;">
                                                <?= ucfirst($fb['status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <p style="font-size:13px;color:var(--text-main);
                                               line-height:1.7;margin:0;
                                               font-style:italic;">
                                        "<?= htmlspecialchars($fb['komentar']) ?>"
                                    </p>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>