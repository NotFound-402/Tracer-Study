<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireRole('admin');

$admin = $_SESSION['user'];
$message = '';
$messageType = 'success';
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save') {
            $nama = trim((string) ($_POST['nama_jurusan'] ?? ''));
            $singkatan = trim((string) ($_POST['singkatan'] ?? ''));

            if ($nama === '' || $singkatan === '') {
                throw new InvalidArgumentException('Nama jurusan dan singkatan wajib diisi.');
            }

            $pdo->prepare('INSERT INTO jurusan (nama_jurusan, singkatan) VALUES (:nama, :singkatan)')
                ->execute([
                    ':nama' => $nama,
                    ':singkatan' => strtoupper($singkatan),
                ]);

            $message = 'Jurusan baru berhasil ditambahkan.';
        } elseif ($action === 'update') {
            $id = (int) ($_POST['id_jurusan'] ?? 0);
            $nama = trim((string) ($_POST['nama_jurusan'] ?? ''));
            $singkatan = trim((string) ($_POST['singkatan'] ?? ''));

            if ($id <= 0 || $nama === '' || $singkatan === '') {
                throw new InvalidArgumentException('Data jurusan tidak valid.');
            }

            $pdo->prepare('UPDATE jurusan SET nama_jurusan = :nama, singkatan = :singkatan WHERE id_jurusan = :id')
                ->execute([
                    ':nama' => $nama,
                    ':singkatan' => strtoupper($singkatan),
                    ':id' => $id,
                ]);

            $message = 'Data jurusan berhasil diperbarui.';
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id_jurusan'] ?? 0);
            if ($id <= 0) {
                throw new InvalidArgumentException('Jurusan tidak valid.');
            }

            $pdo->prepare('DELETE FROM jurusan WHERE id_jurusan = :id')->execute([':id' => $id]);
            $message = 'Jurusan berhasil dihapus.';
        }
    } catch (Throwable $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

$jurusan = $pdo->query(
    'SELECT j.*, COUNT(a.id_alumni) AS total_alumni
     FROM jurusan j
     LEFT JOIN alumni a ON a.id_jurusan = j.id_jurusan
     GROUP BY j.id_jurusan, j.nama_jurusan, j.singkatan
     ORDER BY j.nama_jurusan ASC'
)->fetchAll(PDO::FETCH_ASSOC);

if ($editId > 0) {
    $editJurusan = $pdo->prepare('SELECT * FROM jurusan WHERE id_jurusan = :id LIMIT 1');
    $editJurusan->execute([':id' => $editId]);
    $editJurusan = $editJurusan->fetch(PDO::FETCH_ASSOC);
} else {
    $editJurusan = null;
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
    <title>Data Jurusan | Admin</title>
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
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-200 transition hover:bg-white/10">
                    <span></span> Data Tracer Study
                </a>

                <a href="jurusan.php"
                    class="flex items-center gap-3 rounded-xl bg-blue-600 px-4 py-3 font-medium shadow-lg shadow-blue-950/20">
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
                        <h2 class="text-lg font-bold text-navy">Data Jurusan</h2>
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
                <?php if ($message): ?>
                    <div class="rounded-xl border px-4 py-3 text-sm <?= $messageType === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' ?>">
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-lg font-bold text-navy"><?= $editJurusan ? 'Edit Jurusan' : 'Tambah Jurusan Baru' ?></h2>
                    <form method="POST" class="mt-4 grid gap-4 md:grid-cols-3">
                        <?php if ($editJurusan): ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id_jurusan" value="<?= (int) $editJurusan['id_jurusan'] ?>">
                        <?php else: ?>
                            <input type="hidden" name="action" value="save">
                        <?php endif; ?>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Jurusan</label>
                            <input type="text" name="nama_jurusan" value="<?= e($editJurusan['nama_jurusan'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Singkatan</label>
                            <input type="text" name="singkatan" value="<?= e($editJurusan['singkatan'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm uppercase outline-none focus:border-blue-500" maxlength="10" required>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">
                                <?= $editJurusan ? 'Simpan Perubahan' : 'Tambah Jurusan' ?>
                            </button>
                        </div>
                    </form>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="mb-4">
                        <h2 class="text-lg font-bold text-navy">Daftar Jurusan</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Nama</th>
                                    <th class="px-4 py-3">Singkatan</th>
                                    <th class="px-4 py-3">Alumni</th>
                                    <th class="px-4 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (!$jurusan): ?>
                                    <tr>
                                        <td colspan="4" class="px-4 py-10 text-center text-slate-500">Belum ada data jurusan.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jurusan as $item): ?>
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-3 font-medium text-slate-800"><?= e($item['nama_jurusan']) ?></td>
                                            <td class="px-4 py-3 text-slate-600"><?= e($item['singkatan']) ?></td>
                                            <td class="px-4 py-3 text-slate-600"><?= (int) $item['total_alumni'] ?></td>
                                            <td class="px-4 py-3">
                                                <div class="flex gap-2">
                                                    <a href="?edit=<?= (int) $item['id_jurusan'] ?>" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700">Edit</a>
                                                    <form method="POST" onsubmit="return confirm('Hapus jurusan ini?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id_jurusan" value="<?= (int) $item['id_jurusan'] ?>">
                                                        <button type="submit" class="rounded-lg bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700">Hapus</button>
                                                    </form>
                                                </div>
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