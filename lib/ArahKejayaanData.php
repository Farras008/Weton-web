<?php

/**
 * Data arah kejayaan berdasarkan total neptu.
 * Dipisahkan dari presentasi agar dapat dipakai ulang oleh fitur berikutnya.
 */
final class ArahKejayaanData
{
    private const ARAH_PER_NEPTU = [
        7 => ["arah" => ["Utara", "Timur"], "display" => "Utara atau Timur"],
        8 => ["arah" => ["Utara", "Timur"], "display" => "Utara atau Timur"],
        9 => ["arah" => ["Selatan", "Timur"], "display" => "Selatan atau Timur"],
        10 => ["arah" => ["Selatan", "Timur"], "display" => "Selatan atau Timur"],
        11 => ["arah" => ["Barat"], "display" => "Barat"],
        12 => ["arah" => ["Utara", "Barat"], "display" => "Utara atau Barat"],
        13 => ["arah" => ["Utara", "Timur"], "display" => "Utara atau Timur"],
        14 => ["arah" => ["Selatan", "Timur"], "display" => "Selatan atau Timur"],
        15 => ["arah" => ["Barat"], "display" => "Barat"],
        16 => ["arah" => ["Barat"], "display" => "Barat"],
        17 => ["arah" => ["Utara", "Barat"], "display" => "Utara atau Barat"],
        18 => ["arah" => ["Utara", "Timur"], "display" => "Utara atau Timur"],
    ];

    /** @return array{arah: string[], display: string} */
    public static function untukNeptu(int $totalNeptu): array
    {
        if (!array_key_exists($totalNeptu, self::ARAH_PER_NEPTU)) {
            throw new InvalidArgumentException("Arah kejayaan tidak tersedia untuk total neptu tersebut.");
        }

        return self::ARAH_PER_NEPTU[$totalNeptu];
    }
}
