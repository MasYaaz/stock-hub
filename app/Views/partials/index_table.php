<style>
    /* Custom Scrollbar Tipis */
    .custom-scrollbar::-webkit-scrollbar {
        width: 0px;
        /* Ketebalan horizontal */
        height: 3px;
        /* Ketebalan vertikal */
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
        /* Track transparan agar tidak mengganggu */
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(56, 189, 248, 0.2);
        /* Warna biru sky transparan */
        border-radius: 100px;
        transition: all 0.3s ease;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(56, 189, 248, 0.6);
        /* Lebih terang saat di-hover */
    }

    /* Firefox Support */
    .custom-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: rgba(56, 189, 248, 0.2) transparent;
    }

    /* Container scroll horizontal */
    .horizontal-scroll {
        display: flex;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
    }

    /* Sembunyikan scrollbar untuk Chrome, Safari, dan Opera */
    .horizontal-scroll::-webkit-scrollbar {
        display: none;
    }

    /* Sembunyikan scrollbar untuk IE, Edge, dan Firefox */
    .horizontal-scroll {
        -ms-overflow-style: none;
        /* IE and Edge */
        scrollbar-width: none;
        /* Firefox */
    }

    .recommendation-card {
        scroll-snap-align: start;
        flex: 0 0 auto;
        width: 100%;
        /* Default mobile */
    }

    @media (min-width: 768px) {
        .recommendation-card {
            width: 350px;
            /* Lebar kartu di desktop */
        }
    }
</style>


