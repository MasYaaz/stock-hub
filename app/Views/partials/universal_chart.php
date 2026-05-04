<div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-6 items-end">
    <div class="md:col-span-5 flex flex-col gap-1">
        <div class="flex items-center gap-2 text-[10px] uppercase tracking-widest text-slate-500 font-bold">
            <i data-lucide="<?= $symbol === 'IHSG' ? 'globe' : 'line-chart' ?>" class="w-3.5 h-3.5 text-sky-400"></i>
            <?= $chart_title ?>
        </div>
        <div class="flex items-baseline gap-4 mt-1">
            <div class="text-4xl font-black text-white tracking-tighter" id="livePrice">--</div>
            <div id="liveChange"
                class="flex items-center gap-1.5 text-sm font-bold bg-slate-800/50 px-3 py-1 rounded-full border border-white/5">
                <span id="changeIcon"></span>
                <span id="changeValue">0.00%</span>
            </div>
        </div>
    </div>

    <div class="md:col-span-7 flex flex-wrap justify-start md:justify-end items-center gap-3">
        <div class="inline-flex bg-slate-900/80 p-1 rounded-2xl border border-white/10 shadow-inner">
            <?php
            $ranges = ['1d' => 'NOW', '1w' => '1W', '1m' => '1M', '6m' => '6M', '1y' => '1Y'];
            foreach ($ranges as $val => $label): ?>
                <button type="button"
                    class="btn-range-selector px-4 py-1.5 rounded-xl text-[10px] font-black transition-all duration-200 cursor-pointer <?= ($val === '1d') ? 'bg-sky-400 text-slate-950 shadow-lg shadow-sky-400/20' : 'text-slate-500 hover:text-white' ?>"
                    onclick="updateUniversalChart('<?= $val ?>', '<?= $label ?>')">
                    <?= $label ?>
                </button>
            <?php endforeach; ?>
        </div>

        <button type="button" onclick="toggleTechnicalIndicators()"
            class="btn-indicator-toggle w-10 h-10 flex items-center justify-center rounded-xl bg-slate-800/50 border border-white/10 text-slate-400 hover:text-sky-400 hover:border-sky-400/40 hover:bg-sky-400/10 transition-all duration-300 shadow-sm active:scale-90"
            title="Toggle Indicators">
            <i data-lucide="layers" class="w-5 h-5"></i>
        </button>
    </div>
</div>

<div
    class="relative bg-linear-to-br from-slate-800/40 to-slate-950/60 backdrop-blur-xl border border-white/10 rounded-4xl p-8 shadow-2xl group">
    <div id="chartLoader"
        class="absolute inset-0 bg-slate-900/70 backdrop-blur-md hidden items-center justify-center z-20 rounded-4xl transition-all">
        <div class="flex flex-col items-center gap-3">
            <div class="w-10 h-10 border-4 border-sky-400/20 border-t-sky-400 rounded-full animate-spin"></div>
            <span class="text-[10px] font-black text-sky-400 uppercase tracking-widest">Fetching Data</span>
        </div>
    </div>

    <div class="h-112.5 w-full">
        <canvas id="universalCanvas"></canvas>
    </div>
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
        if (showIndicators) {
            btn.classList.add('bg-sky-400', 'text-slate-950', 'border-sky-400', 'shadow-lg', 'shadow-sky-400/30');
            btn.classList.remove('bg-slate-800/50', 'text-slate-400', 'border-white/10');
        } else {
            btn.classList.remove('bg-sky-400', 'text-slate-950', 'border-sky-400', 'shadow-lg', 'shadow-sky-400/30');
            btn.classList.add('bg-slate-800/50', 'text-slate-400', 'border-white/10');
        }

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
            if (btn.innerText.trim() === activeLabel) {
                btn.classList.add('bg-sky-400', 'text-slate-950', 'shadow-lg', 'shadow-sky-400/20');
                btn.classList.remove('text-slate-500', 'hover:text-white');
            } else {
                btn.classList.remove('bg-sky-400', 'text-slate-950', 'shadow-lg', 'shadow-sky-400/20');
                btn.classList.add('text-slate-500', 'hover:text-white');
            }
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

                // 2. POTONG DATA (Slicing) yang cerdas
                // Jika range adalah 1d, JANGAN POTONG data agar 09:00 tetap muncul.
                // Kita hanya potong buffer untuk range mingguan/bulanan agar indikator teknikal stabil.
                const isIntraday = range.toLowerCase() === '1d';
                const bufferCount = (!isIntraday && rawPrices.length > 30) ? 20 : 0;

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

                // Update Warna Berdasarkan Data (Tailwind Color Hex)
                const isUp = data.change_raw >= 0;
                const themeColor = isUp ? '#10b981' : '#f43f5e'; // Emerald vs Rose
                universalChartInstance.data.datasets[0].borderColor = themeColor;

                const ctx = document.getElementById('universalCanvas').getContext('2d');
                const newGradient = ctx.createLinearGradient(0, 0, 0, 400);
                newGradient.addColorStop(0, isUp ? 'rgba(16, 185, 129, 0.15)' : 'rgba(244, 63, 94, 0.15)');
                newGradient.addColorStop(1, 'rgba(0,0,0,0)');
                universalChartInstance.data.datasets[0].backgroundColor = newGradient;

                universalChartInstance.update();

                // 5. UPDATE UI STATS
                document.getElementById('livePrice').innerText = data.last_price.toLocaleString('id-ID', { minimumFractionDigits: 2 });
                const changeValue = document.getElementById('changeValue');
                const changeIcon = document.getElementById('changeIcon');
                const liveChange = document.getElementById('liveChange');

                if (data.change_raw > 0) {
                    liveChange.className = "flex items-center gap-1.5 text-sm font-bold bg-emerald-500/10 text-emerald-400 px-3 py-1 rounded-full border border-emerald-500/20";
                    changeIcon.innerHTML = '<i data-lucide="trending-up" size="16"></i>';
                    changeValue.innerText = `+${data.change_pct}%`;
                } else if (data.change_raw < 0) {
                    liveChange.className = "flex items-center gap-1.5 text-sm font-bold bg-rose-500/10 text-rose-400 px-3 py-1 rounded-full border border-rose-500/20";
                    changeIcon.innerHTML = '<i data-lucide="trending-down" size="16"></i>';
                    changeValue.innerText = `${data.change_pct}%`;
                } else {
                    liveChange.className = "flex items-center gap-1.5 text-sm font-bold bg-slate-500/10 text-slate-400 px-3 py-1 rounded-full border border-slate-500/20";
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