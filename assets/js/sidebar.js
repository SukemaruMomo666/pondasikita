document.addEventListener('DOMContentLoaded', function() {
    // 1. Handler untuk Dropdown Menu
    const dropdownToggles = document.querySelectorAll('.nav-link[data-toggle="collapse"]');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault(); // Mencegah link navigasi standar

            // Ambil target ID (misal #pesanan-menu)
            const targetId = this.getAttribute('href'); 
            const targetMenu = document.querySelector(targetId);

            if (targetMenu) {
                // Toggle class 'show' pada submenu
                targetMenu.classList.toggle('show');

                // Update atribut aria-expanded untuk styling panah CSS
                const isExpanded = targetMenu.classList.contains('show');
                this.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            }
        });
    });

    // 2. Handler untuk Mobile Sidebar Toggle (Hamburger)
    const sidebar = document.getElementById('sidebar');
    const toggleBtns = document.querySelectorAll('.sidebar-toggle-btn'); // Pastikan tombol toggle punya class ini
    
    // Buat overlay jika belum ada
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    function toggleSidebar() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }

    toggleBtns.forEach(btn => btn.addEventListener('click', toggleSidebar));
    overlay.addEventListener('click', toggleSidebar);
});