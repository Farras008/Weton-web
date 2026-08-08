<?php

require_once __DIR__ . "/lib/Hari.php";
require_once __DIR__ . "/lib/Pasaran.php";
require_once __DIR__ . "/lib/NeptuCalculator.php";
require_once __DIR__ . "/lib/ArahKejayaanData.php";
require_once __DIR__ . "/lib/WatakData.php";
require_once __DIR__ . "/lib/PerbintanganData.php";
require_once __DIR__ . "/lib/WatakKelahiranData.php";
require_once __DIR__ . "/lib/VisitorCounter.php";

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

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $visitorCount = VisitorCounter::increment();
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
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
            $tanggalWeton = new DateTimeImmutable($tanggal);
            $bergantiHari = $waktuLahir === "malam";

            if ($bergantiHari) {
                $tanggalWeton = $tanggalWeton->modify("+1 day");
            }

            $tanggalPerhitungan = $tanggalWeton->format("Y-m-d");
            $hari = Hari::get($tanggalPerhitungan);
            $pasaran = Pasaran::get($tanggalPerhitungan);
            $neptu = hitungNeptu($hari, $pasaran);
            $arahKejayaan = ArahKejayaanData::untukNeptu($neptu["totalNeptu"]);
            $watakHari = WatakData::untukHari($hari);
            $watakPasaran = WatakData::untukPasaran($pasaran);
            $perbintangan = PerbintanganData::untukNeptu($neptu["totalNeptu"]);
            $watakKelahiran = WatakKelahiranData::untukWeton($hari, $pasaran);
            $tanggalWetonTampil = $tanggalWeton->format("j") . " " . $bulanList[(int) $tanggalWeton->format("n")] . " " . $tanggalWeton->format("Y");
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Makna Wetonku — Temukan Hari & Pasaran</title>

    <link rel="stylesheet" href="assets/css/style.css?v=<?= urlencode((string) $stylesheetVersion) ?>">

</head>

<body>

<main class="site-shell">
    <a class="brand" href="index.php" aria-label="Weton Jawa, kembali ke halaman utama"><span class="brand-mark"><span>W</span></span><span>Weton Jawa</span></a>
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow">Warisan kalender Jawa</p>
            <h1>Temukan <em>makna wetonmu.</em></h1>
            <p class="intro">Kenali perpaduan hari dan pasaran Jawa yang menyertai perjalanan hidupmu.</p>
            <p class="heritage-note">Sebuah cara sederhana untuk terhubung dengan tradisi.</p>
            <p class="visitor-count">Sudah dicoba oleh <?= htmlspecialchars(number_format($visitorCount, 0, ",", ".")) ?> orang</p>
        </div>
        <section class="calculator-card" aria-labelledby="form-title">
            <div class="card-heading"><h2 id="form-title">Hitung weton</h2><p>Masukkan tanggal lahir Anda di bawah ini.</p></div>
            <form method="POST" action="#hasil">
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
                <option value="siang" <?= $waktuLahir === "siang" ? "selected" : "" ?>>Pagi–sore (sebelum pukul 18.00)</option>
                <option value="malam" <?= $waktuLahir === "malam" ? "selected" : "" ?>>Malam (pukul 18.00 atau setelahnya)</option>
            </select>
            <p class="field-help">Dalam perhitungan Jawa, malam setelah pukul 18.00 mengikuti hari berikutnya.</p>
        </div>

        <button type="submit">
            Lihat hasil weton <span class="button-arrow" aria-hidden="true">→</span>
        </button>

            </form>
        </section>
    </section>

    <?php if ($error !== ""): ?>
        <div class="error page-error" id="hasil" tabindex="-1"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($neptu !== null): ?>
        <section class="weton-detail" id="hasil" aria-live="polite" tabindex="-1">
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

                <section class="kejayaan-card" aria-labelledby="kejayaan-title">
                    <h3 id="kejayaan-title">Arah kejayaan</h3>
                    <p class="kejayaan-copy">Berdasarkan total neptu <?= $neptu["totalNeptu"] ?>, arah mata angin yang digunakan adalah:</p>
                    <p class="arah-value"><?= htmlspecialchars($arahKejayaan["display"]) ?></p>
                    <div class="arah-chips">
                        <?php foreach ($arahKejayaan["arah"] as $arah): ?><span><?= htmlspecialchars($arah) ?></span><?php endforeach; ?>
                    </div>
                </section>
            </div>

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
                    <p>Sumber: Primbon Jawa — No. 109 Watak Hari dan No. 110 Watak Pekan/Pasaran.</p>
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
                <footer class="watak-kelahiran-source"><p>Sumber: Primbon Jawa — No. 100, Watak bayi menurut hari dan pekan kelahiran.</p><p>Bagian “Makna” merupakan penjelasan dalam bahasa modern berdasarkan keterangan dalam sumber Primbon Jawa.</p><p>Interpretasi ini merupakan bagian dari tradisi Primbon Jawa dan bukan penilaian ilmiah mengenai kepribadian seseorang.</p></footer>
            </section>

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
                <footer class="perbintangan-source"><p>Sumber: Primbon Jawa — No. 117 Perbintangan.</p><p>Bagian “Makna” merupakan penjelasan dalam bahasa modern berdasarkan keterangan sumber Primbon Jawa.</p><p>Perbintangan merupakan bagian dari tradisi Primbon Jawa dan tidak dimaksudkan sebagai kepastian ilmiah mengenai masa depan, kesehatan, atau kehidupan seseorang.</p></footer>
            </section>
        </section>
    <?php endif; ?>
</main>

</body>

</html>
