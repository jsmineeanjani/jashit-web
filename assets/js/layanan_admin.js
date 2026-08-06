$(document).ready(function() {
    // Inisialisasi DataTables
    $('#tabelLayanan').DataTable({
        "language": { 
            "search": "", 
            "searchPlaceholder": "Cari layanan..." 
        },
        "ordering": false 
    });

    // Konfirmasi Hapus Data dengan SweetAlert2
    $(document).on('click', '.btn-hapus', function(e) {
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