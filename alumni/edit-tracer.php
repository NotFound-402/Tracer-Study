<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireRole('alumni');

$alumniSession = $_SESSION['user'];
$idAlumni = (int) $alumniSession['id'];

$stmt = $pdo->prepare(
    'SELECT a.id_alumni, a.nama, a.email, a.nis, a.nisn, a.tahun_kelulusan, j.nama_jurusan, j.singkatan
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

$statusOptions = ['Bekerja', 'Pengangguran', 'Lanjut Kuliah', 'Wirausaha'];
$existingTracer = null;
$detail = null;

$stmt = $pdo->prepare('SELECT * FROM tracer_study WHERE id_alumni = :id LIMIT 1');
$stmt->execute([':id' => $idAlumni]);
$existingTracer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$existingTracer) {
    header('Location: tracer-study.php');
    exit;
}

$detailTableMap = [
    'Bekerja' => 'tracer_kerja',
    'Pengangguran' => 'tracer_pengangguran',
    'Lanjut Kuliah' => 'tracer_kuliah',
    'Wirausaha' => 'tracer_wirausaha',
];

$currentStatus = $existingTracer['status_alumni'] ?? 'Bekerja';
$detailTable = $detailTableMap[$currentStatus] ?? null;
if ($detailTable) {
    $detailStmt = $pdo->prepare("SELECT * FROM {$detailTable} WHERE id_tracer = :id_tracer LIMIT 1");
    $detailStmt->execute([':id_tracer' => $existingTracer['id_tracer']]);
    $detail = $detailStmt->fetch(PDO::FETCH_ASSOC);
}

