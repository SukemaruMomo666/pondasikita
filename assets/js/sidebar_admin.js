document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. Handle Dropdown Menu ---
    const dropdowns = document.querySelectorAll('.nav-item.has-sub > .dropdown-toggle');

    dropdowns.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            const parent = this.parentElement; // li.nav-item
            const submenu = parent.querySelector('.sub-menu');
            
            // Tutup menu lain jika ingin accordion style (opsional)
            // document.querySelectorAll('.nav-item.has-sub.active').forEach(item => {
            //     if (item !== parent) {
            //         item.classList.remove('active');
            //         item.querySelector('.sub-menu').classList.remove('show');
            //     }
            // });

            // Toggle class active pada parent (untuk putar panah)
            parent.classList.toggle('active');
            
            // Toggle class show pada submenu (untuk slide down)
            if (submenu.classList.contains('show')) {
                submenu.classList.remove('show');
            } else {
                submenu.classList.add('show');
            }
        });
    });

    // --- 2. Handle Mobile Sidebar Toggle ---
    // Pastikan Anda punya tombol dengan class .sidebar-toggle-btn di Navbar Anda
    const sidebar = document.getElementById('adminSidebar');
    const toggleBtns = document.querySelectorAll('.sidebar-toggle-btn');
    
    // Buat overlay element
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    function toggleSidebar() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('show');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }

    toggleBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            toggleSidebar();
        });
    });

    // Klik overlay untuk tutup sidebar
    overlay.addEventListener('click', toggleSidebar);
});
document.addEventListener('DOMContentLoaded', function() {
    // Handle Dropdown Menu
    var toggles = document.querySelectorAll('[data-toggle="collapse"]');

    toggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault(); // Mencegah link melompat

            var targetId = this.getAttribute('href'); // Ambil ID target (misal #pesanan-menu)
            var targetMenu = document.querySelector(targetId);

            if (targetMenu) {
                // Toggle class 'show' pada menu target
                targetMenu.classList.toggle('show');
                
                // Update status aria-expanded untuk styling panah
                var isExpanded = targetMenu.classList.contains('show');
                this.setAttribute('aria-expanded', isExpanded);
            }
        });
    });

    // Handle Mobile Sidebar Toggle (Hamburger)
    var sidebarBtn = document.querySelector('.sidebar-toggle-btn');
    var sidebar = document.querySelector('.sidebar');
    
    if(sidebarBtn && sidebar) { 
        sidebarBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
}); 