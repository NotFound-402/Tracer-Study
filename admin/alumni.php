<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

$admin = $_SESSION['user'];

$search = trim((string) ($_GET['q'] ?? ''));
$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = '(a.nama LIKE :q OR a.nis LIKE :q OR a.nisn LIKE :q OR j.nama_jurusan LIKE :q)';
    $params[':q'] = '%' . $search . '%';
}

$whereSql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

$sql = "
    SELECT a.id_alumni, a.nama, a.nis, a.nisn, a.tahun_kelulusan, a.email, a.no_tlpn,
           j.nama_jurusan, j.singkatan, ts.status_alumni, ts.tgl_pengisian
    FROM alumni a
    LEFT JOIN jurusan j ON j.id_jurusan = a.id_jurusan
    LEFT JOIN tracer_study ts ON ts.id_alumni = a.id_alumni
    {$whereSql}
    ORDER BY a.nama ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alumni = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalAlumni = (int) $pdo->query('SELECT COUNT(*) FROM alumni')->fetchColumn();
$totalRespons = (int) $pdo->query('SELECT COUNT(*) FROM tracer_study')->fetchColumn();
$belumMengisi = max(0, $totalAlumni - $totalRespons);

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function statusBadge(string $status): string
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
    <title>Data Alumni | Admin</title>
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
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
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
                    <h2 class="text-lg font-bold text-navy">Data Alumni</h2>
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
            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Total Alumni</p>
                    <p class="mt-3 text-3xl font-bold text-navy"><?= number_format($totalAlumni, 0, ',', '.') ?></p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Sudah Mengisi</p>
                    <p class="mt-3 text-3xl font-bold text-navy"><?= number_format($totalRespons, 0, ',', '.') ?></p>
                </div>
                <div class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Belum Mengisi</p>
                    <p class="mt-3 text-3xl font-bold text-navy"><?= number_format($belumMengisi, 0, ',', '.') ?></p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-navy">Daftar Alumni</h2>
                        <p class="text-sm text-slate-500">Data alumni dan status tracer study</p>
                    </div>
                    <form method="GET" class="w-full max-w-md">
                        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari nama, NIS, NISN, jurusan..." class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500">
                    </form>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">NIS</th>
                                <th class="px-4 py-3">NISN</th>
                                <th class="px-4 py-3">Jurusan</th>
                                <th class="px-4 py-3">Tahun Lulus</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!$alumni): ?>
                                <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">Tidak ada data alumni.</td></tr>
                            <?php else: ?>
                                <?php foreach ($alumni as $item): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-medium text-slate-800"><?= e($item['nama']) ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= e($item['nis']) ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= e($item['nisn'] ?: '-') ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= e($item['nama_jurusan'] ?: '-') ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= e($item['tahun_kelulusan'] ?: '-') ?></td>
                                        <td class="px-4 py-3">
                                            <?php if (!empty($item['status_alumni'])): ?>
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= e(statusBadge($item['status_alumni'])) ?>"><?= e($item['status_alumni']) ?></span>
                                            <?php else: ?>
                                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">Belum Isi</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600"><?= e($item['tgl_pengisian'] ? date('d M Y', strtotime($item['tgl_pengisian'])) : '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>
<script src="/asset/js/admin.js"></script>
</body>
</html>
