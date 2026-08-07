<?php

class Pasaran
{
    private static array $pasaran = [
        "Kliwon",
        "Legi",
        "Pahing",
        "Pon",
        "Wage"
    ];

    public static function get(string $tanggal): string
    {
        // Tanggal acuan
        $acuan = new DateTime("2002-06-23");

        // Tanggal yang dipilih user
        $target = new DateTime($tanggal);

        // Selisih hari
        $selisih = $acuan->diff($target)->days;

        // Kalau tanggal lebih kecil dari acuan
        if ($target < $acuan) {
            $selisih *= -1;
        }

        $index = (($selisih % 5) + 5) % 5;

        return self::$pasaran[$index];
    }
}