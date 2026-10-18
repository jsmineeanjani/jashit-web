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

    ['area_layanan_jahit', 'area_bahan', 'area_aksesoris', 'area_input_bahan'].forEach(id => {
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
    document.getElementById('area_massal').classList.toggle('d-none', tipe !== 'massal');
    if (tipe === 'individu') ubahMetodeUkuran();
}

function ubahMetodeUkuran() {
    const m = document.getElementById('metode_ukuran').value;
    ['area_standar', 'area_custom', 'area_wa'].forEach(id =>
        document.getElementById(id).classList.add('d-none'));
    document.getElementById('area_' + m).classList.remove('d-none');
}

function tambahBarisMassal() {
    const c = document.getElementById('container_massal');
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
    const subTotal = hargaPerPcs * totalQty;

    // Panel estimasi
    document.getElementById('est_qty').textContent = totalQty + ' pcs';
    document.getElementById('est_harga_pcs').textContent = totalQty > 0 ? formatRp(hargaPerPcs) : '—';
    document.getElementById('est_total').textContent = totalQty > 0 ? formatRp(subTotal) : '—';
}

function resetEstimasi() {
    document.getElementById('est_qty').textContent = '0 pcs';
    document.getElementById('est_harga_pcs').textContent = '—';
    document.getElementById('est_total').textContent = '—';
}

function cariLayanan() {
    const q = document.getElementById('inputCari').value.toLowerCase();
    const cards = document.getElementsByClassName('kartu-layanan');
    let ada = false;
    for (const c of cards) {
        const nama = c.querySelector('.txt-nama').innerText.toLowerCase();
        const kat = c.querySelector('.txt-kategori').innerText.toLowerCase();
        const show = nama.includes(q) || kat.includes(q);
        c.style.display = show ? '' : 'none';
        if (show) ada = true;
    }
    document.getElementById('pesanKosong').style.display = ada ? 'none' : 'block';
}