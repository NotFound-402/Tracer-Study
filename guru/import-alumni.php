
<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

requireRole('guru');

use PhpOffice\PhpSpreadsheet\IOFactory;

$preview = [];
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_FILES['file_excel']) ||
        $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK
    ) {
        $errors[] = "File Excel gagal diupload.";
    } else {

        $file = $_FILES['file_excel'];

        $extension = strtolower(
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );

        if (!in_array($extension, ['xlsx', 'xls'])) {
            $errors[] = "Format file harus XLSX atau XLS.";
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = "Ukuran file maksimal 5 MB.";
        } else {

            try {

                $spreadsheet = IOFactory::load($file['tmp_name']);
                $sheet = $spreadsheet->getActiveSheet();

                $highestRow = $sheet->getHighestDataRow();

                // Ambil daftar jurusan
                $jurusanQuery = $pdo->query(
                    "SELECT id_jurusan, singkatan FROM jurusan"
                );

                $jurusanList = [];

                foreach ($jurusanQuery->fetchAll(PDO::FETCH_ASSOC) as $j) {
                    $jurusanList[strtoupper(trim($j['singkatan']))]
                        = $j['id_jurusan'];
                }

                // Ambil data alumni yang sudah terdaftar
                $existing = $pdo->query(
                    "SELECT nis, nisn, email FROM alumni"
                )->fetchAll(PDO::FETCH_ASSOC);

                $usedNis = [];
                $usedNisn = [];
                $usedEmail = [];

                foreach ($existing as $item) {
                    $usedNis[$item['nis']] = true;

                    if (!empty($item['nisn'])) {
                        $usedNisn[$item['nisn']] = true;
                    }

                    $usedEmail[strtolower($item['email'])] = true;
                }

                // Baca Excel mulai baris kedua
                for ($i = 2; $i <= $highestRow; $i++) {

                    $nis = trim((string) $sheet->getCell("A$i")->getFormattedValue());
                    $nisn = trim((string) $sheet->getCell("B$i")->getFormattedValue());
                    $nama = trim((string) $sheet->getCell("C$i")->getValue());
                    $email = strtolower(trim((string) $sheet->getCell("D$i")->getValue()));
                    $tahun = trim((string) $sheet->getCell("E$i")->getFormattedValue());
                    $jurusan = strtoupper(trim((string) $sheet->getCell("F$i")->getValue()));
                    $alamat = trim((string) $sheet->getCell("G$i")->getValue());
                    $telepon = trim((string) $sheet->getCell("H$i")->getValue());

                    // Lewati baris kosong
                    if (
                        $nis === '' && $nisn === '' &&
                        $nama === '' && $email === '' &&
                        $tahun === '' && $jurusan === ''
                    ) {
                        continue;
                    }

                    $rowErrors = [];

                    // Validasi data wajib
                    if ($nis === '') {
                        $rowErrors[] = "NIS kosong";
                    }

                    if ($nisn === '') {
                        $rowErrors[] = "NISN kosong";
                    }

                    if ($nama === '') {
                        $rowErrors[] = "Nama kosong";
                    }

                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $rowErrors[] = "Email tidak valid";
                    }

                    if (
                        !preg_match('/^\d{4}$/', $tahun) ||
                        (int)$tahun < 1900 ||
                        (int)$tahun > 2100
                    ) {
                        $rowErrors[] = "Tahun lulus tidak valid";
                    }

                    // Validasi jurusan
                    if (!isset($jurusanList[$jurusan])) {
                        $rowErrors[] = "Jurusan tidak ditemukan";
                        $id_jurusan = null;
                    } else {
                        $id_jurusan = $jurusanList[$jurusan];
                    }

                    // Validasi duplikasi
                    if (isset($usedNis[$nis])) {
                        $rowErrors[] = "NIS sudah digunakan";
                    }

                    if (isset($usedNisn[$nisn])) {
                        $rowErrors[] = "NISN sudah digunakan";
                    }

                    if (isset($usedEmail[$email])) {
                        $rowErrors[] = "Email sudah digunakan";
                    }

                    // Tandai juga duplikasi dalam file yang sama
                    $usedNis[$nis] = true;
                    $usedNisn[$nisn] = true;
                    $usedEmail[$email] = true;

                    $preview[] = [
                        'nis' => $nis,
                        'nisn' => $nisn,
                        'nama' => $nama,
                        'email' => $email,
                        'tahun' => $tahun,
                        'jurusan' => $jurusan,
                        'id_jurusan' => $id_jurusan,
                        'alamat' => $alamat,
                        'telepon' => $telepon,
                        'errors' => $rowErrors
                    ];
                }

                $spreadsheet->disconnectWorksheets();

                if (empty($preview)) {
                    $errors[] = "Tidak ditemukan data alumni dalam Excel.";
                }

                foreach ($preview as $row) {
                    if (!empty($row['errors'])) {
                        $errors[] = "Ada data yang perlu diperbaiki.";
                        break;
                    }
                }

                // Simpan preview sementara di session
                $_SESSION['import_alumni_preview'] = $preview;

            } catch (Throwable $e) {
                $errors[] = "Gagal membaca Excel: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Alumni</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen p-6">

<div class="max-w-6xl mx-auto">

    <h1 class="text-2xl font-bold text-gray-800">
        Import Data Alumni
    </h1>

    <p class="text-gray-500 mt-1 mb-6">
        Masukkan data alumni secara massal menggunakan Excel.
    </p>

    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">

        <a href="download-template.php"
           class="inline-block text-blue-600 font-medium mb-5">
            Download Template Excel
        </a>

        <form method="POST" enctype="multipart/form-data">

            <label class="block font-medium mb-2">
                Pilih File Excel
            </label>

            <input
                type="file"
                name="file_excel"
                accept=".xlsx"
                required
                class="block w-full border rounded-lg p-3 mb-4"
            >

            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700
                       text-white px-5 py-2.5 rounded-lg">
                Upload dan Preview
            </button>

        </form>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="bg-red-50 text-red-700 p-3 rounded-lg mb-3">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($preview)): ?>

        <?php
        $valid = count(array_filter(
            $preview,
            fn($r) => empty($r['errors'])
        ));

        $invalid = count($preview) - $valid;
        ?>

        <div class="grid grid-cols-2 gap-4 mb-5">

            <div class="bg-green-50 p-4 rounded-xl">
                <p class="text-sm text-green-700">Data Valid</p>
                <h2 class="text-2xl font-bold text-green-700">
                    <?= $valid ?>
                </h2>
            </div>

            <div class="bg-red-50 p-4 rounded-xl">
                <p class="text-sm text-red-700">Data Bermasalah</p>
                <h2 class="text-2xl font-bold text-red-700">
                    <?= $invalid ?>
                </h2>
            </div>

        </div>

        <div class="bg-white rounded-xl shadow-sm p-5">

            <h2 class="font-bold text-lg mb-4">
                Preview Data Alumni
            </h2>

            <div class="overflow-x-auto">

                <table class="w-full text-sm text-left">

                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3">NIS</th>
                            <th class="p-3">NISN</th>
                            <th class="p-3">Nama</th>
                            <th class="p-3">Jurusan</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Keterangan</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($preview as $row): ?>

                        <tr class="border-b">

                            <td class="p-3">
                                <?= htmlspecialchars($row['nis']) ?>
                            </td>

                            <td class="p-3">
                                <?= htmlspecialchars($row['nisn']) ?>
                            </td>

                            <td class="p-3">
                                <?= htmlspecialchars($row['nama']) ?>
                            </td>

                            <td class="p-3">
                                <?= htmlspecialchars($row['jurusan']) ?>
                            </td>

                            <td class="p-3">
                                <?php if (empty($row['errors'])): ?>
                                    <span class="text-green-600 font-medium">
                                        Valid
                                    </span>
                                <?php else: ?>
                                    <span class="text-red-600 font-medium">
                                        Bermasalah
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="p-3 text-red-600">
                                <?= htmlspecialchars(
                                    implode(', ', $row['errors'])
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <?php if ($invalid === 0): ?>

                <form action="simpan-import.php" method="POST" class="mt-5">

                    <button
                        type="submit"
                        class="bg-green-600 hover:bg-green-700
                               text-white px-5 py-2.5 rounded-lg">
                        Import <?= $valid ?> Data Alumni
                    </button>

                </form>

            <?php else: ?>

                <p class="text-red-600 mt-5 text-sm">
                    Perbaiki data yang bermasalah di Excel,
                    lalu upload kembali sebelum melakukan import.
                </p>

            <?php endif; ?>

        </div>

    <?php endif; ?>

</div>

</body>
</html>
