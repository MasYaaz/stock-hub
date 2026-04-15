<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="row g-4 mb-4 align-items-center">
    <div class="col-md-7">
        <h2 class="fw-bold mb-1 text-white">Market Dashboard</h2>
        <p class="text-secondary mb-0">Agregasi data 700+ emiten IHSG secara real-time.</p>
    </div>
    <div class="col-md-5">
        <div class="input-group shadow-sm">
            <span class="input-group-text form-control-dark border-end-0 text-secondary">
                <i class="fa-solid fa-magnifying-glass"></i>
            </span>
            <input type="text" id="stockSearch" class="form-control form-control-dark border-start-0 ps-0"
                placeholder="Cari kode atau nama emiten...">
        </div>
    </div>
</div>

<div class="card shadow-lg border-0 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 75vh;">
            <table class="table table-dark table-hover align-middle mb-0" id="stockTable">
                <thead class="sticky-top bg-dark" style="z-index: 10;">
                    <tr class="text-secondary border-bottom border-secondary"
                        style="font-size: 0.8rem; letter-spacing: 1px;">
                        <th class="ps-4 py-3 uppercase">EMITEN</th>
                        <th class="py-3 uppercase">NAMA PERUSAHAAN</th>
                        <th class="py-3 text-end uppercase">HARGA (IDR)</th>
                        <th class="py-3 text-end uppercase">PERUBAHAN</th>
                        <th class="py-3 text-center uppercase">H / L (TODAY)</th>
                        <th class="py-3 text-center pe-4 uppercase">UPDATE</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if (!empty($stocks)): ?>
                        <?php foreach ($stocks as $s): ?>
                            <?php
                            // Kalkulasi Perubahan Harga
                            $change = $s['last_price'] - $s['previous_close'];
                            $percent = ($s['previous_close'] > 0) ? ($change / $s['previous_close']) * 100 : 0;
                            $trendClass = ($change >= 0) ? 'text-success' : 'text-danger';
                            $trendIcon = ($change >= 0) ? 'fa-caret-up' : 'fa-caret-down';
                            ?>
                            <tr id="row-<?= $s['code'] ?>" class="stock-row border-bottom border-secondary border-opacity-10">
                                <td class="ps-4">
                                    <div class="fw-bold text-info stock-code"><?= $s['code'] ?></div>
                                    <div class="text-secondary" style="font-size: 0.7rem;"><?= $s['sector'] ?></div>
                                </td>
                                <td>
                                    <div class="text-truncate stock-name text-light" style="max-width: 280px;"><?= $s['name'] ?>
                                    </div>
                                </td>
                                <td class="text-end fw-bold text-white">
                                    <?= number_format($s['last_price'], 0, ',', '.') ?>
                                </td>
                                <td class="text-end <?= $trendClass ?>">
                                    <div class="fw-bold" style="font-size: 0.9rem;">
                                        <i class="fa-solid <?= $trendIcon ?> me-1"></i><?= number_format(abs($percent), 2) ?>%
                                    </div>
                                    <div style="font-size: 0.7rem; opacity: 0.7;">
                                        <?= ($change >= 0 ? '+' : '-') . number_format(abs($change), 0, ',', '.') ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column align-items-center" style="font-size: 0.75rem;">
                                        <span class="text-success opacity-75">H:
                                            <?= number_format($s['day_high'], 0, ',', '.') ?></span>
                                        <span class="text-danger opacity-75">L:
                                            <?= number_format($s['day_low'], 0, ',', '.') ?></span>
                                    </div>
                                </td>
                                <td class="text-center pe-4">
                                    <?php if ($s['updated_at'] == '2000-01-01 00:00:00'): ?>
                                        <span class="badge rounded-pill bg-secondary bg-opacity-25 text-warning px-3 py-1"
                                            style="font-size: 0.65rem;">QUEUEING</span>
                                    <?php else: ?>
                                        <div class="text-secondary fw-medium" style="font-size: 0.75rem;">
                                            <?= date('H:i:s', strtotime($s['updated_at'])) ?>
                                        </div>
                                        <div class="opacity-25" style="font-size: 0.6rem;">
                                            <?= date('d M', strtotime($s['updated_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fa-solid fa-database fa-3x mb-3 text-secondary opacity-25"></i>
                                <p class="text-secondary">Data belum tersedia di MariaDB.</p>
                                <a href="<?= base_url('stock/update') ?>"
                                    class="btn btn-info btn-sm rounded-pill px-4">Mulai Sinkronisasi</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function fetchStocksRealtime() {
        fetch('<?= base_url('stock/get_live_data') ?>')
            .then(response => response.json())
            .then(data => {
                data.forEach(stock => {
                    // Cari baris berdasarkan ID
                    const row = document.getElementById(`row-${stock.code}`);
                    if (row) {
                        // 1. Update Harga Terakhir
                        const priceEl = row.querySelector('.last-price');
                        const newPrice = new Intl.NumberFormat('id-ID').format(stock.last_price);

                        // Efek visual jika harga berubah
                        if (priceEl.innerText !== newPrice) {
                            priceEl.innerText = newPrice;
                            priceEl.style.color = '#38bdf8'; // Highlight biru saat berubah
                            setTimeout(() => priceEl.style.color = 'white', 1000);
                        }

                        // 2. Update High/Low dan Timestamp jika perlu
                        // Kamu bisa menambahkan logika update kolom lainnya di sini
                    }
                });
                console.log('Data MariaDB Synced');
            })
            .catch(error => console.error('Error fetching live data:', error));
    }

    // Jalankan setiap 5 detik (5000ms)
    setInterval(fetchStocksRealtime, 5000);

    // 1. Fitur Live Search (Client-Side)
    document.getElementById('stockSearch').addEventListener('input', function () {
        let filter = this.value.toUpperCase();
        let rows = document.querySelectorAll('.stock-row');

        rows.forEach(row => {
            let code = row.querySelector('.stock-code').textContent;
            let name = row.querySelector('.stock-name').textContent;

            if (code.toUpperCase().includes(filter) || name.toUpperCase().includes(filter)) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    });

    // 2. Background Auto-Sync
    // Memicu batch update 15 emiten di latar belakang setiap kali dashboard dibuka
    window.addEventListener('load', () => {
        console.log("Dashboard loaded. Background sync initiated...");
        setTimeout(() => {
            fetch('<?= base_url('stock/update') ?>')
                .then(response => console.log("Sync Batch Completed."))
                .catch(err => console.error("Sync Error:", err));
        }, 3000); // Jeda 3 detik agar tidak mengganggu rendering awal
    });
</script>
<?= $this->endSection() ?>