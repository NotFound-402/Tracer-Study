<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user']['role'])) {
    $redirects = [
        'admin' => '/admin/index.php',
        'guru' => '/guru/index.php',
        'alumni' => '/alumni/index.php',
    ];

    $target = $redirects[$_SESSION['user']['role']] ?? '/login.php';
    header('Location: ' . $target);
    exit;
}

$error = '';
$form = [
    'nama' => '',
    'email' => '',
    'nis' => '',
    'nisn' => '',
    'id_jurusan' => '',
    'tahun_kelulusan' => '',
    'alamat' => '',
    'no_tlpn' => '',
];

$jurusanList = $pdo->query(
    'SELECT id_jurusan, nama_jurusan, singkatan FROM jurusan ORDER BY nama_jurusan ASC'
)->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['nama'] = trim((string) ($_POST['nama'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['nis'] = trim((string) ($_POST['nis'] ?? ''));
    $form['nisn'] = trim((string) ($_POST['nisn'] ?? ''));
    $form['id_jurusan'] = trim((string) ($_POST['id_jurusan'] ?? ''));
    $form['tahun_kelulusan'] = trim((string) ($_POST['tahun_kelulusan'] ?? ''));
    $form['alamat'] = trim((string) ($_POST['alamat'] ?? ''));
    $form['no_tlpn'] = trim((string) ($_POST['no_tlpn'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $requiredFields = [
        'Nama lengkap' => $form['nama'],
        'Email' => $form['email'],
        'NIS' => $form['nis'],
        'NISN' => $form['nisn'],
        'Jurusan' => $form['id_jurusan'],
        'Tahun kelulusan' => $form['tahun_kelulusan'],
        'Password' => $password,
    ];

    $missing = '';
    foreach ($requiredFields as $label => $value) {
        if ($value === '') {
            $missing = $label;
            break;
        }
    }

    if ($missing !== '') {
        $error = $missing . ' wajib diisi.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $checks = [
            'email' => ['query' => 'SELECT 1 FROM alumni WHERE email = :value LIMIT 1', 'value' => $form['email']],
            'nis' => ['query' => 'SELECT 1 FROM alumni WHERE nis = :value LIMIT 1', 'value' => $form['nis']],
            'nisn' => ['query' => 'SELECT 1 FROM alumni WHERE nisn = :value LIMIT 1', 'value' => $form['nisn']],
        ];

        foreach ($checks as $key => $check) {
            $stmt = $pdo->prepare($check['query']);
            $stmt->execute([':value' => $check['value']]);

            if ($stmt->fetch()) {
                $error = ucfirst($key) . ' sudah terdaftar. Gunakan data lain.';
                break;
            }
        }

        if ($error === '') {
            $insert = $pdo->prepare(
                'INSERT INTO alumni (id_jurusan, email, password, nis, nisn, nama, tahun_kelulusan, alamat, no_tlpn)
                VALUES (:id_jurusan, :email, :password, :nis, :nisn, :nama, :tahun_kelulusan, :alamat, :no_tlpn)'
            );

            $insert->execute([
                ':id_jurusan' => (int) $form['id_jurusan'],
                ':email' => $form['email'],
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':nis' => $form['nis'],
                ':nisn' => $form['nisn'],
                ':nama' => $form['nama'],
                ':tahun_kelulusan' => (int) $form['tahun_kelulusan'],
                ':alamat' => $form['alamat'],
                ':no_tlpn' => $form['no_tlpn'],
            ]);

            header('Location: /login.php?registered=1');
            exit;
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
    <title>Daftar Alumni | Tracer Study</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/asset/css/register.css">
</head>
<body>
    <div class="page-shell">
        <div class="register-card">
            <aside class="brand-panel">
                <div>
                    <div class="brand-badge">
                        <span class="brand-mark">🎓</span>
                        <span>TRACER STUDY</span>
                    </div>

                    <h1>Daftar sebagai Alumni</h1>
                    <p>Isi data diri Anda untuk masuk ke sistem dan ikut berpartisipasi dalam tracer study.</p>
                </div>

                <div class="brand-features">
                    <div class="feature-item"><span>✅</span> Data alumni terintegrasi dengan sekolah</div>
                    <div class="feature-item"><span>📊</span> Ikut mengisi tracer study secara mudah</div>
                    <div class="feature-item"><span>🔐</span> Akun aman dan terverifikasi</div>
                </div>
            </aside>

            <main class="form-panel">
                <div class="header">
                    <small>Registrasi</small>
                    <h2>Buat akun alumni</h2>
                    <p>Lengkapi data berikut untuk membuat akun.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">⚠️ <?php echo e($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="register.php" novalidate>
                    <div class="form-grid">
                        <div class="field full">
                            <label for="nama">Nama lengkap</label>
                            <input id="nama" type="text" name="nama" value="<?php echo e($form['nama']); ?>" placeholder="Masukkan nama lengkap" required>
                        </div>

                        <div class="field">
                            <label for="email">Email</label>
                            <input id="email" type="email" name="email" value="<?php echo e($form['email']); ?>" placeholder="contoh@email.com" required>
                        </div>

                        <div class="field">
                            <label for="no_tlpn">Nomor telepon</label>
                            <input id="no_tlpn" type="text" name="no_tlpn" value="<?php echo e($form['no_tlpn']); ?>" placeholder="08xxxxxxxxxx">
                        </div>

                        <div class="field">
                            <label for="nis">NIS</label>
                            <input id="nis" type="text" name="nis" value="<?php echo e($form['nis']); ?>" placeholder="Masukkan NIS" required>
                        </div>

                        <div class="field">
                            <label for="nisn">NISN</label>
                            <input id="nisn" type="text" name="nisn" value="<?php echo e($form['nisn']); ?>" placeholder="Masukkan NISN" required>
                        </div>

                        <div class="field">
                            <label for="id_jurusan">Jurusan</label>
                            <select id="id_jurusan" name="id_jurusan" required>
                                <option value="">Pilih jurusan</option>
                                <?php foreach ($jurusanList as $jurusan): ?>
                                    <option value="<?php echo e($jurusan['id_jurusan']); ?>" <?php echo ($form['id_jurusan'] == $jurusan['id_jurusan']) ? 'selected' : ''; ?>>
                                        <?php echo e($jurusan['nama_jurusan']); ?> (<?php echo e($jurusan['singkatan']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="tahun_kelulusan">Tahun kelulusan</label>
                            <input id="tahun_kelulusan" type="number" min="2000" max="2100" name="tahun_kelulusan" value="<?php echo e($form['tahun_kelulusan']); ?>" placeholder="2024" required>
                        </div>

                        <div class="field full">
                            <label for="alamat">Alamat</label>
                            <textarea id="alamat" name="alamat" placeholder="Masukkan alamat lengkap"><?php echo e($form['alamat']); ?></textarea>
                        </div>

                        <div class="field">
                            <label for="password">Password</label>
                            <input id="password" type="password" name="password" placeholder="Minimal 6 karakter" required>
                        </div>

                        <div class="field">
                            <label for="confirm_password">Konfirmasi password</label>
                            <input id="confirm_password" type="password" name="confirm_password" placeholder="Ulangi password" required>
                        </div>
                    </div>

                    <div class="submit-wrap">
                        <button type="submit" class="submit-btn">Daftar Sekarang</button>
                        <div class="login-link">
                            Sudah punya akun? <a href="login.php">Masuk di sini</a>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>
</body>
</html>
