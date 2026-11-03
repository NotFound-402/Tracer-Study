<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

$admin = $_SESSION['user'];
$statusFilter = $_GET['status'] ?? 'all';
$search = trim((string) ($_GET['q'] ?? ''));

$where = [];
$params = [];

if ($statusFilter !== 'all' && in_array($statusFilter, ['Bekerja', 'Pengangguran', 'Lanjut Kuliah', 'Wirausaha'], true)) {
    $where[] = 'ts.status_alumni = :status';
    $params[':status'] = $statusFilter;
}

if ($search !== '') {
    $where[] = '(a.nama LIKE :search OR a.nis LIKE :search OR a.nisn LIKE :search OR j.nama_jurusan LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT ts.id_tracer, a.nama, a.nis, a.nisn, a.tahun_kelulusan, j.nama_jurusan,
           ts.status_alumni, ts.tgl_pengisian, ts.penilaian_fasilitas, ts.penilaian_kurikulum, ts.saran_masukan
    FROM tracer_study ts
    JOIN alumni a ON a.id_alumni = ts.id_alumni
    LEFT JOIN jurusan j ON j.id_jurusan = a.id_jurusan
    {$whereSql}
    ORDER BY ts.tgl_pengisian DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Data Tracer Study | Admin</title>
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
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Data Alumni
                </a>

                <a href="tracer-study.php"
                    class="flex items-center gap-3 rounded-xl bg-blue-600 px-4 py-3 font-medium shadow-lg shadow-blue-950/20">
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
                    <h2 class="text-lg font-bold text-navy">Data Tracer Study</h2>
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
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-navy">Respons Tracer Study</h1>
                        <p class="mt-1 text-sm text-slate-500">Status dan masukan alumni berdasarkan pengisian survey</p>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <form method="GET" class="flex gap-2">
                            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari alumni..." class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm sm:w-56">
                            <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Cari</button>
                        </form>
                        <form method="GET" class="flex gap-2">
                            <select name="status" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Semua status</option>
                                <option value="Bekerja" <?= $statusFilter === 'Bekerja' ? 'selected' : '' ?>>Bekerja</option>
                                <option value="Pengangguran" <?= $statusFilter === 'Pengangguran' ? 'selected' : '' ?>>Pengangguran</option>
                                <option value="Lanjut Kuliah" <?= $statusFilter === 'Lanjut Kuliah' ? 'selected' : '' ?>>Lanjut Kuliah</option>
                                <option value="Wirausaha" <?= $statusFilter === 'Wirausaha' ? 'selected' : '' ?>>Wirausaha</option>
                            </select>
                            <button type="submit" class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Filter</button>
                        </form>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Jurusan</th>
                                <th class="px-4 py-3">Tahun Lulus</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Penilaian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!$responses): ?>
                                <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Belum ada data tracer study yang sesuai filter.</td></tr>
                            <?php else: ?>
                                <?php foreach ($responses as $row): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-slate-800"><?= e($row['nama']) ?></div>
                                            <div class="text-xs text-slate-500"><?= e($row['nis'] ?: '-') ?></div>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600"><?= e($row['nama_jurusan'] ?: '-') ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= e($row['tahun_kelulusan'] ?: '-') ?></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= e(badge($row['status_alumni'])) ?>"><?= e($row['status_alumni']) ?></span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600"><?= e(date('d M Y', strtotime($row['tgl_pengisian']))) ?></td>
                                        <td class="px-4 py-3 text-slate-600">
                                            <div><?= e($row['penilaian_fasilitas'] ?: 'Belum ada penilaian') ?></div>
                                            <div class="mt-1 text-xs text-slate-500"><?= e($row['penilaian_kurikulum'] ?: 'Belum ada penilaian kurikulum') ?></div>
                                        </td>
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
