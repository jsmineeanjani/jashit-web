$(document).ready(function () {
    // Inisialisasi DataTables (hanya berjalan jika tabel ada di halaman, mis. pengguna.php)
    if ($('#tabelPengguna').length) {
        $('#tabelPengguna').DataTable({
            "dom": "<'row mb-3'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            "language": {
                "search": "",
                "searchPlaceholder": "Search...",
                "lengthMenu": "_MENU_ entries per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "zeroRecords": "Tidak ada data yang ditemukan",
                "infoEmpty": "Menampilkan 0 data",
                "infoFiltered": "(difilter dari _MAX_ total data)",
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            },
            "ordering": false
        });
    }

    // Konfirmasi hapus user dengan SweetAlert2
    $(document).on('click', '.btn-hapus', function (e) {
        e.preventDefault(); // Mencegah link langsung berpindah
        const href = $(this).attr('href'); // Ambil link hapus dari tombol

        Swal.fire({
            title: 'Hapus User?',
            text: "Yakin ingin menghapus user ini secara permanen? Data yang dihapus tidak dapat dikembalikan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444', // Warna merah untuk aksi hapus
            cancelButtonColor: '#64748b',  // Warna abu-abu untuk batal
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true // Menukar posisi tombol (Batal di kiri, Hapus di kanan)
        }).then((result) => {
            if (result.isConfirmed) {
                // Jika diklik 'Ya', arahkan ke link hapus
                window.location.href = href;
            }
        });
    });
});