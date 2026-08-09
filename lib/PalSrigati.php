<?php

final class PalSrigati
{
    public const EXPECTED_NEPTU = [7,8,9,10,11,12,13,14,15,16,17,18];
    public const EXPECTED_AGES = [6,12,18,24,30,36,42,48,54,60,66,72,78,84,90,96,102,108];

    public const PAL_SRIGATI = [
        7 => [
            6 => 5,12 => 2,18 => 5,24 => 2,30 => 1,36 => 3,42 => 3,48 => 5,54 => 2,60 => 5,66 => 2,72 => 1,78 => 3,84 => 3,90 => 5,96 => 2,102 => 5,108 => 2
        ],
        8 => [
            6 => 5,12 => 2,18 => 1,24 => 2,30 => 1,36 => 4,42 => 1,48 => 8,54 => 5,60 => 2,66 => 1,72 => 2,78 => 1,84 => 4,90 => 1,96 => 8,102 => 5,108 => 2
        ],
        9 => [
            6 => 3,12 => 6,18 => 2,24 => 1,30 => 5,36 => 2,42 => 5,48 => 1,54 => 2,60 => 3,66 => 6,72 => 2,78 => 1,84 => 5,90 => 2,96 => 5,102 => 1,108 => 2
        ],
        10 => [
            6 => 2,12 => 1,18 => 5,24 => 2,30 => 2,36 => 4,42 => 1,48 => 1,54 => 5,60 => 5,66 => 2,72 => 1,78 => 5,84 => 2,90 => 2,96 => 4,102 => 1,108 => 1
        ],
        11 => [
            6 => 3,12 => 5,18 => 2,24 => 2,30 => 9,36 => 2,42 => 1,48 => 2,54 => 3,60 => 1,66 => 3,72 => 3,78 => 5,84 => 2,90 => 2,96 => 9,102 => 2,108 => 1
        ],
        12 => [
            6 => 1,12 => 6,18 => 2,24 => 1,30 => 5,36 => 1,42 => 2,48 => 1,54 => 2,60 => 5,66 => 5,72 => 1,78 => 1,84 => 6,90 => 2,96 => 1,102 => 5,108 => 1
        ],
        13 => [
            6 => 4,12 => 2,18 => 1,24 => 6,30 => 1,36 => 2,42 => 2,48 => 6,54 => 3,60 => 1,66 => 2,72 => 3,78 => 6,84 => 4,90 => 2,96 => 1,102 => 6,108 => 1
        ],
        14 => [
            6 => 2,12 => 1,18 => 2,24 => 5,30 => 5,36 => 1,42 => 5,48 => 5,54 => 2,60 => 5,66 => 1,72 => 2,78 => 5,84 => 5,90 => 2,96 => 1,102 => 2,108 => 5
        ],
        15 => [
            6 => 3,12 => 1,18 => 2,24 => 2,30 => 6,36 => 3,42 => 1,48 => 2,54 => 3,60 => 6,66 => 6,72 => 2,78 => 1,84 => 2,90 => 5,96 => 3,102 => 1,108 => 2
        ],
        16 => [
            6 => 1,12 => 4,18 => 2,24 => 3,30 => 1,36 => 2,42 => 4,48 => 2,54 => 3,60 => 8,66 => 3,72 => 1,78 => 8,84 => 2,90 => 1,96 => 3,102 => 1,108 => 4
        ],
        17 => [
            6 => 2,12 => 2,18 => 1,24 => 6,30 => 1,36 => 2,42 => 2,48 => 6,54 => 3,60 => 1,66 => 2,72 => 3,78 => 6,84 => 6,90 => 2,96 => 1,102 => 5,108 => 2
        ],
        18 => [
            6 => 3,12 => 6,18 => 2,24 => 1,30 => 5,36 => 2,42 => 5,48 => 1,54 => 2,60 => 5,66 => 5,72 => 1,78 => 1,84 => 5,90 => 2,96 => 2,102 => 1,108 => 2
        ]
    ];

    public static function validate(): void
    {
        $keys = array_keys(self::PAL_SRIGATI);
        sort($keys);
        if ($keys !== self::EXPECTED_NEPTU) {
            throw new RuntimeException('PalSrigati: neptu keys mismatch');
        }

        $total = 0;
        foreach (self::PAL_SRIGATI as $neptu => $row) {
            $ages = array_keys($row);
            sort($ages);
            if ($ages !== self::EXPECTED_AGES) {
                throw new RuntimeException('PalSrigati: ages missing for neptu ' . $neptu);
            }
            foreach ($row as $ageKey => $val) {
                if (!is_int($val) || $val < 1 || $val > 9) {
                    throw new RuntimeException('PalSrigati: invalid value at neptu ' . $neptu . ' age ' . $ageKey);
                }
                $total++;
            }
        }
        if ($total !== 216) {
            throw new RuntimeException('PalSrigati: total values expected 216, found ' . $total);
        }
    }

    public static function getPeriodKeyForDateOfBirth(DateTimeImmutable $dob, ?DateTimeImmutable $onDate = null): int
    {
        $onDate = $onDate ?? new DateTimeImmutable('now');

        foreach (self::EXPECTED_AGES as $ageKey) {
            $threshold = $dob->modify('+' . $ageKey . ' years');
            if ($onDate <= $threshold) {
                return $ageKey;
            }
        }

            $ages = self::EXPECTED_AGES;
            return end($ages);
    }

    public static function getValue(int $totalNeptu, int $periodKey): int
    {
        if (!isset(self::PAL_SRIGATI[$totalNeptu])) {
            throw new InvalidArgumentException('PalSrigati: unknown totalNeptu ' . $totalNeptu);
        }
        if (!isset(self::PAL_SRIGATI[$totalNeptu][$periodKey])) {
            throw new InvalidArgumentException('PalSrigati: unknown period key ' . $periodKey);
        }
        return self::PAL_SRIGATI[$totalNeptu][$periodKey];
    }

    public static function timelineForNeptu(int $totalNeptu, ?int $maxAge = null): array
    {
        $out = [];
        $prev = 0;
        foreach (self::EXPECTED_AGES as $ageKey) {
            if ($maxAge !== null && $ageKey > $maxAge) {
                break;
            }
            $label = $prev . '–' . $ageKey;
            $out[$ageKey] = ['label' => $label, 'value' => self::PAL_SRIGATI[$totalNeptu][$ageKey]];
            $prev = $ageKey;
        }
        return $out;
    }
}

PalSrigati::validate();

?>