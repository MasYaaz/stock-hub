<!DOCTYPE html>
<html lang="id" class="h-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'BedahSaham' ?> | Stock Analyzer</title>
    <link rel="icon" href="<?= base_url('favicon-light.svg'); ?>" media="(prefers-color-scheme: light)">
    <link rel="icon" href="<?= base_url('favicon-dark.svg'); ?>" media="(prefers-color-scheme: dark)">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg-dark: #0f172a;
            --card-dark: #1e293b;
            --border-dark: rgba(255, 255, 255, 0.08);
            --accent-info: #38bdf8;
            --accent-gold: #fbbf24;
            --text-slate: #94a3b8;
        }

        html,
        body {
            height: 100%;
        }

        body {
            background-color: var(--bg-dark);
            color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            display: flex;
            flex-direction: column;
            padding-top: 80px;
            background-image: radial-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 0);
            background-size: 24px 24px;
        }

        .navbar {
            background-color: rgba(15, 23, 42, 0.6) !important;
            backdrop-filter: blur(12px) saturate(180%);
            border-bottom: 1px solid var(--border-dark);
            padding: 0.1rem 0;
            z-index: 1030;
        }

        .navbar-logo {
            height: 64px;
            /* Sesuaikan dengan selera, biasanya 30-36px */
            width: auto;
            /* Menjaga aspek rasio agar tidak penyet */
            object-fit: contain;
            /* Memberikan sedikit efek glow biru tipis agar match dengan tema */
            filter: drop-shadow(0 0 8px rgba(56, 189, 248, 0.3));
        }

        /* Styling Token Badge */
        .token-badge {
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.2);
            color: var(--accent-info);
            font-weight: 600;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .dropdown-menu-dark {
            background-color: var(--card-dark);
            border: 1px solid var(--border-dark);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
        }

        .dropdown-item:hover {
            background-color: rgba(56, 189, 248, 0.1);
            color: var(--accent-info);
        }

        .btn-nav-accent {
            background: var(--accent-info);
            color: var(--bg-dark);
            font-weight: 600;
            border: none;
        }

        .btn-nav-accent:hover {
            background: #7dd3fc;
            /* Warna biru lebih terang */
            color: var(--bg-dark) !important;
            /* Paksa teks tetap gelap agar terbaca */
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(56, 189, 248, 0.3);
        }

        /* Hover effect untuk link navigasi biasa */
        .hover-info:hover {
            color: var(--accent-info) !important;
            transition: 0.2s;
        }

        main {
            flex: 1 0 auto;
        }

        footer {
            background: rgba(15, 23, 42, 0.9);
            border-top: 1px solid var(--border-dark);
            padding: 2.5rem 0;
            flex-shrink: 0;
        }

        /* Custom Dropdown Arrow */
        .nav-link.dropdown-toggle::after {
            display: none;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?= base_url('stock') ?>">
                <img src="<?= base_url('favicon-dark.svg'); ?>" alt="logo" class="navbar-logo">
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarContent">
                <i data-lucide="menu"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto align-items-center gap-2 mt-3 mt-lg-0">

                    <?php if (!url_is('stock') && !url_is('/')): ?>
                        <li class="nav-item me-lg-2">
                            <a href="<?= base_url('stock') ?>"
                                class="nav-link text-slate hover-info d-flex align-items-center gap-1">
                                <i data-lucide="arrow-left" size="16"></i>
                                <span class="small fw-medium">Kembali</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (session()->get('is_logged')): ?>
                        <li class="nav-item">
                            <a href="<?= base_url('stock/token') ?>" class="text-decoration-none">
                                <div class="token-badge">
                                    <i data-lucide="coins" size="16"></i>
                                    <span><?= session()->get('token_balance') ?? 0 ?> Token</span>
                                </div>
                            </a>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 px-3" href="#"
                                id="profileDrop" role="button" data-bs-toggle="dropdown">
                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white"
                                    style="width: 32px; height: 32px;">
                                    <i data-lucide="user" size="18"></i>
                                </div>
                                <span><?= session()->get('username') ?></span>
                                <i data-lucide="chevron-down" size="14"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg">
                                <li><a class="dropdown-item py-2" href="<?= base_url('stock/token') ?>">
                                        <i data-lucide="shopping-cart" size="16" class="me-2"></i> Top Up</a></li>
                                <li><a class="dropdown-item py-2" href="<?= base_url('stock/token/history') ?>">
                                        <i data-lucide="history" size="16" class="me-2"></i> Riwayat</a></li>
                                <li>
                                    <hr class="dropdown-divider border-secondary">
                                </li>
                                <li><a class="dropdown-item py-2 text-danger" href="<?= base_url('logout') ?>">
                                        <i data-lucide="log-out" size="16" class="me-2"></i> Keluar</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a href="<?= base_url('login') ?>" class="nav-link text-white px-3">Masuk</a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('register') ?>"
                                class="btn btn-nav-accent btn-sm rounded-pill px-4 ms-lg-2 fw-bold">
                                Daftar Gratis
                            </a>
                        </li>
                    <?php endif; ?>

                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <?= $this->renderSection('content') ?>
    </main>

    <footer class="footer">
        <div class="container text-center text-md-start">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 justify-content-center justify-content-md-start mb-2">
                        <span class="fw-bold text-white">BedahSaham</span>
                        <span class="text-slate opacity-50">|</span>
                        <span class="text-slate small">Advanced Stock Analysis AI</span>
                    </div>
                    <p class="text-slate mb-0" style="font-size: 0.75rem;">
                        © 2026 BedahSaham. Menggunakan model DeepSeek untuk analisis presisi.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                    <span class="text-slate" style="font-size: 0.75rem;">
                        Supported by Yahoo Finance & FMP API
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();

        // Navbar scroll effect
        window.addEventListener('scroll', function () {
            const nav = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                nav.style.backgroundColor = 'rgba(15, 23, 42, 0.95) !important';
            } else {
                nav.style.backgroundColor = 'rgba(15, 23, 42, 0.6) !important';
            }
        });
    </script>
</body>

</html>