$form = [
    'status_alumni' => $currentStatus,
    'penilaian_fasilitas' => $existingTracer['penilaian_fasilitas'] ?? '',
    'penilaian_kurikulum' => $existingTracer['penilaian_kurikulum'] ?? '',
    'saran_masukan' => $existingTracer['saran_masukan'] ?? '',
    'nama_perusahaan' => $detail['nama_perusahaan'] ?? '',
    'bidang_usaha' => $detail['bidang_usaha'] ?? '',
    'jabatan' => $detail['jabatan'] ?? '',
    'status_karyawan' => $detail['status_karyawan'] ?? '',
    'pendapatan_bulanan' => $detail['pendapatan_bulanan'] ?? '',
    'keselarasan_keahlian' => $detail['keselarasan_keahlian'] ?? '',
    'waktu_tunggu_bulan' => $detail['waktu_tunggu_bulan'] ?? '',
    'nama_kampus' => $detail['nama_kampus'] ?? '',
    'prodi' => $detail['prodi'] ?? '',
    'jenjang' => $detail['jenjang'] ?? '',
    'keselarasan_jurusan' => $detail['keselarasan_jurusan'] ?? '',
    'nama_usaha' => $detail['nama_usaha'] ?? '',
    'bidang_usaha_wirausaha' => $detail['bidang_usaha'] ?? '',
    'omzet_bulanan' => $detail['omzet_bulanan'] ?? '',
    'keselarasan_keahlian_wirausaha' => $detail['keselarasan_keahlian'] ?? '',
    'kendala' => $detail['kendala'] ?? '',
    'kegiatan_sekarang' => $detail['kegiatan_sekarang'] ?? '',
];

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status_alumni'] ?? $currentStatus;
    $form['status_alumni'] = $status;
    $form['penilaian_fasilitas'] = trim((string) ($_POST['penilaian_fasilitas'] ?? ''));
    $form['penilaian_kurikulum'] = trim((string) ($_POST['penilaian_kurikulum'] ?? ''));
    $form['saran_masukan'] = trim((string) ($_POST['saran_masukan'] ?? ''));

    foreach (['nama_perusahaan', 'bidang_usaha', 'jabatan', 'status_karyawan', 'pendapatan_bulanan', 'keselarasan_keahlian', 'waktu_tunggu_bulan', 'nama_kampus', 'prodi', 'jenjang', 'keselarasan_jurusan', 'nama_usaha', 'bidang_usaha_wirausaha', 'omzet_bulanan', 'keselarasan_keahlian_wirausaha', 'kendala', 'kegiatan_sekarang'] as $field) {
        $form[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    if ($currentStatus === 'Pengangguran' && $status !== 'Pengangguran') {
        $error = 'Anda sudah mengisi tracer pengangguran. Karena itu, tracer lain tidak dapat dipilih lagi.';
    } elseif ($currentStatus !== 'Pengangguran' && $status === 'Pengangguran') {
        $error = 'Status Pengangguran tidak dapat dipilih karena Anda sudah memilih status lain (Bekerja, Lanjut Kuliah, atau Wirausaha).';
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'UPDATE tracer_study
                 SET status_alumni = :status_alumni,
                     penilaian_fasilitas = :penilaian_fasilitas,
                     penilaian_kurikulum = :penilaian_kurikulum,
                     saran_masukan = :saran_masukan,
                     tgl_pengisian = NOW()
                 WHERE id_alumni = :id_alumni'
            );
            $stmt->execute([
                ':status_alumni' => $status,
                ':penilaian_fasilitas' => $form['penilaian_fasilitas'],
                ':penilaian_kurikulum' => $form['penilaian_kurikulum'],
                ':saran_masukan' => $form['saran_masukan'],
                ':id_alumni' => $idAlumni,
            ]);

            $oldTable = $detailTableMap[$currentStatus] ?? null;
            if ($oldTable) {
                $stmt = $pdo->prepare("DELETE FROM {$oldTable} WHERE id_tracer = :id_tracer");
                $stmt->execute([':id_tracer' => $existingTracer['id_tracer']]);
            }

            $targetTable = $detailTableMap[$status] ?? null;
            if ($targetTable) {
                $stmt = $pdo->prepare("DELETE FROM {$targetTable} WHERE id_tracer = :id_tracer");
                $stmt->execute([':id_tracer' => $existingTracer['id_tracer']]);
            }

            $validateStatus = function (string $statusValue) use ($form): void {
                if ($statusValue === 'Bekerja') {
                    if ($form['nama_perusahaan'] === '' || $form['bidang_usaha'] === '' || $form['jabatan'] === '' || $form['status_karyawan'] === '') {
                        throw new InvalidArgumentException('Data pekerjaan wajib diisi lengkap.');
                    }
                }

                if ($statusValue === 'Lanjut Kuliah') {
                    if ($form['nama_kampus'] === '' || $form['prodi'] === '' || $form['jenjang'] === '') {
                        throw new InvalidArgumentException('Data pendidikan lanjutan wajib diisi lengkap.');
                    }
                }

                if ($statusValue === 'Wirausaha') {
                    if ($form['nama_usaha'] === '' || $form['bidang_usaha_wirausaha'] === '') {
                        throw new InvalidArgumentException('Data usaha wajib diisi lengkap.');
                    }
                }

                if ($statusValue === 'Pengangguran') {
                    if ($form['kendala'] === '' && $form['kegiatan_sekarang'] === '') {
                        throw new InvalidArgumentException('Isi minimal kendala atau kegiatan yang sedang dijalani.');
                    }
                }
            };

            $validateStatus($status);

            if ($status === 'Bekerja') {
                $stmt = $pdo->prepare(
                    'INSERT INTO tracer_kerja (id_tracer, nama_perusahaan, bidang_usaha, jabatan, status_karyawan, pendapatan_bulanan, keselarasan_keahlian, waktu_tunggu_bulan)
                     VALUES (:id_tracer, :nama_perusahaan, :bidang_usaha, :jabatan, :status_karyawan, :pendapatan_bulanan, :keselarasan_keahlian, :waktu_tunggu_bulan)'
                );
                $stmt->execute([
                    ':id_tracer' => $existingTracer['id_tracer'],
                    ':nama_perusahaan' => $form['nama_perusahaan'],
                    ':bidang_usaha' => $form['bidang_usaha'],
                    ':jabatan' => $form['jabatan'],
                    ':status_karyawan' => $form['status_karyawan'],
                    ':pendapatan_bulanan' => $form['pendapatan_bulanan'] !== '' ? (float) $form['pendapatan_bulanan'] : null,
                    ':keselarasan_keahlian' => $form['keselarasan_keahlian'] !== '' ? $form['keselarasan_keahlian'] : null,
                    ':waktu_tunggu_bulan' => $form['waktu_tunggu_bulan'] !== '' ? (int) $form['waktu_tunggu_bulan'] : null,
                ]);
            }

            if ($status === 'Lanjut Kuliah') {
                $stmt = $pdo->prepare(
                    'INSERT INTO tracer_kuliah (id_tracer, nama_kampus, prodi, jenjang, keselarasan_jurusan)
                     VALUES (:id_tracer, :nama_kampus, :prodi, :jenjang, :keselarasan_jurusan)'
                );
                $stmt->execute([
                    ':id_tracer' => $existingTracer['id_tracer'],
                    ':nama_kampus' => $form['nama_kampus'],
                    ':prodi' => $form['prodi'],
                    ':jenjang' => $form['jenjang'],
                    ':keselarasan_jurusan' => $form['keselarasan_jurusan'] !== '' ? $form['keselarasan_jurusan'] : null,
                ]);
            }

            if ($status === 'Wirausaha') {
                $stmt = $pdo->prepare(
                    'INSERT INTO tracer_wirausaha (id_tracer, nama_usaha, bidang_usaha, omzet_bulanan, keselarasan_keahlian)
                     VALUES (:id_tracer, :nama_usaha, :bidang_usaha, :omzet_bulanan, :keselarasan_keahlian)'
                );
                $stmt->execute([
                    ':id_tracer' => $existingTracer['id_tracer'],
                    ':nama_usaha' => $form['nama_usaha'],
                    ':bidang_usaha' => $form['bidang_usaha_wirausaha'],
                    ':omzet_bulanan' => $form['omzet_bulanan'] !== '' ? (float) $form['omzet_bulanan'] : null,
                    ':keselarasan_keahlian' => $form['keselarasan_keahlian_wirausaha'] !== '' ? $form['keselarasan_keahlian_wirausaha'] : null,
                ]);
            }

            if ($status === 'Pengangguran') {
                $stmt = $pdo->prepare(
                    'INSERT INTO tracer_pengangguran (id_tracer, kendala, kegiatan_sekarang)
                     VALUES (:id_tracer, :kendala, :kegiatan_sekarang)'
                );
                $stmt->execute([
                    ':id_tracer' => $existingTracer['id_tracer'],
                    ':kendala' => $form['kendala'] !== '' ? $form['kendala'] : null,
                    ':kegiatan_sekarang' => $form['kegiatan_sekarang'] !== '' ? $form['kegiatan_sekarang'] : null,
                ]);
            }

            $pdo->commit();
            $success = true;
            $currentStatus = $status;
            $existingTracer['status_alumni'] = $status;

            $detailStmt = $pdo->prepare("SELECT * FROM {$detailTableMap[$status]} WHERE id_tracer = :id_tracer LIMIT 1");
            $detailStmt->execute([':id_tracer' => $existingTracer['id_tracer']]);
            $detail = $detailStmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tracer Study | Alumni</title>
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

        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-navy text-white transition-transform duration-300 lg:translate-x-0 lg:w-64">
            <div class="flex items-center gap-3 border-b border-white/10 px-5 py-6">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 text-xl">🎓</div>
                <div>
                    <p class="font-bold tracking-wide">TRACER STUDY</p>
                    <p class="text-xs text-blue-200">SMKN 1 SUKAWATI</p>
                </div>
            </div>

            <nav class="flex flex-wrap gap-1 p-3 lg:flex-1 lg:flex-col">
                <p class="hidden px-3 pb-2 pt-3 text-xs font-semibold uppercase tracking-widest text-blue-200 lg:block">Menu Alumni</p>

                <a href="index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Dashboard
                </a>

                <a href="tracer-study.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Isi Tracer Study
                </a>

                <a href="riwayat.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Riwayat Saya
                </a>

                <a href="edit-tracer.php" aria-current="page" class="flex items-center gap-3 rounded-xl bg-blue-600 px-4 py-3 font-medium text-white">
                    <span></span> Edit Tracer Study
                </a>

                <a href="profil.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Profil Saya
                </a>
            </nav>

            <div class="border-t border-white/10 p-3">
                <a href="/logout.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-rose-500/20 hover:text-white">
                    <span>↪</span> Logout
                </a>
            </div>
        </aside>

        <div class="min-w-0 flex-1 lg:ml-64">
            <header class="flex min-h-[72px] items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-4 sm:px-8">
                <div class="flex items-center gap-3">
                    <button id="menuButton" class="rounded-lg p-2 text-xl hover:bg-slate-100 lg:hidden" aria-label="Buka menu" aria-expanded="false">☰</button>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-blue-600">Portal Alumni</p>
                        <h2 class="text-lg font-bold text-navy">Edit Tracer Study</h2>
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
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.12em] text-blue-600">Form Edit</p>
                            <h1 class="mt-2 text-2xl font-bold text-navy">Perbarui Hasil Tracer Study</h1>
                        </div>
                        <div class="rounded-xl bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-700">
                            Status saat ini: <?= e($currentStatus) ?>
                        </div>
                    </div>

                    <?php if (isset($_GET['locked']) && $_GET['locked'] == '1'): ?>
                        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700">
                            Anda sudah mengisi tracer pengangguran. Silakan edit data pengangguran ini dan jangan memilih status lain.
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                            Data tracer study berhasil diperbarui.
                        </div>
                    <?php elseif ($error): ?>
                        <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                            <?= e($error) ?>
                        </div>
                    <?php endif; ?>
                </section>

                <form method="POST" class="space-y-6">
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-lg font-bold text-navy">1. Status saat ini</h2>
                        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <?php foreach ($statusOptions as $option): ?>
                                <?php
                                $disabled = false;
                                if ($currentStatus === 'Pengangguran' && $option !== 'Pengangguran') {
                                    $disabled = true;
                                }
                                if ($currentStatus !== 'Pengangguran' && $option === 'Pengangguran') {
                                    $disabled = true;
                                }
                                if ($currentStatus !== 'Pengangguran' && $option === $currentStatus) {
                                    $disabled = true;
                                }
                                ?>
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition <?= $disabled ? 'cursor-not-allowed border-slate-200 bg-slate-50 opacity-70' : 'hover:border-blue-200 hover:bg-blue-50/50' ?>">
                                    <input type="radio" name="status_alumni" value="<?= e($option) ?>" <?= ($form['status_alumni'] === $option) ? 'checked' : '' ?> <?= $disabled ? 'disabled' : '' ?> class="h-4 w-4 text-blue-600">
                                    <span class="font-medium text-slate-700"><?= e($option) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">
                            <?= $currentStatus === 'Pengangguran'
                                ? 'Karena Anda sudah mengisi tracer pengangguran, form status lain dinonaktifkan sesuai alur yang diminta.'
                                : 'Status yang sudah Anda isi ditandai sebagai status aktif. Pilihan Pengangguran dinonaktifkan agar tidak bertabrakan dengan status yang sudah Anda pilih.' ?>
                        </p>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-lg font-bold text-navy">2. Penilaian</h2>
                        <div class="mt-4 grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Penilaian fasilitas sekolah</label>
                                <textarea name="penilaian_fasilitas" rows="4" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="Bagaimana penilaian Anda terhadap fasilitas sekolah? "><?= e($form['penilaian_fasilitas']) ?></textarea>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Penilaian kurikulum</label>
                                <textarea name="penilaian_kurikulum" rows="4" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="Bagaimana penilaian Anda terhadap kurikulum? "><?= e($form['penilaian_kurikulum']) ?></textarea>
                            </div>
                        </div>
                        <div class="mt-5">
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Saran dan masukan</label>
                            <textarea name="saran_masukan" rows="4" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="Saran untuk SMKN 1 Sukawati"><?= e($form['saran_masukan']) ?></textarea>
                        </div>
                    </section>

                    <section id="statusFields" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-lg font-bold text-navy">3. Detail sesuai status</h2>

                        <div data-status="Bekerja" class="status-block mt-5 hidden">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Nama perusahaan</label>
                                    <input type="text" name="nama_perusahaan" value="<?= e($form['nama_perusahaan']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="PT/Perusahaan">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Bidang usaha</label>
                                    <input type="text" name="bidang_usaha" value="<?= e($form['bidang_usaha']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="Contoh: Teknologi, Retail, Jasa">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Jabatan</label>
                                    <input type="text" name="jabatan" value="<?= e($form['jabatan']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="Staff, Supervisor, dll">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Status karyawan</label>
                                    <select name="status_karyawan" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500">
                                        <option value="">Pilih status</option>
                                        <option value="Tetap" <?= ($form['status_karyawan'] === 'Tetap') ? 'selected' : '' ?>>Tetap</option>
                                        <option value="Kontrak" <?= ($form['status_karyawan'] === 'Kontrak') ? 'selected' : '' ?>>Kontrak</option>
                                        <option value="Magang" <?= ($form['status_karyawan'] === 'Magang') ? 'selected' : '' ?>>Magang</option>
                                        <option value="Freelance" <?= ($form['status_karyawan'] === 'Freelance') ? 'selected' : '' ?>>Freelance</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Pendapatan bulanan</label>
                                    <input type="number" name="pendapatan_bulanan" value="<?= e($form['pendapatan_bulanan']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="5000000">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Waktu tunggu (bulan)</label>
                                    <input type="number" name="waktu_tunggu_bulan" value="<?= e($form['waktu_tunggu_bulan']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="0">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Keselarasan keahlian</label>
                                    <select name="keselarasan_keahlian" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500">
                                        <option value="">Pilih tingkat kesesuaian</option>
                                        <option value="Sangat Sesuai" <?= ($form['keselarasan_keahlian'] === 'Sangat Sesuai') ? 'selected' : '' ?>>Sangat Sesuai</option>
                                        <option value="Sesuai" <?= ($form['keselarasan_keahlian'] === 'Sesuai') ? 'selected' : '' ?>>Sesuai</option>
                                        <option value="Kurang Sesuai" <?= ($form['keselarasan_keahlian'] === 'Kurang Sesuai') ? 'selected' : '' ?>>Kurang Sesuai</option>
                                        <option value="Tidak Sesuai" <?= ($form['keselarasan_keahlian'] === 'Tidak Sesuai') ? 'selected' : '' ?>>Tidak Sesuai</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div data-status="Lanjut Kuliah" class="status-block mt-5 hidden">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Nama kampus</label>
                                    <input type="text" name="nama_kampus" value="<?= e($form['nama_kampus']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="Universitas/Politeknik">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Program studi</label>
                                    <input type="text" name="prodi" value="<?= e($form['prodi']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="Teknik Informatika">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Jenjang</label>
                                    <select name="jenjang" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500">
                                        <option value="">Pilih jenjang</option>
                                        <option value="D1" <?= ($form['jenjang'] === 'D1') ? 'selected' : '' ?>>D1</option>
                                        <option value="D2" <?= ($form['jenjang'] === 'D2') ? 'selected' : '' ?>>D2</option>
                                        <option value="D3" <?= ($form['jenjang'] === 'D3') ? 'selected' : '' ?>>D3</option>
                                        <option value="D4" <?= ($form['jenjang'] === 'D4') ? 'selected' : '' ?>>D4</option>
                                        <option value="S1" <?= ($form['jenjang'] === 'S1') ? 'selected' : '' ?>>S1</option>
                                        <option value="S2" <?= ($form['jenjang'] === 'S2') ? 'selected' : '' ?>>S2</option>
                                        <option value="S3" <?= ($form['jenjang'] === 'S3') ? 'selected' : '' ?>>S3</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Keselarasan jurusan</label>
                                    <select name="keselarasan_jurusan" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500">
                                        <option value="">Pilih tingkat kesesuaian</option>
                                        <option value="Sangat Sesuai" <?= ($form['keselarasan_jurusan'] === 'Sangat Sesuai') ? 'selected' : '' ?>>Sangat Sesuai</option>
                                        <option value="Sesuai" <?= ($form['keselarasan_jurusan'] === 'Sesuai') ? 'selected' : '' ?>>Sesuai</option>
                                        <option value="Kurang Sesuai" <?= ($form['keselarasan_jurusan'] === 'Kurang Sesuai') ? 'selected' : '' ?>>Kurang Sesuai</option>
                                        <option value="Tidak Sesuai" <?= ($form['keselarasan_jurusan'] === 'Tidak Sesuai') ? 'selected' : '' ?>>Tidak Sesuai</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div data-status="Wirausaha" class="status-block mt-5 hidden">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Nama usaha</label>
                                    <input type="text" name="nama_usaha" value="<?= e($form['nama_usaha']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="Usaha Anda">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Bidang usaha</label>
                                    <input type="text" name="bidang_usaha_wirausaha" value="<?= e($form['bidang_usaha_wirausaha']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="Contoh: Kuliner, Fashion, Jasa">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Omzet bulanan</label>
                                    <input type="number" name="omzet_bulanan" value="<?= e($form['omzet_bulanan']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="5000000">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Keselarasan keahlian</label>
                                    <select name="keselarasan_keahlian_wirausaha" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500">
                                        <option value="">Pilih tingkat kesesuaian</option>
                                        <option value="Sangat Sesuai" <?= ($form['keselarasan_keahlian_wirausaha'] === 'Sangat Sesuai') ? 'selected' : '' ?>>Sangat Sesuai</option>
                                        <option value="Sesuai" <?= ($form['keselarasan_keahlian_wirausaha'] === 'Sesuai') ? 'selected' : '' ?>>Sesuai</option>
                                        <option value="Kurang Sesuai" <?= ($form['keselarasan_keahlian_wirausaha'] === 'Kurang Sesuai') ? 'selected' : '' ?>>Kurang Sesuai</option>
                                        <option value="Tidak Sesuai" <?= ($form['keselarasan_keahlian_wirausaha'] === 'Tidak Sesuai') ? 'selected' : '' ?>>Tidak Sesuai</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div data-status="Pengangguran" class="status-block mt-5 hidden">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Kendala</label>
                                    <textarea name="kendala" rows="4" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="Apa kendala yang Anda hadapi? "><?= e($form['kendala']) ?></textarea>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Kegiatan saat ini</label>
                                    <textarea name="kegiatan_sekarang" rows="4" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="Kegiatan yang sedang Anda lakukan saat ini"><?= e($form['kegiatan_sekarang']) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="flex items-center justify-end gap-3">
                        <a href="riwayat.php" class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">Kembali</a>
                        <button type="submit" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/20 transition hover:bg-blue-700">
                            Simpan Perubahan
                        </button>
                    </section>
                </form>
            </main>
        </div>
    </div>

    <script src="/asset/js/admin.js"></script>
    <script>
        const statusInputs = document.querySelectorAll('input[name="status_alumni"]');
        const statusBlocks = document.querySelectorAll('.status-block');

        function updateStatusBlocks() {
            const selected = document.querySelector('input[name="status_alumni"]:checked')?.value || 'Bekerja';
            statusBlocks.forEach((block) => {
                block.classList.toggle('hidden', block.dataset.status !== selected);
            });
        }

        statusInputs.forEach((input) => input.addEventListener('change', updateStatusBlocks));
        updateStatusBlocks();
    </script>
</body>

</html>