<div class="mb-12">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
        <div>
            <h3 class="text-2xl font-black text-white tracking-tighter uppercase">
                Smart Recommendations
            </h3>
            <p class="text-slate-500 text-sm">Strategi investasi berdasarkan performa fundamental 5 tahun terakhir</p>
        </div>

        <div class="flex flex-wrap gap-2 justify-start md:justify-end" id="recommendationFilter">
            <button onclick="filterRecommendation('all')" data-filter="all"
                class="filter-btn active px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider border border-sky-500/20 bg-sky-500/10 text-sky-400 transition-all">
                All Strategy
            </button>
            <button onclick="filterRecommendation('Blue Chip / Compounder')" data-filter="Blue Chip / Compounder"
                class="filter-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider border border-white/5 bg-slate-900/40 text-slate-400 hover:text-white transition-all">
                Blue Chip
            </button>
            <button onclick="filterRecommendation('Value Investing (Undervalued)')"
                data-filter="Value Investing (Undervalued)"
                class="filter-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider border border-white/5 bg-slate-900/40 text-slate-400 hover:text-white transition-all">
                Value Investing
            </button>
            <button onclick="filterRecommendation('Dividend Aristocrat')" data-filter="Dividend Aristocrat"
                class="filter-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider border border-white/5 bg-slate-900/40 text-slate-400 hover:text-white transition-all">
                Dividend
            </button>
            <button onclick="filterRecommendation('Growth Stock')" data-filter="Growth Stock"
                class="filter-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider border border-white/5 bg-slate-900/40 text-slate-400 hover:text-white transition-all">
                Growth
            </button>
            <button onclick="filterRecommendation('High Efficiency')" data-filter="High Efficiency"
                class="filter-btn px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider border border-white/5 bg-slate-900/40 text-slate-400 hover:text-white transition-all">
                High ROE
            </button>
        </div>
    </div>

    <!-- Container Scroll Horizontal -->
    <div class="horizontal-scroll gap-6 pb-4 p-6" id="recommendationGrid">
        <?php if (!empty($best_picks)): ?>
            <?php foreach ($best_picks as $pick): ?>
                <div class="recommendation-card group relative bg-slate-900/40 backdrop-blur-xl border border-white/10 rounded-4xl p-6 hover:border-sky-500/50 transition-all duration-500 shadow-xl shadow-black/20"
                    data-type="<?= $pick['recommendation_type']; ?>">

                    <div class="absolute -top-3 -right-3">
                        <div
                            class="bg-linear-to-br <?= $pick['score'] >= 90 ? 'from-amber-400 to-orange-600' : 'from-sky-400 to-blue-600' ?> text-slate-950 font-black px-4 py-2 rounded-2xl shadow-lg text-sm">
                            Score: <?= $pick['score']; ?>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 mb-4">
                        <div
                            class="w-12 h-12 shrink-0 flex items-center justify-center rounded-2xl overflow-hidden bg-slate-800 border border-white/5 shadow-inner">
                            <img src="https://financialmodelingprep.com/image-stock/<?= $pick['code'] ?>.JK.png"
                                class="max-w-full max-h-full object-contain p-1" alt="<?= $pick['code'] ?>"
                                onerror="handleMissingLogo(this, '<?= $pick['code'] ?>')">
                        </div>
                        <div>
                            <h4
                                class="text-lg font-black text-white leading-none group-hover:text-sky-400 transition-colors uppercase">
                                <?= $pick['code']; ?>
                            </h4>
                            <span
                                class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block mt-1"><?= $pick['name']; ?></span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/5 border border-white/10">
                            <i data-lucide="<?= $pick['icon'] ?? 'activity' ?>" class="w-3.5 h-3.5 text-sky-400"></i>
                            <span class="text-[10px] font-black text-slate-200 uppercase tracking-tighter">
                                <?= $pick['recommendation_type']; ?>
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 py-4 border-y border-white/5 mb-6">
                        <div class="text-center">
                            <div class="text-[9px] text-slate-500 font-black uppercase mb-1">PER</div>
                            <div class="text-sm font-mono font-bold text-slate-200"><?= number_format($pick['per'], 1); ?>x
                            </div>
                        </div>
                        <div class="text-center border-x border-white/5">
                            <div class="text-[9px] text-slate-500 font-black uppercase mb-1">PBV</div>
                            <div class="text-sm font-mono font-bold text-slate-200"><?= number_format($pick['pbv'], 1); ?>x
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-[9px] text-slate-500 font-black uppercase mb-1">ROE</div>
                            <div class="text-sm font-mono font-bold text-emerald-400"><?= number_format($pick['roe'], 1); ?>%
                            </div>
                        </div>
                    </div>

                    <a href="<?= base_url('stock/detail/' . $pick['code']) ?>"
                        class="flex items-center justify-center gap-2 w-full py-3.5 bg-sky-500/10 hover:bg-sky-500 text-sky-400 hover:text-slate-950 rounded-2xl border border-sky-500/20 hover:border-sky-500 transition-all font-black text-[10px] uppercase tracking-widest active:scale-95 group/btn">
                        Detail Analisis
                        <i data-lucide="chevron-right" class="w-4 h-4 group-hover/btn:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="w-full py-20 text-center bg-slate-900/20 rounded-[3rem] border border-dashed border-white/10">
                <p class="text-slate-500 font-bold uppercase tracking-widest text-xs">Data analisis belum tersedia</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
    <div class="text-center md:text-left">
        <h3
            class="text-2xl font-black text-white flex items-center justify-center md:justify-start gap-3 tracking-tighter">
            <div class="p-2 rounded-xl bg-sky-500/10 border border-sky-500/20">
                <i data-lucide="layout-list" class="text-sky-400 w-6 h-6"></i>
            </div>
            MARKET EXPLORER
        </h3>
        <p class="text-slate-500 text-sm mt-1">
            Menampilkan <span class="text-sky-400 font-bold"><?= count($stocks) ?></span> emiten pilihan terbaik
        </p>
    </div>

    <div class="w-full md:w-96 relative group">

        <i data-lucide="search"
            class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 w-4 h-4 group-focus-within:text-sky-400 transition-colors"></i>

        <input type="text" id="stockSearch"
            class="w-full bg-slate-900/60 border border-white/10 rounded-2xl py-2.5 pl-11 pr-4 text-slate-100 placeholder:text-slate-600 focus:outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 transition-all font-medium"
            placeholder="Cari kode atau nama perusahaan..">

    </div>
</div>

