<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Logika Market Status (Sebaiknya dipindah ke Controller/Helper nanti)
date_default_timezone_set('Asia/Jakarta');
$now = new DateTime();
$day = (int) $now->format('N'); // 1 (Senin) - 7 (Minggu)
$time = $now->format('H:i');

$is_open = false;
if ($day >= 1 && $day <= 5) {
    $is_friday = ($day == 5);
    $session1_end = $is_friday ? '11:30' : '12:00';

    $in_session1 = ($time >= '09:00' && $time <= $session1_end);
    $in_session2 = ($time >= '13:30' && $time <= '16:00');

    if ($in_session1 || $in_session2)
        $is_open = true;
}
?>

<style>
    .index-header-card {
        background: rgba(30, 41, 59, 0.4);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        transition: transform 0.3s ease;
    }

    .market-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        border-radius: 100px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.5px;
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
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: currentColor;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(0.9);
            opacity: 1;
        }

        50% {
            transform: scale(1.4);
            opacity: 0.5;
        }

        100% {
            transform: scale(0.9);
            opacity: 1;
        }
    }
</style>

<div class="index-header-card p-4 mb-4 shadow-sm">
    <div class="row align-items-center">
        <div class="col-md-7">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-info bg-opacity-10 p-3 rounded-4 border border-info border-opacity-20 text-info">
                    <i data-lucide="layout-dashboard" size="28"></i>
                </div>
                <div>
                    <h1 class="h4 fw-bold text-white mb-0">Market Overview</h1>
                    <p class="text-secondary small mb-0">Monitoring Indonesia Stock Exchange Real-time</p>
                </div>
            </div>
        </div>

        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <div class="d-flex flex-column align-items-md-end gap-2">
                <?php if ($is_open): ?>
                    <div class="market-status-badge status-open">
                        <span class="pulse-dot"></span>
                        MARKET OPEN
                    </div>
                <?php else: ?>
                    <div class="market-status-badge status-closed">
                        <i data-lucide="lock" size="14"></i>
                        MARKET CLOSED
                    </div>
                <?php endif; ?>

                <div class="text-secondary d-flex align-items-center gap-2" style="font-size: 0.7rem;">
                    <i data-lucide="clock" size="12"></i>
                    <span>Last Sync: <strong><?= date('H:i:s') ?> WIB</strong></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-5">
    <?= view('partials/universal_chart', [
        'symbol' => 'IHSG',
        'chart_title' => 'Indonesia Composite Index (JKSE)'
    ]) ?>
</div>

<div class="mb-4">
    <?= $this->include('partials/index_table') ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Init Icons
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Data Syncing
        const syncMarketData = async () => {
            try {
                const response = await fetch('<?= base_url('stock/get_live_data') ?>');
                const data = await response.json();
                if (typeof updateTableUI === "function") updateTableUI(data);
            } catch (error) {
                console.error('Market sync failed:', error);
            }
        };

        // Poll every 5 seconds
        setInterval(syncMarketData, 5000);
    });
</script>

<?= $this->endSection() ?>