$(document).ready(function () {
    $('#tabelTransaksi').DataTable({
        "dom": "<'row mb-3'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        "language": { "search": "", "searchPlaceholder": "Cari kode / nama..." },
        "ordering": false 
    });

    // Konfirmasi Terima
    $(document).on('click', '.btn-terima', function (e) {
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
    $(document).on('click', '.btn-tolak', function (e) {
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