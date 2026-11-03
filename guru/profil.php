<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireRole('guru');

$guruId = (int) ($_SESSION['user']['id'] ?? 0);
$message = '';
$messageType = 'success';

$stmt = $pdo->prepare('SELECT * FROM guru WHERE id_guru = :id LIMIT 1');
$stmt->execute([':id' => $guruId]);
$guru = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$guru) {
    http_response_code(404);
    exit('Data guru tidak ditemukan.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim((string) ($_POST['nama'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $noTlpn = trim((string) ($_POST['no_tlpn'] ?? ''));
    $passwordBaru = trim((string) ($_POST['password_baru'] ?? ''));

    if ($nama === '' || $email === '') {
        $message = 'Nama dan email wajib diisi.';
        $messageType = 'error';
    } else {
        $emailCheck = $pdo->prepare('SELECT id_guru FROM guru WHERE email = :email AND id_guru != :id LIMIT 1');
        $emailCheck->execute([':email' => $email, ':id' => $guruId]);
        if ($emailCheck->fetch()) {
            $message = 'Email sudah digunakan oleh guru lain.';
            $messageType = 'error';
        } else {
            $sql = 'UPDATE guru SET nama = :nama, email = :email, no_tlpn = :no_tlpn';
            $params = [
                ':nama' => $nama,
                ':email' => $email,
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
                $sql .= ' WHERE id_guru = :id';
                $params[':id'] = $guruId;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                $_SESSION['user']['name'] = $nama;
                $_SESSION['user']['email'] = $email;

                $stmt = $pdo->prepare('SELECT * FROM guru WHERE id_guru = :id LIMIT 1');
                $stmt->execute([':id' => $guruId]);
                $guru = $stmt->fetch(PDO::FETCH_ASSOC);

                $message = 'Profil berhasil diperbarui.';
                $messageType = 'success';
            }
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profil Guru | Tracer Study</title>
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
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Laporan
                </a>

                <a href="profil.php"
                    class="flex items-center gap-3 rounded-xl bg-blue-600 px-4 py-3 font-medium text-white">
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
                    <h2 class="text-lg font-bold text-navy">Profil Saya</h2>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold"><?= e($guru['nama']) ?></p>
                    <p class="text-xs text-slate-500">Guru</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">
                    <?= e(strtoupper(substr($guru['nama'], 0, 1))) ?>
                </div>
            </div>
        </header>

        <main class="space-y-6 p-4 sm:p-8">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.12em] text-blue-600">Akun Guru</p>
                        <h1 class="mt-2 text-2xl font-bold text-navy">Edit Profil</h1>
                    </div>
                    <div class="rounded-xl bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700">NIP: <?= e($guru['nip']) ?></div>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="mt-5 rounded-xl border px-4 py-3 text-sm font-medium <?= $messageType === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' ?>">
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>
            </section>

            <form method="POST" class="space-y-6">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama lengkap</label>
                            <input type="text" name="nama" value="<?= e($guru['nama']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">NIP</label>
                            <input type="text" value="<?= e($guru['nip']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500" readonly>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                            <input type="email" name="email" value="<?= e($guru['email']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nomor telepon</label>
                            <input type="text" name="no_tlpn" value="<?= e($guru['no_tlpn']) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="08xxx">
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Password baru</label>
                            <input type="password" name="password_baru" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" placeholder="Kosongkan jika tidak ingin diubah">
                        </div>
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
