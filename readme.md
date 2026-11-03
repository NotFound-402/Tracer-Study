# Tracer Study SMKN 1 Sukawati

Aplikasi web untuk pendataan dan monitoring alumni SMKN 1 Sukawati melalui program Tracer Study. Sistem ini memungkinkan alumni mengisi data setelah lulus, serta admin/guru melihat hasil survei, laporan, dan statistik perkembangan lulusan.

## Fitur Utama

- Form tracer study alumni
- Login untuk alumni, guru, dan admin
- Dashboard statistik lulusan
- Manajemen data alumni dan jurusan
- Laporan dan analisis status pekerjaan, kuliah, dan wirausaha
- Import data alumni (untuk kebutuhan operasional sekolah)
- Pengelolaan profil dan riwayat pengisian tracer study

## Teknologi yang Digunakan

- PHP
- MySQL
- Tailwind CSS
- JavaScript
- Composer
- PhpSpreadsheet

## Struktur Project

```text
Tracer-Study/
├── admin/                  # Halaman admin
├── alumni/                 # Halaman alumni
├── asset/                  # CSS, JS, gambar, dan aset frontend
├── config/                 # Konfigurasi aplikasi dan database
├── guru/                   # Halaman guru
├── templates/              # Template umum (jika digunakan)
├── vendor/                 # Dependensi Composer
├── composer.json           # Konfigurasi Composer
├── index.php               # Landing page utama
├── login.php               # Halaman login
├── register.php            # Registrasi akun
├── logout.php              # Logout
├── readme.md               # Dokumentasi project
└── ...
```

## Persyaratan

Sebelum menjalankan aplikasi, pastikan perangkat Anda sudah memiliki:

- PHP 8.x
- MySQL / MariaDB
- Composer
- Web server lokal seperti XAMPP, Laragon, atau Apache

## Setup dan Instalasi

1. Clone atau unduh project ini ke folder web server Anda.
2. Masuk ke direktori project.
3. Jalankan perintah berikut untuk menginstal dependency:

```bash
composer install
```

4. Buat database MySQL baru dengan nama:

```text
db_tracer_study
```

5. Pastikan konfigurasi database sudah sesuai di file:

- [config/database.php](config/database.php)

Default konfigurasi yang dipakai pada project ini adalah:

```php
$host = '127.0.0.1';
$dbname = 'db_tracer_study';
$username = 'root';
$password = '';
```

6. Jalankan aplikasi di browser dengan URL lokal, misalnya:

```text
http://localhost/Tracer-Study/
```

## Cara Penggunaan

### 1. Halaman Utama
- Akses [index.php](index.php)
- Halaman ini menampilkan profil sekolah, statistik alumni, dan informasi tracer study

### 2. Login Alumni
- Akses [login.php](login.php)
- Alumni dapat login dan mengisi tracer study serta melihat riwayat pengisiannya

### 3. Area Admin/Guru
- Admin dan guru dapat mengelola data alumni, laporan, dan statistik dari folder [admin/](admin/) dan [guru/](guru/)

## Role Akses

- Admin: mengelola seluruh data sistem
- Guru: melihat dan mengelola data alumni serta laporan
- Alumni: mengisi tracer study dan melihat profil/riwayat sendiri

## Catatan Penting

- Pastikan database MySQL aktif sebelum mengakses aplikasi.
- Jika terjadi error koneksi database, cek file konfigurasi di [config/database.php](config/database.php).
- Periksa juga apakah table yang dibutuhkan sudah dibuat di database.

## Informasi Project

Project ini dibuat untuk mendukung kegiatan pengumpulan data lulusan SMKN 1 Sukawati agar dapat digunakan untuk evaluasi mutu pendidikan dan kebutuhan pengembangan sekolah.

## Developer Note

Jika Anda ingin mengembangkan lebih lanjut, project ini masih bisa dikembangkan dengan menambahkan:

- fitur export laporan PDF/Excel
- validasi form yang lebih ketat
- dashboard interaktif dengan grafik yang lebih lengkap
- sistem role dan permission yang lebih rapi

---

Semoga project ini bermanfaat untuk kebutuhan tracer study sekolah.
