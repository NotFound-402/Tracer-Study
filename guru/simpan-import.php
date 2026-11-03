
<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireRole('guru');

$preview = $_SESSION['import_alumni_preview'] ?? [];

if (empty($preview)) {
    die("Tidak ada data yang akan diimport.");
}

// Pastikan seluruh data sudah valid
foreach ($preview as $row) {
    if (!empty($row['errors'])) {
        die("Masih ada data yang bermasalah. Silakan upload ulang.");
    }
}

try {

    $pdo->beginTransaction();

    $sql = "
        INSERT INTO alumni (
            id_jurusan,
            email,
            password,
            nis,
            nisn,
            nama,
            tahun_kelulusan,
            alamat,
            no_tlpn
        ) VALUES (
            :jurusan,
            :email,
            :password,
            :nis,
            :nisn,
            :nama,
            :tahun,
            :alamat,
            :telepon
        )
    ";

    $stmt = $pdo->prepare($sql);

    $total = 0;

    foreach ($preview as $row) {

        // Password awal = NISN
        $password = password_hash(
            $row['nisn'],
            PASSWORD_DEFAULT
        );

        $stmt->execute([
            ':jurusan' => $row['id_jurusan'],
            ':email' => $row['email'],
            ':password' => $password,
            ':nis' => $row['nis'],
            ':nisn' => $row['nisn'],
            ':nama' => $row['nama'],
            ':tahun' => $row['tahun'],
            ':alamat' => $row['alamat'],
            ':telepon' => $row['telepon']
        ]);

        $total++;
    }

    $pdo->commit();

    unset($_SESSION['import_alumni_preview']);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    die("Import gagal. Tidak ada data yang disimpan. Periksa kembali data dan database.");
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Import Berhasil</title>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center">

    <div class="bg-white p-8 rounded-xl shadow-sm text-center max-w-md">

        <div class="text-green-600 text-5xl mb-4">
            ✓
        </div>

        <h1 class="text-2xl font-bold text-gray-800">
            Import Berhasil!
        </h1>

        <p class="text-gray-500 mt-3">
            <?= $total ?> data alumni berhasil dimasukkan ke database.
        </p>

        <a href="data-alumni.php"
           class="inline-block bg-blue-600 text-white px-5 py-2.5
                  rounded-lg mt-6">
            Kembali ke Data Alumni
        </a>

    </div>

</body>
</html>
