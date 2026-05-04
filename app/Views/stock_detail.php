<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
date_default_timezone_set('Asia/Jakarta');
$now = new DateTime();
$day = $now->format('N');
$time = $now->format('H:i');

$is_open = false;
if ($day >= 1 && $day <= 5) {
    if ($day == 5) { // Jumat
        if (($time >= '09:00' && $time <= '11:30') || ($time >= '13:30' && $time <= '16:00'))
            $is_open = true;
    } else { // Senin - Kamis
        if (($time >= '09:00' && $time <= '12:00') || ($time >= '13:30' && $time <= '16:00'))
            $is_open = true;
    }
}
?>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="max-w-7xl mx-auto p-4 space-y-6">

    <div
        class="relative overflow-hidden bg-slate-800/40 backdrop-blur-xl border border-white/10 rounded-[28px] p-6 shadow-2xl">
        <div
            class="absolute -inset-full bg-[radial-gradient(circle_at_50%_50%,rgba(56,189,248,0.05),transparent_50%)] pointer-events-none">
        </div>

        <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="relative">

                    <img src="https://financialmodelingprep.com/image-stock/<?= $stock['code'] ?>.JK.png"
                        onerror="handleMissingLogo(this, '<?= $stock['code'] ?>')"
                        class="w-14 h-14 rounded-xl object-contain bg-white p-1.5 border-2 border-slate-700 shadow-sm"
                        alt="<?= $stock['code'] ?>" />


                </div>

                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h1 class="text-3xl font-bold text-white tracking-tight"><?= $stock['code'] ?></h1>
                        <span
                            class="px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-sky-400 bg-sky-400/10 border border-sky-400/20 rounded-lg">
                            <?= $stock['sector'] ?>
                        </span>
                    </div>
                    <h5 class="text-slate-400 font-medium opacity-80"><?= $stock['name'] ?></h5>
                </div>
            </div>

            <div class="flex flex-col md:items-end gap-2">
                <?php if ($is_open): ?>
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-widest bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-current animate-pulse"></span>
                        Market Open
                    </div>
                <?php else: ?>
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-widest bg-red-500/10 text-red-500 border border-red-500/20">
                        <i data-lucide="lock" class="w-3 h-3"></i>
                        Market Closed
                    </div>
                <?php endif; ?>
                <div class="flex items-center gap-1.5 text-[10px] text-slate-500 italic">
                    <i data-lucide="info" class="w-3 h-3"></i> Real-time data from IDX via Yahoo & FMP
                </div>
            </div>
        </div>
    </div>

    <div class="bg-slate-900/50 rounded-3xl border border-white/5 p-1">
        <?= view('partials/universal_chart', [
            'symbol' => $stock['code'],
            'chart_title' => 'Price Performance: ' . $stock['code']
        ]) ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <div class="lg:col-span-5 space-y-6">
            <div class="bg-slate-800/30 border border-white/5 rounded-3xl p-6 shadow-xl">
                <div class="flex items-center gap-2 mb-6">
                    <i data-lucide="bar-chart-3" class="text-sky-400 w-5 h-5"></i>
                    <h6 class="font-bold text-white uppercase tracking-wider text-sm">Key Statistics</h6>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-6">
                    <?php
                    $stats = [
                        ['label' => 'Market Cap', 'val' => (isset($stock['market_cap']) ? number_format($stock['market_cap'] / 1e12, 2) . ' T' : 'N/A'), 'icon' => 'database', 'color' => 'text-sky-400'],
                        ['label' => 'PER Ratio', 'val' => number_format($stock['per'] ?? 0, 2) . 'x', 'icon' => 'trending-up', 'color' => 'text-white'],
                        ['label' => 'PBV Ratio', 'val' => number_format($stock['pbv'] ?? 0, 2) . 'x', 'icon' => 'layers', 'color' => 'text-white'],
                        ['label' => 'ROE', 'val' => number_format($stock['roe'] ?? 0, 2) . '%', 'icon' => 'activity', 'color' => 'text-emerald-400'],
                        ['label' => 'DER', 'val' => number_format($stock['der'] ?? 0, 2) . 'x', 'icon' => 'shield-alert', 'color' => 'text-white'],
                        ['label' => 'Dividend', 'val' => 'Rp ' . number_format($stock['dividend'] ?? 0, 0, ',', '.'), 'icon' => 'banknote', 'color' => 'text-white'],
                        ['label' => 'Div. Yield', 'val' => number_format($stock['dividend_yield'] ?? 0, 2) . '%', 'icon' => 'pie-chart', 'color' => 'text-emerald-400'],
                        ['label' => 'Beta', 'val' => number_format($stock['beta'] ?? 0, 2), 'icon' => 'zap', 'color' => 'text-white'],
                    ];
                    foreach ($stats as $s): ?>
                        <div class="p-3 rounded-2xl bg-white/2 border border-white/5">
                            <div class="flex items-center gap-1.5 text-[9px] text-slate-500 uppercase tracking-widest mb-1">
                                <i data-lucide="<?= $s['icon'] ?>" class="w-3 h-3"></i> <?= $s['label'] ?>
                            </div>
                            <div class="text-base font-bold <?= $s['color'] ?> tracking-tight"><?= $s['val'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <hr class="border-white/5 my-6">

                <div class="space-y-3">
                    <div class="flex items-center gap-1.5 text-[9px] text-slate-500 uppercase tracking-widest">
                        <i data-lucide="info" class="w-3 h-3"></i> Business Description
                    </div>
                    <p class="text-slate-400 text-xs leading-relaxed text-justify">
                        <?= $stock['description'] ?? 'Description not available in database.' ?>
                    </p>
                </div>

                <?php if (!empty($histories)): ?>
                    <div class="mt-8 space-y-4">
                        <div class="flex items-center gap-1.5 text-[9px] text-slate-500 uppercase tracking-widest">
                            <i data-lucide="history" class="w-3 h-3"></i> Historical Performance (FY)
                        </div>
                        <div class="overflow-hidden rounded-xl border border-white/5">
                            <table class="w-full text-[11px] text-left text-slate-400">
                                <thead class="bg-white/2 text-slate-500">
                                    <tr>
                                        <th class="px-4 py-2 font-semibold uppercase">Year</th>
                                        <th class="px-4 py-2 text-right font-semibold uppercase">Revenue</th>
                                        <th class="px-4 py-2 text-right font-semibold uppercase">Net Profit</th>
                                        <th class="px-4 py-2 text-right font-semibold uppercase">ROE</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/2">
                                    <?php foreach ($histories as $h): ?>
                                        <tr class="hover:bg-white/1 transition-colors">
                                            <td class="px-4 py-2.5 font-bold text-slate-200"><?= $h['year'] ?></td>
                                            <td class="px-4 py-2.5 text-right"><?= $h['revenue'] ?></td>
                                            <td class="px-4 py-2.5 text-right"><?= $h['net_profit'] ?></td>
                                            <td class="px-4 py-2.5 text-right text-emerald-400 font-medium">
                                                <?= number_format($h['roe'], 1) ?>%
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="lg:col-span-7">
            <div class="h-full bg-slate-800/30 border border-sky-500/20 rounded-3xl p-6 shadow-xl flex flex-col">

                <div class="flex items-center justify-between mb-6">
                    <h6 class="flex items-center gap-2 font-bold text-sky-400 uppercase tracking-wider text-sm">
                        <i data-lucide="bot" class="w-5 h-5"></i> AI Smart Analysis
                    </h6>
                    <button onclick="triggerAI('<?= $stock['code'] ?>')" id="btnAI"
                        class="flex items-center gap-2 px-4 py-2 bg-sky-400 hover:bg-sky-300 transition-colors text-slate-900 rounded-full text-xs font-bold">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span>Update Analysis</span>
                    </button>
                </div>

                <div class="bg-slate-950/50 rounded-2xl p-6 border border-white/5 mb-4 grow overflow-y-auto">
                    <div id="aiContent" class="prose prose-invert prose-sm max-w-none text-justify">
                        <?php if ($last_analysis): ?>
                            <div id="renderArea"></div>
                            <script>
                                document.getElementById('renderArea').innerHTML = marked.parse(`<?= addslashes($last_analysis->analysis_content) ?>`);
                            </script>
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center py-12 text-center opacity-50">
                                <i data-lucide="brain-circuit" class="w-12 h-12 mb-4"></i>
                                <p class="text-xs">Belum ada analisis untuk emiten ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

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

    async function triggerAI(code) {
        const aiBox = document.getElementById('aiContent');
        const btn = document.getElementById('btnAI');

        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        btn.innerHTML = `<svg class="animate-spin h-4 w-4 text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <span>AI Thinking...</span>`;

        aiBox.style.opacity = '0.4';

        try {
            const response = await fetch(`<?= base_url('api/analyze') ?>/${code}`);
            const data = await response.json();
            if (data.status === 'success') {
                aiBox.innerHTML = marked.parse(data.analysis);
                aiBox.style.opacity = '1';
                const navBalance = document.getElementById('nav-token-balance');
                if (navBalance && data.remaining_tokens !== undefined) {
                    // Format angka dengan titik (Indonesian style)
                    const formatted = new Intl.NumberFormat('id-ID').format(data.remaining_tokens);
                    navBalance.innerHTML = `${formatted} <span class="hidden lg:inline text-[10px] opacity-70">TOKEN</span>`;
                }
            }
        } catch (error) {
            aiBox.innerHTML = '<div class="text-red-400 p-4 bg-red-400/10 border border-red-400/20 rounded-xl text-xs">Gagal memproses AI. Pastikan server Ollama aktif.</div>';
            aiBox.style.opacity = '1';
        } finally {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            btn.innerHTML = `<i data-lucide="sparkles" class="w-4 h-4"></i> <span>Update Analysis</span>`;
            lucide.createIcons();
        }
    }
</script>

<?= $this->endSection() ?>