<div
    class="relative bg-slate-900/40 backdrop-blur-xl border border-white/5 rounded-[2.5rem] overflow-hidden shadow-2xl shadow-slate-950/50 flex flex-col">

    <div class="shrink-0 bg-slate-950/90 backdrop-blur-md border-b border-white/5 z-20">
        <table class="w-full text-left border-collapse table-fixed">
            <thead>
                <tr class="text-[10px] uppercase tracking-[0.2em] text-slate-500">
                    <th class="pl-8 py-6 font-black w-[20%]">Emiten</th>
                    <th class="py-6 font-black w-[20%]">Perusahaan</th>
                    <th class="py-6 font-black text-right w-[15%]">Harga</th>
                    <th class="py-6 font-black text-right w-[15%]">Change (1D)</th>
                    <th class="py-6 font-black text-center w-[15%]">Range (H/L)</th>
                    <th class="pr-8 py-6 font-black text-center w-[15%]">Opsi</th>
                </tr>
            </thead>
        </table>
    </div>

    <div class="overflow-y-auto max-h-[60vh] custom-scrollbar relative">
        <table class="w-full text-left border-collapse table-fixed" id="stockTable">
            <thead class="invisible">
                <tr class="h-0">
                    <th class="w-[20%] py-0"></th>
                    <th class="w-[20%] py-0"></th>
                    <th class="w-[15%] py-0"></th>
                    <th class="w-[15%] py-0"></th>
                    <th class="w-[15%] py-0"></th>
                    <th class="w-[15%] py-0"></th>
                </tr>
            </thead>
            <tbody id="stockTableBody">
                <?php foreach ($stocks as $s):
                    $change = $s['last_price'] - $s['previous_close'];
                    $percent = ($s['previous_close'] > 0) ? ($change / $s['previous_close']) * 100 : 0;

                    $notasi = strtolower($s['notation'] ?? '');
                    $notationStyle = match ($notasi) {
                        'utama' => 'text-emerald-400 bg-emerald-400/10 border-emerald-400/20',
                        'pengembangan' => 'text-amber-400 bg-amber-400/10 border-amber-400/20',
                        'akselerasi' => 'text-sky-400 bg-sky-400/10 border-sky-400/20',
                        default => 'text-slate-400 bg-slate-400/5 border-white/5'
                    };
                    ?>
                    <tr id="row-<?= $s['code'] ?>"
                        class="stock-row group hover:bg-white/3 border-b border-white/2 transition-colors">
                        <td class="pl-6 py-5">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-10 h-10 shrink-0 flex items-center justify-center rounded-xl overflow-hidden shadow-lg shadow-black/40 border border-white/5 bg-slate-800">
                                    <img src="https://financialmodelingprep.com/image-stock/<?= $s['code'] ?>.JK.png"
                                        class="max-w-full max-h-full object-contain" alt="<?= $s['code'] ?>" loading="lazy"
                                        onerror="handleMissingLogo(this, '<?= $s['code'] ?>')">
                                </div>
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="badge-code text-sm font-black font-mono text-white group-hover:text-sky-400 transition-colors">
                                            <?= $s['code'] ?>
                                        </span>
                                    </div>
                                    <span
                                        class="text-[9px] text-slate-500 font-bold uppercase tracking-tighter"><?= $s['sector'] ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="py-5">
                            <div
                                class="stock-name-text text-sm font-medium text-slate-300 truncate group-hover:text-white transition-colors">
                                <?= $s['name'] ?>
                            </div>
                        </td>
                        <td class="py-5 text-right">
                            <span class="last-price font-mono font-bold text-white text-base">
                                <?= number_format($s['last_price'], 0, ',', '.') ?>
                            </span>
                        </td>
                        <td class="py-5 text-right">
                            <div
                                class="change-wrapper inline-flex items-center justify-end gap-1 text-xs font-black <?= $change >= 0 ? 'text-emerald-400' : 'text-rose-400' ?>">
                                <i data-lucide="<?= $change >= 0 ? 'trending-up' : 'trending-down' ?>"
                                    class="w-3.5 h-3.5"></i>
                                <span class="percent-text"><?= number_format(abs($percent), 2) ?>%</span>
                            </div>
                        </td>
                        <td class="py-5 text-center">
                            <div
                                class="inline-flex flex-col font-mono text-[9px] font-bold p-2 bg-white/2 rounded-xl border border-white/5">
                                <div class="text-emerald-500/80">H: <?= number_format($s['day_high'], 0, ',', '.') ?></div>
                                <div class="text-rose-500/80">L: <?= number_format($s['day_low'], 0, ',', '.') ?></div>
                            </div>
                        </td>
                        <td class="pr-8 py-5 text-center">
                            <a href="<?= base_url('stock/detail/' . $s['code']) ?>"
                                class="inline-flex items-center gap-2 bg-white/5 hover:bg-sky-500 text-slate-300 hover:text-slate-950 text-[10px] font-black px-5 py-2 rounded-full border border-white/10 hover:border-sky-500 transition-all active:scale-90 uppercase">
                                View Detail
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

    // Fix Search Logic
    document.getElementById('stockSearch').addEventListener('input', function () {
        const filter = this.value.toUpperCase().trim();
        const rows = document.querySelectorAll('#stockTableBody .stock-row');

        rows.forEach(row => {
            const codeText = row.querySelector('.badge-code').textContent.toUpperCase();
            const nameText = row.querySelector('.stock-name-text').textContent.toUpperCase();

            if (codeText.includes(filter) || nameText.includes(filter)) {
                row.style.display = "";
                row.classList.remove('hidden'); // Memastikan class hidden tailwind hilang
            } else {
                row.style.display = "none";
            }
        });
    });

    // Fix Real-time UI Update
    window.updateTableUI = function (data) {
        data.forEach(stock => {
            const row = document.getElementById(`row-${stock.code}`);
            if (row) {
                const priceEl = row.querySelector('.last-price');
                const changeWrapper = row.querySelector('.change-wrapper');
                const percentText = row.querySelector('.percent-text');
                const newPrice = new Intl.NumberFormat('id-ID').format(stock.last_price);

                if (priceEl.innerText !== newPrice) {
                    priceEl.innerText = newPrice;

                    const prevClose = parseFloat(stock.previous_close);
                    const change = stock.last_price - prevClose;
                    const percent = (prevClose > 0) ? (change / prevClose) * 100 : 0;

                    // Update UI Colors & Icons
                    if (change >= 0) {
                        changeWrapper.className = 'change-wrapper inline-flex items-center justify-end gap-1 text-xs font-black text-emerald-400';
                        changeWrapper.querySelector('i').setAttribute('data-lucide', 'trending-up');
                    } else {
                        changeWrapper.className = 'change-wrapper inline-flex items-center justify-end gap-1 text-xs font-black text-rose-400';
                        changeWrapper.querySelector('i').setAttribute('data-lucide', 'trending-down');
                    }

                    percentText.textContent = Math.abs(percent).toFixed(2) + '%';

                    // Flash Effect
                    const originalColor = "text-white";
                    priceEl.classList.remove('text-white');
                    priceEl.classList.add(change >= 0 ? 'text-emerald-400' : 'text-rose-400');

                    setTimeout(() => {
                        priceEl.classList.remove('text-emerald-400', 'text-rose-400');
                        priceEl.classList.add('text-white');
                    }, 2000);

                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            }
        });
    }
    /**
     * Fungsi untuk menangani logo yang tidak ditemukan (404)
     * Mengganti image dengan inisial emiten menggunakan UI-Avatars
     */
    function handleMissingLogo(img, code) {
        // Mencegah looping terus menerus jika UI-Avatars juga gagal (jarang terjadi)
        img.onerror = null;

        // Menggunakan UI-Avatars dengan style yang matching dengan dashboard kamu (Sky Blue)
        // background: 0ea5e9 (Sky 500), color: fff (Putih), bold: true
        const fallbackUrl = `https://ui-avatars.com/api/?name=${code}&background=0ea5e9&color=fff&font-size=0.4&bold=true`;

        img.src = fallbackUrl;

        // Opsional: Beri style tambahan pada parent agar terlihat lebih rapi sebagai "initial icon"
        img.parentElement.classList.add('bg-slate-800');
    }

    function filterRecommendation(type) {
        const cards = document.querySelectorAll('.recommendation-card');
        const buttons = document.querySelectorAll('.filter-btn');

        // 1. Update State Tombol
        buttons.forEach(btn => {
            if (btn.getAttribute('data-filter') === type) {
                btn.classList.add('bg-sky-500/10', 'text-sky-400', 'border-sky-500/20', 'active');
                btn.classList.remove('bg-slate-900/40', 'text-slate-400', 'border-white/5');
            } else {
                btn.classList.remove('bg-sky-500/10', 'text-sky-400', 'border-sky-500/20', 'active');
                btn.classList.add('bg-slate-900/40', 'text-slate-400', 'border-white/5');
            }
        });

        // 2. Filter Kartu dengan Class
        cards.forEach(card => {
            // Beri efek transisi halus
            card.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
            card.style.opacity = '0';

            setTimeout(() => {
                const cardType = card.getAttribute('data-type');

                if (type === 'all' || cardType === type) {
                    // Hapus class hidden milik Tailwind
                    card.classList.remove('hidden');

                    // Trigger reflow untuk animasi opacity
                    setTimeout(() => {
                        card.style.opacity = '1';
                    }, 10);
                } else {
                    // Tambahkan class hidden milik Tailwind
                    card.classList.add('hidden');
                }
            }, 200);
        });
    }
</script>