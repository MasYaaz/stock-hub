<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="py-4">
    <div class="row align-items-center mb-5">
        <div class="col-md-8">
            <h1 class="fw-bold text-white mb-2" style="letter-spacing: -1px;">Pilih Paket Token</h1>
            <p class="text-slate opacity-75">Isi ulang saldo untuk menggunakan fitur analisis mendalam DeepSeek AI.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <div class="p-3 rounded-4 shadow-sm"
                style="background: rgba(56, 189, 248, 0.03); border: 1px solid rgba(56, 189, 248, 0.15); backdrop-filter: blur(10px);">
                <small class="text-slate d-block mb-1 font-monospace"
                    style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px;">Saldo Saat Ini</small>
                <h3 class="text-info fw-bold mb-0 d-flex align-items-center justify-content-md-end gap-2">
                    <i data-lucide="coins" size="24"></i>
                    <span><?= session()->get('token_balance') ?></span>
                    <small class="text-slate fw-normal" style="font-size: 0.9rem;">Token</small>
                </h3>
            </div>
        </div>
    </div>

    <div class="row g-4 justify-content-center">
        <?php foreach ($packages as $p): ?>
            <div class="col-md-4">
                <div class="card h-100 border-0 pricing-card transition-hover"
                    style="background: rgba(30, 41, 59, 0.45); border: 1px solid rgba(255, 255, 255, 0.05) !important; border-radius: 32px; overflow: hidden;">

                    <div class="card-body p-4 p-xl-5">
                        <div class="mb-4 text-center">
                            <span class="badge rounded-pill mb-3 py-2 px-3"
                                style="background: rgba(56, 189, 248, 0.1); color: var(--accent-info); font-size: 0.65rem; letter-spacing: 1.5px; border: 1px solid rgba(56, 189, 248, 0.2);">
                                <?= strtoupper($p->package_name) ?>
                            </span>
                            <div class="d-flex align-items-baseline justify-content-center gap-1">
                                <span class="text-slate h5 mb-0">Rp</span>
                                <h2 class="display-5 fw-bold text-white mb-0" style="letter-spacing: -2px;">
                                    <?= number_format($p->price, 0, ',', '.') ?>
                                </h2>
                            </div>
                        </div>

                        <div class="token-feature-box mb-4 py-3 text-center rounded-4"
                            style="background: rgba(15, 23, 42, 0.3); border: 1px solid rgba(255,255,255,0.03);">
                            <h4 class="text-info fw-bold mb-0"><?= $p->token_amount ?></h4>
                            <small class="text-slate text-uppercase small"
                                style="font-size: 0.6rem; letter-spacing: 1px;">Kredit Analisis AI</small>
                        </div>

                        <ul class="list-unstyled mb-5 px-2">
                            <li class="mb-3 d-flex align-items-center gap-3">
                                <div class="icon-circle shadow-sm"><i data-lucide="zap" size="14" class="text-info"></i>
                                </div>
                                <span class="text-slate small">Analisis Menggunakan AI</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center gap-3">
                                <div class="icon-circle shadow-sm"><i data-lucide="infinity" size="14"
                                        class="text-info"></i></div>
                                <span class="text-slate small">Tanpa Batas Waktu (Lifetime)</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center gap-3">
                                <div class="icon-circle shadow-sm"><i data-lucide="shield-check" size="14"
                                        class="text-info"></i></div>
                                <span class="text-slate small">Analisa dengan data terbaru</span>
                            </li>
                        </ul>

                        <a href="javascript:void(0)" data-url="<?= base_url('stock/token/buy/' . $p->id) ?>"
                            data-package="<?= $p->package_name ?>" data-price="<?= number_format($p->price, 0, ',', '.') ?>"
                            class="btn btn-buy btn-confirm-buy w-100 fw-bold py-3 rounded-pill transition shadow-lg">
                            Beli Sekarang
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    /* Global Card Glow & Hover */
    .pricing-card {
        position: relative;
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .pricing-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, transparent, var(--accent-info), transparent);
        opacity: 0;
        transition: 0.3s;
    }

    .pricing-card:hover {
        transform: translateY(-12px);
        background: rgba(30, 41, 59, 0.7) !important;
        border-color: rgba(56, 189, 248, 0.3) !important;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 20px rgba(56, 189, 248, 0.1);
    }

    .pricing-card:hover::before {
        opacity: 1;
    }

    /* Icon Circle Styling */
    .icon-circle {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: rgba(56, 189, 248, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* Button Custom Styling */
    .btn-buy {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(56, 189, 248, 0.3);
        color: var(--accent-info);
    }

    .btn-buy:hover {
        background: var(--accent-info);
        color: #0f172a;
        box-shadow: 0 0 20px rgba(56, 189, 248, 0.4);
    }

    /* Utilitas Text */
    .text-slate {
        color: #94a3b8;
    }
</style>

<script>
    lucide.createIcons();

    // Logika Konfirmasi Pembelian
    document.querySelectorAll('.btn-confirm-buy').forEach(button => {
        button.addEventListener('click', function (e) {
            const targetUrl = this.getAttribute('data-url');
            const packageName = this.getAttribute('data-package');
            const price = this.getAttribute('data-price');

            Swal.fire({
                title: 'Konfirmasi Pembelian',
                html: `Anda akan membeli paket <b>${packageName}</b><br>seharga <b class="text-success">Rp ${price}</b>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#38bdf8', // Warna accent-info kamu
                cancelButtonColor: '#1e293b',
                confirmButtonText: 'Ya, Beli!',
                cancelButtonText: 'Batal',
                background: '#0f172a', // Menyesuaikan tema dark kamu
                color: '#ffffff',
                backdrop: `rgba(15, 23, 42, 0.6)`
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tampilkan loading sebentar sebelum redirect
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Mengarahkan ke pembayaran',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Redirect ke URL pembelian
                    window.location.href = targetUrl;
                }
            });
        });
    });

    // Opsional: Cek jika ada flash data sukses/gagal dari controller
    <?php if (session()->getFlashdata('success')): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?= session()->getFlashdata('success') ?>',
            background: '#0f172a',
            color: '#ffffff'
        });
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: '<?= session()->getFlashdata('error') ?>',
            background: '#0f172a',
            color: '#ffffff'
        });
    <?php endif; ?>
</script>
<?= $this->endSection() ?>