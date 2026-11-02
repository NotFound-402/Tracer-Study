<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Alumni | Tracer Study SMKN 1 Sukawati</title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>
<script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: '#142D50',
                        brand: '#2563EB'
                    }
                }
            }
        };
    </script>
<body class="bg-slate-50 text-slate-800">
    <div class="min-h-screen">
        <div id="sidebarOverlay" class="fixed inset-0 z-30 hidden bg-slate-900/40 lg:hidden"></div>

        <!-- Sidebar -->
        <aside id="sidebar"
            class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-navy text-white transition-transform duration-300 lg:translate-x-0">

            <div class="flex items-center gap-3 border-b border-white/10 px-5 py-6">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/10 text-xl">

                </div>
                <div>
                    <p class="font-bold tracking-wide">TRACER STUDY</p>
                    <p class="text-xs text-blue-200">SMKN 1 SUKAWATI</p>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">
                <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-widest text-blue-200">
                    Menu Utama
                </p>

                <a href="index.php"
                class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                <span></span> Dashboard
            </a>
            
            <a href="alumni.php"
            class="flex items-center gap-3 rounded-xl bg-blue-600 px-4 py-3 font-medium shadow-lg shadow-blue-950/20">
                    <span></span> Data Alumni
                </a>

                <a href="tracer-study.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Data Tracer Study
                </a>

                <a href="pekerjaan.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Data Pekerjaan
                </a>

                <a href="pendidikan.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Data Pendidikan
                </a>

                <a href="wirausaha.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Data Wirausaha
                </a>

                <a href="laporan.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Laporan
                </a>

                <a href="pengaturan.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Pengaturan
                </a>
            </nav>

            <div class="border-t border-white/10 p-3">

                <a href="../logout.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-rose-500/20 hover:text-white">
                    <span>↪</span> Logout
                </a>
            </div>
        </aside>

        <!-- Area konten -->
        <div class="min-h-screen lg:ml-64">

            <!-- Header -->
            <header class="sticky top-0 z-30 flex h-[72px] items-center gap-4 border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
                <button id="menuButton"
                    class="rounded-lg p-2 text-xl hover:bg-slate-100 lg:hidden"
                    aria-label="Buka menu">☰</button>

                <div class="hidden max-w-xl flex-1 sm:block">
                    <div class="flex items-center gap-3 rounded-xl bg-slate-100 px-4 py-3">
                        <span class="text-slate-400">⌕</span>

                        <input id="searchInput" type="search"
                            placeholder="Cari nama alumni atau jurusan..."
                            class="w-full bg-transparent text-sm outline-none placeholder:text-slate-400">
                    </div>
                </div>

                <div class="ml-auto flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold">Administrator</p>
                        <p class="text-xs text-slate-500">Admin</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">
                        A
                    </div>
                </div>
            </header>
        </div>
    </div>

    <script src="/asset/js/admin.js"></script>
</body>
</html>