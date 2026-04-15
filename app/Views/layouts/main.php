<!DOCTYPE html>
<html lang="id" class="h-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Stock-Hub' ?> | Aggregator Saham</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg-dark: #0f172a;
            --card-dark: #1e293b;
            --border-dark: #334155;
            --accent-info: #38bdf8;
        }

        /* PERBAIKAN: Pastikan html dan body mengambil tinggi penuh */
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
            /* Mengatur alur secara vertikal */
        }

        /* Custom Navbar */
        .navbar {
            background-color: rgba(30, 41, 59, 0.8) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-dark);
            padding: 1rem 0;
            flex-shrink: 0;
            /* Navbar tidak boleh mengecil */
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: -0.5px;
            color: var(--accent-info) !important;
            font-size: 1.5rem;
        }

        .nav-link {
            color: #94a3b8 !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--accent-info) !important;
        }

        /* PERBAIKAN: Konten utama akan mengambil sisa ruang yang tersedia */
        main {
            flex: 1 0 auto;
            /* Memberi perintah untuk 'tumbuh' memenuhi sisa layar */
        }

        /* Utility Classes */
        .form-control-dark {
            background-color: var(--bg-dark) !important;
            border: 1px solid var(--border-dark) !important;
            color: white !important;
            border-radius: 10px;
        }

        .form-control-dark:focus {
            border-color: var(--accent-info) !important;
            box-shadow: 0 0 0 0.25rem rgba(56, 189, 248, 0.15) !important;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-dark);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-dark);
            border-radius: 10px;
        }

        footer {
            background-color: var(--card-dark);
            border-top: 1px solid var(--border-dark);
            flex-shrink: 0;
            /* Footer tidak boleh mengecil */
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?= base_url('stock') ?>">
                <i class="fa-solid fa-bolt-lightning me-2"></i>STOCK-HUB
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link <?= (url_is('stock') || url_is('/')) ? 'active' : '' ?>"
                            href="<?= base_url('stock') ?>">
                            <i class="fa-solid fa-gauge-high me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-info btn-sm rounded-pill px-4 fw-bold shadow-sm"
                            href="<?= base_url('stock/update') ?>">
                            <i class="fa-solid fa-arrows-rotate me-1"></i> Sync Data
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <?= $this->renderSection('content') ?>
    </main>

    <footer class="footer py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <span class="text-secondary small">© 2026 <b>Stock-Hub</b>. Developed for Academic Research.</span>
                </div>
                <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
                    <span class="badge bg-dark text-secondary border border-secondary">
                        <i class="fa-solid fa-microchip me-1"></i> CI 4.x + MariaDB
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>