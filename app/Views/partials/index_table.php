<style>
    /* Card & Table Styling */
    .market-card {
        background: rgba(30, 41, 59, 0.4);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 28px;
    }

    .table-dark {
        --bs-table-bg: transparent;
        --bs-table-hover-bg: rgba(56, 189, 248, 0.04);
    }

    .stock-row {
        transition: all 0.2s ease;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
    }

    .stock-row:hover {
        background: rgba(56, 189, 248, 0.04) !important;
    }

    /* Area Kolom Emiten (Revised) */
    .logo-container {
        flex-shrink: 0;
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .company-logo-sm {
        width: 100%;
        height: 100%;
        border-radius: 12px;
        object-fit: contain;
        background: white;
        padding: 4px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .logo-placeholder {
        width: 100%;
        height: 100%;
        border-radius: 12px;
        background: #1e293b;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .emiten-info-wrapper {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }

    .emiten-top-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .badge-code {
        background: rgba(56, 189, 248, 0.12);
        color: #38bdf8;
        border: 1px solid rgba(56, 189, 248, 0.2);
        padding: 2px 8px;
        border-radius: 6px;
        font-weight: 800;
        font-size: 0.75rem;
        font-family: 'JetBrains Mono', monospace;
        /* Lebih pro */
    }

    .badge-notation {
        font-size: 0.6rem;
        text-transform: uppercase;
        padding: 2px 6px;
        border-radius: 6px;
        font-weight: 700;
        background: rgba(255, 255, 255, 0.05);
        color: #94a3b8;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .notation-utama {
        color: #10b981;
        border-color: rgba(16, 185, 129, 0.3);
        background: rgba(16, 185, 129, 0.1);
    }

    .notation-pengembangan {
        color: #f59e0b;
        border-color: rgba(245, 158, 11, 0.3);
        background: rgba(245, 158, 11, 0.1);
    }

    .notation-akselerasi {
        color: #38bdf8;
        border-color: rgba(56, 189, 248, 0.3);
        background: rgba(56, 189, 248, 0.1);
    }

    .sector-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        font-size: 0.65rem;
        font-weight: 500;
    }

    /* Search Bar */
    .search-wrapper {
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 18px;
        padding: 6px 18px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .search-wrapper:focus-within {
        border-color: #38bdf8;
        box-shadow: 0 0 20px rgba(56, 189, 248, 0.15);
    }

    .search-input {
        background: transparent;
        border: none;
        color: #f8fafc;
        padding: 10px;
        width: 100%;
        outline: none;
        font-weight: 500;
    }
</style>

<div class="row g-4 mb-4 align-items-center">
    <div class="col-md-7 text-center text-md-start">
        <h3
            class="fw-bold mb-1 text-white d-flex align-items-center justify-content-center justify-content-md-start gap-2">
            <i data-lucide="layout-list" class="text-info" size="24"></i>
            Market Explorer
        </h3>
        <p class="text-slate-500 mb-0 small">Menampilkan <span class="text-info fw-bold"><?= count($stocks) ?></span>
            emiten pilihan terbaik</p>
    </div>
    <div class="col-md-5">
        <div class="search-wrapper d-flex align-items-center">
            <i data-lucide="search" size="18" class="text-slate-500"></i>
            <input type="text" id="stockSearch" class="search-input"
                placeholder="Cari kode (AMRT) atau nama perusahaan...">
        </div>
    </div>
</div>

<div class="market-card shadow-2xl overflow-hidden">
    <div class="table-responsive" style="max-height: 75vh;">
        <table class="table table-dark table-hover align-middle mb-0" id="stockTable">
            <thead class="sticky-top" style="z-index: 10; background: #111827;">
                <tr class="text-slate-500"
                    style="font-size: 0.7rem; letter-spacing: 1.5px; border-bottom: 2px solid rgba(255,255,255,0.05);">
                    <th class="ps-4 py-4">EMITEN</th>
                    <th class="py-4">PERUSAHAAN</th>
                    <th class="py-4 text-end">HARGA</th>
                    <th class="py-4 text-end">PERUBAHAN (DAILY)</th>
                    <th class="py-4 text-center">RANGE (H/L)</th>
                    <th class="py-4 text-center pe-4">OPSI</th>
                </tr>
            </thead>
            <tbody id="stockTableBody">
                <?php foreach ($stocks as $s): ?>
                    <?php
                    $change = $s['last_price'] - $s['previous_close'];
                    $percent = ($s['previous_close'] > 0) ? ($change / $s['previous_close']) * 100 : 0;

                    // Logic Ikon Sektor
                    $sectorIcon = 'component';
                    $sectorLower = strtolower($s['sector']);
                    if (strpos($sectorLower, 'health') !== false)
                        $sectorIcon = 'heart-pulse';
                    elseif (strpos($sectorLower, 'finance') !== false)
                        $sectorIcon = 'landmark';
                    elseif (strpos($sectorLower, 'energy') !== false)
                        $sectorIcon = 'zap';
                    elseif (strpos($sectorLower, 'tech') !== false)
                        $sectorIcon = 'cpu';
                    elseif (strpos($sectorLower, 'consumer') !== false)
                        $sectorIcon = 'shopping-bag';
                    elseif (strpos($sectorLower, 'basic') !== false)
                        $sectorIcon = 'shovels';
                    elseif (strpos($sectorLower, 'transport') !== false)
                        $sectorIcon = 'truck';
                    elseif (strpos($sectorLower, 'infrastruct') !== false)
                        $sectorIcon = 'hard-hat';
                    elseif (strpos($sectorLower, 'prop') !== false)
                        $sectorIcon = 'home';

                    // Logic Notasi
                    $notationClass = '';
                    $notasi = strtolower($s['notation'] ?? '');
                    if ($notasi == 'utama')
                        $notationClass = 'notation-utama';
                    elseif ($notasi == 'pengembangan')
                        $notationClass = 'notation-pengembangan';
                    elseif ($notasi == 'akselerasi')
                        $notationClass = 'notation-akselerasi';
                    ?>
                    <tr id="row-<?= $s['code'] ?>" class="stock-row">
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="logo-container">
                                    <?php if (!empty($s['image'])): ?>
                                        <img src="<?= $s['image'] ?>" class="company-logo-sm" alt="logo" loading="lazy">
                                    <?php else: ?>
                                        <div class="logo-placeholder">
                                            <i data-lucide="building-2" size="18"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="emiten-info-wrapper">
                                    <div class="emiten-top-row">
                                        <span class="badge-code"><?= $s['code'] ?></span>
                                        <?php if (!empty($s['notation'])): ?>
                                            <span class="badge-notation <?= $notationClass ?>"><?= $s['notation'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="sector-pill mt-1">
                                        <i data-lucide="<?= $sectorIcon ?>" size="12" style="stroke-width: 2.5px;"></i>
                                        <span><?= $s['sector'] ?></span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td>
                            <div class="text-truncate text-light fw-medium stock-name-text"
                                style="max-width: 220px; font-size: 0.9rem;">
                                <?= $s['name'] ?>
                            </div>
                        </td>
                        <td class="text-end fw-bold text-white last-price"
                            style="font-size: 1rem; font-family: 'JetBrains Mono', monospace;">
                            <?= number_format($s['last_price'], 0, ',', '.') ?>
                        </td>
                        <td class="text-end">
                            <div
                                class="fw-bold small d-flex align-items-center justify-content-end gap-1 <?= $change >= 0 ? 'text-success' : 'text-danger' ?>">
                                <i data-lucide="<?= $change >= 0 ? 'trending-up' : 'trending-down' ?>" size="14"></i>
                                <?= number_format(abs($percent), 2) ?>%
                            </div>
                        </td>
                        <td class="text-center">
                            <div style="font-size: 0.7rem; line-height: 1.4;">
                                <div class="text-success opacity-75">H: <?= number_format($s['day_high'], 0, ',', '.') ?>
                                </div>
                                <div class="text-danger opacity-75">L: <?= number_format($s['day_low'], 0, ',', '.') ?>
                                </div>
                            </div>
                        </td>
                        <td class="text-center pe-4">
                            <a href="<?= base_url('stock/detail/' . $s['code']) ?>"
                                class="btn btn-sm btn-outline-info rounded-pill px-3 fw-bold" style="font-size: 0.75rem;">
                                Detail
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    document.getElementById('stockSearch').addEventListener('input', function () {
        const filter = this.value.toUpperCase().trim();
        const rows = document.querySelectorAll('#stockTableBody .stock-row');
        rows.forEach(row => {
            const codeText = row.querySelector('.badge-code').textContent.toUpperCase();
            const nameText = row.querySelector('.stock-name-text').textContent.toUpperCase();
            row.style.display = (codeText.includes(filter) || nameText.includes(filter)) ? "" : "none";
        });
    });

    window.updateTableUI = function (data) {
        data.forEach(stock => {
            const row = document.getElementById(`row-${stock.code}`);
            if (row) {
                const priceEl = row.querySelector('.last-price');
                const changeWrapper = row.querySelector('.text-success, .text-danger'); // Container persen
                const changeIcon = changeWrapper.querySelector('i'); // Ikon Lucide
                const changeText = changeWrapper.lastChild; // Text node persen

                const newPrice = new Intl.NumberFormat('id-ID').format(stock.last_price);

                // Cek jika harga berubah
                if (priceEl.innerText !== newPrice) {
                    // 1. Update Harga Utama
                    priceEl.innerText = newPrice;

                    // 2. Hitung Ulang Perubahan & Persentase
                    const prevClose = parseFloat(stock.previous_close); // Pastikan data ini dikirim dari server
                    const change = stock.last_price - prevClose;
                    const percent = (prevClose > 0) ? (change / prevClose) * 100 : 0;
                    const percentFormatted = Math.abs(percent).toFixed(2) + '%';

                    // 3. Update Warna dan Ikon Berdasarkan Tren Baru
                    if (change >= 0) {
                        changeWrapper.className = 'fw-bold small d-flex align-items-center justify-content-end gap-1 text-success';
                        if (changeIcon.getAttribute('data-lucide') !== 'trending-up') {
                            changeIcon.setAttribute('data-lucide', 'trending-up');
                        }
                    } else {
                        changeWrapper.className = 'fw-bold small d-flex align-items-center justify-content-end gap-1 text-danger';
                        if (changeIcon.getAttribute('data-lucide') !== 'trending-down') {
                            changeIcon.setAttribute('data-lucide', 'trending-down');
                        }
                    }

                    // 4. Update Teks Persentase
                    changeText.textContent = ' ' + percentFormatted;

                    // 5. Efek Flash pada harga
                    priceEl.style.color = (change >= 0) ? '#10b981' : '#ef4444';
                    setTimeout(() => priceEl.style.color = 'white', 2000);

                    // Re-render Ikon Lucide jika berubah
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            }
        });
    }
</script>