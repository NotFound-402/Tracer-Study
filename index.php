<?php

require_once __DIR__ . '/config/database.php';

$statistik = [
    'alumni' => 0,
    'responden' => 0,
    'bekerja' => 0,
    'wirausaha' => 0,
    'kuliah' => 0,
    'belum_bekerja' => 0,
];

try {
    $statistik['alumni'] = (int) $pdo
        ->query('SELECT COUNT(*) FROM alumni')
        ->fetchColumn();

    $statistik['responden'] = (int) $pdo
        ->query('SELECT COUNT(*) FROM tracer_study')
        ->fetchColumn();

    $query = "
        SELECT
        status_alumni,
        COUNT(*) AS total
        FROM tracer_study
        GROUP BY status_alumni
    ";

    foreach ($pdo->query($query)->fetchAll() as $row) {
        switch ($row['status_alumni']) {
            case 'Bekerja':
                $statistik['bekerja'] = (int) $row['total'];
                break;
            case 'Wirausaha':
                $statistik['wirausaha'] = (int) $row['total'];
                break;
            case 'Lanjut Kuliah':
                $statistik['kuliah'] = (int) $row['total'];
                break;
            case 'Pengangguran':
                $statistik['belum_bekerja'] = (int) $row['total'];
                break;
        }
    }
} catch (PDOException $e) {
    echo '<pre>';
    echo htmlspecialchars($e->getMessage());
    echo '</pre>';
    exit;
}
$persentaseRespons = $statistik['alumni'] > 0
    ? round(($statistik['responden'] / $statistik['alumni']) * 100)
    : 0;

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tracer Study Alumni SMKN 1 Sukawati">
    <title>Tracer Study | SMKN 1 Sukawati</title>
    <link rel="stylesheet" href="asset/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
</head>

