<style>
    /* 1. Chart & Container Styles */
    .chart-container {
        position: relative;
        background: linear-gradient(145deg, #1e293b, #111827);
        border: 1px solid #334155;
        border-radius: 24px;
        padding: 2rem;
    }

    /* 2. Group Button Wrapper */
    .range-selector-wrapper {
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 4px;
        display: inline-flex;
        gap: 4px;
    }

    /* 3. Base Button Styles */
    .btn-time-range,
    .btn-indicator-toggle {
        border: none !important;
        font-size: 0.75rem;
        font-weight: 700;
        transition: all 0.2s ease-in-out;
    }

    .btn-time-range {
        color: #94a3b8;
        padding: 6px 16px;
        background: transparent;
    }

    .btn-time-range:hover {
        color: #f8fafc;
        background: rgba(255, 255, 255, 0.05);
    }

    .btn-time-range.active {
        background: #38bdf8 !important;
        color: #0f172a !important;
        box-shadow: 0 4px 12px rgba(56, 189, 248, 0.25);
    }

    /* 4. Indicator Toggle Specific Style */
    .btn-indicator-toggle {
        background: rgba(30, 41, 59, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: #94a3b8;
        padding: 0;
        /* Padding nol agar width/height konsisten */
        transition: all 0.3s ease;
        flex-shrink: 0;
        /* Mencegah tombol gepeng */
    }

    .btn-indicator-toggle:hover {
        background: rgba(56, 189, 248, 0.1);
        color: #38bdf8;
        border-color: rgba(56, 189, 248, 0.4) !important;
        transform: translateY(-1px);
    }

    .btn-indicator-toggle.active-mode {
        background: #38bdf8 !important;
        color: #0f172a !important;
        border-color: #38bdf8 !important;
        box-shadow: 0 0 15px rgba(56, 189, 248, 0.4);
    }

    /* 5. Stats & Loading */
    .stat-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #f8fafc;
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(30, 41, 59, 0.7);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 20;
        border-radius: 24px;
    }
</style>

<div class="row g-4 mb-4 align-items-end">
    <div class="col-md-5">
        <div class="stat-label mb-1">
            <i data-lucide="<?= $symbol === 'IHSG' ? 'globe' : 'line-chart' ?>" size="14"></i>
            <?= $chart_title ?>
        </div>
        <div class="d-flex align-items-baseline gap-3">
            <div class="stat-value" id="livePrice">--</div>
            <div id="liveChange" class="fw-bold" style="font-size: 1rem;">
                <span id="changeIcon"></span>
                <span id="changeValue">0.00%</span>
            </div>
        </div>
    </div>

    <div class="col-md-7 d-flex flex-wrap justify-content-md-end align-items-center gap-3">
        <div class="range-selector-wrapper rounded-pill shadow-sm">
            <?php
            $ranges = ['1d' => 'NOW', '1w' => '1W', '1m' => '1M', '6m' => '6M', '1y' => '1Y'];
            foreach ($ranges as $val => $label): ?>
                <button type="button"
                    class="btn btn-time-range rounded-pill btn-range-selector <?= ($val === '1d') ? 'active' : '' ?>"
                    onclick="updateUniversalChart('<?= $val ?>', '<?= $label ?>')">
                    <?= $label ?>
                </button>
            <?php endforeach; ?>
        </div>

        <button type="button"
            class="btn btn-indicator-toggle rounded-circle d-flex align-items-center justify-content-center"
            style="width: 40px; height: 40px;" onclick="toggleTechnicalIndicators()" title="Toggle Indicators">
            <i data-lucide="layers" size="18"></i>
        </button>
    </div>
</div>

<div class="chart-container shadow-2xl mb-5">
    <div id="chartLoader" class="loading-overlay">
        <div class="spinner-border text-info" role="status"></div>
    </div>
    <canvas id="universalCanvas" style="height: 450px;"></canvas>
</div>

<script>
    const CHART_SYMBOL = "<?= $symbol ?>";
    let universalChartInstance = null;
    let showIndicators = false;

    // --- FUNGSI MATH INDIKATOR (ANTI-TERPOTONG) ---
    function calculateSMA(data, period = 20) {
        return data.map((_, i) => {
            if (i < period - 1) return data[0]; // Isi awal dengan harga pertama agar garis nyambung
            const slice = data.slice(i - period + 1, i + 1);
            return slice.reduce((a, b) => a + b, 0) / period;
        });
    }

    function calculateBollingerBands(data, period = 20) {
        let upper = [], lower = [];
        data.forEach((_, i) => {
            if (i < period - 1) {
                upper.push(data[0]); lower.push(data[0]);
            } else {
                const slice = data.slice(i - period + 1, i + 1);
                const avg = slice.reduce((a, b) => a + b, 0) / period;
                const sd = Math.sqrt(slice.reduce((sq, n) => sq + Math.pow(n - avg, 2), 0) / period);
                upper.push(avg + (sd * 2));
                lower.push(avg - (sd * 2));
            }
        });
        return { upper, lower };
    }

    function calculateRSI(data, period = 14) {
        if (data.length <= period) return Array(data.length).fill(null);
        let rsi = Array(period).fill(null);
        let gains = [], losses = [];
        for (let i = 1; i < data.length; i++) {
            let diff = data[i] - data[i - 1];
            gains.push(diff > 0 ? diff : 0);
            losses.push(diff < 0 ? Math.abs(diff) : 0);
        }
        for (let i = period; i < data.length; i++) {
            let avgGain = gains.slice(i - period, i).reduce((a, b) => a + b) / period;
            let avgLoss = losses.slice(i - period, i).reduce((a, b) => a + b) / period;
            if (avgLoss === 0) rsi.push(100);
            else {
                let rs = avgGain / avgLoss;
                rsi.push(100 - (100 / (1 + rs)));
            }
        }
        return rsi;
    }

    // --- INITIALIZATION ---
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('universalCanvas').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(56, 189, 248, 0.3)');
        gradient.addColorStop(1, 'rgba(56, 189, 248, 0)');

        universalChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: CHART_SYMBOL,
                        data: [],
                        borderColor: '#38bdf8',
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.2,
                        pointRadius: 0,
                        zIndex: 10
                    },
                    {
                        label: 'SMA 20',
                        data: [],
                        borderColor: '#ffffff', // Kontras Putih
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0,
                        spanGaps: true,
                        hidden: true
                    },
                    {
                        label: 'BB Upper',
                        data: [],
                        borderColor: 'rgba(255, 215, 0, 0.6)', // Kontras Emas
                        borderWidth: 1.5,
                        fill: '+1',
                        backgroundColor: 'rgba(255, 255, 255, 0.03)',
                        pointRadius: 0,
                        spanGaps: true,
                        hidden: true
                    },
                    {
                        label: 'BB Lower',
                        data: [],
                        borderColor: 'rgba(255, 215, 0, 0.6)',
                        borderWidth: 1.5,
                        fill: false,
                        pointRadius: 0,
                        spanGaps: true,
                        hidden: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        cornerRadius: 12,
                        displayColors: true, // Ubah jadi true agar ada kotak warna pembeda
                        // filter: (item) => item.datasetIndex === 0, // <--- HAPUS ATAU KOMENTARI BARIS INI
                        callbacks: {
                            label: (context) => {
                                // Format angka agar rapi di tooltip
                                const val = new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                return `${context.dataset.label}: ${val}`;
                            }
                        }
                    }
                },
                scales: {
                    y: { position: 'right', grid: { color: 'rgba(51, 65, 85, 0.2)' }, ticks: { color: '#64748b', font: { size: 10 } } },
                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 10 }, maxTicksLimit: 12 } }
                }
            }
        });

        updateUniversalChart('1d', 'NOW');
    });

    window.toggleTechnicalIndicators = function () {
        showIndicators = !showIndicators;

        // Toggle class warna saja
        const btn = document.querySelector('.btn-indicator-toggle');
        btn.classList.toggle('active-mode', showIndicators);

        // Update visibilitas dataset
        [1, 2, 3].forEach(index => {
            universalChartInstance.setDatasetVisibility(index, showIndicators);
        });

        universalChartInstance.update();
    };

    window.updateUniversalChart = async function (range, label = null) {
        const loader = document.getElementById('chartLoader');
        if (loader) loader.style.display = 'flex';

        const activeLabel = label || (range.toUpperCase() === '1D' ? 'NOW' : range.toUpperCase());
        document.querySelectorAll('.btn-range-selector').forEach(btn => {
            btn.classList.toggle('active', btn.innerText.trim() === activeLabel);
        });

        try {
            const response = await fetch(`<?= base_url('stock/history') ?>/${CHART_SYMBOL}/${range.toLowerCase()}`);
            const data = await response.json();

            if (data.prices && data.prices.length > 0) {
                const rawPrices = data.prices;
                const rawLabels = data.labels;

                // 1. HITUNG INDIKATOR DARI DATA MENTAH (Full Data dari Backend)
                // Kita hitung sebelum dipotong agar indikator punya "bekal" data masa lalu
                const fullSMA = calculateSMA(rawPrices, 20);
                const fullBB = calculateBollingerBands(rawPrices, 20);
                const rsiArr = calculateRSI(rawPrices, 14);

                // 2. POTONG DATA (Slicing)
                // Kita buang 20 data pertama (buffer) agar chart tampil bersih
                // Jika data sedikit (seperti 1D/NOW), kita tidak perlu potong terlalu banyak
                const bufferCount = rawPrices.length > 30 ? 20 : 0;

                const displayPrices = rawPrices.slice(bufferCount);
                const displayLabels = rawLabels.slice(bufferCount);
                const displaySMA = fullSMA.slice(bufferCount);
                const displayBB_Upper = fullBB.upper.slice(bufferCount);
                const displayBB_Lower = fullBB.lower.slice(bufferCount);

                // 3. UPDATE CHART DENGAN DATA HASIL POTONGAN
                universalChartInstance.data.labels = displayLabels;
                universalChartInstance.data.datasets[0].data = displayPrices;
                universalChartInstance.data.datasets[1].data = displaySMA;
                universalChartInstance.data.datasets[2].data = displayBB_Upper;
                universalChartInstance.data.datasets[3].data = displayBB_Lower;

                // Pertahankan status toggle (Show/Hide Indicators)
                [1, 2, 3].forEach(idx => {
                    universalChartInstance.setDatasetVisibility(idx, showIndicators);
                });

                // RSI Stat (diambil dari data paling baru/akhir)
                const currentRSI = rsiArr[rsiArr.length - 1];
                if (currentRSI) {
                    const status = currentRSI >= 70 ? 'Overbought' : (currentRSI <= 30 ? 'Oversold' : 'Neutral');
                    document.getElementById('changeValue').title = `RSI: ${currentRSI.toFixed(2)} (${status})`;
                }

                // 4. WARNA DINAMIS (Berdasarkan tren harga yang ditampilkan)
                const isUp = data.change_raw >= 0;
                const themeColor = isUp ? '#10b981' : '#ef4444';
                universalChartInstance.data.datasets[0].borderColor = themeColor;

                const ctx = document.getElementById('universalCanvas').getContext('2d');
                const newGradient = ctx.createLinearGradient(0, 0, 0, 400);
                newGradient.addColorStop(0, isUp ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)');
                newGradient.addColorStop(1, 'rgba(0,0,0,0)');
                universalChartInstance.data.datasets[0].backgroundColor = newGradient;

                universalChartInstance.update();

                // 5. UPDATE UI STATS
                document.getElementById('livePrice').innerText = data.last_price.toLocaleString('id-ID', { minimumFractionDigits: 2 });
                const changeValue = document.getElementById('changeValue');
                const changeIcon = document.getElementById('changeIcon');
                const liveChange = document.getElementById('liveChange');

                if (data.change_raw > 0) {
                    liveChange.className = "text-success fw-bold";
                    changeIcon.innerHTML = '<i data-lucide="trending-up" size="16"></i>';
                    changeValue.innerText = `+${data.change_pct}%`;
                } else if (data.change_raw < 0) {
                    liveChange.className = "text-danger fw-bold";
                    changeIcon.innerHTML = '<i data-lucide="trending-down" size="16"></i>';
                    changeValue.innerText = `${data.change_pct}%`;
                } else {
                    liveChange.className = "text-secondary fw-bold";
                    changeIcon.innerHTML = '<i data-lucide="minus" size="16"></i>';
                    changeValue.innerText = `0.00%`;
                }
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        } catch (e) {
            console.error("Chart Error:", e);
        } finally {
            if (loader) loader.style.display = 'none';
        }
    }
</script>