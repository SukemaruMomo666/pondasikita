<div class="tab-pane fade show active" id="pengiriman-content">
    <p class="text-secondary mb-4">Aktifkan jasa kirim yang ingin Anda gunakan. Jasa kirim yang aktif akan muncul sebagai pilihan bagi pembeli saat checkout.</p>
    
    <form action="../actions/proses_pengaturan_pengiriman.php" method="POST">
        <div class="shipping-category">
            <div class="category-title">
                <span>Reguler (Cashless)</span>
            </div>
            <div class="category-content">
                <div class="courier-item">
                    <div class="courier-info">
                        <span class="courier-name">J&T Express</span>
                    </div>
                    <div class="courier-actions">
                        <label class="toggle-switch"><input type="checkbox" name="kurir[JNT_REG]" checked><span class="toggle-slider"></span></label>
                    </div>
                </div>
                <div class="courier-item">
                    <div class="courier-info">
                        <span class="courier-name">SiCepat REG</span>
                    </div>
                    <div class="courier-actions">
                        <label class="toggle-switch"><input type="checkbox" name="kurir[SICEPAT_REG]" checked><span class="toggle-slider"></span></label>
                    </div>
                </div>
                 </div>
        </div>

        <div class="shipping-category">
            <div class="category-title">
                <span>Hemat</span>
            </div>
            <div class="category-content">
                <div class="courier-item">
                    <div class="courier-info">
                        <span class="courier-name">J&T Economy</span>
                    </div>
                    <div class="courier-actions">
                        <label class="toggle-switch"><input type="checkbox" name="kurir[JNT_ECO]"><span class="toggle-slider"></span></label>
                    </div>
                </div>
                 </div>
        </div>

        <div class="shipping-category">
            <div class="category-title">
                <span>Kargo</span>
            </div>
            <div class="category-content">
                 <div class="courier-item">
                    <div class="courier-info">
                        <span class="courier-name">JNE Trucking (JTR)</span>
                    </div>
                    <div class="courier-actions">
                        <label class="toggle-switch"><input type="checkbox" name="kurir[JNE_TRUCK]"><span class="toggle-slider"></span></label>
                    </div>
                </div>
                 </div>
        </div>

        <div class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">Simpan Pengaturan Pengiriman</button>
        </div>
    </form>
</div>