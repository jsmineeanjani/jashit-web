/* =========================================
   Bagian 1: Penyesuaian Form Dinamis
   (dipakai di layanan_tambah.php & layanan_edit.php)
   Vanilla JS - tidak butuh jQuery
   ========================================= */
function sesuaikanForm() {
    const kategoriSelect = document.getElementById('kategori');
    if (!kategoriSelect) return;

    const kategori = kategoriSelect.value;
    const labelNama = document.getElementById('label_nama');
    const inputNama = document.getElementById('input_nama');
    const labelHarga = document.getElementById('label_harga');
    const kolomHarga = document.getElementById('kolom_harga');
    const kolomEstimasi = document.getElementById('kolom_estimasi');
    const inputEstimasi = document.getElementById('estimasi_hari');

    if (kategori === 'Bahan/Material') {
        labelNama.innerHTML = 'Nama Bahan <span class="text-danger">*</span>';
        if (inputNama.value === '') inputNama.placeholder = 'Contoh: Kain Katun';

        labelHarga.innerHTML = 'Harga Per Meter (Rp)';
        kolomEstimasi.classList.add('d-none');
        kolomHarga.classList.remove('col-md-6');
        kolomHarga.classList.add('col-md-12');

        if (inputEstimasi && (inputEstimasi.value === '' || inputEstimasi.value > 0)) {
            inputEstimasi.value = '0';
        }

    } else if (kategori === 'Aksesoris') {
        labelNama.innerHTML = 'Nama Aksesoris <span class="text-danger">*</span>';
        if (inputNama.value === '') inputNama.placeholder = 'Contoh: Tote Bag, Topi Custom';

        labelHarga.innerHTML = 'Harga Satuan (Rp)';
        kolomEstimasi.classList.add('d-none');
        kolomHarga.classList.remove('col-md-6');
        kolomHarga.classList.add('col-md-12');

        if (inputEstimasi && (inputEstimasi.value === '' || inputEstimasi.value > 0)) {
            inputEstimasi.value = '0';
        }

    } else {
        labelNama.innerHTML = 'Nama Layanan <span class="text-danger">*</span>';
        if (inputNama.value === '') inputNama.placeholder = 'Contoh: Jahit Kemeja Batik Pria';

        labelHarga.innerHTML = 'Harga Mulai (Rp)';
        kolomEstimasi.classList.remove('d-none');
        kolomHarga.classList.remove('col-md-12');
        kolomHarga.classList.add('col-md-6');

        if (inputEstimasi && inputEstimasi.value === '0') {
            inputEstimasi.value = '';
        }
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const kategoriSelect = document.getElementById('kategori');
    if (kategoriSelect) {
        sesuaikanForm(); // Jalankan saat pertama kali diload
        kategoriSelect.addEventListener('change', sesuaikanForm); // Jalankan tiap kali dropdown diubah
    }
});

/* =========================================
   Bagian 2: Tabel Layanan (DataTables + SweetAlert2)
   (dipakai di layanan.php)
   Butuh jQuery - dibungkus pengecekan supaya aman
   dipakai bersama di halaman yang tidak load jQuery
   ========================================= */
if (typeof jQuery !== 'undefined') {
    $(document).ready(function () {
        // Inisialisasi DataTables (hanya jika tabelnya ada di halaman)
        if ($('#tabelLayanan').length) {
            $('#tabelLayanan').DataTable({
                "language": {
                    "search": "",
                    "searchPlaceholder": "Cari layanan..."
                },
                "ordering": false
            });
        }

        // Konfirmasi Hapus Data dengan SweetAlert2
        $(document).on('click', '.btn-hapus', function (e) {
            e.preventDefault();
            const href = $(this).attr('href');

            Swal.fire({
                title: 'Hapus Layanan?',
                text: "Layanan ini beserta gambarnya akan dihapus permanen.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });
    });
}