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

<style>
    /* ... (Semua style CSS kamu tetap sama, tidak saya ubah sedikitpun) ... */
    .detail-card-header {
        background: rgba(30, 41, 59, 0.4);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 28px;
        position: relative;
        overflow: hidden;
    }

    .detail-card-header::after {
        content: "";
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle at 50% 50%, rgba(56, 189, 248, 0.03), transparent 50%);
        pointer-events: none;
    }

    .market-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 100px;
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .status-open {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .status-closed {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .pulse-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: currentColor;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(0.95);
            opacity: 1;
        }

        50% {
            transform: scale(1.5);
            opacity: 0.5;
        }

        100% {
            transform: scale(0.95);
            opacity: 1;
        }
    }

    .info-label {
        font-size: 0.65rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .info-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: #f8fafc;
    }

    .badge-sector {
        background: rgba(56, 189, 248, 0.1);
        color: #38bdf8;
        border: 1px solid rgba(56, 189, 248, 0.2);
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        font-size: 0.75rem;
    }

    .ai-box {
        background: rgba(15, 23, 42, 0.5);
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(56, 189, 248, 0.1);
    }

    #aiContent {
        color: #cbd5e1;
        font-size: 0.85rem;
        line-height: 1.7;
    }

    .company-img {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        object-fit: contain;
        background: white;
        padding: 6px;
        border: 2px solid #334155;
    }

    .stat-item {
        padding: 12px;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* Penambahan style tabel history agar match dengan tema */
    .history-table {
        font-size: 0.7rem;
        color: #cbd5e1;
    }

    .history-table th {
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        padding: 8px;
    }

    .history-table td {
        padding: 8px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.02);
    }
</style>

<div class="detail-card-header p-4 mb-4 shadow-2xl">
    <div class="row align-items-center">
        <div class="col-md-7 d-flex align-items-center gap-3">
            <div class="position-relative">
                <?php if ($stock['image']): ?>
                    <img src="<?= $stock['image'] ?>" class="company-img shadow-sm" alt="logo">
                <?php else: ?>
                    <div
                        class="company-img d-flex align-items-center justify-content-center bg-dark text-slate-500 border border-slate-700">
                        <i data-lucide="building-2" size="24"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h1 class="h3 fw-bold text-white mb-0" style="letter-spacing: -0.5px;"><?= $stock['code'] ?></h1>
                    <span class="badge-sector"><?= $stock['sector'] ?></span>
                </div>
                <h5 class="text-slate-400 small fw-medium mb-0 opacity-80"><?= $stock['name'] ?></h5>
            </div>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <div class="mb-2">
                <?php if ($is_open): ?>
                    <div class="market-status-badge status-open"><span class="pulse-dot"></span> Market Open </div>
                <?php else: ?>
                    <div class="market-status-badge status-closed"><i data-lucide="lock" size="10"></i> Market Closed </div>
                <?php endif; ?>
            </div>
            <div class="text-slate-500 d-flex align-items-center justify-content-md-end gap-1"
                style="font-size: 0.65rem;">
                <i data-lucide="info" size="12"></i> Real-time data from IDX via Yahoo & FMP
            </div>
        </div>
    </div>
</div>

