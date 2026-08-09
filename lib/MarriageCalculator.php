<?php

require_once __DIR__ . "/NeptuData.php";
require_once __DIR__ . "/Marriage22.php";
require_once __DIR__ . "/Marriage23.php";

final class MarriageCalculator
{
    public static function getWetonByNeptu(int $targetNeptu): array
    {
        $weton = [];
        foreach (NeptuData::HARI as $hari => $neptuHari) {
            foreach (NeptuData::PASARAN as $pasaran => $neptuPasaran) {
                if ($neptuHari + $neptuPasaran === $targetNeptu) {
                    $weton[] = $hari . " " . $pasaran;
                }
            }
        }
        return $weton;
    }

    public static function getMarriageResult(int $userNeptu, int $partnerNeptu): array
    {
        if ($userNeptu < 7 || $userNeptu > 18 || $partnerNeptu < 7 || $partnerNeptu > 18) {
            throw new InvalidArgumentException("Neptu pasangan harus berada pada rentang 7–18.");
        }

        $combinedNeptu = $userNeptu + $partnerNeptu;
        $result23 = Marriage23::calculate($combinedNeptu);
        $result22 = null;

        try {
            $result22 = Marriage22::calculate($combinedNeptu);
        } catch (LogicException) {
            // No. 23 tetap dapat dihitung walau total ini tidak memiliki hasil di No. 22.
        }

        $kurangBaik22 = $result22 !== null && in_array($result22["nama"], ["Satriya Wirang", "Bumi Kapetak", "Lebu Katiup Angin"], true);

        $kelompok = $result23["status"] === "baik" && !$kurangBaik22 ? "utama" : ($result23["status"] === "baik" ? "alternatif" : "kurang-selaras");

        return ["userNeptu" => $userNeptu, "partnerNeptu" => $partnerNeptu, "combinedNeptu" => $combinedNeptu, "wetonPasangan" => self::getWetonByNeptu($partnerNeptu), "result22" => $result22, "result23" => $result23, "kelompok" => $kelompok];
    }

    public static function getPartnerRecommendations(int $userNeptu): array
    {
        $groups = ["utama" => [], "alternatif" => [], "kurang-selaras" => []];
        for ($partnerNeptu = 7; $partnerNeptu <= 18; $partnerNeptu++) {
            $result = self::getMarriageResult($userNeptu, $partnerNeptu);
            $groups[$result["kelompok"]][] = $result;
        }
        return $groups;
    }
}
