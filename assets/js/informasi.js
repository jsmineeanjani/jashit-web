$(document).ready(function() {
    // Inisialisasi DataTables
    $('#tabelDiskon').DataTable({
        "dom": "<'row mb-3'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        "language": { 
            "search": "", 
            "searchPlaceholder": "Cari diskon..." 
        },
        "ordering": false 
    });

    // Konfirmasi Hapus Data dengan SweetAlert2
    $(document).on('click', '.btn-hapus', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        
        Swal.fire({
            title: 'Hapus Diskon?',
            text: "Data diskon yang dihapus tidak bisa dikembalikan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });
});