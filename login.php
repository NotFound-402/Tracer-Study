<?php
session_start();
require_once __DIR__ . '/config/database.php';

function verifyPasswordCompat($password, $storedHash)
{
    if ($storedHash === null || $storedHash === '') {
        return false;
    }

    if (password_get_info($storedHash)['algo'] !== 0 || (isset($storedHash[0]) && $storedHash[0] === '$')) {
        return password_verify($password, $storedHash);
    }

    return hash_equals((string) $storedHash, (string) $password)
        || hash_equals((string) $storedHash, md5($password))
        || hash_equals((string) $storedHash, sha1($password));
}

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
$role = 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'admin';
    $identifier = $role === 'alumni'
        ? trim((string) ($_POST['nisn'] ?? ''))
        : trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($identifier === '' || $password === '') {
        $error = $role === 'alumni'
            ? 'NISN dan password wajib diisi.'
            : 'Email dan password wajib diisi.';
    } else {
        $tables = [
            'admin' => ['table' => 'admin', 'id' => 'id_admin', 'name' => 'nama'],
            'guru' => ['table' => 'guru', 'id' => 'id_guru', 'name' => 'nama'],
            'alumni' => ['table' => 'alumni', 'id' => 'id_alumni', 'name' => 'nama'],
        ];

        $table = $tables[$role]['table'] ?? null;

        if ($table) {
            if ($role === 'alumni') {
                $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE nisn = :identifier LIMIT 1");
                $stmt->execute([':identifier' => $identifier]);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $identifier]);
            }

            $user = $stmt->fetch();

            if ($user && verifyPasswordCompat($password, $user['password'] ?? '')) {
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id' => $user[$tables[$role]['id']],
                    'role' => $role,
                    'name' => $user[$tables[$role]['name']],
                    'email' => $user['email'],
                ];

                $redirects = [
                    'admin' => '/admin/index.php',
                    'guru' => '/guru/index.php',
                    'alumni' => '/alumni/index.php',
                ];

                header('Location: ' . ($redirects[$role] ?? '/index.php'));
                exit;
            }
        }

        $error = $role === 'alumni'
            ? 'NISN atau password salah.'
            : 'Email atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracer Study | Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/asset/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="login-body">
    <div class="login-shell">
        <aside class="login-visual">
            <div class="login-brand">
                <span class="brand-mark"></span>
                <div>
                    <strong>TRACER STUDY</strong>
                    <small>SMKN 1 SUKAWATI</small>
                </div>
            </div>

            <div class="login-slogan">Melacak Jejak <br> Meraih Masa Depan</div>

            <div class="login-hero-copy">
                <h1>Tracer Study<br>SMKN 1 Sukawati</h1>
                <p>Sistem pendataan alumni dan pelacakan lulusan untuk membangun masa depan yang lebih baik.</p>
            </div>

            <div class="login-metrics">
                <div>
                    <span>📊</span>
                    <div>
                        <strong>Data Alumni</strong>
                        <small>Terintegrasi</small>
                    </div>
                </div>
                <div>
                    <span>🧭</span>
                    <div>
                        <strong>Informasi</strong>
                        <small>Akurat</small>
                    </div>
                </div>
                <div>
                    <span>🎯</span>
                    <div>
                        <strong>Pengembangan</strong>
                        <small>Karier</small>
                    </div>
                </div>
            </div>
        </aside>

        <main class="login-panel">
            <div class="login-header">
                <div class="mini-logo">
                    <span>🏫</span>
                </div>
                <div class="title-group">
                    <h2>SMKN 1 SUKAWATI</h2>
                    <p>Tracer Study Alumni</p>
                </div>
            </div>

            <div class="welcome-text">
                <h3>Selamat Datang <span>👋</span></h3>
                <p>Silakan pilih jenis akun untuk masuk ke sistem.</p>
            </div>

            <form method="POST" action="login.php" class="login-form">
                <div class="role-grid" role="tablist" aria-label="Pilih role login">
                    <button type="button" class="role-card active" data-role="admin">
                        <span class="role-icon"><i data-lucide="shield-check"></i></span>
                        <span>Admin</span>
                    </button>
                    <button type="button" class="role-card" data-role="guru">
                        <span class="role-icon"><i data-lucide="book-open"></i></span>
                        <span>Guru</span>
                    </button>
                    <button type="button" class="role-card" data-role="alumni">
                        <span class="role-icon"><i data-lucide="user-circle-2"></i></span>
                        <span>Alumni</span>
                    </button>
                </div>

                <input type="hidden" name="role" id="selectedRole" value="admin">

                <?php if (isset($_GET['registered']) && $_GET['registered'] === '1'): ?>
                    <div class="error-box" style="background: rgba(24, 160, 88, 0.08); border-color: rgba(24, 160, 88, 0.18); color: #0f7d46;">
                        Pendaftaran berhasil. Silakan login dengan akun alumni Anda.
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="error-box"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="field-group" id="login-identifier-group">
                    <label for="loginIdentifier"><?php echo $role === 'alumni' ? 'NISN' : 'Email ' . ucfirst($role); ?></label>
                    <div class="input-wrap">
                        <span class="input-icon"><i data-lucide="<?php echo $role === 'alumni' ? 'badge-check' : 'mail'; ?>"></i></span>
                        <input
                            id="loginIdentifier"
                            type="<?php echo $role === 'alumni' ? 'text' : 'email'; ?>"
                            name="<?php echo $role === 'alumni' ? 'nisn' : 'email'; ?>"
                            placeholder="<?php echo $role === 'alumni' ? 'Masukkan NISN' : 'Masukkan email ' . $role; ?>"
                            value="<?php echo htmlspecialchars($role === 'alumni' ? ($_POST['nisn'] ?? '') : ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            required>
                    </div>
                </div>

                <div class="field-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <span class="input-icon"><i data-lucide="lock"></i></span>
                        <input id="password" type="password" name="password" placeholder="Masukkan password" required>
                        <button type="button" class="toggle-password" aria-label="Tampilkan password">
                            <i data-lucide="eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Masuk sebagai <?php echo ucfirst($role); ?> <span>→</span></button>

                <div class="helper-row">
                    <span class="helper-icon"><i data-lucide="check-circle"></i></span>
                    <span>Lupa password? Hubungi administrator</span>
                </div>

                <div class="helper-row" style="margin-top: 12px;">
                    <span>Belum punya akun?</span>
                    <a href="register.php" style="color: #2563eb; font-weight: 700; text-decoration: none;">Daftar alumni</a>
                </div>

            </form>
        </main>
    </div>

    <script>
        const roleButtons = document.querySelectorAll('.role-card');
        const selectedRoleInput = document.getElementById('selectedRole');
        const loginIdentifierInput = document.getElementById('loginIdentifier');
        const loginIdentifierLabel = document.querySelector('label[for="loginIdentifier"]');
        const loginIdentifierIcon = document.querySelector('#login-identifier-group .input-icon i');
        const passwordButton = document.querySelector('.toggle-password');
        const passwordInput = document.getElementById('password');
        const submitButton = document.querySelector('.submit-btn');
        const roleLabels = {
            admin: 'Admin',
            guru: 'Guru',
            alumni: 'Alumni'
        };

        function syncRole(role) {
            roleButtons.forEach((button) => {
                const isActive = button.dataset.role === role;
                button.classList.toggle('active', isActive);
            });

            const isAlumni = role === 'alumni';
            selectedRoleInput.value = role;
            const label = roleLabels[role] || 'Admin';
            submitButton.innerHTML = 'Masuk sebagai ' + label + ' <span>→</span>';

            loginIdentifierLabel.textContent = isAlumni ? 'NISN' : 'Email ' + label;
            loginIdentifierInput.name = isAlumni ? 'nisn' : 'email';
            loginIdentifierInput.type = isAlumni ? 'text' : 'email';
            loginIdentifierInput.placeholder = isAlumni ? 'Masukkan NISN' : 'Masukkan email ' + role;

            if (loginIdentifierIcon) {
                loginIdentifierIcon.setAttribute('data-lucide', isAlumni ? 'badge-check' : 'mail');
                if (window.lucide) {
                    lucide.createIcons();
                }
            }
        }

        roleButtons.forEach((button) => {
            button.addEventListener('click', () => {
                syncRole(button.dataset.role);
            });
        });

        if (passwordButton && passwordInput) {
            passwordButton.addEventListener('click', () => {
                const show = passwordInput.type === 'password';
                passwordInput.type = show ? 'text' : 'password';
                passwordButton.innerHTML = show ? '<i data-lucide="eye-off"></i>' : '<i data-lucide="eye"></i>';
                if (window.lucide) {
                    lucide.createIcons();
                }
            });
        }

        if (window.lucide) {
            lucide.createIcons();
        }
    </script>
</body>

</html>