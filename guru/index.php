<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireRole('guru');

$guru = $_SESSION['user'];

// Statistik utama
$totalAlumni = (int) $pdo
    ->query("SELECT COUNT(*) FROM alumni")
    ->fetchColumn();

$totalRespons = (int) $pdo
    ->query("SELECT COUNT(*) FROM tracer_study")
    ->fetchColumn();

$belumMengisi = max(0, $totalAlumni - $totalRespons);

$partisipasi = $totalAlumni > 0
    ? round(($totalRespons / $totalAlumni) * 100, 1)
    : 0;

// Ringkasan status alumni
$statusMap = [
    'Bekerja' => 0,
    'Lanjut Kuliah' => 0,
    'Wirausaha' => 0,
    'Pengangguran' => 0,
];

$statusQuery = $pdo->query("
    SELECT status_alumni, COUNT(*) AS total
    FROM tracer_study
    GROUP BY status_alumni
");

foreach ($statusQuery->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (isset($statusMap[$row['status_alumni']])) {
        $statusMap[$row['status_alumni']] = (int) $row['total'];
    }
}

// Jumlah alumni per tahun kelulusan
$tahunQuery = $pdo->query("
    SELECT tahun_kelulusan, COUNT(*) AS total
    FROM alumni
    GROUP BY tahun_kelulusan
    ORDER BY tahun_kelulusan ASC
");

$tahunData = $tahunQuery->fetchAll(PDO::FETCH_ASSOC);

// Lima respons terbaru
$responsQuery = $pdo->query("
    SELECT
        a.nama,
        a.tahun_kelulusan,
        j.singkatan,
        t.status_alumni,
        t.tgl_pengisian
    FROM tracer_study t
    JOIN alumni a ON a.id_alumni = t.id_alumni
    LEFT JOIN jurusan j ON j.id_jurusan = a.id_jurusan
    ORDER BY t.tgl_pengisian DESC
    LIMIT 5
");

$responsTerbaru = $responsQuery->fetchAll(PDO::FETCH_ASSOC);

function e($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function labelStatus(string $status): string
{
    return $status === 'Pengangguran'
        ? 'Belum Bekerja'
        : $status;
}

function warnaStatus(string $status): string
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Tracer Study SMKN 1 Sukawati</title>

    <!-- Tailwind untuk tahap prototipe -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Grafik -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
                    Menu Guru
                </p>

                <a href="index.php"
                    class="flex items-center gap-3 rounded-xl bg-blue-600 px-4 py-3 font-medium text-white">
                    <span>▦</span> Dashboard
                </a>

                <a href="alumni.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span>♙</span> Data Alumni
                </a>

                <a href="tracer-study.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span>▤</span> Data Tracer Study
                </a>

                <a href="laporan.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span>▥</span> Laporan
                </a>

                <a href="profil.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span>♙</span> Profil Saya
                </a>
            </nav>

            <div class="border-t border-white/10 p-3">
                <a href="/logout.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-rose-500/20 hover:text-white">
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
                        <p class="text-sm font-semibold">
                            <?= e($guru['name'] ?? 'Guru') ?>
                        </p>
                        <p class="text-xs text-slate-500">Guru</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">
                        <?= e(strtoupper(substr($guru['name'] ?? 'G', 0, 1))) ?>
                    </div>
                </div>
            </header>

            <main class="space-y-6 p-4 sm:p-6 lg:p-8">

                <!-- Sambutan -->
                <section class="flex flex-col justify-between gap-4 md:flex-row md:items-center">

                    <h1 class="text-2xl font-bold tracking-tight text-navy sm:text-3xl">
                        Selamat Datang, <?= e($guru['name'] ?? 'Guru') ?>! 👋
                    </h1>
                    <p class="mt-2 text-sm text-slate-500">
                        Pantau perkembangan alumni dan hasil tracer study SMKN 1 Sukawati.
                    </p>
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                        📅 <?= e(date('d M Y')) ?>
                    </div>
                </section>

                <!-- Kartu statistik -->
                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                    <div class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-500">Total Alumni</span>
                            <span class="rounded-xl bg-blue-50 p-3 text-xl">👥</span>
                        </div>
                        <p class="mt-4 text-3xl font-bold text-navy">
                            <?= number_format($totalAlumni, 0, ',', '.') ?>
                        </p>
                        <p class="mt-2 text-xs text-slate-500">Alumni terdaftar di sistem</p>
                    </div>

                    <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-500">Sudah Mengisi</span>
                            <span class="rounded-xl bg-emerald-50 p-3 text-xl">📋</span>
                        </div>
                        <p class="mt-4 text-3xl font-bold text-navy">
                            <?= number_format($totalRespons, 0, ',', '.') ?>
                        </p>
                        <p class="mt-2 text-xs text-slate-500">Respons tracer study tercatat</p>
                    </div>

                    <div class="rounded-2xl border border-rose-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-500">Belum Mengisi</span>
                            <span class="rounded-xl bg-rose-50 p-3 text-xl">⏳</span>
                        </div>
                        <p class="mt-4 text-3xl font-bold text-navy">
                            <?= number_format($belumMengisi, 0, ',', '.') ?>
                        </p>
                        <p class="mt-2 text-xs text-slate-500">Alumni belum mengisi kuesioner</p>
                    </div>

                    <div class="rounded-2xl border border-indigo-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-500">Tingkat Partisipasi</span>
                            <span class="rounded-xl bg-indigo-50 p-3 text-xl">📈</span>
                        </div>
                        <p class="mt-4 text-3xl font-bold text-navy">
                            <?= number_format($partisipasi, 1, ',', '.') ?>%
                        </p>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-blue-600"
                                style="width: <?= min(100, $partisipasi) ?>%"></div>
                        </div>
                    </div>

                </section>

                <!-- Grafik dan informasi -->
                <section class="grid gap-6 xl:grid-cols-3">

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-1">
                        <div class="mb-5">
                            <h2 class="font-bold text-navy">Status Alumni</h2>
                            <p class="mt-1 text-sm text-slate-500">Berdasarkan respons tracer study</p>
                        </div>
                        <div class="mx-auto max-w-xs">
                            <canvas id="statusChart" aria-label="Grafik status alumni"></canvas>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                        <div class="mb-5">
                            <h2 class="font-bold text-navy">Alumni per Tahun Kelulusan</h2>
                            <p class="mt-1 text-sm text-slate-500">Jumlah alumni yang terdaftar per angkatan</p>
                        </div>
                        <div class="h-72">
                            <canvas id="tahunChart" aria-label="Grafik alumni per tahun"></canvas>
                        </div>
                    </div>

                </section>

                <!-- Tabel respons terbaru -->
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col justify-between gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center">
                        <div>
                            <h2 class="font-bold text-navy">Respons Tracer Study Terbaru</h2>
                            <p class="mt-1 text-sm text-slate-500">Lima respons terakhir yang masuk</p>
                        </div>
                        <a href="tracer-study.php"
                            class="text-sm font-semibold text-blue-600 hover:text-blue-800">
                            Lihat semua →
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-4">Nama Alumni</th>
                                    <th class="px-5 py-4">Tahun Lulus</th>
                                    <th class="px-5 py-4">Jurusan</th>
                                    <th class="px-5 py-4">Status</th>
                                    <th class="px-5 py-4">Tanggal Pengisian</th>
                                </tr>
                            </thead>
                            <tbody id="responseTable" class="divide-y divide-slate-100">
                                <?php if (!$responsTerbaru): ?>
                                    <tr>
                                        <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                                            Belum ada respons tracer study.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($responsTerbaru as $row): ?>
                                        <tr class="response-row transition hover:bg-slate-50">
                                            <td class="px-5 py-4 font-medium text-slate-800">
                                                <?= e($row['nama']) ?>
                                            </td>
                                            <td class="px-5 py-4 text-slate-600">
                                                <?= e($row['tahun_kelulusan']) ?>
                                            </td>
                                            <td class="px-5 py-4 text-slate-600">
                                                <?= e($row['singkatan'] ?: '-') ?>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= e(warnaStatus($row['status_alumni'])) ?>">
                                                    <?= e(labelStatus($row['status_alumni'])) ?>
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-slate-600">
                                                <?= e(date('d M Y', strtotime($row['tgl_pengisian']))) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <footer class="pb-4 text-center text-xs text-slate-400">
                    © <?= date('Y') ?> Tracer Study SMKN 1 Sukawati
                </footer>

            </main>
        </div>
    </div>

    <script>
        const statusData = <?= json_encode(
                                array_values($statusMap),
                                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                            ) ?>;

        const tahunRows = <?= json_encode(
                                $tahunData,
                                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                            ) ?>;

        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: [
                    'Bekerja',
                    'Lanjut Kuliah',
                    'Wirausaha',
                    'Belum Bekerja'
                ],
                datasets: [{
                    data: statusData,
                    backgroundColor: [
                        '#10b981',
                        '#3b82f6',
                        '#f59e0b',
                        '#f43f5e'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        new Chart(document.getElementById('tahunChart'), {
            type: 'bar',
            data: {
                labels: tahunRows.map(row => row.tahun_kelulusan),
                datasets: [{
                    label: 'Jumlah Alumni',
                    data: tahunRows.map(row => Number(row.total)),
                    backgroundColor: '#2563eb',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>
    <script src="/asset/js/admin.js"></script>
</body>

</html>