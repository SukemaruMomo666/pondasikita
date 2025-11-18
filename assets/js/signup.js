document.addEventListener('DOMContentLoaded', function() {

    // Ambil elemen-elemen yang diperlukan dari DOM
    const termsPrivacyLink = document.getElementById('terms-privacy-link');
    const modalContainer = document.getElementById('modal-container');
    const modalTitle = document.getElementById('modal-title');
    const modalBodyEl = document.getElementById('modal-body');
    const modalAgreeBtn = document.getElementById('modal-agree-btn');
    const modalCloseBtn = document.querySelector('.modal-close');
    const mainCheckbox = document.getElementById('terms');
    const checkboxLabel = document.querySelector('.terms-label');

    // === KONTEN BARU YANG LEBIH PANJANG DAN PROFESIONAL ===
    const combinedContent = `
        <h3>Syarat & Ketentuan</h3>
        <p><strong>Penerimaan Ketentuan:</strong> Dengan mengakses dan menggunakan layanan Toko Bangunan Tiga Daya ("Layanan"), Anda setuju untuk terikat oleh Syarat dan Ketentuan ini. Jika Anda tidak setuju, Anda tidak diizinkan menggunakan layanan kami.</p>
        <p><strong>Akun Pengguna:</strong> Anda bertanggung jawab penuh untuk menjaga kerahasiaan informasi akun dan kata sandi Anda. Semua aktivitas yang terjadi di bawah akun Anda adalah tanggung jawab Anda. Segera beri tahu kami jika ada penggunaan akun yang tidak sah.</p>
        <p><strong>Informasi Produk dan Harga:</strong> Kami berusaha untuk menyajikan informasi produk, termasuk gambar dan harga, seakurat mungkin. Namun, kami tidak menjamin bahwa deskripsi produk atau konten lain dari layanan ini akurat, lengkap, atau bebas dari kesalahan. Harga dan ketersediaan produk dapat berubah sewaktu-waktu tanpa pemberitahuan sebelumnya.</p>
        <p><strong>Pembatasan Tanggung Jawab:</strong> Toko Bangunan Tiga Daya tidak akan bertanggung jawab atas kerusakan langsung, tidak langsung, insidental, atau konsekuensial yang timbul dari penggunaan atau ketidakmampuan untuk menggunakan layanan kami.</p>
        <p><strong>Perubahan Ketentuan:</strong> Kami berhak untuk mengubah atau merevisi Syarat & Ketentuan ini kapan saja. Versi terbaru akan selalu diposting di situs kami.</p>

        <br><hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;"><br> 

        <h3>Kebijakan Privasi</h3>
        <p><strong>Informasi yang Kami Kumpulkan:</strong> Kami mengumpulkan informasi yang Anda berikan secara langsung, seperti nama, alamat email, nomor telepon, dan alamat pengiriman saat Anda mendaftar atau melakukan transaksi. Kami juga dapat mengumpulkan data teknis secara otomatis, termasuk alamat IP, jenis browser, dan data cookie.</p>
        <p><strong>Penggunaan Informasi Anda:</strong> Informasi Anda digunakan untuk:</p>
        <ul>
            <li>Memproses pesanan dan transaksi Anda.</li>
            <li>Mengirimkan pembaruan status pesanan dan informasi pengiriman.</li>
            <li>Berkomunikasi dengan Anda mengenai produk, layanan, dan promosi.</li>
            <li>Meningkatkan dan mempersonalisasi pengalaman Anda di situs kami.</li>
            <li>Mencegah aktivitas penipuan dan menjaga keamanan platform.</li>
        </ul>
        <p><strong>Pembagian Informasi:</strong> Kami tidak menjual atau menyewakan data pribadi Anda kepada pihak ketiga. Kami hanya membagikan informasi Anda dengan mitra tepercaya yang diperlukan untuk menjalankan layanan kami, seperti perusahaan logistik untuk pengiriman dan penyedia gerbang pembayaran untuk memproses pembayaran.</p>
        <p><strong>Keamanan Data:</strong> Kami menerapkan langkah-langkah keamanan teknis dan organisasi yang wajar untuk melindungi data pribadi Anda dari akses, pengungkapan, perubahan, atau perusakan yang tidak sah.</p>
        <br>
        <p style="text-align: center; color: #555; font-style: italic; font-weight: bold;">Silakan gulir hingga akhir untuk dapat menyetujui ketentuan ini.</p>
    `;

    function openModal() {
        modalTitle.innerText = 'Syarat & Ketentuan dan Kebijakan Privasi';
        modalBodyEl.innerHTML = combinedContent;
        modalContainer.classList.add('active');
        document.body.classList.add('modal-open');
        modalAgreeBtn.disabled = true;
        modalBodyEl.scrollTop = 0;
    }

    function closeModal() {
        modalContainer.classList.remove('active');
        document.body.classList.remove('modal-open');
    }

    // === PERBAIKAN LOGIKA EVENT LISTENER ===
    // Fungsi ini akan menangani klik pada checkbox dan juga pada labelnya
    function handleCheckboxClick(e) {
        // Selalu cegah aksi default browser (mencentang/menghilangkan centang)
        e.preventDefault();

        if (mainCheckbox.checked) {
            // Jika checkbox SUDAH tercentang, user ingin menghilangkannya. Izinkan.
            mainCheckbox.checked = false;
        } else {
            // Jika checkbox BELUM tercentang, user ingin menyetujui. Tampilkan modal.
            openModal();
        }
    }

    // Pasang listener pada checkbox dan link
    mainCheckbox.addEventListener('click', handleCheckboxClick);
    termsPrivacyLink.addEventListener('click', (e) => {
        e.preventDefault();
        openModal();
    });

    modalAgreeBtn.addEventListener('click', () => {
        mainCheckbox.checked = true;
        closeModal();
    });

    modalCloseBtn.addEventListener('click', closeModal);

    modalContainer.addEventListener('click', (e) => {
        if (e.target === modalContainer) {
            closeModal();
        }
    });

    modalBodyEl.addEventListener('scroll', () => {
        // Toleransi 10 piksel untuk memastikan event ter-trigger di semua browser
        const isScrolledToBottom = modalBodyEl.scrollHeight - modalBodyEl.scrollTop <= modalBodyEl.clientHeight + 10;
        if (isScrolledToBottom) {
            modalAgreeBtn.disabled = false;
        }
    });
});