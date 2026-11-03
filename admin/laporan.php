<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

$admin = $_SESSION['user'];

$totalAlumni = (int) $pdo->query('SELECT COUNT(*) FROM alumni')->fetchColumn();
$totalRespons = (int) $pdo->query('SELECT COUNT(*) FROM tracer_study')->fetchColumn();
$partisipasi = $totalAlumni > 0 ? round(($totalRespons / $totalAlumni) * 100, 1) : 0;

$statusRows = $pdo->query('SELECT status_alumni, COUNT(*) AS total FROM tracer_study GROUP BY status_alumni ORDER BY total DESC')->fetchAll(PDO::FETCH_ASSOC);
$statusMap = [
    'Bekerja' => 0,
    'Lanjut Kuliah' => 0,
    'Wirausaha' => 0,
    'Pengangguran' => 0,
];
foreach ($statusRows as $row) {
    if (isset($statusMap[$row['status_alumni']])) {
        $statusMap[$row['status_alumni']] = (int) $row['total'];
    }
}

$yearRows = $pdo->query('SELECT tahun_kelulusan, COUNT(*) AS total FROM alumni GROUP BY tahun_kelulusan ORDER BY tahun_kelulusan ASC')->fetchAll(PDO::FETCH_ASSOC);
$yearLabels = array_map(static fn ($row): string => (string) $row['tahun_kelulusan'], $yearRows);
$yearValues = array_map(static fn ($row): int => (int) $row['total'], $yearRows);

$topCompanies = $pdo->query(
    'SELECT k.nama_perusahaan, COUNT(*) AS total
     FROM tracer_kerja k
     JOIN tracer_study ts ON ts.id_tracer = k.id_tracer
     GROUP BY k.nama_perusahaan
     ORDER BY total DESC, k.nama_perusahaan ASC
     LIMIT 5'
)->fetchAll(PDO::FETCH_ASSOC);

$topCampuses = $pdo->query(
    'SELECT pk.nama_kampus, COUNT(*) AS total
     FROM tracer_kuliah pk
     JOIN tracer_study ts ON ts.id_tracer = pk.id_tracer
     GROUP BY pk.nama_kampus
     ORDER BY total DESC, pk.nama_kampus ASC
     LIMIT 5'
)->fetchAll(PDO::FETCH_ASSOC);

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function badge($status): string
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
    <title>Laporan | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
