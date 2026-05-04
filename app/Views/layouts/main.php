<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'BedahSaham' ?> | Stock Analyzer</title>

    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">

    <link rel="icon" href="<?= base_url('favicon-light.svg'); ?>" media="(prefers-color-scheme: light)">
    <link rel="icon" href="<?= base_url('favicon-dark.svg'); ?>" media="(prefers-color-scheme: dark)">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body class="flex flex-col h-full">
    <nav id="mainNav"
        class="fixed top-0 isolate inset-x-0 z-50 h-20 flex items-center transition-all duration-500 ease-in-out border-b border-transparent">
        <div class="container mx-auto px-4 flex items-center justify-between">
            <a href="<?= base_url('stock') ?>" class="flex items-center group relative">
                <div
                    class="absolute -inset-2 bg-sky-500/20 blur-xl rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                </div>
                <img src="<?= base_url('favicon-dark.svg'); ?>" alt="logo"
                    class="h-10 md:h-11 w-auto relative group-hover:scale-105 transition-transform duration-300">
            </a>

            <button class="md:hidden text-slate-300 hover:text-white p-2 transition-colors rounded-lg hover:bg-white/5"
                onclick="toggleMobileMenu()">
                <i data-lucide="menu" id="menuIcon" class="w-6 h-6"></i>
            </button>

            <div class="hidden md:flex items-center gap-6">
                <ul class="flex items-center gap-4 list-none m-0 p-0">
                    <?php if (!url_is('stock') && !url_is('/')): ?>
                        <li>
                            <a href="<?= base_url('stock') ?>"
                                class="flex items-center gap-2 text-sm font-medium text-slate-400 hover:text-sky-400 transition-all px-3 py-2 rounded-lg hover:bg-sky-500/5">
                                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                                Kembali
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (session()->get('is_logged')): ?>
                        <li>
                            <a href="<?= base_url('stock/token') ?>"
                                class="flex items-center gap-2 bg-linear-to-r from-sky-500/10 to-blue-500/10 border border-sky-500/20 text-sky-400 px-4 py-2 rounded-full text-xs font-bold hover:border-sky-500/40 transition-all shadow-sm group">
                                <i data-lucide="coins" class="w-3.5 h-3.5 group-hover:rotate-12 transition-transform"></i>
                                <span
                                    id="nav-token-balance"><?= number_format(session()->get('token_balance') ?? 0, 0, ',', '.') ?>
                                    <span class="hidden lg:inline text-[10px] opacity-70">TOKEN</span></span>
                            </a>
                        </li>

                        <li class="relative group">
                            <button
                                class="flex items-center gap-3 text-slate-300 group-hover:text-white transition-colors py-2 outline-none">
                                <div
                                    class="w-9 h-9 bg-linear-to-br from-slate-800 to-slate-900 rounded-full flex items-center justify-center border border-white/10 group-hover:border-sky-500/50 transition-all shadow-inner">
                                    <i data-lucide="user" class="w-4.5 h-4.5"></i>
                                </div>
                                <div class="flex flex-col items-start leading-none">
                                    <span
                                        class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">Account</span>
                                    <span
                                        class="text-sm font-semibold truncate max-w-25"><?= session()->get('username') ?></span>
                                </div>
                                <i data-lucide="chevron-down"
                                    class="w-3.5 h-3.5 opacity-50 group-hover:rotate-180 group-hover:opacity-100 transition-transform"></i>
                            </button>

                            <div
                                class="absolute right-0 top-full pt-2 w-56 opacity-0 invisible group-hover:opacity-100 group-hover:visible translate-y-2 group-hover:translate-y-0 transition-all duration-300">
                                <div
                                    class="bg-slate-900/95 border border-white/10 rounded-2xl shadow-2xl overflow-hidden backdrop-blur-xl">
                                    <div class="px-4 py-3 border-b border-white/5 bg-white/5">
                                        <p class="text-[10px] text-slate-500 font-bold uppercase">Menu Utama</p>
                                    </div>
                                    <a href="<?= base_url('stock/token') ?>"
                                        class="flex items-center gap-3 px-4 py-3 text-xs font-medium text-slate-300 hover:bg-sky-500/10 hover:text-sky-400 transition-colors">
                                        <i data-lucide="shopping-cart" class="w-4 h-4 text-sky-500"></i> Top Up Token
                                    </a>
                                    <a href="<?= base_url('stock/token/history') ?>"
                                        class="flex items-center gap-3 px-4 py-3 text-xs font-medium text-slate-300 hover:bg-sky-500/10 hover:text-sky-400 transition-colors">
                                        <i data-lucide="history" class="w-4 h-4 text-sky-500"></i> Riwayat Transaksi
                                    </a>
                                    <div class="border-t border-white/5"></div>
                                    <a href="<?= base_url('logout') ?>"
                                        class="flex items-center gap-3 px-4 py-4 text-xs font-bold text-rose-400 hover:bg-rose-500/10 transition-colors">
                                        <i data-lucide="log-out" class="w-4 h-4"></i> Keluar Sesi
                                    </a>
                                </div>
                            </div>
                        </li>
                    <?php else: ?>
                        <li><a href="<?= base_url('login') ?>"
                                class="text-slate-300 text-sm font-bold hover:text-sky-400 transition-colors px-4">Masuk</a>
                        </li>
                        <li>
                            <a href="<?= base_url('register') ?>"
                                class="bg-sky-500 hover:bg-sky-400 text-slate-950 px-6 py-2.5 rounded-full text-xs font-black shadow-lg shadow-sky-500/20 transition-all active:scale-95 uppercase">
                                Daftar Gratis
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div id="menuBackdrop" onclick="toggleMobileMenu()"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-60 opacity-0 invisible transition-all duration-300 md:hidden">
    </div>

    <sidebar id="mobileSidebar"
        class="fixed top-0 right-0 bottom-0 w-75 z-100 bg-slate-900 border-l border-white/10 translate-x-full transition-transform duration-500 ease-out md:hidden shadow-2xl">

        <div class="flex flex-col h-full p-6">
            <div class="flex justify-end mb-8">
                <button onclick="toggleMobileMenu()"
                    class="text-slate-400 p-2 hover:text-white hover:bg-white/5 rounded-full transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <ul class="flex flex-col gap-5 list-none p-0 m-0">
                <?php if (!url_is('stock') && !url_is('/')): ?>
                    <a href="<?= base_url('stock') ?>"
                        class="flex items-center justify-center gap-3 w-full py-3 px-4 rounded-xl border border-white/10 text-slate-300 font-bold hover:bg-white/5 hover:text-sky-400 hover:border-sky-500/30 transition-all active:scale-95 group">
                        <i data-lucide="arrow-left" class="w-5 h-5 group-hover:-translate-x-1 transition-transform"></i>
                        <span>Kembali ke Explorer</span>
                    </a>
                <?php endif; ?>
                <?php if (session()->get('is_logged')): ?>
                    <li class="my-4 border-t border-white/5 pt-6">
                        <div class="flex items-center gap-4 p-4 bg-white/5 rounded-2xl border border-white/10">
                            <div
                                class="w-10 h-10 bg-sky-500/20 rounded-full flex items-center justify-center border border-sky-500/30">
                                <i data-lucide="user" class="text-sky-400 w-5 h-5"></i>
                            </div>
                            <div class="overflow-hidden">
                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest leading-none mb-1">
                                    Signed in as</p>
                                <p class="text-sm font-bold text-white truncate">
                                    <?= session()->get('username') ?>
                                </p>
                            </div>
                        </div>
                    </li>
                    <li>
                        <a href="<?= base_url('stock/token') ?>"
                            class="flex items-center gap-3 text-sky-400 bg-sky-500/10 p-4 rounded-xl border border-sky-500/20 font-bold active:scale-95 transition-transform">
                            <i data-lucide="coins" class="w-5 h-5"></i>
                            <span>
                                <?= number_format(session()->get('token_balance') ?? 0, 0, ',', '.') ?> <span
                                    class="text-[10px] opacity-70">TOKEN</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= base_url('stock/token/history') ?>"
                            class="flex items-center gap-3 text-slate-300 font-medium p-2">
                            <i data-lucide="history" class="w-5 h-5 text-slate-500"></i> Riwayat
                        </a>
                    </li>
                    <li>
                        <a href="<?= base_url('logout') ?>"
                            class="flex items-center gap-3 text-rose-500 font-bold p-2 mt-2">
                            <i data-lucide="log-out" class="w-5 h-5"></i> Keluar
                        </a>
                    </li>
                <?php else: ?>
                    <li class="mt-4 flex flex-col gap-3">
                        <a href="<?= base_url('login') ?>"
                            class="w-full text-center text-white font-bold py-3 rounded-xl border border-white/10 hover:bg-white/5 transition-all">
                            Masuk
                        </a>
                        <a href="<?= base_url('register') ?>"
                            class="w-full text-center bg-sky-500 text-slate-950 font-black py-3 rounded-xl shadow-lg shadow-sky-500/20 active:scale-95 transition-all">
                            DAFTAR GRATIS
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="mt-auto pb-4 border-t border-white/5 pt-6 text-center">
                <p class="text-slate-600 text-[10px] tracking-[0.2em] font-bold">BedahSaham | Advanced Stock Analysis
                </p>
                <p class="text-text-slate text-[11px] leading-relaxed">
                    Sistem analisis saham dengan AI.
                </p>
            </div>
        </div>
    </sidebar>

    <main class="grow container mx-auto px-4 py-4">
        <?= $this->renderSection('content') ?>
    </main>

    <footer class="bg-slate-950/90 border-t border-white/10 py-10 mt-auto">
        <div class="container mx-auto px-4 text-center md:text-left">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <div class="flex items-center justify-center md:justify-start gap-3 mb-2">
                        <span class="font-bold text-white tracking-tight">BedahSaham</span>
                        <span class="text-white/20">|</span>
                        <span class="text-text-slate text-xs uppercase tracking-widest">Advanced Stock AI</span>
                    </div>
                    <p class="text-text-slate text-[11px] leading-relaxed">
                        © 2026 BedahSaham. Menggunakan model DeepSeek untuk analisis presisi.<br>
                        Surabaya, Indonesia.
                    </p>
                </div>
                <div class="text-center md:text-right">
                    <span class="text-text-slate/50 text-[10px] uppercase tracking-tighter">
                        Supported by Yahoo Finance & FMP API
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Inisialisasi Lucide
        lucide.createIcons();

        // Fungsi Toggle Mobile Menu
        function toggleMobileMenu() {
            const sidebar = document.getElementById('mobileSidebar');
            const backdrop = document.getElementById('menuBackdrop');
            const isOpening = sidebar.classList.contains('translate-x-full');

            if (isOpening) {
                // Buka Menu
                backdrop.classList.remove('invisible', 'opacity-0');
                backdrop.classList.add('opacity-100');
                sidebar.classList.remove('translate-x-full');
                document.body.style.overflow = 'hidden';
            } else {
                // Tutup Menu
                backdrop.classList.remove('opacity-100');
                backdrop.classList.add('opacity-0');
                sidebar.classList.add('translate-x-full');
                document.body.style.overflow = '';

                // Sembunyikan backdrop setelah animasi selesai
                setTimeout(() => {
                    if (sidebar.classList.contains('translate-x-full')) {
                        backdrop.classList.add('invisible');
                    }
                }, 300);
            }
        }

        // Scroll Logic yang lebih clean
        window.addEventListener('scroll', () => {
            const nav = document.getElementById('mainNav');
            if (window.scrollY > 10) {
                nav.classList.add('bg-slate-950/80', 'backdrop-blur-md', 'h-16', 'border-white/10');
                nav.classList.remove('h-20', 'border-transparent');
            } else {
                nav.classList.remove('bg-slate-950/80', 'backdrop-blur-md', 'h-16', 'border-white/10');
                nav.classList.add('h-20', 'border-transparent');
            }
        });
    </script>
</body>

</html>