
<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireRole('alumni');

$alumniSession = $_SESSION['user'];
$idAlumni = (int) $alumniSession['id'];

// Ambil data alumni yang sedang login
$stmt = $pdo->prepare("
    SELECT
        a.id_alumni,
        a.nama,
        a.email,
        a.nis,
        a.tahun_kelulusan,
        j.nama_jurusan,
        j.singkatan
    FROM alumni a
    LEFT JOIN jurusan j ON j.id_jurusan = a.id_jurusan
    WHERE a.id_alumni = :id
    LIMIT 1
");
$stmt->execute(['id' => $idAlumni]);
$alumni = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumni) {
    http_response_code(404);
    exit('Data alumni tidak ditemukan. Hubungi administrator.');
}

// Cek apakah alumni sudah mengisi tracer study
$stmt = $pdo->prepare("
    SELECT id_tracer, status_alumni, tgl_pengisian
    FROM tracer_study
    WHERE id_alumni = :id
    LIMIT 1
");
$stmt->execute(['id' => $idAlumni]);
$tracer = $stmt->fetch(PDO::FETCH_ASSOC);
$sudahMengisi = (bool) $tracer;

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Alumni | Tracer Study SMKN 1 Sukawati</title>

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

    <!-- Sidebar -->
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-navy text-white transition-transform duration-300 lg:translate-x-0 lg:w-64">
        <div class="flex items-center gap-3 border-b border-white/10 px-5 py-6">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10">
                🎓
            </div>
            <div>
                <p class="font-bold tracking-wide">TRACER STUDY</p>
                <p class="text-xs text-blue-200">SMKN 1 SUKAWATI</p>
            </div>
        </div>

        <nav class="flex flex-wrap gap-1 p-3 lg:flex-1 lg:flex-col">
            <p class="hidden px-3 pb-2 pt-3 text-xs font-semibold uppercase tracking-widest text-blue-200 lg:block">
                Menu Alumni
            </p>

            <a href="index.php"
               aria-current="page"
               class="flex items-center gap-3 rounded-xl bg-blue-600 px-4 py-3 font-medium text-white">
                <span></span> Dashboard
            </a>

            <a href="tracer-study.php"
               class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                <span></span> Isi Tracer Study
            </a>

            <a href="riwayat.php"
               class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                <span></span> Riwayat Saya
            </a>

            <?php if ($sudahMengisi): ?>
            <a href="edit-tracer.php"
               class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                <span></span> Edit Tracer Study
            </a>
            <?php endif; ?>
            
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

    <!-- Konten utama -->
    <div class="min-w-0 flex-1 lg:ml-64">

        <!-- Header -->
        <header class="flex min-h-[72px] items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-4 sm:px-8">
            <div class="flex items-center gap-3">
                <button id="menuButton"
                        class="rounded-lg p-2 text-xl hover:bg-slate-100 lg:hidden"
                        aria-label="Buka menu"
                        aria-expanded="false">☰</button>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-blue-600">
                        Portal Alumni
                    </p>
                    <h2 class="text-lg font-bold text-navy">Dashboard Saya</h2>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold">
                        <?= e($alumni['nama']) ?>
                    </p>
                    <p class="text-xs text-slate-500">Alumni</p>
                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">
                    <?= e(strtoupper(substr($alumni['nama'], 0, 1))) ?>
                </div>
            </div>
        </header>

        <main class="space-y-6 p-4 sm:p-8">

            <!-- Sambutan -->
            <section>
                <p class="mb-2 text-sm font-semibold text-blue-600">
                    PORTAL ALUMNI SMKN 1 SUKAWATI
                </p>

                <h1 class="text-2xl font-bold tracking-tight text-navy sm:text-3xl">
                    Halo, <?= e($alumni['nama']) ?>! 👋
                </h1>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Yuk, bagikan perjalananmu setelah lulus untuk membantu
                    sekolah meningkatkan kualitas pendidikan.
                </p>
            </section>

            <!-- Status tracer study -->
            <section class="rounded-2xl border bg-white p-5 shadow-sm sm:p-7
                <?= $sudahMengisi ? 'border-emerald-200' : 'border-blue-200' ?>">

                <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl
                            <?= $sudahMengisi ? 'bg-emerald-50' : 'bg-blue-50' ?>">
                            <span class="text-2xl">
                                <?= $sudahMengisi ? '✅' : '📋' ?>
                            </span>
                        </div>

                        <div>
                            <h2 class="text-lg font-bold text-navy">
                                <?= $sudahMengisi
                                    ? 'Tracer Study Sudah Diisi'
                                    : 'Lengkapi Tracer Study Kamu' ?>
                            </h2>

                            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">
                                <?= $sudahMengisi
                                    ? 'Terima kasih sudah berpartisipasi. Kamu bisa melihat status dan jawaban tracer study milikmu.'
                                    : 'Ceritakan kegiatanmu setelah lulus. Pengisian ini membantu sekolah mengevaluasi relevansi pendidikan dengan dunia kerja dan kuliah.' ?>
                            </p>

                            <div class="mt-3">
                                <?php if ($sudahMengisi): ?>
                                    <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        Sudah Mengisi
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                        Belum Mengisi
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <a href="<?= $sudahMengisi ? 'riwayat.php' : 'tracer-study.php' ?>"
                       class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold text-white transition
                       <?= $sudahMengisi
                           ? 'bg-emerald-600 hover:bg-emerald-700'
                           : 'bg-blue-600 hover:bg-blue-700' ?>">
                        <?= $sudahMengisi
                            ? 'Lihat Jawaban Saya'
                            : 'Mulai Isi Tracer Study' ?>
                        <span>→</span>
                    </a>
                </div>

                <?php if ($sudahMengisi): ?>
                    <div class="mt-5 border-t border-slate-100 pt-4 text-sm text-slate-500">
                        Status setelah lulus:
                        <span class="font-semibold text-slate-800">
                            <?= e(labelStatus($tracer['status_alumni'])) ?>
                        </span>

                        <span class="mx-2">•</span>

                        Diisi pada:
                        <span class="font-medium text-slate-800">
                            <?= e(date(
                                'd M Y',
                                strtotime($tracer['tgl_pengisian'])
                            )) ?>
                        </span>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Data kelulusan -->
            <section>
                <div class="mb-4">
                    <h2 class="text-lg font-bold text-navy">Data Kelulusan Saya</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Informasi akun dan kelulusan yang terdaftar di sistem.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Nama Lengkap</p>
                        <p class="mt-2 break-words font-semibold text-navy">
                            <?= e($alumni['nama']) ?>
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Tahun Kelulusan</p>
                        <p class="mt-2 text-xl font-bold text-navy">
                            <?= e($alumni['tahun_kelulusan']) ?>
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Jurusan</p>
                        <p class="mt-2 font-semibold text-navy">
                            <?= e($alumni['nama_jurusan'] ?: '-') ?>
                            <?php if (!empty($alumni['singkatan'])): ?>
                                <span class="text-sm font-normal text-slate-500">
                                    (<?= e($alumni['singkatan']) ?>)
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2 xl:col-span-3">
                        <p class="text-sm text-slate-500">Email Akun</p>
                        <p class="mt-2 break-all font-medium text-navy">
                            <?= e($alumni['email']) ?>
                        </p>
                    </div>

                </div>

                <div class="mt-4 flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4">
                    <span class="text-lg">ℹ️</span>
                    <p class="text-sm leading-6 text-blue-900">
                        Pastikan data kelulusanmu benar. Jika ada data yang
                        tidak sesuai, hubungi administrator sekolah.
                    </p>
                </div>
            </section>

            <!-- Panduan -->
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="font-bold text-navy">Panduan Pengisian</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Ikuti langkah sederhana berikut.
                </p>

                <div class="mt-5 grid gap-5 sm:grid-cols-3">
                    <div class="flex gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">
                            1
                        </div>
                        <div>
                            <p class="font-semibold text-navy">Pilih status</p>
                            <p class="mt-1 text-sm leading-5 text-slate-500">
                                Tentukan kegiatanmu setelah lulus.
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">
                            2
                        </div>
                        <div>
                            <p class="font-semibold text-navy">Lengkapi data</p>
                            <p class="mt-1 text-sm leading-5 text-slate-500">
                                Isi pertanyaan sesuai kondisi sebenarnya.
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">
                            3
                        </div>
                        <div>
                            <p class="font-semibold text-navy">Kirim jawaban</p>
                            <p class="mt-1 text-sm leading-5 text-slate-500">
                                Periksa jawaban sebelum mengirim.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <footer class="pb-4 text-center text-xs text-slate-400">
                © <?= date('Y') ?> Tracer Study SMKN 1 Sukawati
            </footer>

        </main>
    </div>
</div>

<script src="/asset/js/admin.js"></script>
</body>
</html>
                                