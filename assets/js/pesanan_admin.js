// =========================================================================
// Elemen yang namanya sama dipakai di lebih dari satu halaman pesanan
// (total_harga, dp_dibayar, sisa_tagihan, jenis_pakaian ada di tambah & edit)
// =========================================================================
const totalInput = document.getElementById('total_harga');
const dpInput = document.getElementById('dp_dibayar');
const sisaInput = document.getElementById('sisa_tagihan');
const jenisSelect = document.getElementById('jenis_pakaian');

// =========================================================================
// KHUSUS pesanan_tambah.php
// Dideteksi lewat elemen #jumlah_individu yang cuma ada di halaman ini.
// =========================================================================
const inputJumlahIndividu = document.getElementById('jumlah_individu');
const metodeBayarSelect = document.getElementById('metode_pembayaran');

if (inputJumlahIndividu) {

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

    function hitungSisa() {
        const total = parseInt(totalInput.value) || 0;
        const dp = parseInt(dpInput.value) || 0;
        const sisa = total - dp;
        sisaInput.value = sisa < 0 ? 0 : sisa;
    }

    // Wajibkan pilih metode pembayaran kalau ada nominal DP diisi
    function toggleMetodeBayar() {
        const dp = parseInt(dpInput.value) || 0;
        if (dp > 0) {
            metodeBayarSelect.setAttribute('required', 'required');
        } else {
            metodeBayarSelect.removeAttribute('required');
        }
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

    // HTML pesanan_tambah.php memanggil fungsi-fungsi ini lewat atribut inline
    // (onclick="...", onchange="...", oninput="..."), jadi wajib didaftarkan
    // ke window supaya bisa diakses dari luar file JS ini.
    window.hitungTotalHargaOtomatis = hitungTotalHargaOtomatis;
    window.hitungSisa = hitungSisa;
    window.toggleMetodeBayar = toggleMetodeBayar;
    window.toggleSkalaPesanan = toggleSkalaPesanan;
    window.ubahMetodeUkuranIndividu = ubahMetodeUkuranIndividu;
    window.tambahBarisMassal = tambahBarisMassal;
    window.hapusBaris = hapusBaris;
    window.kalkulasiQtyMassal = kalkulasiQtyMassal;
}

// =========================================================================
// KHUSUS pesanan_edit.php
// Dideteksi lewat elemen #area_jenis_manual yang cuma ada di halaman ini.
// =========================================================================
const areaManual = document.getElementById('area_jenis_manual');

if (areaManual) {

    function hitungSisaEdit() {
        const total = parseInt(totalInput.value) || 0;
        let dp = parseInt(dpInput.value) || 0;
        if (dp > total) { dp = total; dpInput.value = dp; }
        const sisa = total - dp;
        sisaInput.value = sisa;
    }

    function cekCustomJenis() {
        if (jenisSelect.value === 'custom_manual_input') {
            areaManual.classList.remove('d-none');
            areaManual.querySelector('input').setAttribute('required', 'required');
        } else {
            areaManual.classList.add('d-none');
            areaManual.querySelector('input').removeAttribute('required');
        }
    }

    // Dipanggil lewat onchange="cekCustomJenis()" di HTML
    window.cekCustomJenis = cekCustomJenis;

    if (totalInput) totalInput.addEventListener('input', hitungSisaEdit);
    if (dpInput) dpInput.addEventListener('input', hitungSisaEdit);
}

// =========================================================================
// KHUSUS pesanan.php (tabel kelola pesanan)
// Butuh jQuery - dibungkus pengecekan supaya aman dipakai bersama file ini
// di halaman yang tidak load jQuery (tambah & edit tidak load jQuery).
// =========================================================================
if (typeof jQuery !== 'undefined') {
    $(document).ready(function () {
        if ($('#tabelPesanan').length) {
            $('#tabelPesanan').DataTable({
                "dom": "<'row mb-3'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
                       "<'row'<'col-sm-12'tr>>" +
                       "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                "language": { "search": "", "searchPlaceholder": "Cari pesanan..." },
                "ordering": false
            });
        }

        $(document).on('click', '.btn-hapus', function (e) {
            e.preventDefault();
            const href = $(this).attr('href');
            Swal.fire({
                title: 'Hapus Pesanan?',
                text: "Yakin ingin menghapus data pesanan ini? Aksi ini tidak dapat dibatalkan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) window.location.href = href;
            });
        });
    });
}