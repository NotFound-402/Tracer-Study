<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireRole('guru');

$guru = $_SESSION['user'];

$totalAlumni = (int) $pdo->query('SELECT COUNT(*) FROM alumni')->fetchColumn();
$totalTracer = (int) $pdo->query('SELECT COUNT(*) FROM tracer_study')->fetchColumn();
$partisipasi = $totalAlumni > 0 ? round(($totalTracer / $totalAlumni) * 100, 1) : 0;

$statusRows = $pdo->query('SELECT status_alumni, COUNT(*) AS total FROM tracer_study GROUP BY status_alumni ORDER BY total DESC')->fetchAll(PDO::FETCH_ASSOC);
$yearRows = $pdo->query('SELECT tahun_kelulusan, COUNT(*) AS total FROM alumni GROUP BY tahun_kelulusan ORDER BY tahun_kelulusan ASC')->fetchAll(PDO::FETCH_ASSOC);

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function statusColor($status): string
{
    return match ($status) {
        'Bekerja' => 'bg-emerald-50 text-emerald-700',
        'Lanjut Kuliah' => 'bg-blue-50 text-blue-700',
        'Wirausaha' => 'bg-amber-50 text-amber-700',
        'Pengangguran' => 'bg-rose-50 text-rose-700',
        default => 'bg-slate-100 text-slate-600',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Laporan Guru | Tracer Study</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
<div class="min-h-screen lg:flex">
    <div id="sidebarOverlay" class="fixed inset-0 z-30 hidden bg-slate-900/40 lg:hidden"></div>

    <aside id="sidebar"
            class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-navy text-white transition-transform duration-300 lg:translate-x-0">

            <div class="flex items-center gap-3 border-b border-white/10 px-5 py-6">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/10 text-xl">
                    🎓
                </div>
                <div>
                    <p class="font-bold tracking-wide">TRACER STUDY</p>
                    <p class="text-xs text-blue-200">SMKN 1 SUKAWATI</p>
                </div>
            </div>


            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">
                <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-widest text-blue-200">
                    Menu Guru
                </p>

                <a href="index.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Dashboard
                </a>

                <a href="data-alumni.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Data Alumni
                </a>

                <a href="data-tracer-study.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Data Tracer Study
                </a>

                <a href="laporan.php"
                    class="flex items-center gap-3 rounded-xl bg-blue-600 px-4 py-3 font-medium text-white">
                    <span></span> Laporan
                </a>

                <a href="profil.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Profil Saya
                </a>
            </nav>

            <div class="border-t border-white/10 p-3">
                <a href="/logout.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-rose-500/20 hover:text-white">
                    <span>↪</span> Logout
                </a>
            </div>
        </aside>


    <div class="min-w-0 flex-1 lg:ml-64">
        <header class="flex min-h-[72px] items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-4 sm:px-8">
            <div class="flex items-center gap-3">
                <button id="menuButton" class="rounded-lg p-2 text-xl hover:bg-slate-100 lg:hidden" aria-label="Buka menu" aria-expanded="false">☰</button>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-blue-600">Portal Guru</p>
                    <h2 class="text-lg font-bold text-navy">Laporan</h2>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold"><?= e($guru['name'] ?? 'Guru') ?></p>
                    <p class="text-xs text-slate-500">Guru</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">
                    <?= e(strtoupper(substr($guru['name'] ?? 'G', 0, 1))) ?>
                </div>
            </div>
        </header>

        <main class="space-y-6 p-4 sm:p-8">
            <div class="flex flex-wrap gap-3">

                <a href="download-template.php"
                   class="inline-flex items-center gap-2 px-4 py-2
                          bg-blue-600 text-white rounded-lg
                          hover:bg-blue-700 transition">

                    <i data-lucide="download"></i>
                    Download Template
                </a>

                <a href="import-alumni.php"
                   class="inline-flex items-center gap-2 px-4 py-2
                          bg-green-600 text-white rounded-lg
                          hover:bg-green-700 transition">

                    <i data-lucide="file-up"></i>
                    Import Excel
                </a>

            </div>

            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Total Alumni</p>
                    <p class="mt-3 text-3xl font-bold text-navy"><?= number_format($totalAlumni, 0, ',', '.') ?></p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Tracer Study Terisi</p>
                    <p class="mt-3 text-3xl font-bold text-navy"><?= number_format($totalTracer, 0, ',', '.') ?></p>
                </div>
                <div class="rounded-2xl border border-blue-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Partisipasi</p>
                    <p class="mt-3 text-3xl font-bold text-navy"><?= number_format($partisipasi, 1, ',', '.') ?>%</p>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-navy">Status Alumni</h2>
                    <div class="mt-5 space-y-3">
                        <?php foreach ($statusRows as $row): ?>
                            <?php $percent = $totalTracer > 0 ? ($row['total'] / $totalTracer) * 100 : 0; ?>
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="font-medium text-slate-700"><?= e($row['status_alumni']) ?></span>
                                    <span class="text-slate-500"><?= e($row['total']) ?> alumni</span>
                                </div>
                                <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-blue-600" style="width: <?= min(100, $percent) ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-navy">Alumni per Tahun Kelulusan</h2>
                    <div class="mt-5 space-y-3">
                        <?php foreach ($yearRows as $row): ?>
                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                                <span class="font-medium text-slate-700"><?= e($row['tahun_kelulusan']) ?></span>
                                <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700"><?= e($row['total']) ?> orang</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>
<script src="/asset/js/admin.js"></script>
</body>
</html>
