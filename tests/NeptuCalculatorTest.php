<?php

require_once __DIR__ . "/../lib/NeptuCalculator.php";
require_once __DIR__ . "/../lib/ArahKejayaanData.php";

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
