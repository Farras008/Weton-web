<?php

require_once __DIR__ . "/NeptuData.php";

/**
 * Menghitung neptu dari hari dan pasaran yang telah ditentukan sistem.
 * Fitur Primbon lain harus memakai fungsi ini agar nilai selalu konsisten.
 *
 * @return array{hari: string, pasaran: string, weton: string, neptuHari: int, neptuPasaran: int, totalNeptu: int}
 */
function hitungNeptu(string $hari, string $pasaran): array
{
    $hari = NeptuData::normalisasiHari($hari);
    $pasaran = trim($pasaran);

    if (!array_key_exists($hari, NeptuData::HARI)) {
        throw new InvalidArgumentException("Hari tidak valid. Gunakan salah satu hari yang tersedia di database neptu.");
    }

    if (!array_key_exists($pasaran, NeptuData::PASARAN)) {
        throw new InvalidArgumentException("Pasaran tidak valid. Gunakan salah satu pasaran yang tersedia di database neptu.");
    }

    $neptuHari = NeptuData::HARI[$hari];
    $neptuPasaran = NeptuData::PASARAN[$pasaran];

    return [
        "hari" => $hari,
        "pasaran" => $pasaran,
        "weton" => $hari . " " . $pasaran,
        "neptuHari" => $neptuHari,
        "neptuPasaran" => $neptuPasaran,
        "totalNeptu" => $neptuHari + $neptuPasaran,
    ];
}
