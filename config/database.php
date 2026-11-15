<?php

// Konfigurasi koneksi database untuk aplikasi Tracer Study.
// Nilai ini digunakan agar PHP dapat terhubung ke database MySQL lokal.
$host = '127.0.0.1';
$dbname = 'db_tracer_study';
$username = 'root';
$password = '';

// DSN (Data Source Name) berisi informasi host, nama database, dan charset.
$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

// Opsi PDO untuk keamanan dan konsistensi hasil query.
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Membuat koneksi database dan menyimpannya ke variabel $pdo.
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Jika koneksi gagal, catat pesan error dan hentikan aplikasi.
    error_log($e->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi MySQL.');
}
