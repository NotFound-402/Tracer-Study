<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireRole('alumni');

$alumniSession = $_SESSION['user'];
$idAlumni = (int) $alumniSession['id'];

$stmt = $pdo->prepare(
    'SELECT a.id_alumni, a.nama, a.email, a.nis, a.tahun_kelulusan, j.nama_jurusan
     FROM alumni a
     LEFT JOIN jurusan j ON j.id_jurusan = a.id_jurusan
     WHERE a.id_alumni = :id LIMIT 1'
);
$stmt->execute([':id' => $idAlumni]);
$alumni = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumni) {
    http_response_code(404);
    exit('Data alumni tidak ditemukan.');
}

$stmt = $pdo->prepare('SELECT * FROM tracer_study WHERE id_alumni = :id LIMIT 1');
$stmt->execute([':id' => $idAlumni]);
$tracer = $stmt->fetch(PDO::FETCH_ASSOC);

$detail = null;
if ($tracer) {
    $status = $tracer['status_alumni'];

    $tableMap = [
        'Bekerja' => 'tracer_kerja',
        'Pengangguran' => 'tracer_pengangguran',
        'Lanjut Kuliah' => 'tracer_kuliah',
        'Wirausaha' => 'tracer_wirausaha',
    ];

    $table = $tableMap[$status] ?? null;
    if ($table) {
        $detailStmt = $pdo->prepare("SELECT * FROM {$table} WHERE id_tracer = :id_tracer LIMIT 1");
        $detailStmt->execute([':id_tracer' => $tracer['id_tracer']]);
        $detail = $detailStmt->fetch(PDO::FETCH_ASSOC);
    }
}

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatRupiah($value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Alumni | Tracer Study</title>
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
           class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-navy text-white transition-transform duration-300 lg:translate-x-0 lg:w-64">
        <div class="flex items-center gap-3 border-b border-white/10 px-5 py-6">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 text-xl">🎓</div>
            <div>
                <p class="font-bold tracking-wide">TRACER STUDY</p>
                <p class="text-xs text-blue-200">SMKN 1 SUKAWATI</p>
            </div>
        </div>

        <nav class="flex flex-1 flex-col gap-1 p-3">
            <p class="px-3 pb-2 pt-3 text-xs font-semibold uppercase tracking-widest text-blue-200">Menu Alumni</p>

            <a href="index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                <span>▦</span> Dashboard
            </a>
            <a href="tracer-study.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                <span>▤</span> Isi Tracer Study
            </a>
            <a href="riwayat.php" class="flex items-center gap-3 rounded-xl bg-blue-600 px-4 py-3 font-medium text-white">
                <span>▧</span> Riwayat Saya
            </a>
            <a href="profil.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                <span>♙</span> Profil Saya
            </a>

            <div class="mt-auto border-t border-white/10 pt-3">
                <a href="/logout.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-rose-500/20 hover:text-white">
                    <span>↪</span> Logout
                </a>
            </div>
        </nav>
    </aside>

    <div class="min-w-0 flex-1 lg:ml-64">
        <header class="flex min-h-[72px] items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-4 sm:px-8">
            <div class="flex items-center gap-3">
                <button id="menuButton" class="rounded-lg p-2 text-xl hover:bg-slate-100 lg:hidden" aria-label="Buka menu" aria-expanded="false">☰</button>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-blue-600">Portal Alumni</p>
                    <h2 class="text-lg font-bold text-navy">Riwayat Saya</h2>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold"><?= e($alumni['nama']) ?></p>
                    <p class="text-xs text-slate-500">Alumni</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">
                    <?= e(strtoupper(substr($alumni['nama'], 0, 1))) ?>
                </div>
            </div>
        </header>

        <main class="space-y-6 p-4 sm:p-8">
            <?php if (isset($_GET['locked']) && $_GET['locked'] == '1'): ?>
                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-700">
                    Tracer study Anda sudah terisi. Form pengisian tidak bisa dibuka kembali.
                </section>
            <?php endif; ?>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.12em] text-blue-600">Laporan Alumni</p>
                        <h1 class="mt-2 text-2xl font-bold text-navy">Riwayat Tracer Study</h1>
                    </div>
                    <div class="rounded-xl bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700">
                        <?= $tracer ? 'Terdata' : 'Belum ada data' ?>
                    </div>
                </div>
            </section>

            <?php if (!$tracer): ?>
                <section class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
                    <p class="text-lg font-semibold text-slate-700">Belum ada riwayat tracer study.</p>
                    <p class="mt-2 text-sm text-slate-500">Silakan isi form tracer study terlebih dahulu untuk melihat riwayat Anda.</p>
                    <a href="tracer-study.php" class="mt-5 inline-block rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">Isi Tracer Study</a>
                </section>
            <?php else: ?>
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.1em] text-slate-500">Status</p>
                            <p class="mt-2 text-xl font-bold text-navy"><?= e($tracer['status_alumni']) ?></p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.1em] text-slate-500">Tanggal pengisian</p>
                            <p class="mt-2 text-xl font-bold text-navy"><?= e(date('d-m-Y', strtotime($tracer['tgl_pengisian']))) ?></p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.1em] text-slate-500">NIS</p>
                            <p class="mt-2 text-xl font-bold text-navy"><?= e($alumni['nis']) ?></p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.1em] text-slate-500">Tahun lulus</p>
                            <p class="mt-2 text-xl font-bold text-navy"><?= e($alumni['tahun_kelulusan']) ?></p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-lg font-bold text-navy">Penilaian & Masukan</h2>
                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-700">Penilaian fasilitas sekolah</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600"><?= e($tracer['penilaian_fasilitas'] ?: 'Belum diisi') ?></p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-700">Penilaian kurikulum</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600"><?= e($tracer['penilaian_kurikulum'] ?: 'Belum diisi') ?></p>
                        </div>
                    </div>
                    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-700">Saran dan masukan</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600"><?= e($tracer['saran_masukan'] ?: 'Belum diisi') ?></p>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-lg font-bold text-navy">Detail Status: <?= e($tracer['status_alumni']) ?></h2>
                    <div class="mt-5">
                        <?php if ($tracer['status_alumni'] === 'Bekerja' && $detail): ?>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Nama perusahaan</p><p class="mt-2 font-semibold text-slate-800"><?= e($detail['nama_perusahaan']) ?></p></div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Bidang usaha</p><p class="mt-2 font-semibold text-slate-800"><?= e($detail['bidang_usaha'] ?: '-') ?></p></div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Jabatan</p><p class="mt-2 font-semibold text-slate-800"><?= e($detail['jabatan'] ?: '-') ?></p></div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Status karyawan</p><p class="mt-2 font-semibold text-slate-800"><?= e($detail['status_karyawan'] ?: '-') ?></p></div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Pendapatan bulanan</p><p class="mt-2 font-semibold text-slate-800"><?= formatRupiah($detail['pendapatan_bulanan']) ?></p></div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Waktu tunggu</p><p class="mt-2 font-semibold text-slate-800"><?= e($detail['waktu_tunggu_bulan'] ?: '-') ?> bulan</p></div>
                                <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Keselarasan keahlian</p><p class="mt-2 font-semibold text-slate-800"><?= e($detail['keselarasan_keahlian'] ?: '-') ?></p></div>
                            </div>
                        <?php elseif ($tracer['status_alumni'] === 'Lanjut Kuliah' && $detail): ?>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Nama kampus</p><p class="mt-2 font-semibold text-slate-800"><?= e($detail['nama_kampus']) ?></p></div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Program studi</p><p class="mt-2 font-semibold text-slate-800"><?= e($detail['prodi'] ?: '-') ?></p></div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Jenjang</p><p class="mt-2 font-semibold text-slate-800"><?= e($detail['jenjang'] ?: '-') ?></p></div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Keselarasan jurusan</p><p class="mt-2 font-semibold text-slate-800"><?= e($detail['keselarasan_jurusan'] ?: '-') ?></p></div>
                            </div>
                        <?php elseif ($tracer['status_alumni'] === 'Wirausaha' && $detail): ?>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Nama usaha</p><p class="mt-2 font-semibold text-slate-800"><?= e($detail['nama_usaha']) ?></p></div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Bidang usaha</p><p class="mt-2 font-semibold text-slate-800"><?= e($detail['bidang_usaha'] ?: '-') ?></p></div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Omzet bulanan</p><p class="mt-2 font-semibold text-slate-800"><?= formatRupiah($detail['omzet_bulanan']) ?></p></div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Keselarasan keahlian</p><p class="mt-2 font-semibold text-slate-800"><?= e($detail['keselarasan_keahlian'] ?: '-') ?></p></div>
                            </div>
                        <?php elseif ($tracer['status_alumni'] === 'Pengangguran' && $detail): ?>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Kendala</p><p class="mt-2 text-sm leading-6 text-slate-700"><?= e($detail['kendala'] ?: 'Belum diisi') ?></p></div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.1em] text-slate-500">Kegiatan saat ini</p><p class="mt-2 text-sm leading-6 text-slate-700"><?= e($detail['kegiatan_sekarang'] ?: 'Belum diisi') ?></p></div>
                            </div>
                        <?php else: ?>
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
                                Detail status belum tersedia untuk data ini.
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="/asset/js/admin.js"></script>
</body>
</html>
