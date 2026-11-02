<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireRole('alumni');

$alumniSession = $_SESSION['user'];
$idAlumni = (int) $alumniSession['id'];

$jurusanStmt = $pdo->query('SELECT id_jurusan, nama_jurusan, singkatan FROM jurusan ORDER BY nama_jurusan ASC');
$jurusanList = $jurusanStmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    'SELECT a.*, j.nama_jurusan, j.singkatan
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

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim((string) ($_POST['nama'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $nis = trim((string) ($_POST['nis'] ?? ''));
    $nisn = trim((string) ($_POST['nisn'] ?? ''));
    $tahunKelulusan = trim((string) ($_POST['tahun_kelulusan'] ?? ''));
    $idJurusan = (int) ($_POST['id_jurusan'] ?? 0);
    $alamat = trim((string) ($_POST['alamat'] ?? ''));
    $noTlpn = trim((string) ($_POST['no_tlpn'] ?? ''));
    $passwordBaru = trim((string) ($_POST['password_baru'] ?? ''));

    if ($nama === '' || $email === '' || $nis === '' || $tahunKelulusan === '' || $idJurusan <= 0) {
        $message = 'Nama, email, NIS, tahun kelulusan, dan jurusan wajib diisi.';
        $messageType = 'error';
    } else {
        $emailCheck = $pdo->prepare('SELECT id_alumni FROM alumni WHERE email = :email AND id_alumni != :id LIMIT 1');
        $emailCheck->execute([':email' => $email, ':id' => $idAlumni]);
        if ($emailCheck->fetch()) {
            $message = 'Email sudah digunakan oleh alumni lain.';
            $messageType = 'error';
        } else {
            $nisCheck = $pdo->prepare('SELECT id_alumni FROM alumni WHERE nis = :nis AND id_alumni != :id LIMIT 1');
            $nisCheck->execute([':nis' => $nis, ':id' => $idAlumni]);
            if ($nisCheck->fetch()) {
                $message = 'NIS sudah digunakan.';
                $messageType = 'error';
            } else {
                if ($nisn !== '') {
                    $nisnCheck = $pdo->prepare('SELECT id_alumni FROM alumni WHERE nisn = :nisn AND id_alumni != :id LIMIT 1');
                    $nisnCheck->execute([':nisn' => $nisn, ':id' => $idAlumni]);
                    if ($nisnCheck->fetch()) {
                        $message = 'NISN sudah digunakan oleh alumni lain.';
                        $messageType = 'error';
                    }
                }
            }
        }
    }

    if ($message === '') {
        $sql = 'UPDATE alumni SET
                    id_jurusan = :id_jurusan,
                    email = :email,
                    nis = :nis,
                    nisn = :nisn,
                    nama = :nama,
                    tahun_kelulusan = :tahun_kelulusan,
                    alamat = :alamat,
                    no_tlpn = :no_tlpn';
        $params = [
            ':id_jurusan' => $idJurusan,
            ':email' => $email,
            ':nis' => $nis,
            ':nisn' => $nisn !== '' ? $nisn : null,
            ':nama' => $nama,
            ':tahun_kelulusan' => $tahunKelulusan,
            ':alamat' => $alamat !== '' ? $alamat : null,
            ':no_tlpn' => $noTlpn !== '' ? $noTlpn : null,
        ];

        if ($passwordBaru !== '') {
            if (strlen($passwordBaru) < 6) {
                $message = 'Password baru minimal 6 karakter.';
                $messageType = 'error';
            } else {
                $sql .= ', password = :password';
                $params[':password'] = password_hash($passwordBaru, PASSWORD_DEFAULT);
            }
        }

        if ($message === '') {
            $sql .= ' WHERE id_alumni = :id_alumni';
            $params[':id_alumni'] = $idAlumni;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $stmt = $pdo->prepare(
                'SELECT a.*, j.nama_jurusan, j.singkatan FROM alumni a LEFT JOIN jurusan j ON j.id_jurusan = a.id_jurusan WHERE a.id_alumni = :id LIMIT 1'
            );
            $stmt->execute([':id' => $idAlumni]);
            $alumni = $stmt->fetch(PDO::FETCH_ASSOC);

            $message = 'Profil berhasil diperbarui.';
            $messageType = 'success';
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
    <title>Profil Alumni | Tracer Study</title>
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
            <a href="riwayat.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                <span>▧</span> Riwayat Saya
            </a>
            <a href="profil.php" class="flex items-center gap-3 rounded-xl bg-blue-600 px-4 py-3 font-medium text-white">
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
                    <h2 class="text-lg font-bold text-navy">Profil Saya</h2>
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
                        <p class="text-sm font-semibold uppercase tracking-[0.12em] text-blue-600">Informasi Akun</p>
                        <h1 class="mt-2 text-2xl font-bold text-navy">Edit Profil Alumni</h1>
                    </div>
                    <div class="rounded-xl bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700">
                        <?= e($alumni['nama_jurusan'] ?: 'Jurusan belum diatur') ?>
                    </div>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="mt-5 rounded-xl border px-4 py-3 text-sm font-medium <?= $messageType === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' ?>">
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>
            </section>

            <form method="POST" class="space-y-6">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-lg font-bold text-navy">Data Pribadi</h2>

                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama lengkap</label>
                            <input type="text" name="nama" value="<?= e($alumni['nama']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                            <input type="email" name="email" value="<?= e($alumni['email']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">NIS</label>
                            <input type="text" name="nis" value="<?= e($alumni['nis']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">NISN</label>
                            <input type="text" name="nisn" value="<?= e($alumni['nisn']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="Opsional">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Tahun kelulusan</label>
                            <input type="number" name="tahun_kelulusan" min="2000" max="2100" value="<?= e($alumni['tahun_kelulusan']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Jurusan</label>
                            <select name="id_jurusan" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" required>
                                <option value="">Pilih jurusan</option>
                                <?php foreach ($jurusanList as $jur): ?>
                                    <option value="<?= (int) $jur['id_jurusan'] ?>" <?= ((int) $alumni['id_jurusan'] === (int) $jur['id_jurusan']) ? 'selected' : '' ?>><?= e($jur['nama_jurusan']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-lg font-bold text-navy">Kontak & Keamanan</h2>
                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nomor telepon</label>
                            <input type="text" name="no_tlpn" value="<?= e($alumni['no_tlpn']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="08xxxxxxxxxx">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Password baru</label>
                            <input type="password" name="password_baru" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="Kosongkan jika tidak ingin diubah">
                        </div>
                    </div>
                    <div class="mt-5">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Alamat</label>
                        <textarea name="alamat" rows="4" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="Alamat rumah atau domisili"><?= e($alumni['alamat']) ?></textarea>
                    </div>
                </section>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/20 transition hover:bg-blue-700">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>

<script src="/asset/js/admin.js"></script>
</body>
</html>
