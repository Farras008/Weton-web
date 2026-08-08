<?php

/**
 * Satu-satunya sumber data nilai neptu dalam aplikasi.
 */
final class NeptuData
{
    public const HARI = [
        "Minggu" => 5,
        "Senin" => 4,
        "Selasa" => 3,
        "Rabu" => 7,
        "Kamis" => 8,
        "Jumat" => 6,
        "Sabtu" => 9,
    ];

    public const PASARAN = [
        "Legi" => 5,
        "Pahing" => 9,
        "Pon" => 7,
        "Wage" => 4,
        "Kliwon" => 8,
    ];

    /** Ahad diterima sebagai nama lain, lalu selalu dinormalisasi ke label UI: Minggu. */
    public static function normalisasiHari(string $hari): string
    {
        $hari = trim($hari);

        return $hari === "Ahad" ? "Minggu" : $hari;
    }
}
