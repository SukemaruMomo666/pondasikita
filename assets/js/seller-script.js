document.addEventListener('DOMContentLoaded', function() {
    
    // Elemen-elemen yang dibutuhkan (pastikan ID-nya benar)
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    // Fungsi untuk membuka/menutup sidebar
    function toggleSidebar() {
        if (sidebar && sidebarOverlay) {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        }
    }
    
    // --- Logika Inti yang Sudah Diperbaiki ---

    // 1. Event listener untuk tombol toggle
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(event) {
            // Hentikan event agar tidak memicu listener di 'document'
            event.stopPropagation(); 
            toggleSidebar();
        });
    }

    // 2. Event listener untuk seluruh dokumen (untuk menutup sidebar)
    document.addEventListener('click', function(event) {
        // Cek apakah sidebar, overlay, dan elemen yang diklik ada
        if (!sidebar || !sidebarOverlay) return;

        // Cek apakah sidebar sedang aktif/terbuka
        const isSidebarActive = sidebar.classList.contains('active');

        // Cek apakah yang diklik adalah bagian dari sidebar
        const isClickInsideSidebar = sidebar.contains(event.target);

        // Jika sidebar sedang terbuka DAN yang diklik BUKAN bagian dari sidebar
        if (isSidebarActive && !isClickInsideSidebar) {
            toggleSidebar();
        }
    });

});