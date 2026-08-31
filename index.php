<?php

session_start();

require_once __DIR__ . "/lib/Hari.php";
require_once __DIR__ . "/lib/Pasaran.php";
require_once __DIR__ . "/lib/NeptuCalculator.php";
require_once __DIR__ . "/lib/ArahKejayaanData.php";
require_once __DIR__ . "/lib/WatakData.php";
require_once __DIR__ . "/lib/PerbintanganData.php";
require_once __DIR__ . "/lib/WatakKelahiranData.php";
require_once __DIR__ . "/lib/VisitorCounter.php";
require_once __DIR__ . "/lib/ArticleData.php";
require_once __DIR__ . "/lib/PalSrigati.php";
require_once __DIR__ . "/lib/WatakBayi.php";
require_once __DIR__ . "/lib/WatakBayiTanggal.php";
require_once __DIR__ . "/lib/MarriageCalculator.php";
require_once __DIR__ . "/lib/ServiceReading.php";
require_once __DIR__ . "/lib/DokuCheckoutService.php";

$hari = "";
$pasaran = "";
$error = "";
$tgl = "";
$bln = "";
$thn = "";
$waktuLahir = "";
$bergantiHari = false;
$tanggalWetonTampil = "";
$neptu = null;
$arahKejayaan = null;
$watakHari = null;
$watakPasaran = null;
$perbintangan = null;
$watakKelahiran = null;
$stylesheetVersion = filemtime(__DIR__ . "/assets/css/style.css");
$visitorCount = 0;
$palPeriodKey = null;
$palValue = null;
$palTimeline = [];
$ageYears = null;
$watakBayi = null;
$watakBayiTanggal = null;
$jodohRekomendasi = null;
$serviceChosen = true;
$selectedService = trim((string) ($_POST['service'] ?? $_GET['service'] ?? 'character'));
$serviceDefinitions = [
    'character' => ['title' => 'Cek Watak & Karakter', 'description' => 'Kenali karakter, kecenderungan sifat, kekuatan, dan sisi yang perlu dikembangkan berdasarkan wetonmu.', 'icon' => '◒'],
    'direction' => ['title' => 'Cek Arah Mata Angin Kejayaan', 'description' => 'Temukan arah yang dipercaya membawa peluang, keberuntungan, dan perkembangan terbaik berdasarkan wetonmu.', 'icon' => '✧'],
    'rezeki' => ['title' => 'Cek Fase Rezeki', 'description' => 'Lihat gambaran fase perjalanan rezeki dan periode kehidupan yang memiliki karakter berbeda.', 'icon' => '◈'],
    'jodoh' => ['title' => 'Cek Jodoh', 'description' => 'Pelajari kecenderungan kecocokan pasangan dan dinamika hubungan berdasarkan weton.', 'icon' => '♡'],
    'complete' => ['title' => 'Cek Weton Lengkap', 'description' => 'Dapatkan pembacaan weton secara menyeluruh dengan seluruh analisis yang tersedia.', 'icon' => '✦'],
];
if (!isset($serviceDefinitions[$selectedService])) $selectedService = 'character';
$paymentCsrf = $_SESSION['payment_csrf'] ??= bin2hex(random_bytes(32));

$bulanList = [
    1 => "Januari",
    2 => "Februari",
    3 => "Maret",
    4 => "April",
    5 => "Mei",
    6 => "Juni",
    7 => "Juli",
    8 => "Agustus",
    9 => "September",
    10 => "Oktober",
    11 => "November",
    12 => "Desember"
];