<body class="min-h-screen bg-slate-50 text-slate-800">
<div class="min-h-screen lg:flex">
    <div id="sidebarOverlay" class="fixed inset-0 z-30 hidden bg-slate-900/40 lg:hidden"></div>

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
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Data Alumni
                </a>

                <a href="tracer-study.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Data Tracer Study
                </a>

                <a href="jurusan.php"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Data Jurusan
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
                    class="flex items-center gap-3 rounded-xl bg-blue-600 px-4 py-3 font-medium shadow-lg shadow-blue-950/20">
                    <span></span> Laporan
                </a>
            </nav>

            <div class="border-t border-white/10 p-3">

                <a href="../logout.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-rose-500/20 hover:text-white">
                    <span>↪</span> Logout
                </a>
            </div>
        </aside>

    <div class="min-w-0 flex-1 lg:ml-64">
        <header class="flex min-h-[72px] items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-4 sm:px-8">
            <div class="flex items-center gap-3">
                <button id="menuButton" class="rounded-lg p-2 text-xl hover:bg-slate-100 lg:hidden" aria-label="Buka menu" aria-expanded="false">☰</button>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-blue-600">Portal Admin</p>
                    <h2 class="text-lg font-bold text-navy">Laporan</h2>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold"><?= e($admin['name'] ?? 'Admin') ?></p>
                    <p class="text-xs text-slate-500">Admin</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">
                    <?= e(strtoupper(substr($admin['name'] ?? 'A', 0, 1))) ?>
                </div>
            </div>
        </header>

        <main class="space-y-6 p-4 sm:p-8">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Total Alumni</p>
                    <p class="mt-3 text-3xl font-bold text-navy"><?= number_format($totalAlumni, 0, ',', '.') ?></p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Sudah Mengisi</p>
                    <p class="mt-3 text-3xl font-bold text-navy"><?= number_format($totalRespons, 0, ',', '.') ?></p>
                </div>
                <div class="rounded-2xl border border-violet-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Partisipasi</p>
                    <p class="mt-3 text-3xl font-bold text-navy"><?= number_format($partisipasi, 1, ',', '.') ?>%</p>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Bekerja</p>
                    <p class="mt-3 text-3xl font-bold text-navy"><?= number_format($statusMap['Bekerja'], 0, ',', '.') ?></p>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-1">
                    <div class="mb-5">
                        <h2 class="font-bold text-navy">Status Alumni</h2>
                        <p class="mt-1 text-sm text-slate-500">Komposisi berdasarkan hasil tracer study</p>
                    </div>
                    <div class="mx-auto max-w-xs">
                        <canvas id="statusChart" aria-label="Grafik status alumni"></canvas>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                    <div class="mb-5">
                        <h2 class="font-bold text-navy">Alumni per Tahun Kelulusan</h2>
                        <p class="mt-1 text-sm text-slate-500">Distribusi alumni dari masing-masing angkatan</p>
                    </div>
                    <div class="h-72">
                        <canvas id="yearChart" aria-label="Grafik alumni per tahun kelulusan"></canvas>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-navy">Komposisi Status</h2>
                    <div class="mt-5 space-y-4">
                        <?php foreach (['Bekerja', 'Lanjut Kuliah', 'Wirausaha', 'Pengangguran'] as $status): ?>
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="inline-flex items-center gap-2 font-medium text-slate-700">
                                        <span class="inline-block h-2.5 w-2.5 rounded-full <?= $status === 'Bekerja' ? 'bg-emerald-500' : ($status === 'Lanjut Kuliah' ? 'bg-blue-500' : ($status === 'Wirausaha' ? 'bg-amber-500' : 'bg-rose-500')) ?>"></span>
                                        <?= e($status) ?>
                                    </span>
                                    <span class="font-semibold text-slate-600"><?= number_format($statusMap[$status], 0, ',', '.') ?></span>
                                </div>
                                <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full <?= $status === 'Bekerja' ? 'bg-emerald-500' : ($status === 'Lanjut Kuliah' ? 'bg-blue-500' : ($status === 'Wirausaha' ? 'bg-amber-500' : 'bg-rose-500')) ?>" style="width: <?= $totalRespons > 0 ? min(100, ($statusMap[$status] / $totalRespons) * 100) : 0 ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-navy">Perusahaan Terbanyak</h2>
                    <div class="mt-5 space-y-3">
                        <?php if (!$topCompanies): ?>
                            <p class="text-sm text-slate-500">Belum ada data perusahaan.</p>
                        <?php else: ?>
                            <?php foreach ($topCompanies as $index => $company): ?>
                                <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700"><?= $index + 1 ?></span>
                                        <span class="font-medium text-slate-700"><?= e($company['nama_perusahaan'] ?: '-') ?></span>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-600"><?= (int) $company['total'] ?> alumni</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-navy">Kampus Favorit</h2>
                    <div class="mt-5 space-y-3">
                        <?php if (!$topCampuses): ?>
                            <p class="text-sm text-slate-500">Belum ada data kampus.</p>
                        <?php else: ?>
                            <?php foreach ($topCampuses as $index => $campus): ?>
                                <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700"><?= $index + 1 ?></span>
                                        <span class="font-medium text-slate-700"><?= e($campus['nama_kampus'] ?: '-') ?></span>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-600"><?= (int) $campus['total'] ?> alumni</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-navy">Ringkasan Status</h2>
                    <div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach (['Bekerja', 'Lanjut Kuliah', 'Wirausaha', 'Pengangguran'] as $status): ?>
                                    <tr>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= e(badge($status)) ?>"><?= e($status) ?></span>
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-slate-700"><?= number_format($statusMap[$status], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>

<script>
    const statusChart = new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Bekerja', 'Lanjut Kuliah', 'Wirausaha', 'Pengangguran'],
            datasets: [{
                data: [<?= (int) $statusMap['Bekerja'] ?>, <?= (int) $statusMap['Lanjut Kuliah'] ?>, <?= (int) $statusMap['Wirausaha'] ?>, <?= (int) $statusMap['Pengangguran'] ?>],
                backgroundColor: ['#10B981', '#3B82F6', '#F59E0B', '#F87171'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            }
        }
    });

    const yearChart = new Chart(document.getElementById('yearChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($yearLabels) ?>,
            datasets: [{
                label: 'Jumlah Alumni',
                data: <?= json_encode($yearValues) ?>,
                backgroundColor: '#2563EB',
                borderRadius: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
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
