
<?php

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function requireLogin(): void
{
    if (empty($_SESSION['user'])) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole(string $role): void
{
    requireLogin();

    if (($_SESSION['user']['role'] ?? '') !== $role) {
        $redirects = [
            'admin' => '/admin/index.php',
            'guru' => '/guru/index.php',
            'alumni' => '/alumni/index.php',
        ];

        $target = $redirects[$_SESSION['user']['role'] ?? ''] ?? '/login.php';

        header("Location: $target");
        exit;
    }
}