$visitorCount = VisitorCounter::getCount();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tgl = trim($_POST["tanggal"] ?? "");
    $bln = trim($_POST["bulan"] ?? "");
    $thn = trim($_POST["tahun"] ?? "");
    $waktuLahir = trim($_POST["waktu_lahir"] ?? "");

    if ($tgl === "" || $bln === "" || $thn === "" || $waktuLahir === "") {
        $error = "Silakan isi tanggal lahir dan waktu kelahiran dengan benar.";
    } elseif (!ctype_digit($tgl) || !ctype_digit($bln) || !ctype_digit($thn)) {
        $error = "Input tanggal harus berupa angka.";
    } elseif (!in_array($waktuLahir, ["siang", "malam"], true)) {
        $error = "Pilih waktu kelahiran yang tersedia.";
    } else {
        $tgl = (int) $tgl;
        $bln = (int) $bln;
        $thn = (int) $thn;

        if (!checkdate($bln, $tgl, $thn)) {
            $error = "Tanggal tidak valid. Periksa kembali tanggal, bulan, dan tahun Anda.";
        } else {
            $tanggal = sprintf("%04d-%02d-%02d", $thn, $bln, $tgl);
            $dob = new DateTimeImmutable($tanggal);
            $bergantiHari = $waktuLahir === "malam";
            $reading = ServiceReading::calculate($dob, $waktuLahir);
            $tanggalWeton = $reading['tanggalWeton']; $hari = $reading['hari']; $pasaran = $reading['pasaran'];
            $neptu = $reading['neptu']; $arahKejayaan = $reading['arahKejayaan']; $watakHari = $reading['watakHari'];
            $watakPasaran = $reading['watakPasaran']; $perbintangan = $reading['perbintangan']; $watakKelahiran = $reading['watakKelahiran'];
            $tanggalWetonTampil = $tanggalWeton->format("j") . " " . $bulanList[(int) $tanggalWeton->format("n")] . " " . $tanggalWeton->format("Y");

            // Pal Srigati: compute using original date of birth (not shifted by malam)
                try {
                $ageInterval = $dob->diff(new DateTimeImmutable('now'));
                $ageYears = $ageInterval->y;
                $palPeriodKey = PalSrigati::getPeriodKeyForDateOfBirth($dob);
                if (isset(PalSrigati::PAL_SRIGATI[$neptu["totalNeptu"]])) {
                    $palValue = PalSrigati::getValue($neptu["totalNeptu"], $palPeriodKey);
                    // limit timeline display to ages up to 60
                    $palTimeline = PalSrigati::timelineForNeptu($neptu["totalNeptu"], 60);
                } else {
                    $palValue = null;
                    $palTimeline = [];
                }
                    // Watak Bayi (No. 105)
                    if (isset(WatakBayi::WATAK_BAYI[$neptu["totalNeptu"]])) {
            $watakBayi = $reading['watakBayi']; $watakBayiTanggal = $reading['watakBayiTanggal']; $jodohRekomendasi = $reading['jodoh'];
                    } else {
                        $watakBayi = null;
                    }
            } catch (Throwable $e) {
                $palPeriodKey = null;
                $palValue = null;
                $palTimeline = [];
                    $watakBayi = null;
            }
        }
    }

    if (VisitorCounter::isEnabled()) {
        $visitorCount = VisitorCounter::increment();
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($serviceDefinitions[$selectedService]['title']) ?> | Weton Online</title>
    <meta name="description" content="<?= htmlspecialchars($serviceDefinitions[$selectedService]['description']) ?>">
    <link rel="canonical" href="https://weton.online/">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6150985802567042" crossorigin="anonymous"></script>
    <link rel="icon" type="image/png" href="/assets/favicon.png">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= urlencode((string) $stylesheetVersion) ?>">
    <script src="<?= htmlspecialchars(DokuCheckoutService::checkoutScriptUrl(), ENT_QUOTES, 'UTF-8') ?>"></script>

</head>

<body>

<main class="site-shell">
<header class="site-header"><a class="brand" href="index.php" aria-label="Weton Jawa, kembali ke halaman utama"><span class="brand-mark"><span>W</span></span><span>Weton Jawa</span></a><nav class="site-nav site-nav-home" aria-label="Navigasi utama"><a href="#hitung-weton" aria-current="page">Home</a><a href="artikel.php">Artikel</a><a href="tentang.php">Tentang</a><a href="kebijakan-privasi.php">Privasi</a><a href="kontak.php">Kontak</a></nav></header>
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow">Warisan kalender Jawa</p>
            <h1>Temukan <em>makna wetonmu</em></h1>
            <p class="intro">Kenali perpaduan hari dan pasaran Jawa yang menyertai perjalanan hidupmu.</p>
            <div class="hero-links reading-picker" aria-label="Pilih pembacaan weton"><span class="reading-picker-label">Pilih pembacaan</span><div class="reading-choice-list"><a class="reading-choice reading-choice-complete<?= $selectedService === 'complete' ? ' is-active' : '' ?>" href="?service=complete#hitung-weton">Cek Weton Lengkap</a><?php foreach (['character', 'direction', 'rezeki', 'jodoh'] as $key): ?><a class="reading-choice<?= $selectedService === $key ? ' is-active' : '' ?>" href="?service=<?= urlencode($key) ?>#hitung-weton"><?= htmlspecialchars($serviceDefinitions[$key]['title']) ?></a><?php endforeach; ?></div></div>
            <p class="heritage-note">Sebuah cara sederhana untuk terhubung dengan tradisi.</p>
            <p class="visitor-count">Sudah dicoba oleh <?= htmlspecialchars(number_format($visitorCount, 0, ",", ".")) ?> orang</p>
        </div>
        <?php if ($serviceChosen): ?><section class="calculator-card" id="hitung-weton" aria-labelledby="form-title">
            <div class="card-heading"><p class="eyebrow"><?= htmlspecialchars($serviceDefinitions[$selectedService]['title']) ?></p><h2 id="form-title">Mulai pembacaanmu</h2><p>Masukkan tanggal lahir dan waktu kelahiran. Satu data cukup untuk semua pembacaan.</p></div>
            <form method="POST" action="#hasil">
                <input type="hidden" name="service" value="<?= htmlspecialchars($selectedService, ENT_QUOTES, 'UTF-8') ?>">
                <label for="tanggal">Tanggal lahir</label>
                <div class="tanggal-group">

            <input
                type="number"
                name="tanggal"
                id="tanggal"
                placeholder="17"
                min="1"
                max="31"
                required
                value="<?= htmlspecialchars((string) $tgl) ?>">

            <select name="bulan" aria-label="Bulan lahir" required>

                <option value="">Pilih Bulan</option>

                <?php foreach ($bulanList as $key => $value): ?>

                    <option value="<?= $key ?>" <?= (string) $bln === (string) $key ? "selected" : "" ?>>
                        <?= $value ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <input
                type="number"
                name="tahun"
                aria-label="Tahun lahir"
                placeholder="1998"
                min="1900"
                max="2100"
                required
                value="<?= htmlspecialchars((string) $thn) ?>">

        </div>

        <div class="waktu-field">
            <label for="waktu_lahir">Waktu kelahiran</label>
            <select name="waktu_lahir" id="waktu_lahir" required>
                <option value="">Pilih waktu kelahiran</option>
                <option value="siang" <?= $waktuLahir === "siang" ? "selected" : "" ?>>Pagi sampai sore (sebelum pukul 18.00)</option>
                <option value="malam" <?= $waktuLahir === "malam" ? "selected" : "" ?>>Malam (pukul 18.00 atau setelahnya)</option>
            </select>
            <p class="field-help">Dalam perhitungan Jawa, malam setelah pukul 18.00 mengikuti hari berikutnya.</p>
        </div>

        <button type="submit">
            Lihat hasil weton <span class="button-arrow" aria-hidden="true">→</span>
        </button>

            </form>
        </section><?php endif; ?>
    </section>

    <?php if ($error !== ""): ?>
        <div class="error page-error" id="hasil" tabindex="-1"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($neptu !== null): ?>
        <section class="weton-detail service-result-<?= htmlspecialchars($selectedService, ENT_QUOTES, 'UTF-8') ?>" id="hasil" aria-live="polite" tabindex="-1">
            <div class="detail-intro">
                <p class="eyebrow">Lembaran wetonmu</p>
                <h2><?= htmlspecialchars($neptu["weton"]) ?></h2>
                <p>
                    <?php if ($bergantiHari): ?>
                        Perhitungan mengikuti hari berikutnya, yaitu <?= htmlspecialchars($tanggalWetonTampil) ?>.
                    <?php else: ?>
                        Perhitungan menggunakan tanggal lahirmu, yaitu <?= htmlspecialchars($tanggalWetonTampil) ?>.
                    <?php endif; ?>
                </p>
            </div>

            <div class="detail-grid">
                <section class="neptu-section" aria-labelledby="neptu-title">
                    <div class="neptu-heading"><span aria-hidden="true">✦</span><div><h3 id="neptu-title">Perhitungan neptu</h3><p>Nilai dasar hari dan pasaran wetonmu.</p></div></div>
                    <div class="neptu-math" aria-label="<?= htmlspecialchars($neptu["neptuHari"] . " ditambah " . $neptu["neptuPasaran"] . " sama dengan " . $neptu["totalNeptu"]) ?>">
                        <div><small><?= htmlspecialchars($neptu["hari"]) ?></small><strong><?= $neptu["neptuHari"] ?></strong></div><span aria-hidden="true">+</span>
                        <div><small><?= htmlspecialchars($neptu["pasaran"]) ?></small><strong><?= $neptu["neptuPasaran"] ?></strong></div><span aria-hidden="true">=</span>
                        <div class="neptu-total"><small>Total neptu</small><strong><?= $neptu["totalNeptu"] ?></strong></div>
                    </div>
                    <p class="neptu-caption"><strong><?= htmlspecialchars($neptu["weton"]) ?></strong> memiliki total neptu <strong><?= $neptu["totalNeptu"] ?></strong>.</p>
                </section>

                <?php if (in_array($selectedService, ['direction', 'complete'], true)): ?><section class="kejayaan-card" aria-labelledby="kejayaan-title">
                    <h3 id="kejayaan-title">Arah kejayaan</h3>
                    <p class="kejayaan-copy">Berdasarkan total neptu <?= $neptu["totalNeptu"] ?>, arah mata angin yang digunakan adalah:</p>
                    <p class="arah-value"><?= htmlspecialchars($arahKejayaan["display"]) ?></p>
                    <div class="arah-chips">
                        <?php foreach ($arahKejayaan["arah"] as $arah): ?><span><?= htmlspecialchars($arah) ?></span><?php endforeach; ?>
                    </div>
                </section><?php endif; ?>
            </div>

            <?php if (in_array($selectedService, ['character', 'complete'], true)): ?>
            <section class="watak-section" aria-labelledby="watak-title">
                <div class="watak-intro">
                    <p class="eyebrow">Primbon Jawa</p>
                    <h3 id="watak-title">Watak wetonmu</h3>
                    <p>Menilik watak dari hari dan pasaran kelahiran menurut Primbon Jawa.</p>
                </div>
                <div class="watak-grid">
                    <article class="watak-card">
                        <p class="watak-label">Watak hari</p>
                        <h4><?= htmlspecialchars($watakHari["nama"]) ?></h4>
                        <blockquote><?= htmlspecialchars($watakHari["sumber"]) ?></blockquote>
                        <p class="makna-label">Makna</p>
                        <p class="makna-copy"><?= htmlspecialchars($watakHari["makna"]) ?></p>
                    </article>
                    <article class="watak-card">
                        <p class="watak-label">Watak pasaran</p>
                        <h4><?= htmlspecialchars($watakPasaran["nama"]) ?></h4>
                        <blockquote><?= htmlspecialchars($watakPasaran["sumber"]) ?></blockquote>
                        <p class="makna-label">Makna</p>
                        <p class="makna-copy"><?= htmlspecialchars($watakPasaran["makna"]) ?></p>
                    </article>
                </div>
                <footer class="watak-source">
                    <p>Sumber: Primbon Jawa, No. 109 Watak Hari dan No. 110 Watak Pekan/Pasaran.</p>
                    <p>Bagian “Makna” merupakan penjelasan dalam bahasa modern berdasarkan keterangan dalam sumber Primbon Jawa.</p>
                    <p>Interpretasi ini merupakan bagian dari tradisi Primbon Jawa dan bukan penilaian ilmiah mengenai kepribadian seseorang.</p>
                </footer>
            </section>

            <section class="watak-kelahiran-section" aria-labelledby="watak-kelahiran-title">
                <div class="watak-kelahiran-intro">
                    <p class="eyebrow">Primbon Jawa No. 100</p>
                    <h3 id="watak-kelahiran-title">Watak kelahiran</h3>
                    <p>Petikan watak menurut hari dan pasaran kelahiran dalam Primbon Jawa.</p>
                </div>
                <article class="watak-kelahiran-card">
                    <div class="weton-name"><p>Weton kelahiran</p><h4><?= htmlspecialchars($neptu["weton"]) ?></h4></div>
                    <div class="watak-kelahiran-body">
                        <p class="petikan-label">Petikan Primbon</p>
                        <blockquote><?= htmlspecialchars($watakKelahiran["sumber"]) ?></blockquote>
                        <p class="makna-label">Makna</p>
                        <p class="watak-kelahiran-makna"><?= htmlspecialchars($watakKelahiran["makna"]) ?></p>
                    </div>
                </article>
                <footer class="watak-kelahiran-source"><p>Sumber: Primbon Jawa, No. 100, Watak bayi menurut hari dan pekan kelahiran.</p><p>Bagian “Makna” merupakan penjelasan dalam bahasa modern berdasarkan keterangan dalam sumber Primbon Jawa.</p><p>Interpretasi ini merupakan bagian dari tradisi Primbon Jawa dan bukan penilaian ilmiah mengenai kepribadian seseorang.</p></footer>
            </section>

            <section class="watak-bayi-section" aria-labelledby="watak-bayi-title">
                <div class="watak-bayi-intro">
                    <p class="eyebrow">Primbon Jawa No. 105</p>
                    <h3 id="watak-bayi-title">Watak Bayi</h3>
                    <p>Melihat watak kelahiran dari jumlah neptu dan tanggal lahir menurut Primbon Jawa.</p>
                </div>

                <article class="watak-kelahiran-card">
                    <div class="weton-name"><p>Watak menurut neptu</p><h4>NEPTU <?= htmlspecialchars($neptu["totalNeptu"]) ?></h4></div>
                    <div class="watak-kelahiran-body">
                        <p class="petikan-label">Petikan Primbon</p>
                        <blockquote><?= $watakBayi !== null ? htmlspecialchars($watakBayi['sumber']) : '-' ?></blockquote>
                        <p class="makna-label">Makna</p>
                        <p class="watak-kelahiran-makna"><?= $watakBayi !== null ? htmlspecialchars($watakBayi['makna']) : '-' ?></p>
                    </div>
                </article>

                <p class="watak-bayi-reading-label">Watak menurut tanggal lahir</p>
                <?php if ($watakBayiTanggal !== null): ?>
                    <article class="watak-kelahiran-card watak-tanggal-card">
                        <div class="weton-name"><p>Primbon Jawa No. 101</p><h4>TANGGAL <?= htmlspecialchars((string) $tgl) ?></h4></div>
                        <div class="watak-kelahiran-body">
                            <p class="petikan-label">Petikan Primbon</p>
                            <blockquote><?= htmlspecialchars($watakBayiTanggal["sumber"]) ?></blockquote>
                            <p class="makna-label">Makna</p>
                            <p class="watak-kelahiran-makna"><?= htmlspecialchars($watakBayiTanggal["makna"]) ?></p>
                        </div>
                    </article>
                <?php else: ?>
                    <div class="watak-bayi-unavailable">Data watak untuk tanggal <?= htmlspecialchars((string) $tgl) ?> belum tersedia dalam sumber yang digunakan.</div>
                <?php endif; ?>

                <footer class="watak-bayi-source"><p>Sumber: Primbon Jawa, No. 105, Watak Bayi.</p><p>Sumber: Primbon Jawa, No. 101, Watak bayi menurut tanggal kelahiran.</p><p>Bagian “Makna” merupakan penjelasan dalam bahasa modern berdasarkan keterangan sumber Primbon Jawa.</p><p>Interpretasi ini merupakan bagian dari tradisi Primbon Jawa dan bukan penilaian ilmiah mengenai kepribadian seseorang.</p></footer>
            </section>
            <?php endif; ?>

            <?php if ($selectedService === 'complete'): ?>
            <section class="perbintangan-section" aria-labelledby="perbintangan-title">
                <div class="perbintangan-intro">
                    <p class="eyebrow">Primbon Jawa No. 117</p>
                    <h3 id="perbintangan-title">Perbintangan</h3>
                    <p>Jejak bintang dalam perhitungan Primbon Jawa.</p>
                </div>
                <div class="perbintangan-panel">
                    <div class="bintang-utama"><p>Bintangmu</p><h4><span aria-hidden="true">✦</span> <?= htmlspecialchars($perbintangan["bintang"]) ?> <span aria-hidden="true">✦</span></h4><small>Neptu <?= $neptu["totalNeptu"] ?></small></div>
                    <div class="bintang-content">
                        <p class="petikan-label">Petikan Primbon</p>
                        <blockquote><?= htmlspecialchars($perbintangan["sumber"]) ?></blockquote>
                        <p class="makna-label">Makna</p>
                        <p class="bintang-makna"><?= htmlspecialchars($perbintangan["makna"]) ?></p>
                    </div>
                    <dl class="bintang-facts">
                        <div><dt>Bala bantuan</dt><dd>Bintang <?= htmlspecialchars($perbintangan["balaBantuan"]) ?></dd></div>
                        <div><dt>Musuh</dt><dd>Bintang <?= htmlspecialchars($perbintangan["musuh"]) ?></dd></div>
                        <div><dt>Kesaktian</dt><dd><?= htmlspecialchars($perbintangan["kesaktian"]) ?></dd></div>
                        <div><dt>Syarat</dt><dd><?= htmlspecialchars($perbintangan["syarat"]) ?></dd></div>
                        <div><dt>Kejayaan</dt><dd><?= htmlspecialchars($perbintangan["kejayaan"]) ?></dd></div>
                        <div><dt>Kemalangan</dt><dd><?= htmlspecialchars($perbintangan["kemalangan"]) ?><small>Menurut keterangan sumber Primbon Jawa.</small></dd></div>
                        <div><dt>Penyakit</dt><dd><?= htmlspecialchars($perbintangan["penyakit"]) ?><small>Menurut keterangan sumber Primbon Jawa; bukan penilaian medis.</small></dd></div>
                    </dl>
                </div>
                <footer class="perbintangan-source"><p>Sumber: Primbon Jawa, No. 117 Perbintangan.</p><p>Bagian “Makna” merupakan penjelasan dalam bahasa modern berdasarkan keterangan sumber Primbon Jawa.</p><p>Perbintangan merupakan bagian dari tradisi Primbon Jawa dan tidak dimaksudkan sebagai kepastian ilmiah mengenai masa depan, kesehatan, atau kehidupan seseorang.</p></footer>
            </section>
            <?php endif; ?>
            <?php if (in_array($selectedService, ['rezeki', 'complete'], true)): ?>
            <section class="pal-srigati-section" aria-labelledby="pal-title">
                <div class="pal-intro">
                    <p class="eyebrow">Primbon Jawa No. 96</p>
                    <h3 id="pal-title">Pal Srigati</h3>
                    <p>Perubahan rejeki penghidupan dalam siklus enam tahunan menurut Primbon Jawa.</p>
                </div>

                <div class="pal-grid">
                    <article class="pal-current-card">
                        <p class="pal-card-kicker">Pal Srigati saat ini</p>
                        <p class="pal-value"><?= $palValue !== null ? htmlspecialchars((string)$palValue) : '-' ?></p>
                        <p class="pal-value-caption">Nilai periode kehidupanmu</p>
                        <div class="pal-stat-grid">
                            <div><small>Neptu kelahiran</small><strong><?= htmlspecialchars($neptu["totalNeptu"]) ?></strong></div>
                            <div><small>Usia saat ini</small><strong><?= htmlspecialchars((string) ($ageYears ?? "-")) ?> <em>th</em></strong></div>
                            <div><small>Periode aktif</small><strong><?= $palPeriodKey !== null ? htmlspecialchars((string) ((int) $palPeriodKey - 6)) . ' sampai ' . htmlspecialchars((string) $palPeriodKey) : '-' ?> <em>th</em></strong></div>
                        </div>
                        <p class="pal-interpretation">
                            <?php if ($palValue === 1): ?>
                                Menurut keterangan Primbon, angka 1 merupakan nilai terendah dalam perhitungan Pal Srigati.
                            <?php elseif ($palValue === 9): ?>
                                Menurut keterangan Primbon, angka 9 merupakan nilai tertinggi dalam perhitungan Pal Srigati.
                            <?php else: ?>
                                Menurut keterangan Primbon, angka yang lebih besar menunjukkan keadaan yang lebih beruntung dan menyenangkan.
                            <?php endif; ?>
                        </p>
                        <p class="pal-scale">Skala: 1 sampai 9 (1 = terendah, 9 = tertinggi)</p>
                    </article>

                    <aside class="pal-timeline">
                        <div class="pal-timeline-heading"><div><p>Lintasan hidup</p><h4>Timeline enam tahunan</h4></div><span>Nilai 1 sampai 9</span></div>
                        <div class="pal-timeline-list">
                            <?php foreach ($palTimeline as $ageKey => $row): ?>
                                <?php $isCurrent = $ageKey === $palPeriodKey; ?>
                                <div class="pal-timeline-item<?= $isCurrent ? ' current' : '' ?>">
                                    <div class="pal-timeline-label"><?= htmlspecialchars($row['label']) ?> tahun</div>
                                    <div class="pal-timeline-value"><?= htmlspecialchars((string)$row['value']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </aside>
                </div>

                <footer class="pal-source">
                    <p>Sumber: Primbon Jawa, No. 96 Pal Srigati.</p>
                    <p>Pal Srigati merupakan perhitungan tradisional Primbon Jawa mengenai perubahan rejeki penghidupan dalam siklus enam tahunan berdasarkan jumlah neptu hari dan pekan kelahiran.</p>
                    <p>Interpretasi ini merupakan bagian dari tradisi Primbon Jawa dan bukan kepastian mengenai kondisi rejeki, kehidupan, atau masa depan seseorang.</p>
                </footer>
            </section>
            <?php endif; ?>

            <?php if (in_array($selectedService, ['jodoh', 'complete'], true)): ?>
            <section class="jodoh-section" aria-labelledby="jodoh-title">
                <div class="jodoh-intro"><p class="eyebrow">Primbon Jawa No. 22 &amp; 23</p><h3 id="jodoh-title">Jodoh &amp; Pernikahan</h3><p>Melihat kemungkinan pasangan berdasarkan jumlah neptu kelahiran menurut perhitungan Primbon Jawa.</p></div>
                <div class="jodoh-user-neptu">
                    <div class="jodoh-user-neptu-value"><span>Neptumu</span><strong><?= $neptu["totalNeptu"] ?></strong></div>
                    <p>Beberapa neptu pasangan berikut memberi hasil yang lebih selaras menurut sumber.</p>
                </div>

                <?php $jodohGroups = ["utama" => "Rekomendasi utama", "alternatif" => "Alternatif", "kurang-selaras" => "Kurang selaras"]; ?>
                <?php foreach ($jodohGroups as $groupKey => $groupLabel): ?>
                    <div class="jodoh-group jodoh-<?= $groupKey ?>"><h4><?= $groupLabel ?></h4><div class="jodoh-grid">
                        <?php foreach ($jodohRekomendasi[$groupKey] as $jodoh): ?>
                            <article class="jodoh-card<?= $jodoh["result22"] === null ? " jodoh-card--partial" : "" ?>">
                                <p class="jodoh-neptu-label">Neptu pasangan</p><strong class="jodoh-neptu"><?= $jodoh["partnerNeptu"] ?></strong>
                                <p class="jodoh-weton"><?= htmlspecialchars(implode(" · ", $jodoh["wetonPasangan"])) ?></p>
                                <div class="jodoh-result"><small>Total: <?= $neptu["totalNeptu"] ?> + <?= $jodoh["partnerNeptu"] ?> = <?= $jodoh["combinedNeptu"] ?></small>
                                    <?php if ($jodoh["result22"] !== null): ?>
                                        <strong><?= htmlspecialchars($jodoh["result22"]["nama"]) ?></strong><p class="jodoh-arti">Arti: <?= htmlspecialchars($jodoh["result22"]["arti"]) ?></p>
                                    <?php endif; ?>
                                    <span class="jodoh-status <?= $jodoh["result23"]["status"] === "baik" ? "baik" : "kurang-baik" ?>"><?= htmlspecialchars($jodoh["result23"]["nama"]) ?> · <?= htmlspecialchars($jodoh["result23"]["status"]) ?></span>
                                </div>
                                <?php if ($jodoh["result22"] !== null): ?>
                                    <div class="jodoh-reading"><p class="jodoh-reading-title">Gambaran hubungan</p><p><?= htmlspecialchars($jodoh["result22"]["makna"]) ?></p></div>
                                <?php endif; ?>
                                <div class="jodoh-reading"><p class="jodoh-reading-title">Arah rumah tangga</p><p><?= htmlspecialchars($jodoh["result23"]["makna"]) ?></p></div>
                            </article>
                        <?php endforeach; ?>
                    </div></div>
                <?php endforeach; ?>

                <details class="jodoh-all"><summary>Lihat semua kemungkinan</summary><div class="jodoh-all-content">
                    <?php foreach (["utama", "alternatif", "kurang-selaras"] as $groupKey): foreach ($jodohRekomendasi[$groupKey] as $jodoh): ?>
                        <p><strong>Neptu <?= $jodoh["partnerNeptu"] ?></strong>, <?= htmlspecialchars(implode(", ", $jodoh["wetonPasangan"])) ?>; <?php if ($jodoh["result22"] !== null): ?><?= htmlspecialchars($jodoh["result22"]["nama"]) ?> dan <?php endif; ?><?= htmlspecialchars($jodoh["result23"]["nama"]) ?>.</p>
                    <?php endforeach; endforeach; ?>
                </div></details>
                <footer class="jodoh-source"><p>Sumber: Primbon Jawa, No. 22 dan No. 23 Perhitungan untuk suami istri (Pernikahan).</p><p>Perhitungan ini merupakan bagian dari tradisi Primbon Jawa dan digunakan sebagai bahan refleksi budaya. Hasil perhitungan bukan kepastian mengenai kecocokan, masa depan, atau keberlangsungan sebuah hubungan.</p></footer>
            </section>

            <?php if ($selectedService === 'complete'): ?>
            <section class="full-reading-cta" aria-labelledby="full-reading-title">
                <p class="eyebrow">Pembacaan lengkap</p>
                <h3 id="full-reading-title">Buka hasil wetonmu secara lengkap</h3>
                <p>Selesaikan pembayaran QRIS untuk membuka pembacaan lengkap di halaman ini.</p>
                <form method="post" action="/api/doku/create-payment" class="payment-form" data-doku-payment-form>
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($paymentCsrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="product" value="weton_full">
                    <input type="hidden" name="birth_date" value="<?= htmlspecialchars(sprintf('%04d-%02d-%02d', (int) $thn, (int) $bln, (int) $tgl), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="birth_time" value="<?= htmlspecialchars($waktuLahir, ENT_QUOTES, 'UTF-8') ?>">
                    <label for="payment-email">Email</label>
                    <div class="payment-form-row"><input type="email" id="payment-email" name="email" maxlength="254" autocomplete="email" required placeholder="nama@email.com"><button type="submit">Buka Hasil Lengkap — Rp5.000 <span class="button-arrow" aria-hidden="true">→</span></button></div>
                </form>
                <p class="payment-note" data-payment-message aria-live="polite">Hasil dasar di atas tetap dapat Anda baca gratis.</p>
            </section>
            <?php endif; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
    <section class="guide-hub" id="panduan" aria-labelledby="guide-hub-title">
        <div class="guide-hub-heading"><div><p class="eyebrow">Panduan Weton</p><h2 id="guide-hub-title">Mulai belajar dari yang paling dasar.</h2></div><p>Artikel ringkas untuk memahami weton, pasaran, dan neptu dengan konteks budaya yang mudah diikuti.</p></div>
        <div class="guide-grid">
            <?php foreach (ArticleData::ARTICLES as $slug => $article): ?>
                <article class="guide-card"><p class="guide-category"><?= htmlspecialchars($article['category']) ?></p><h3><a href="<?= htmlspecialchars(ArticleData::path($slug)) ?>"><?= htmlspecialchars($article['title']) ?></a></h3><p><?= htmlspecialchars($article['description']) ?></p><footer><span><?= htmlspecialchars($article['reading_time']) ?></span><a href="<?= htmlspecialchars(ArticleData::path($slug)) ?>" aria-label="Baca <?= htmlspecialchars($article['title']) ?>">Baca artikel <span aria-hidden="true">→</span></a></footer></article>
            <?php endforeach; ?>
        </div>
        <p class="guide-note">Artikel baru akan terus ditambahkan. Mulai dari panduan dasar, lalu gunakan kalkulator saat Anda siap mengetahui weton kelahiran.</p>
    </section>
    <?php endif; ?>
</main>
<footer class="site-footer"><p>© <?= date('Y') ?> Weton Online · Warisan kalender Jawa, hadir dalam bentuk digital.</p><nav aria-label="Navigasi footer"><a href="#hitung-weton">Hitung Weton</a><a href="artikel.php">Artikel</a><a href="tentang.php">Tentang</a><a href="kebijakan-privasi.php">Kebijakan Privasi</a><a href="kontak.php">Kontak</a></nav></footer>

<script>
(() => {
    const form = document.querySelector('[data-doku-payment-form]');
    if (!form) return;
    const button = form.querySelector('button[type="submit"]');
    const message = document.querySelector('[data-payment-message]');
    let pollTimer;
    const setMessage = (text) => { message.textContent = text; };
    const stopPolling = () => { if (pollTimer) window.clearInterval(pollTimer); pollTimer = undefined; };
    const checkPayment = async (invoice) => {
        const response = await fetch('/api/doku/payment-status?invoice=' + encodeURIComponent(invoice), {credentials: 'same-origin'});
        if (!response.ok) throw new Error('Status payment tidak dapat diperiksa.');
        const data = await response.json();
        if (data.paid) { stopPolling(); window.location.assign('/payment/doku-result.php?invoice=' + encodeURIComponent(invoice)); return; }
        if (['FAILED', 'EXPIRED'].includes(data.status)) { stopPolling(); button.disabled = false; setMessage('Pembayaran belum diterima. Silakan coba lagi.'); }
    };
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (typeof window.loadJokulCheckout !== 'function') { setMessage('Checkout belum siap. Silakan muat ulang halaman.'); return; }
        button.disabled = true; setMessage('Menyiapkan pembayaran QRIS…');
        try {
            const body = Object.fromEntries(new FormData(form));
            const response = await fetch('/api/doku/create-payment', {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'Accept': 'application/json'}, body: JSON.stringify(body)});
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Pembayaran belum dapat dibuat.');
            window.loadJokulCheckout(data.paymentUrl);
            setMessage('Selesaikan pembayaran QRIS di jendela DOKU. Kami akan membuka hasil setelah pembayaran diterima.');
            const started = Date.now();
            pollTimer = window.setInterval(async () => { try { await checkPayment(data.invoice); if (Date.now() - started > 15 * 60 * 1000) { stopPolling(); button.disabled = false; setMessage('Pembayaran belum diterima. Silakan selesaikan QRIS atau coba lagi.'); } } catch (_) {} }, 2500);
        } catch (error) { button.disabled = false; setMessage(error.message || 'Pembayaran belum dapat dibuat. Silakan coba lagi.'); }
    });
})();
</script>
<script>
(() => {
    const toggle = document.querySelector('.service-card-specific');
    const panel = document.getElementById('specific-services');
    if (!toggle || !panel) return;
    toggle.addEventListener('click', () => {
        const open = panel.hidden;
        panel.hidden = !open;
        panel.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) panel.scrollIntoView({behavior: 'smooth', block: 'nearest'});
    });
})();
</script>
</body>

</html>
