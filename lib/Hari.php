<?php

class Hari
{
    private static array $hari = [
        "Minggu",
        "Senin",
        "Selasa",
        "Rabu",
        "Kamis",
        "Jumat",
        "Sabtu"
    ];

    public static function get(string $tanggal): string
    {
        $timestamp = strtotime($tanggal);

        $index = date("w", $timestamp);

        return self::$hari[$index];
    }
}