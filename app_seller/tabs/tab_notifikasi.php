<div class="tab-pane fade show active" id="notifikasi-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="card-title mb-0">Notifikasi Email</h4>
        <button class="btn btn-outline-secondary">Nonaktifkan Semua</button>
    </div>

    <form action="../actions/proses_pengaturan_notifikasi.php" method="POST">
        <div class="shipping-category">
             <div class="category-title">Informasi Pesanan & Produk</div>
             <div class="category-content">
                <div class="courier-item">
                    <div class="courier-info">
                        <span class="courier-name">Pesanan</span>
                        <p class="courier-desc">Informasi terbaru dari status pesanan.</p>
                    </div>
                    <div class="courier-actions">
                         <label class="toggle-switch"><input type="checkbox" name="notif[pesanan]" checked><span class="toggle-slider"></span></label>
                    </div>
                </div>
                <div class="courier-item">
                    <div class="courier-info">
                        <span class="courier-name">Produk</span>
                        <p class="courier-desc">Informasi tentang status terkini dari produkmu.</p>
                    </div>
                    <div class="courier-actions">
                         <label class="toggle-switch"><input type="checkbox" name="notif[produk]" checked><span class="toggle-slider"></span></label>
                    </div>
                </div>
             </div>
        </div>

        <div class="shipping-category">
             <div class="category-title">Informasi Media Sosial & Promosi</div>
             <div class="category-content">
                <div class="courier-item">
                    <div class="courier-info">
                        <span class="courier-name">Promosi</span>
                        <p class="courier-desc">Informasi eksklusif tentang promo dan penawaran yang akan datang.</p>
                    </div>
                    <div class="courier-actions">
                         <label class="toggle-switch"><input type="checkbox" name="notif[promosi]" checked><span class="toggle-slider"></span></label>
                    </div>
                </div>
             </div>
        </div>

        <div class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">Simpan Pengaturan Notifikasi</button>
        </div>
    </form>
</div>