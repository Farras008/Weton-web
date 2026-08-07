<?php

require_once __DIR__ . "/lib/Hari.php";
require_once __DIR__ . "/lib/Pasaran.php";

$hari = "";
$pasaran = "";
$error = "";
$tgl = "";
$bln = "";
$thn = "";
$waktuLahir = "";
$bergantiHari = false;
$tanggalWetonTampil = "";

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
            $tanggalWeton = new DateTimeImmutable($tanggal);
            $bergantiHari = $waktuLahir === "malam";

            if ($bergantiHari) {
                $tanggalWeton = $tanggalWeton->modify("+1 day");
            }

            $tanggalPerhitungan = $tanggalWeton->format("Y-m-d");
            $hari = Hari::get($tanggalPerhitungan);
            $pasaran = Pasaran::get($tanggalPerhitungan);
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

    <title>Weton Jawa — Temukan Hari & Pasaran</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<main class="site-shell">
    <a class="brand" href="index.php" aria-label="Weton Jawa, kembali ke halaman utama"><span class="brand-mark"><span>W</span></span><span>Weton Jawa</span></a>
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow">Warisan kalender Jawa</p>
            <h1>Kenali <em>wetonmu,</em> pahami ceritanya.</h1>
            <p class="intro">Temukan perpaduan hari dan pasaran Jawa dari tanggal lahirmu dalam hitungan detik.</p>
            <p class="heritage-note">Sebuah cara sederhana untuk terhubung dengan tradisi.</p>
        </div>
        <section class="calculator-card" aria-labelledby="form-title">
            <div class="card-heading"><h2 id="form-title">Hitung weton</h2><p>Masukkan tanggal lahir Anda di bawah ini.</p></div>
            <form method="POST">
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

    <?php if ($error !== ""): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php elseif ($hari !== ""): ?>

        <div class="hasil" aria-live="polite">
            <p class="result-title"><span aria-hidden="true">✓</span> Hasil perhitungan Anda</p>
            <div class="result-grid">
                <div class="result-item"><small>Hari</small><strong><?= htmlspecialchars($hari) ?></strong></div>
                <div class="result-item"><small>Pasaran</small><strong><?= htmlspecialchars($pasaran) ?></strong></div>
            </div>
            <p class="result-note">
                <span aria-hidden="true">✦</span>
                <?php if ($bergantiHari): ?>
                    Karena lahir malam, weton dihitung mengikuti hari berikutnya: <strong><?= htmlspecialchars($tanggalWetonTampil) ?></strong>.
                <?php else: ?>
                    Karena lahir sebelum malam, weton dihitung pada tanggal lahir: <strong><?= htmlspecialchars($tanggalWetonTampil) ?></strong>.
                <?php endif; ?>
            </p>

        </div>

    <?php endif; ?>
            </form>
        </section>
    </section>
</main>

</body>

</html>
