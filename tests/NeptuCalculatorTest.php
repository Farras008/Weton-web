<?php

require_once __DIR__ . "/../lib/NeptuCalculator.php";
require_once __DIR__ . "/../lib/ArahKejayaanData.php";
require_once __DIR__ . "/../lib/WatakData.php";
require_once __DIR__ . "/../lib/PerbintanganData.php";
require_once __DIR__ . "/../lib/WatakKelahiranData.php";
require_once __DIR__ . "/../lib/WatakBayi.php";
require_once __DIR__ . "/../lib/WatakBayiTanggal.php";
require_once __DIR__ . "/../lib/MarriageCalculator.php";

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException("Test gagal: " . $message);
    }
}

$seninPahing = hitungNeptu("Senin", "Pahing");
expect($seninPahing === [
    "hari" => "Senin", "pasaran" => "Pahing", "weton" => "Senin Pahing",
    "neptuHari" => 4, "neptuPasaran" => 9, "totalNeptu" => 13,
], "Senin Pahing harus bernilai 13");

expect(hitungNeptu("Selasa", "Wage")["totalNeptu"] === 7, "Selasa Wage harus bernilai 7");
expect(hitungNeptu("Ahad", "Kliwon")["totalNeptu"] === 13, "Ahad harus dinormalisasi ke Minggu dan bernilai 13");
expect(hitungNeptu("Sabtu", "Pahing")["totalNeptu"] === 18, "Sabtu Pahing harus bernilai 18");
expect(ArahKejayaanData::untukNeptu(13)["display"] === "Utara atau Timur", "Neptu 13 harus memakai data arah yang sesuai");
expect(ArahKejayaanData::untukNeptu(15)["arah"] === ["Barat"], "Neptu 15 harus memakai arah Barat");
expect(WatakData::untukHari("Rabu")["nama"] === "Rabu", "Watak hari Rabu harus tersedia");
expect(WatakData::untukPasaran("Pon")["nama"] === "Pon", "Watak pasaran Pon harus tersedia");
expect(WatakData::untukHari("Senin")["nama"] === "Senin" && WatakData::untukPasaran("Pahing")["nama"] === "Pahing", "Watak Senin Pahing harus tersedia");
expect(WatakData::untukHari("Sabtu")["nama"] === "Sabtu" && WatakData::untukPasaran("Wage")["nama"] === "Wage", "Watak Sabtu Wage harus tersedia");
expect(WatakData::untukHari("Minggu")["nama"] === "Ahad / Minggu", "Minggu harus dipetakan ke data Ahad");
expect(WatakData::untukPasaran("Kliwon")["sumber"] !== "", "Watak Ahad Kliwon harus memiliki sumber lengkap");

$kombinasiWatak = 0;
foreach (NeptuData::HARI as $hari => $_) {
    $dataHari = WatakData::untukHari($hari);
    expect($dataHari["sumber"] !== "" && $dataHari["makna"] !== "", "Watak $hari harus lengkap");

    foreach (NeptuData::PASARAN as $pasaran => $_) {
        $dataPasaran = WatakData::untukPasaran($pasaran);
        expect($dataPasaran["sumber"] !== "" && $dataPasaran["makna"] !== "", "Watak $pasaran harus lengkap");
        $kombinasiWatak++;
    }
}
expect($kombinasiWatak === 35, "Semua 35 kombinasi harus memiliki data watak hari dan pasaran");

$bintangPerNeptu = [7 => "Mijan", 8 => "Arab", 9 => "Kukus", 10 => "Jadi", 11 => "Dalu", 12 => "Kuda", 13 => "Asma", 14 => "Sur", 15 => "Jun", 16 => "Surtan", 17 => "Sada", 18 => "Sumbul"];
foreach ($bintangPerNeptu as $neptu => $bintang) {
    $dataBintang = PerbintanganData::untukNeptu($neptu);
    expect($dataBintang["bintang"] === $bintang, "Neptu $neptu harus memakai Bintang $bintang");
    expect($dataBintang["sumber"] !== "" && $dataBintang["makna"] !== "" && $dataBintang["penyakit"] !== "", "Data Bintang $bintang harus lengkap");
}

$watakKelahiranTest = ["Rabu Pon", "Jumat Kliwon", "Senin Pahing", "Kamis Wage", "Ahad Kliwon"];
foreach ($watakKelahiranTest as $weton) {
    [$hariTest, $pasaranTest] = explode(" ", $weton, 2);
    $dataWatakKelahiran = WatakKelahiranData::untukWeton($hariTest, $pasaranTest);
    expect($dataWatakKelahiran["sumber"] !== "" && $dataWatakKelahiran["makna"] !== "", "Data watak kelahiran $weton harus lengkap");
}
expect(count(WatakKelahiranData::WATAK_KELAHIRAN) === 35, "Database watak kelahiran harus memuat 35 kombinasi");
expect(count(WatakBayi::WATAK_BAYI) === 12, "Database Watak Bayi No. 105 harus memuat 12 data");
expect(count(WatakBayiTanggal::WATAK_BAYI_TANGGAL) === 30, "Database Watak Bayi tanggal No. 101 harus memuat 30 data");
expect(WatakBayiTanggal::get(1)["sumber"] !== "", "Watak bayi tanggal 1 harus tersedia");
expect(WatakBayiTanggal::get(23)["makna"] !== "", "Watak bayi tanggal 23 harus tersedia");
expect(WatakBayiTanggal::get(30)["sumber"] !== "", "Watak bayi tanggal 30 harus tersedia");
expect(WatakBayiTanggal::get(31) === null, "Tanggal 31 tidak boleh memakai fallback data lain");
$jodohDana = MarriageCalculator::getMarriageResult(9, 8);
expect($jodohDana["combinedNeptu"] === 17 && $jodohDana["result23"]["nama"] === "Dana" && $jodohDana["result23"]["status"] === "baik", "Ahad Wage dan Selasa Legi harus menghasilkan Dana yang baik");
expect(Marriage22::calculate(28)["nama"] === "Lebu Katiup Angin", "Total 28 harus menghasilkan Lebu Katiup Angin pada No. 22");
expect(count(MarriageCalculator::getPartnerRecommendations(13)["utama"]) + count(MarriageCalculator::getPartnerRecommendations(13)["alternatif"]) + count(MarriageCalculator::getPartnerRecommendations(13)["kurang-selaras"]) === 12, "Rekomendasi harus memuat neptu pasangan 7–18");

$jumlahKombinasi = 0;
foreach (NeptuData::HARI as $hari => $neptuHari) {
    foreach (NeptuData::PASARAN as $pasaran => $neptuPasaran) {
        $hasil = hitungNeptu($hari, $pasaran);
        expect($hasil["totalNeptu"] === $neptuHari + $neptuPasaran, "$hari $pasaran harus dihitung dari database");
        $jumlahKombinasi++;
    }
}
expect($jumlahKombinasi === 35, "Sistem harus mendukung tepat 35 kombinasi weton");

try {
    hitungNeptu("Libur", "Legi");
    expect(false, "Hari tidak valid harus ditolak");
} catch (InvalidArgumentException $exception) {
    expect(str_contains($exception->getMessage(), "Hari tidak valid"), "Pesan error hari harus jelas");
}

try {
    hitungNeptu("Senin", "Tidak Ada");
    expect(false, "Pasaran tidak valid harus ditolak");
} catch (InvalidArgumentException $exception) {
    expect(str_contains($exception->getMessage(), "Pasaran tidak valid"), "Pesan error pasaran harus jelas");
}

echo "Semua test Neptu berhasil (35 kombinasi).\n";
