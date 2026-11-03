
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();

// Sheet data alumni
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Alumni');

// Header kolom
$headers = [
    'NIS',
    'NISN',
    'Nama',
    'Email',
    'Tahun Lulus',
    'Jurusan',
    'Alamat',
    'No Telepon'
];

$sheet->fromArray($headers, null, 'A1');

// Contoh data
$sheet->fromArray([
    ['12345', '0098765432', 'Contoh Nama', 'contoh@email.com',
     '2026', 'RPL', 'Alamat contoh', '081234567890']
], null, 'A2');

// Format NIS, NISN, dan nomor telepon sebagai teks
$sheet->getStyle('A:B')->getNumberFormat()
    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

$sheet->getStyle('H:H')->getNumberFormat()
    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

// Styling header
$sheet->getStyle('A1:H1')->getFont()->setBold(true);
$sheet->getStyle('A1:H1')->getAlignment()
    ->setHorizontal('center');

// Lebar kolom otomatis
foreach (range('A', 'H') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Bersihkan output buffer agar file tidak rusak
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Download Excel
$filename = 'Template_Data_Alumni.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

$spreadsheet->disconnectWorksheets();
exit;