<?= view('partials/universal_chart', [
    'symbol' => $stock['code'],
    'chart_title' => 'Price Performance: ' . $stock['code']
]) ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="detail-card p-4 mb-4">
            <div class="d-flex align-items-center gap-2 mb-4">
                <i data-lucide="bar-chart-3" class="text-info"></i>
                <h6 class="fw-bold text-white mb-0">Key Statistics</h6>
            </div>

            <div class="row g-3">
                <div class="col-6">
                    <div class="stat-item">
                        <div class="info-label"><i data-lucide="database" size="12"></i> Market Cap</div>
                        <div class="info-value text-info" style="font-size: 1rem;">
                            <?= isset($stock['market_cap']) ? number_format($stock['market_cap'] / 1000000000000, 2) . ' T' : 'N/A' ?>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-item">
                        <div class="info-label"><i data-lucide="trending-up" size="12"></i> PER Ratio</div>
                        <div class="info-value"><?= number_format($stock['per'] ?? 0, 2) ?>x</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-item">
                        <div class="info-label"><i data-lucide="layers" size="12"></i> PBV Ratio</div>
                        <div class="info-value"><?= number_format($stock['pbv'] ?? 0, 2) ?>x</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-item">
                        <div class="info-label"><i data-lucide="activity" size="12"></i> ROE</div>
                        <div class="info-value text-success"><?= number_format($stock['roe'] ?? 0, 2) ?>%</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-item">
                        <div class="info-label"><i data-lucide="shield-alert" size="12"></i> DER</div>
                        <div class="info-value"><?= number_format($stock['der'] ?? 0, 2) ?>x</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-item">
                        <div class="info-label"><i data-lucide="banknote" size="12"></i> Dividend</div>
                        <div class="info-value">Rp <?= number_format($stock['dividend'] ?? 0, 0, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-item">
                        <div class="info-label"><i data-lucide="pie-chart" size="12"></i> Div. Yield</div>
                        <div class="info-value text-success"><?= number_format($stock['dividend_yield'] ?? 0, 2) ?>%
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-item">
                        <div class="info-label"><i data-lucide="zap" size="12"></i> Beta</div>
                        <div class="info-value"><?= number_format($stock['beta'] ?? 0, 2) ?></div>
                    </div>
                </div>
            </div>

            <hr class="border-secondary opacity-20 my-4">

            <div class="info-label"><i data-lucide="info" size="12"></i> Business Description</div>
            <p class="text-secondary mt-2 mb-4" style="font-size: 0.75rem; text-align: justify; line-height: 1.6;">
                <?= $stock['description'] ?? 'Description not available in database.' ?>
            </p>

            <?php if (!empty($histories)): ?>
                <div class="info-label"><i data-lucide="history" size="12"></i> Historical Performance (FY)</div>
                <div class="table-responsive">
                    <table class="w-100 history-table">
                        <thead>
                            <tr>
                                <th class="text-start">Year</th>
                                <th class="text-end">Revenue</th>
                                <th class="text-end">Net Profit</th>
                                <th class="text-end">ROE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($histories as $h): ?>
                                <tr>
                                    <td class="text-start fw-bold"><?= $h['year'] ?></td>
                                    <td class="text-end"><?= $h['revenue'] ?></td>
                                    <td class="text-end"><?= $h['net_profit'] ?></td>
                                    <td class="text-end"><?= number_format($h['roe'], 1) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="detail-card p-4 border-info border-opacity-25 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-info mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="bot"></i> AI Smart Analysis
                </h6>
                <button onclick="triggerAI('<?= $stock['code'] ?>')"
                    class="btn btn-sm btn-info rounded-pill px-3 shadow-sm d-flex align-items-center gap-2">
                    <i data-lucide="sparkles" size="14"></i> Update Analysis
                </button>
            </div>
            <div class="ai-box">
                <div id="aiContent">
                    <?php if ($stock['ai_analysis']): ?>
                        <script>document.write(marked.parse(`<?= addslashes($stock['ai_analysis']) ?>`))</script>
                    <?php else: ?>
                    <span class="text-white small">Belum ada analisis terbaru. Klik tombol untuk analisis saham.</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($stock['last_ai_update']): ?>
            <div class="mt-3 text-end d-flex align-items-center justify-content-end gap-1"
                style="font-size: 0.65rem; color: #64748b;">
                <i data-lucide="calendar" size="10"></i> Analysis Date:
                <?= date('d M Y, H:i', strtotime($stock['last_ai_update'])) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
    async function triggerAI(code) {
        const aiBox = document.getElementById('aiContent');
        const btn = event.currentTarget;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>AI Thinking...';
        aiBox.style.opacity = '0.4';
        try {
            const response = await fetch(`<?= base_url('stock/analyze-ai') ?>/${code}`);
            const data = await response.json();
            if (data.status === 'success') {
                aiBox.innerHTML = marked.parse(data.analysis);
                aiBox.style.opacity = '1';
            }
        } catch (error) {
            aiBox.innerHTML = '<span class="text-danger small">Gagal memproses AI. Pastikan server Ollama aktif.</span>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="sparkles" class="me-1"></i> Update Analysis';
            lucide.createIcons();
        }
    }
</script>

<?= $this->endSection() ?>