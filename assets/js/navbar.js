// File: /assets/js/navbar.js

document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.js-hamburger');
    const navLinks = document.querySelector('.js-nav-links');
    const allDropdowns = document.querySelectorAll('.js-dropdown');

    // 1. Fungsi untuk toggle hamburger menu
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', (event) => {
            // Menghentikan event agar tidak langsung ditutup oleh 'document.click'
            event.stopPropagation();
            navLinks.classList.toggle('active');
        });
    }

    // 2. Fungsi untuk setiap dropdown (termasuk profile)
    allDropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('.profile-btn');
        const content = dropdown.querySelector('.js-dropdown-content');

        if (trigger && content) {
            trigger.addEventListener('click', (event) => {
                event.stopPropagation();
                // Tutup dropdown lain sebelum membuka yang ini
                closeAllDropdowns(dropdown);
                content.classList.toggle('active');
            });
        }
    });

    // Fungsi untuk menutup semua dropdown yang aktif
    function closeAllDropdowns(exceptThisOne = null) {
        allDropdowns.forEach(dropdown => {
            if (dropdown !== exceptThisOne) {
                const content = dropdown.querySelector('.js-dropdown-content');
                if (content) {
                    content.classList.remove('active');
                }
            }
        });
    }

    // Menutup menu jika pengguna mengklik di luar area menu
    document.addEventListener('click', () => {
        closeAllDropdowns();
        if (navLinks) {
            navLinks.classList.remove('active');
        }
    });
});