<body>
    <header class="navbar">
        <a class="brand" href="#beranda">
            <span class="brand-icon">
                <img src="asset/images/logo.png" alt="Logo SMKN 1 Sukawati" width="50" height="50">
            </span>
            <span>
                <strong>SMKN 1 SUKAWATI</strong>
                <small>TRACER STUDY ALUMNI</small>
            </span>
        </a>

        <button class="menu-toggle" id="menuToggle"
            aria-label="Buka menu" aria-expanded="false"
            aria-controls="navMenu">☰</button>

        <nav class="nav-menu" id="navMenu">
            <a href="#beranda">Beranda</a>
            <a href="#tentang">Tentang</a>
            <a href="#statistik">Statistik</a>
            <a href="#panduan">Panduan</a>
            <a href="login.php" class="nav-login">Login Alumni</a>
        </nav>
    </header>

    <main>
        <section class="hero" id="beranda">
            <div class="hero-content reveal">
                <span class="eyebrow">ALUMNI • KARIER • PENDIDIKAN</span>

                <h1>Melangkah Setelah Lulus,
                    <span>Menginspirasi Masa Depan.</span>
                </h1>

                <p>
                    Mari berbagi perjalanan setelah lulus dari SMKN 1 Sukawati.
                    Partisipasi kamu membantu sekolah mengevaluasi pendidikan
                    dan meningkatkan kualitas lulusan.
                </p>

                <div class="hero-actions">
                    <a href="login.php" class="btn btn-primary">
                        Isi Tracer Study <span>→</span>
                    </a>
                    <a href="#tentang" class="btn btn-outline">
                        Pelajari Lebih Lanjut
                    </a>
                </div>

                <div class="hero-note">
                    ✓ Proses pengisian mudah
                    <span>|</span>
                    ✓ Data dikelola sekolah
                </div>
            </div>

            <div class="hero-visual reveal">
                <div class="visual-card visual-main">
                    <div class="visual-icon">🎓</div>
                    <span class="visual-label">MASA DEPAN ALUMNI</span>
                    <h2>Satu langkah kecil, dampak besar bagi sekolah.</h2>
                    <p>Kerja · Wirausaha · Kuliah · Karier</p>
                </div>
                <div class="floating-card">
                    <span class="floating-icon">📊</span>
                    <div>
                        <strong><?= e($statistik['responden']) ?> respons</strong>
                        <small>Data tracer study</small>
                    </div>
                </div>
            </div>
        </section>

        <section class="stats-section" id="statistik">
            <div class="section-heading reveal">
                <span class="eyebrow">DATA ALUMNI</span>
                <h2>Perjalanan Lulusan Kami</h2>
                <p>Ringkasan data tracer study alumni SMKN 1 Sukawati.</p>
            </div>

            <div class="stats-grid">
                <article class="stat-card reveal">
                    <div class="stat-icon blue">♙</div>
                    <p>Total Alumni</p>
                    <h3 class="count-up" data-target="<?= (int) $statistik['alumni'] ?>">0</h3>
                    <small>Alumni terdaftar</small>
                </article>

                <article class="stat-card reveal">
                    <div class="stat-icon green">✓</div>
                    <p>Sudah Mengisi</p>
                    <h3 class="count-up" data-target="<?= (int) $statistik['responden'] ?>">0</h3>
                    <small><?= e($persentaseRespons) ?>% dari total alumni</small>
                </article>

                <article class="stat-card reveal">
                    <div class="stat-icon purple">▣</div>
                    <p>Bekerja</p>
                    <h3 class="count-up" data-target="<?= (int) $statistik['bekerja'] ?>">0</h3>
                    <small>Alumni bekerja</small>
                </article>

                <article class="stat-card reveal">
                    <div class="stat-icon orange">↗</div>
                    <p>Wirausaha</p>
                    <h3 class="count-up" data-target="<?= (int) $statistik['wirausaha'] ?>">0</h3>
                    <small>Alumni berwirausaha</small>
                </article>
            </div>

            <div class="status-grid">
                <article class="status-card reveal">
                    <span class="status-symbol">🎓</span>
                    <div>
                        <h3>Lanjut Kuliah</h3>
                        <p><?= number_format($statistik['kuliah'], 0, ',', '.') ?> alumni</p>
                    </div>
                </article>

                <article class="status-card reveal">
                    <span class="status-symbol">⌕</span>
                    <div>
                        <h3>Belum Bekerja</h3>
                        <p><?= number_format($statistik['belum_bekerja'], 0, ',', '.') ?> alumni</p>
                    </div>
                </article>
            </div>

            <p class="data-note">
                Statistik diperbarui berdasarkan respons yang tersimpan di sistem.
            </p>
        </section>

        <section class="about-section" id="tentang">
            <div class="about-mark reveal"><img src="" alt=""></div>
            <div class="about-content reveal">
                <span class="eyebrow">APA ITU TRACER STUDY?</span>
                <h2>Terhubung dengan alumni, berkembang bersama.</h2>
                <p>
                    Tracer study merupakan pendataan lulusan untuk mengetahui
                    aktivitas alumni setelah menyelesaikan pendidikan.
                    Informasi ini membantu sekolah mengevaluasi kurikulum,
                    kompetensi, dan relevansi pembelajaran dengan kebutuhan
                    dunia kerja maupun pendidikan lanjutan.
                </p>

                <div class="benefit-list">
                    <div><span>✓</span> Evaluasi kualitas pendidikan</div>
                    <div><span>✓</span> Mengetahui kondisi lulusan</div>
                    <div><span>✓</span> Mendukung peningkatan kompetensi siswa</div>
                </div>
            </div>
        </section>

        <section class="journey-section">
            <div class="section-heading reveal">
                <span class="eyebrow">SETELAH LULUS</span>
                <h2>Setiap Alumni Punya Perjalanan</h2>
                <p>Ke mana pun langkahmu, ceritamu berarti bagi sekolah.</p>
            </div>

            <div class="journey-grid">
                <article class="journey-card reveal">
                    <span class="journey-icon">💼</span>
                    <h3>Bekerja</h3>
                    <p>Bagikan pengalaman dan perjalanan kariermu.</p>
                </article>

                <article class="journey-card reveal">
                    <span class="journey-icon">🚀</span>
                    <h3>Wirausaha</h3>
                    <p>Ceritakan usaha yang sedang kamu kembangkan.</p>
                </article>

                <article class="journey-card reveal">
                    <span class="journey-icon">🎓</span>
                    <h3>Lanjut Kuliah</h3>
                    <p>Bagikan pengalaman melanjutkan pendidikan.</p>
                </article>

                <article class="journey-card reveal">
                    <span class="journey-icon">🌱</span>
                    <h3>Belum Bekerja</h3>
                    <p>Bantu sekolah memahami tantangan para lulusan.</p>
                </article>
            </div>
        </section>

        <section class="guide-section" id="panduan">
            <div class="guide-content reveal">
                <span class="eyebrow">MULAI SEKARANG</span>
                <h2>Luangkan beberapa menit untuk masa depan almamatermu.</h2>
                <p>
                    Login menggunakan akun alumni, periksa data profil,
                    kemudian isi kuesioner sesuai keadaanmu saat ini.
                </p>
                <a href="login.php" class="btn btn-light">
                    Mulai Isi Tracer Study →
                </a>
            </div>
        </section>
    </main>

    <footer class="footer">
        <a class="brand footer-brand" href="#beranda">
            <span class="brand-icon"></span>
            <span>
                <strong>SMKN 1 SUKAWATI</strong>
                <small>TRACER STUDY ALUMNI</small>
            </span>
        </a>

        <p>Melacak perjalanan lulusan, meningkatkan kualitas pendidikan.</p>
        <div class="footer-bottom">
            <span>© <?= date('Y') ?> SMKN 1 Sukawati</span>
            <a href="login.php">Login Sistem</a>
        </div>
    </footer>

    <script src="asset/js/app.js"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-1a/pXj49ZQ1aHEmrJ+gMw1otqoVsYwlEnlD8mIfY2TV03r20Y0CN7uqx1tQogjPL" crossorigin="anonymous"></script>
</body>

</html>